<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryLevel;
use App\Models\InventoryLocation;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PublicProductApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    private InventoryLocation $location;

    protected function setUp(): void
    {
        parent::setUp();
        $this->location = InventoryLocation::factory()->create(['code' => 'MAIN', 'is_active' => true]);
    }

    public function test_product_listing_returns_contract_shape(): void
    {
        [$product] = $this->createPublicProduct(['name' => 'Luna Dress', 'slug' => 'luna-dress'], [
            'price_amount' => 399000, 'compare_at_amount' => 449000, 'currency' => 'IDR',
        ], 10);
        $category = Category::factory()->create(['name' => 'Dresses', 'slug' => 'dresses']);
        $product->categories()->attach($category, ['is_primary' => true, 'sort_order' => 0]);
        ProductImage::factory()->for($product)->create(['path' => 'products/luna.webp']);

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonPath('data.0.public_id', $product->public_id)
            ->assertJsonPath('data.0.primary_category.public_id', $category->public_id)
            ->assertJsonPath('data.0.price.price_amount', 399000)
            ->assertJsonPath('data.0.price.compare_at_amount', 449000)
            ->assertJsonPath('data.0.price.currency', 'IDR')
            ->assertJsonPath('data.0.available', true)
            ->assertJsonPath('meta.pagination.total', 1);
    }

    public function test_product_detail_returns_all_public_nested_data(): void
    {
        [$product, $variant] = $this->createPublicProduct(['slug' => 'luna-dress'], [
            'sku' => 'NOU-LUNA-CRM-S', 'title' => 'Cream / S', 'price_amount' => 399000, 'compare_at_amount' => 449000,
        ], 10);
        $category = Category::factory()->create(['slug' => 'dresses']);
        $product->categories()->attach($category, ['is_primary' => true, 'sort_order' => 0]);
        ProductImage::factory()->for($product)->create(['variant_id' => $variant->id, 'path' => 'products/luna.webp']);
        $color = ProductOption::factory()->for($product)->create(['name' => 'Color', 'code' => 'color']);
        $cream = ProductOptionValue::factory()->for($color, 'option')->create(['label' => 'Cream', 'code' => 'cream']);
        $variant->optionValues()->attach($cream);
        ProductVariant::factory()->for($product)->create(['sku' => 'NOU-LUNA-HIDDEN', 'combination_key' => 'hidden', 'is_active' => false]);

        $this->getJson('/api/v1/products/luna-dress')
            ->assertOk()
            ->assertJsonPath('data.public_id', $product->public_id)
            ->assertJsonPath('data.categories.0.public_id', $category->public_id)
            ->assertJsonPath('data.images.0.variant_public_id', $variant->public_id)
            ->assertJsonPath('data.options.0.code', 'color')
            ->assertJsonPath('data.options.0.values.0.code', 'cream')
            ->assertJsonPath('data.variants.0.sku', 'NOU-LUNA-CRM-S')
            ->assertJsonPath('data.variants.0.selected_options.0.option_code', 'color')
            ->assertJsonPath('data.variants.0.selected_options.0.value_code', 'cream')
            ->assertJsonPath('data.variants.0.available', true)
            ->assertJsonCount(1, 'data.variants');
    }

    public function test_draft_archived_and_future_products_are_hidden(): void
    {
        foreach ([
            ['slug' => 'draft', 'status' => 'draft'],
            ['slug' => 'archived', 'status' => 'archived'],
            ['slug' => 'future', 'status' => 'active', 'published_at' => now()->addDay()],
        ] as $state) {
            [$product] = $this->createPublicProduct($state);
            $this->getJson('/api/v1/products/'.$product->slug)->assertNotFound()->assertJsonPath('meta.errors.0.code', 'product_not_found');
        }

        $this->getJson('/api/v1/products')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_soft_deleted_product_is_hidden(): void
    {
        [$product] = $this->createPublicProduct(['slug' => 'deleted']);
        $product->delete();

        $this->getJson('/api/v1/products')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/products/deleted')->assertNotFound()->assertJsonPath('meta.errors.0.code', 'product_not_found');
    }

    public function test_product_without_active_variant_is_hidden_and_inactive_variants_are_excluded(): void
    {
        $this->createPublicProduct(['slug' => 'no-active'], ['is_active' => false]);
        [$visible] = $this->createPublicProduct(['slug' => 'visible']);
        ProductVariant::factory()->for($visible)->create(['combination_key' => 'hidden', 'is_active' => false]);

        $this->getJson('/api/v1/products')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/products/no-active')->assertNotFound();
        $this->getJson('/api/v1/products/visible')->assertOk()->assertJsonCount(1, 'data.variants');
    }

    public function test_availability_calculation_uses_active_locations(): void
    {
        [$available] = $this->createPublicProduct(['slug' => 'available'], [], 10, 3, 2);
        [$unavailable] = $this->createPublicProduct(['slug' => 'unavailable'], [], 5, 3, 2);
        [$inactive, $inactiveVariant] = $this->createPublicProduct(['slug' => 'inactive-location']);
        $inactiveLocation = InventoryLocation::factory()->create(['is_active' => false]);
        InventoryLevel::factory()->for($inactiveVariant, 'variant')->for($inactiveLocation, 'location')->create(['on_hand' => 100]);

        $bySlug = collect($this->getJson('/api/v1/products')->assertOk()->json('data'))->keyBy('slug');
        $this->assertTrue($bySlug[$available->slug]['available']);
        $this->assertFalse($bySlug[$unavailable->slug]['available']);
        $this->assertFalse($bySlug[$inactive->slug]['available']);
    }

    public function test_category_filter_and_unknown_category_behavior(): void
    {
        $category = Category::factory()->create(['slug' => 'dresses']);
        [$matching] = $this->createPublicProduct(['slug' => 'matching']);
        $this->createPublicProduct(['slug' => 'other']);
        $matching->categories()->attach($category, ['is_primary' => true, 'sort_order' => 0]);

        $this->getJson('/api/v1/products?category=dresses')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.public_id', $matching->public_id);
        $this->getJson('/api/v1/products?category=missing')->assertNotFound()->assertJsonPath('meta.errors.0.code', 'category_not_found');
    }

    public function test_search_matches_name_only(): void
    {
        [$matching] = $this->createPublicProduct(['name' => 'Luna Linen Dress', 'slug' => 'matching']);
        $this->createPublicProduct(['name' => 'Cotton Shirt', 'slug' => 'other', 'description' => 'Luna']);

        $this->getJson('/api/v1/products?search=luna')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.public_id', $matching->public_id);
    }

    public function test_minimum_and_maximum_price_filters(): void
    {
        [$matching] = $this->createPublicProduct(['slug' => 'matching'], ['price_amount' => 300000]);
        $this->createPublicProduct(['slug' => 'cheap'], ['price_amount' => 100000]);
        $this->createPublicProduct(['slug' => 'expensive'], ['price_amount' => 500000]);

        $this->getJson('/api/v1/products?min_price=200000&max_price=400000')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.public_id', $matching->public_id);
    }

    public function test_availability_filter(): void
    {
        [$available] = $this->createPublicProduct(['slug' => 'available'], [], 2);
        [$unavailable] = $this->createPublicProduct(['slug' => 'unavailable'], [], 0);

        $this->getJson('/api/v1/products?availability=available')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.public_id', $available->public_id);
        $this->getJson('/api/v1/products?availability=unavailable')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.public_id', $unavailable->public_id);
    }

    public function test_all_supported_sort_modes(): void
    {
        Carbon::setTestNow('2026-09-16T00:00:00Z');
        [$alpha] = $this->createPublicProduct(['name' => 'Alpha', 'slug' => 'alpha', 'created_at' => now()->subDay()], ['price_amount' => 200000]);
        [$zulu] = $this->createPublicProduct(['name' => 'Zulu', 'slug' => 'zulu', 'created_at' => now()], ['price_amount' => 100000]);

        foreach ([
            'newest' => $zulu->public_id, 'oldest' => $alpha->public_id,
            'price_asc' => $zulu->public_id, 'price_desc' => $alpha->public_id,
            'name_asc' => $alpha->public_id, 'name_desc' => $zulu->public_id,
        ] as $sort => $publicId) {
            $this->getJson('/api/v1/products?sort='.$sort)->assertOk()->assertJsonPath('data.0.public_id', $publicId);
        }
        Carbon::setTestNow();
    }

    public function test_product_pagination(): void
    {
        $this->createPublicProduct(['slug' => 'one']);
        $this->createPublicProduct(['slug' => 'two']);
        $this->createPublicProduct(['slug' => 'three']);

        $this->getJson('/api/v1/products?per_page=2&page=2')
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.pagination.total', 3)->assertJsonPath('meta.pagination.per_page', 2)
            ->assertJsonPath('meta.pagination.current_page', 2)->assertJsonPath('meta.pagination.last_page', 2);
    }

    public function test_invalid_queries_return_documented_422_response(): void
    {
        foreach (['per_page=101', 'availability=sometimes', 'sort=price', 'min_price=-1', 'min_price=500&max_price=100', 'search=', 'unsupported=value'] as $query) {
            $response = $this->getJson('/api/v1/products?'.$query);
            $this->assertSame(422, $response->status(), 'Query should be invalid: '.$query);
            $response->assertJsonPath('data', null)->assertJsonPath('message', 'Validation failed.')
                ->assertJsonStructure(['meta' => ['errors' => [['code', 'field', 'message']]]]);
        }
    }

    public function test_product_detail_rejects_listing_filters(): void
    {
        $this->createPublicProduct(['slug' => 'detail']);

        $this->getJson('/api/v1/products/detail?sort=newest')
            ->assertUnprocessable()
            ->assertJsonPath('meta.errors.0.field', 'sort');
    }

    public function test_payloads_exclude_internal_and_raw_inventory_fields(): void
    {
        [$product] = $this->createPublicProduct(['slug' => 'safe-payload'], ['combination_key' => 'secret-combination'], 20, 3, 2);
        $json = json_encode($this->getJson('/api/v1/products/safe-payload')->assertOk()->json(), JSON_THROW_ON_ERROR);

        foreach (['"id"', 'combination_key', 'on_hand', 'reserved', 'safety_stock', 'version'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $json);
        }
        $this->assertStringContainsString($product->public_id, $json);
    }

    /**
     * @param  array<string, mixed>  $productAttributes
     * @param  array<string, mixed>  $variantAttributes
     * @return array{Product, ProductVariant}
     */
    private function createPublicProduct(array $productAttributes = [], array $variantAttributes = [], ?int $onHand = null, int $reserved = 0, int $safetyStock = 0): array
    {
        $product = Product::factory()->create($productAttributes);
        $variant = ProductVariant::factory()->for($product)->create(array_merge([
            'is_active' => true, 'is_default' => true, 'barcode' => null,
        ], $variantAttributes));

        if ($onHand !== null) {
            InventoryLevel::factory()->for($variant, 'variant')->for($this->location, 'location')->create([
                'on_hand' => $onHand, 'reserved' => $reserved, 'safety_stock' => $safetyStock,
            ]);
        }

        return [$product, $variant];
    }
}
