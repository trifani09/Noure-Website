<?php

namespace App\Catalog;

use App\Exceptions\ProductConflictException;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductVariant;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminProductVariantService
{
    public function addOption(Product $product, array $attributes): ProductOption
    {
        if ($product->variants()->exists()) {
            throw new ProductConflictException('variants_require_regeneration', 'Existing variants must be regenerated before adding a product option.');
        }

        return DB::transaction(function () use ($product, $attributes): ProductOption {
            try {
                $values = $attributes['values'];
                unset($attributes['values']);
                $option = $product->options()->create($attributes);
                foreach ($values as $value) {
                    $option->values()->create($value);
                }

                return $option->load(['values' => fn ($query) => $query->orderBy('sort_order')]);
            } catch (UniqueConstraintViolationException $exception) {
                throw $this->conflictFor($exception);
            }
        });
    }

    public function addOptionValue(ProductOption $option, array $attributes)
    {
        try {
            return DB::transaction(fn () => $option->values()->create($attributes));
        } catch (UniqueConstraintViolationException $exception) {
            throw $this->conflictFor($exception);
        }
    }

    public function generate(Product $product, array $attributes): array
    {
        return DB::transaction(function () use ($product, $attributes): array {
            try {
                $options = $product->options()->with(['values' => fn ($query) => $query->orderBy('sort_order')])->orderBy('sort_order')->get();
                $productCurrency = $product->variants()->value('currency');
                if ($productCurrency !== null && $productCurrency !== $attributes['defaults']['currency']) {
                    throw new ProductConflictException('product_currency_conflict', 'All variants of a product must use the same currency.');
                }
                $selected = $this->selectedValues($options, $attributes['option_values']);
                $combinations = $this->cartesian($selected->values()->all());
                $existingByKey = $product->variants()->with('optionValues.option')->get()->keyBy('combination_key');
                $created = collect();
                $existing = collect();

                foreach ($combinations as $combination) {
                    $combinationKey = $this->combinationKey($combination);
                    if ($existingByKey->has($combinationKey)) {
                        $existing->push($existingByKey->get($combinationKey));

                        continue;
                    }
                    $codes = $combination->mapWithKeys(fn ($value) => [$value->option->code => $value->code]);
                    $sku = $attributes['sku_template'];
                    foreach ($codes as $optionCode => $valueCode) {
                        $sku = str_replace('{'.$optionCode.'}', $valueCode, $sku);
                    }
                    if (preg_match('/\{[^}]+\}/', $sku) || strlen($sku) > 100) {
                        throw new ProductConflictException('sku_conflict', 'The SKU template did not produce a valid SKU.');
                    }
                    $variant = $product->variants()->create(array_merge($attributes['defaults'], [
                        'sku' => $sku,
                        'title' => $combination->pluck('label')->implode(' / '),
                        'combination_key' => $combinationKey,
                        'is_default' => false,
                    ]));
                    $variant->optionValues()->sync($combination->pluck('id'));
                    $created->push($variant->load('optionValues.option'));
                }

                return ['created' => $created, 'existing' => $existing];
            } catch (UniqueConstraintViolationException $exception) {
                throw $this->conflictFor($exception);
            }
        });
    }

    public function update(Product $product, ProductVariant $variant, array $attributes): ProductVariant
    {
        return DB::transaction(function () use ($product, $variant, $attributes): ProductVariant {
            try {
                $locked = $product->variants()->lockForUpdate()->findOrFail($variant->id);
                if (($attributes['is_active'] ?? true) === false && $locked->is_default) {
                    throw new ProductConflictException('default_variant_cannot_be_disabled', 'Select another active default before disabling this variant.');
                }
                if (isset($attributes['currency'])) {
                    $otherCurrency = $product->variants()->whereKeyNot($locked->id)->where('currency', '!=', $attributes['currency'])->exists();
                    if ($otherCurrency) {
                        throw new ProductConflictException('product_currency_conflict', 'All variants of a product must use the same currency.');
                    }
                }
                $price = $attributes['price_amount'] ?? $locked->price_amount;
                $compareAt = array_key_exists('compare_at_amount', $attributes) ? $attributes['compare_at_amount'] : $locked->compare_at_amount;
                if ($compareAt !== null && $compareAt <= $price) {
                    throw new ProductConflictException('compare_at_amount_invalid', 'The compare-at amount must exceed the price.');
                }
                if (array_key_exists('option_values', $attributes)) {
                    $values = $this->selectedValues($product->options()->with('values')->orderBy('sort_order')->get(), $attributes['option_values'])
                        ->map(fn (Collection $optionValues) => $optionValues->first())
                        ->values();
                    $attributes['combination_key'] = $this->combinationKey($values);
                    unset($attributes['option_values']);
                    $locked->update($attributes);
                    $locked->optionValues()->sync($values->pluck('id'));
                } else {
                    $locked->update($attributes);
                }

                return $locked->load('optionValues.option');
            } catch (UniqueConstraintViolationException $exception) {
                throw $this->conflictFor($exception);
            }
        });
    }

    public function setDefault(Product $product, ProductVariant $variant): ProductVariant
    {
        return DB::transaction(function () use ($product, $variant): ProductVariant {
            $locked = $product->variants()->lockForUpdate()->findOrFail($variant->id);
            if (! $locked->is_active) {
                throw new ProductConflictException('inactive_variant_cannot_be_default', 'An inactive variant cannot be the default.');
            }
            $product->variants()->where('is_default', true)->update(['is_default' => false]);
            $locked->update(['is_default' => true]);

            return $locked->load('optionValues.option');
        });
    }

    private function selectedValues(Collection $options, array $selections): Collection
    {
        if ($options->pluck('code')->sort()->values()->all() !== collect(array_keys($selections))->sort()->values()->all()) {
            throw new ProductConflictException('variant_combination_conflict', 'Select exactly one or more values for every product option.');
        }
        $selected = collect();
        foreach ($options as $option) {
            $codes = is_array($selections[$option->code]) ? $selections[$option->code] : [$selections[$option->code]];
            $values = $option->values->whereIn('code', $codes)->values();
            if ($values->count() !== count(array_unique($codes))) {
                throw new ProductConflictException('variant_combination_conflict', 'An option value does not belong to this product.');
            }
            $selected->put($option->code, $values);
        }

        return $selected;
    }

    private function cartesian(array $sets): array
    {
        $result = [collect()];
        foreach ($sets as $set) {
            $next = [];
            foreach ($result as $combination) {
                foreach ($set as $value) {
                    $next[] = $combination->concat([$value]);
                }
            }
            $result = $next;
        }

        return $result;
    }

    private function combinationKey(Collection $values): string
    {
        return $values->pluck('id')->implode(':');
    }

    private function conflictFor(UniqueConstraintViolationException $exception): ProductConflictException
    {
        $message = strtolower($exception->getMessage());
        if (str_contains($message, 'sku')) {
            return new ProductConflictException('sku_conflict', 'The SKU is already in use.');
        }
        if (str_contains($message, 'combination')) {
            return new ProductConflictException('variant_combination_conflict', 'Variant option combinations must be unique.');
        }
        if (str_contains($message, 'barcode')) {
            return new ProductConflictException('barcode_conflict', 'The barcode is already in use.');
        }

        return new ProductConflictException('option_code_conflict', 'The option or value code is already in use.');
    }
}
