<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\InventoryLevel;
use App\Models\InventoryLocation;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;

class DevelopmentCatalogSeeder extends Seeder
{
    use WithoutModelEvents;

    private const SEED_KEY = 'noure-development-catalog-v1';

    public function run(): void
    {
        if (! app()->environment(['local', 'development'])) {
            throw new LogicException('DevelopmentCatalogSeeder may only run in a local or development environment.');
        }

        $definitions = $this->productDefinitions();
        $seededCount = Product::query()->where('metadata->development_seed', self::SEED_KEY)->count();

        if ($seededCount === count($definitions)) {
            $this->command?->info('The development catalog is already seeded.');

            return;
        }

        if ($seededCount > 0 || Product::withTrashed()->whereIn('slug', array_column($definitions, 'slug'))->exists()) {
            throw new RuntimeException('Development catalog seed data is incomplete or conflicts with existing product slugs.');
        }

        DB::transaction(function () use ($definitions): void {
            $categories = $this->seedCategories();
            $location = $this->mainInventoryLocation();

            foreach ($definitions as $definition) {
                $this->seedProduct($definition, $categories, $location);
            }
        });

        $this->command?->info('Seeded '.count($definitions).' development catalog products.');
    }

    /** @return array<string, Category> */
    private function seedCategories(): array
    {
        $definitions = [
            ['Dresses', 'dresses', 'Refined dresses for everyday moments and special occasions.', 10, true, null],
            ['Midi Dresses', 'midi-dresses', 'Versatile midi silhouettes with considered details.', 10, true, 'dresses'],
            ['Maxi Dresses', 'maxi-dresses', 'Flowing full-length dresses for effortless dressing.', 20, true, 'dresses'],
            ['Tops', 'tops', 'Blouses, knits, and elevated everyday tops.', 20, true, null],
            ['Bottoms', 'bottoms', 'Tailored trousers and skirts designed to mix and match.', 30, true, null],
            ['Outerwear', 'outerwear', 'Light layers and structured outerwear.', 40, true, null],
            ['Accessories', 'accessories', 'Finishing pieces for a considered wardrobe.', 50, true, null],
            ['Preview Collection', 'preview-collection', 'Development-only inactive category for visibility checks.', 90, false, null],
        ];
        $categories = [];

        foreach ($definitions as [$name, $slug, $description, $sortOrder, $isActive, $parentSlug]) {
            $category = Category::query()->firstOrNew(['slug' => $slug]);
            $category->fill(Category::factory()->raw([
                'parent_id' => $parentSlug === null ? null : $categories[$parentSlug]->getKey(),
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'image_path' => 'development/catalog/categories/'.$slug.'.webp',
                'sort_order' => $sortOrder,
                'is_active' => $isActive,
            ]));
            $category->save();
            $categories[$slug] = $category;
        }

        return $categories;
    }

    private function mainInventoryLocation(): InventoryLocation
    {
        $location = InventoryLocation::query()->firstOrNew(['code' => 'MAIN']);

        if (! $location->exists) {
            $location->fill(InventoryLocation::factory()->raw([
                'code' => 'MAIN',
                'name' => 'Main warehouse',
                'address' => ['city' => 'Jakarta', 'country_code' => 'ID'],
                'contact' => null,
                'is_active' => true,
            ]));
            $location->save();
        }

        return $location;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, Category>  $categories
     */
    private function seedProduct(array $definition, array $categories, InventoryLocation $location): void
    {
        $product = Product::factory()->create([
            'name' => $definition['name'],
            'slug' => $definition['slug'],
            'short_description' => $definition['short_description'],
            'description' => $definition['description'],
            'brand' => 'Noure',
            'status' => $definition['status'] ?? 'active',
            'published_at' => $definition['published_at'] ?? now()->subDays(7),
            'metadata' => ['development_seed' => self::SEED_KEY, 'material' => $definition['material']],
        ]);

        foreach ($definition['categories'] as $index => $categorySlug) {
            $product->categories()->attach($categories[$categorySlug], [
                'is_primary' => $index === 0,
                'sort_order' => $index * 10,
            ]);
        }

        foreach (range(1, 3) as $imageNumber) {
            ProductImage::factory()->for($product)->create([
                'variant_id' => null,
                'path' => sprintf('development/catalog/products/%s/%02d.webp', $definition['slug'], $imageNumber),
                'alt_text' => $definition['name'].' product view '.$imageNumber,
                'sort_order' => $imageNumber - 1,
                'is_primary' => $imageNumber === 1,
            ]);
        }

        $values = $this->seedOptions($product, $definition['variants']);

        foreach ($definition['variants'] as $index => $variantDefinition) {
            $selectedValues = collect(['color', 'size'])
                ->filter(fn (string $optionCode) => isset($variantDefinition[$optionCode]))
                ->map(fn (string $optionCode) => $values[$optionCode][$variantDefinition[$optionCode]])
                ->values();

            $variant = ProductVariant::factory()->for($product)->create([
                'sku' => $variantDefinition['sku'],
                'title' => $selectedValues->pluck('label')->implode(' / '),
                'combination_key' => $selectedValues->pluck('id')->implode(':'),
                'price_amount' => $definition['price_amount'],
                'compare_at_amount' => $index === 0 ? ($definition['compare_at_amount'] ?? null) : null,
                'currency' => 'IDR',
                'barcode' => null,
                'weight_grams' => $definition['weight_grams'],
                'is_default' => $index === 0,
                'is_active' => true,
            ]);
            $variant->optionValues()->attach($selectedValues->pluck('id'));

            $stock = ($definition['all_unavailable'] ?? false)
                ? ['on_hand' => 3, 'reserved' => 2, 'safety_stock' => 1]
                : $this->stockForIndex($index);

            InventoryLevel::factory()->for($variant, 'variant')->for($location, 'location')->create([
                ...$stock,
                'version' => 0,
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     * @return array<string, array<string, ProductOptionValue>>
     */
    private function seedOptions(Product $product, array $variants): array
    {
        $labels = ['cream' => 'Cream', 'black' => 'Black', 'sage' => 'Sage', 'mocha' => 'Mocha', 'navy' => 'Navy', 's' => 'S', 'm' => 'M', 'l' => 'L', 'xl' => 'XL'];
        $swatches = ['cream' => '#F3EBDD', 'black' => '#171717', 'sage' => '#A8B5A2', 'mocha' => '#8B6F5A', 'navy' => '#1E2A44'];
        $result = [];

        foreach (['color' => 'Color', 'size' => 'Size'] as $optionCode => $optionName) {
            $codes = collect($variants)->pluck($optionCode)->filter()->unique()->values();
            if ($codes->isEmpty()) {
                continue;
            }

            $option = ProductOption::factory()->for($product)->create([
                'name' => $optionName,
                'code' => $optionCode,
                'sort_order' => $optionCode === 'color' ? 0 : 1,
            ]);

            foreach ($codes as $sortOrder => $code) {
                $result[$optionCode][$code] = ProductOptionValue::factory()->for($option, 'option')->create([
                    'label' => $labels[$code],
                    'code' => $code,
                    'swatch_value' => $swatches[$code] ?? null,
                    'sort_order' => $sortOrder,
                ]);
            }
        }

        return $result;
    }

    /** @return array{on_hand: int, reserved: int, safety_stock: int} */
    private function stockForIndex(int $index): array
    {
        return match ($index % 4) {
            0 => ['on_hand' => 18, 'reserved' => 2, 'safety_stock' => 2],
            1 => ['on_hand' => 4, 'reserved' => 1, 'safety_stock' => 2],
            2 => ['on_hand' => 3, 'reserved' => 2, 'safety_stock' => 1],
            default => ['on_hand' => 10, 'reserved' => 0, 'safety_stock' => 1],
        };
    }

    /** @return array<int, array<string, mixed>> */
    private function productDefinitions(): array
    {
        return [
            $this->product('Luna Linen Midi Dress', 'luna-linen-midi-dress', 'A breathable linen-blend midi with a softly defined waist.', 'A warm-weather staple with a shaped bodice, practical side pockets, and an easy midi skirt.', 'Linen blend', ['midi-dresses', 'dresses'], 399000, 449000, 420, 'LUNA', [['cream', 's'], ['cream', 'm'], ['black', 'm'], ['black', 'l']]),
            $this->product('Serene Satin Maxi Dress', 'serene-satin-maxi-dress', 'A fluid satin maxi designed for evening occasions.', 'Bias-cut satin creates an elegant drape, finished with adjustable straps and a softly flared hem.', 'Satin', ['maxi-dresses', 'dresses'], 629000, 699000, 510, 'SERENE', [['navy', 's'], ['navy', 'm'], ['mocha', 'm'], ['mocha', 'l']]),
            $this->product('Amara Wrap Midi Dress', 'amara-wrap-midi-dress', 'A versatile wrap midi with a gently textured finish.', 'The adjustable wrap waist and softly gathered sleeve make Amara an effortless day-to-evening piece.', 'Viscose crepe', ['midi-dresses', 'dresses'], 459000, null, 390, 'AMARA', [['sage', 's'], ['sage', 'm'], ['black', 'm'], ['black', 'xl']]),
            $this->product('Elara Ribbed Knit Top', 'elara-ribbed-knit-top', 'A fitted rib-knit top with a clean square neckline.', 'Made from a comfortable stretch knit, Elara layers neatly while retaining its shape throughout the day.', 'Cotton knit', ['tops'], 249000, 289000, 240, 'ELARA', [['cream', 's'], ['cream', 'm'], ['black', 'm'], ['black', 'l']]),
            $this->product('Celeste Silk Blouse', 'celeste-silk-blouse', 'A relaxed silk-touch blouse with a softly draped collar.', 'Celeste balances an easy silhouette with refined covered buttons and wide cuffs.', 'Silk blend', ['tops'], 379000, null, 210, 'CELESTE', [['cream', 's'], ['cream', 'm'], ['navy', 'm'], ['navy', 'l']]),
            $this->product('Mira Wide-Leg Trousers', 'mira-wide-leg-trousers', 'High-rise tailored trousers with an elegant wide leg.', 'Pressed creases and a smooth waistband give Mira a polished line from office hours to dinner.', 'Tailoring twill', ['bottoms'], 419000, 469000, 560, 'MIRA', [['black', 's'], ['black', 'm'], ['black', 'l'], ['mocha', 'm']]),
            $this->product('Naya Tailored Midi Skirt', 'naya-tailored-midi-skirt', 'A streamlined midi skirt with a subtle front split.', 'Naya is cut from structured twill with a clean waistband and a comfortable back vent.', 'Tailoring twill', ['bottoms'], 349000, null, 430, 'NAYA', [['black', 's'], ['black', 'm'], ['sage', 'm'], ['sage', 'l']]),
            $this->product('Aria Cropped Jacket', 'aria-cropped-jacket', 'A softly structured cropped jacket for modern layering.', 'Aria features a neat collar, tonal buttons, and a boxy proportion that pairs naturally with high-rise separates.', 'Cotton twill', ['outerwear'], 579000, 649000, 680, 'ARIA', [['cream', 's'], ['cream', 'm'], ['black', 'm'], ['black', 'l']]),
            $this->product('Noelle Belted Trench Coat', 'noelle-belted-trench-coat', 'A lightweight trench with classic storm-flap detailing.', 'Designed for transitional weather with a removable belt, deep pockets, and a fluid longline silhouette.', 'Cotton gabardine', ['outerwear'], 899000, null, 980, 'NOELLE', [['mocha', 'm'], ['mocha', 'l'], ['navy', 'm'], ['navy', 'xl']]),
            $this->product('Solene Mini Shoulder Bag', 'solene-mini-shoulder-bag', 'A compact shoulder bag with a softly curved profile.', 'Solene holds daily essentials in a lined interior and closes with a discreet magnetic fastening.', 'Vegan leather', ['accessories'], 329000, 379000, 460, 'SOLENE', [['cream', null], ['black', null], ['mocha', null]]),
            $this->product('Liora Silk Scarf', 'liora-silk-scarf', 'A lightweight printed scarf for subtle color and movement.', 'Wear Liora at the neck, in the hair, or tied to a bag for an easy finishing detail.', 'Silk', ['accessories'], 179000, null, 70, 'LIORA', [['sage', null], ['navy', null], ['cream', null]]),
            $this->product('Esme Soft Knit Cardigan', 'esme-soft-knit-cardigan', 'A fine-gauge cardigan with a relaxed, polished shape.', 'Esme has a soft hand feel, shell buttons, and ribbed edges. This seeded product is intentionally sold out.', 'Viscose knit', ['tops', 'outerwear'], 389000, null, 360, 'ESME', [['cream', 's'], ['cream', 'm'], ['mocha', 'l']], ['all_unavailable' => true]),
            $this->product('Studio Sample Shirt Dress', 'studio-sample-shirt-dress', 'A development draft used to verify storefront visibility.', 'This record intentionally remains in draft and must never appear in the public catalog.', 'Cotton poplin', ['midi-dresses', 'dresses'], 429000, null, 440, 'STUDIO', [['cream', 'm']], ['status' => 'draft']),
            $this->product('Heritage Leather Belt', 'heritage-leather-belt', 'An archived accessory used to verify storefront visibility.', 'This record intentionally remains archived and must never appear in the public catalog.', 'Leather', ['accessories'], 229000, null, 160, 'HERITAGE', [['black', null], ['mocha', null]], ['status' => 'archived']),
            $this->product('Aurelia Evening Maxi Dress', 'aurelia-evening-maxi-dress', 'A future collection maxi used to verify scheduled publishing.', 'This active record has a future publication time and must remain hidden until that time.', 'Satin crepe', ['maxi-dresses', 'dresses'], 749000, 829000, 590, 'AURELIA', [['navy', 's'], ['navy', 'm']], ['published_at' => now()->addMonth()]),
        ];
    }

    /**
     * @param  array<int, array{0: string, 1: ?string}>  $combinations
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function product(string $name, string $slug, string $shortDescription, string $description, string $material, array $categories, int $priceAmount, ?int $compareAtAmount, int $weightGrams, string $skuPrefix, array $combinations, array $overrides = []): array
    {
        $variants = [];
        foreach ($combinations as [$color, $size]) {
            $variants[] = [
                'color' => $color,
                'size' => $size,
                'sku' => 'NOU-'.$skuPrefix.'-'.strtoupper($color).($size === null ? '' : '-'.strtoupper($size)),
            ];
        }

        return array_merge([
            'name' => $name,
            'slug' => $slug,
            'short_description' => $shortDescription,
            'description' => $description,
            'material' => $material,
            'categories' => $categories,
            'price_amount' => $priceAmount,
            'compare_at_amount' => $compareAtAmount,
            'weight_grams' => $weightGrams,
            'variants' => $variants,
        ], $overrides);
    }
}
