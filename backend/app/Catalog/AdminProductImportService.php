<?php

namespace App\Catalog;

use App\Exceptions\ProductConflictException;
use App\Imports\TabularFileReader;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminProductImportService
{
    public function __construct(private readonly TabularFileReader $reader, private readonly AdminProductService $products) {}

    public function preview(UploadedFile $products, UploadedFile $variants, ?UploadedFile $images, array $mediaFiles): array
    {
        return $this->assemble($this->reader->read($products), $this->reader->read($variants), $images ? $this->reader->read($images) : [], $mediaFiles);
    }

    public function import(UploadedFile $products, UploadedFile $variants, ?UploadedFile $images, array $mediaFiles): array
    {
        $preview = $this->preview($products, $variants, $images, $mediaFiles);
        $summary = ['detected' => count($preview['rows']), 'created_products' => 0, 'created_variants' => 0, 'imported_images' => 0, 'failed' => 0];
        $results = [];
        foreach ($preview['rows'] as $row) {
            if ($row['errors']) {
                $summary['failed']++;
                $results[] = $row;

                continue;
            }
            $stored = [];
            try {
                $payload = $row['payload'];
                foreach ($payload['images'] as &$image) {
                    if (isset($image['upload_name'])) {
                        $file = collect($mediaFiles)->first(fn (UploadedFile $media) => $media->getClientOriginalName() === $image['upload_name']);
                        $path = $file?->store('products/'.$payload['slug'], 'public');
                        if (! $path) {
                            throw new ProductConflictException('image_file_missing', 'An uploaded image file could not be stored.');
                        }
                        $stored[] = $path;
                        $dimensions = @getimagesize($file->getRealPath());
                        $image['path'] = $path;
                        $image['width'] = $dimensions[0] ?? null;
                        $image['height'] = $dimensions[1] ?? null;
                        $image['mime_type'] = $file->getMimeType();
                        unset($image['upload_name']);
                    }
                }
                unset($image);
                $this->products->create($payload);
                $summary['created_products']++;
                $summary['created_variants'] += count($payload['variants']);
                $summary['imported_images'] += count($payload['images']);
                $results[] = ['reference' => $row['reference'], 'errors' => [], 'status' => 'created'];
            } catch (\Throwable $exception) {
                Storage::disk('public')->delete($stored);
                $summary['failed']++;
                $results[] = ['reference' => $row['reference'], 'errors' => [['field' => null, 'code' => $exception instanceof ProductConflictException ? $exception->errorCode : 'import_failed', 'message' => $exception->getMessage()]], 'status' => 'failed'];
            }
        }

        return ['summary' => $summary, 'rows' => $results];
    }

    private function assemble(array $productRows, array $variantRows, array $imageRows, array $mediaFiles): array
    {
        $rows = [];
        $seenReferences = [];
        $seenSlugs = [];
        $seenSkus = [];
        $uploadedNames = collect($mediaFiles)->map(fn (UploadedFile $file) => $file->getClientOriginalName())->all();
        foreach ($productRows as $index => $productRow) {
            $reference = trim((string) ($productRow['product_reference'] ?? ''));
            $errors = [];
            foreach (['product_reference', 'name', 'category', 'status'] as $field) {
                if (trim((string) ($productRow[$field] ?? '')) === '') {
                    $errors[] = $this->error($field, 'required', 'Products row '.($index + 2).": $field is required.");
                }
            }
            if ($reference !== '' && isset($seenReferences[$reference])) {
                $errors[] = $this->error('product_reference', 'duplicate_product', 'The product reference is duplicated in the import.');
            }
            $seenReferences[$reference] = true;
            $slug = Str::slug((string) (($productRow['slug'] ?? '') ?: ($productRow['name'] ?? '')));
            if (isset($seenSlugs[$slug]) || Product::query()->where('slug', $slug)->exists()) {
                $errors[] = $this->error('slug', 'duplicate_product', 'The product slug already exists.');
            }
            $seenSlugs[$slug] = true;
            $category = Category::query()->whereRaw('LOWER(slug) = ?', [strtolower(trim((string) ($productRow['category'] ?? '')))])->first();
            if (! $category) {
                $errors[] = $this->error('category', 'invalid_category', 'The category does not exist.');
            }
            if (! in_array($productRow['status'] ?? null, ['draft', 'active', 'archived'], true)) {
                $errors[] = $this->error('status', 'invalid', 'Status must be draft, active, or archived.');
            }
            $relatedVariants = array_values(array_filter($variantRows, fn ($variant) => trim((string) ($variant['product_reference'] ?? '')) === $reference));
            if (! $relatedVariants) {
                $errors[] = $this->error('variants', 'required', 'At least one variant is required.');
            }
            [$variants, $options, $variantErrors] = $this->variants($relatedVariants, $seenSkus);
            $errors = array_merge($errors, $variantErrors);
            $relatedImages = array_values(array_filter($imageRows, fn ($image) => trim((string) ($image['product_reference'] ?? '')) === $reference));
            [$images, $imageErrors] = $this->images($relatedImages, $uploadedNames, array_column($variants, 'sku'));
            $errors = array_merge($errors, $imageErrors);
            $payload = [
                'name' => trim((string) ($productRow['name'] ?? '')), 'slug' => $slug,
                'short_description' => null, 'description' => ($productRow['description'] ?? '') ?: null, 'brand' => ($productRow['brand'] ?? '') ?: null,
                'status' => $productRow['status'] ?? '', 'published_at' => ($productRow['published_at'] ?? '') ?: null, 'metadata' => ['import_reference' => $reference],
                'categories' => $category ? [['category_public_id' => $category->public_id, 'is_primary' => true, 'sort_order' => 0]] : [],
                'options' => $options, 'variants' => $variants, 'images' => $images,
            ];
            $rows[] = ['reference' => $reference, 'row' => $index + 2, 'name' => $payload['name'], 'slug' => $slug, 'variants' => count($variants), 'images' => count($images), 'errors' => $errors, 'payload' => $payload];
        }
        $known = array_keys($seenReferences);
        foreach ([['variants', $variantRows], ['images', $imageRows]] as [$type, $relatedRows]) {
            foreach ($relatedRows as $index => $related) {
                if (! in_array(trim((string) ($related['product_reference'] ?? '')), $known, true)) {
                    $rows[] = ['reference' => $related['product_reference'] ?? '', 'row' => $index + 2, 'name' => '', 'slug' => '', 'variants' => 0, 'images' => 0, 'payload' => [], 'errors' => [$this->error('product_reference', 'unknown_product', ucfirst($type).' row references an unknown product.')]];
                }
            }
        }

        return ['summary' => ['detected' => count($productRows), 'valid' => count(array_filter($rows, fn ($row) => ! $row['errors'])), 'invalid' => count(array_filter($rows, fn ($row) => (bool) $row['errors']))], 'rows' => $rows];
    }

    private function variants(array $rows, array &$seenSkus): array
    {
        $variants = [];
        $optionValues = [];
        $errors = [];
        $combinations = [];
        foreach ($rows as $index => $row) {
            $sku = trim((string) ($row['sku'] ?? ''));
            if ($sku === '') {
                $errors[] = $this->error('sku', 'required', 'Variant SKU is required.');
            } elseif (isset($seenSkus[strtolower($sku)]) || ProductVariant::withTrashed()->where('sku', $sku)->exists()) {
                $errors[] = $this->error('sku', 'duplicate_sku', "SKU $sku is already in use.");
            }
            $seenSkus[strtolower($sku)] = true;
            $selected = $this->optionValues((string) ($row['option_values'] ?? ''));
            if (! $selected) {
                $errors[] = $this->error('option_values', 'invalid_variant_combination', 'Option values must use option=value pairs separated by |.');
            }
            ksort($selected);
            $key = json_encode($selected);
            if (in_array($key, $combinations, true)) {
                $errors[] = $this->error('option_values', 'invalid_variant_combination', 'Variant combinations must be unique per product.');
            }
            $combinations[] = $key;
            foreach ($selected as $option => $value) {
                $optionValues[$option][$value] = true;
            }
            $price = filter_var($row['price_amount'] ?? null, FILTER_VALIDATE_INT);
            $compare = ($row['compare_at_amount'] ?? '') === '' ? null : filter_var($row['compare_at_amount'], FILTER_VALIDATE_INT);
            if ($price === false || $price < 0) {
                $errors[] = $this->error('price_amount', 'invalid_price', 'Price must be a non-negative integer.');
            }
            if ($compare !== null && ($compare === false || $compare <= $price)) {
                $errors[] = $this->error('compare_at_amount', 'invalid_price', 'Compare price must exceed selling price.');
            }
            $currency = strtoupper(trim((string) ($row['currency'] ?? '')));
            if (! preg_match('/^[A-Z]{3}$/', $currency)) {
                $errors[] = $this->error('currency', 'invalid_currency', 'Currency must be a three-letter uppercase code.');
            }
            $variants[] = ['sku' => $sku, 'title' => ($row['title'] ?? '') ?: implode(' / ', array_map(fn ($value) => Str::headline($value), $selected)), 'option_values' => $selected, 'price_amount' => $price === false ? -1 : $price, 'compare_at_amount' => $compare === false ? null : $compare, 'currency' => $currency, 'barcode' => null, 'weight_grams' => ($row['weight_grams'] ?? '') === '' ? null : (int) $row['weight_grams'], 'is_active' => $this->boolean($row['is_active'] ?? '1'), 'is_default' => false];
        }
        $activeIndex = array_search(true, array_column($variants, 'is_active'), true);
        if ($activeIndex === false && $variants) {
            $errors[] = $this->error('is_active', 'invalid_default', 'At least one variant must be active.');
        } elseif ($activeIndex !== false) {
            $variants[$activeIndex]['is_default'] = true;
        }
        $currencies = array_unique(array_column($variants, 'currency'));
        if (count($currencies) > 1) {
            $errors[] = $this->error('currency', 'invalid_currency', 'All variants of a product must use one currency.');
        }
        $options = [];
        $sort = 0;
        foreach ($optionValues as $code => $values) {
            $valueRows = [];
            $valueSort = 0;
            foreach (array_keys($values) as $value) {
                $valueRows[] = ['label' => Str::headline($value), 'code' => Str::slug($value), 'swatch_value' => null, 'sort_order' => $valueSort++];
            } $options[] = ['name' => Str::headline($code), 'code' => Str::slug($code), 'sort_order' => $sort++, 'values' => $valueRows];
        }

        return [$variants, $options, $errors];
    }

    private function images(array $rows, array $uploadedNames, array $variantSkus): array
    {
        $images = [];
        $errors = [];
        $primaryScopes = [];
        foreach ($rows as $row) {
            $path = trim((string) ($row['file_path'] ?? ''));
            $variantSku = trim((string) ($row['variant_sku'] ?? '')) ?: null;
            if ($path === '' || str_contains($path, '..') || Str::startsWith($path, ['/', '\\'])) {
                $errors[] = $this->error('file_path', 'missing_image_file', 'Image file path is missing or unsafe.');

                continue;
            }
            $uploaded = in_array($path, $uploadedNames, true);
            if (! $uploaded && (! str_starts_with($path, 'products/') || ! Storage::disk('public')->exists($path))) {
                $errors[] = $this->error('file_path', 'missing_image_file', "Image file $path does not exist.");
            }
            if ($variantSku && ! in_array($variantSku, $variantSkus, true)) {
                $errors[] = $this->error('variant_sku', 'invalid_variant', 'Image variant SKU does not belong to this product.');
            }
            $scope = $variantSku ?? 'product';
            $primary = $this->boolean($row['is_primary'] ?? '0');
            if ($primary && isset($primaryScopes[$scope])) {
                $errors[] = $this->error('is_primary', 'duplicate_primary', 'Only one primary image is allowed per product or variant.');
            }
            if ($primary) {
                $primaryScopes[$scope] = true;
            }
            $images[] = ['path' => $uploaded ? '' : $path, 'upload_name' => $uploaded ? $path : null, 'alt_text' => ($row['alt_text'] ?? '') ?: null, 'width' => null, 'height' => null, 'mime_type' => null, 'sort_order' => (int) ($row['sort_order'] ?? 0), 'is_primary' => $primary, 'variant_sku' => $variantSku];
        }
        foreach ($images as &$image) {
            if ($image['upload_name'] === null) {
                unset($image['upload_name']);
            }
        }

        return [$images, $errors];
    }

    private function optionValues(string $input): array
    {
        $values = [];
        foreach (array_filter(explode('|', $input)) as $pair) {
            $parts = array_map('trim', explode('=', $pair, 2));
            if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
                return [];
            } $values[Str::slug($parts[0])] = Str::slug($parts[1]);
        }

        return $values;
    }

    private function boolean(mixed $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'active'], true);
    }

    private function error(string $field, string $code, string $message): array
    {
        return ['field' => $field, 'code' => $code, 'message' => $message];
    }
}
