<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->category = Category::factory()->create();
    }

    public function test_admin_can_create_list_and_view_a_product(): void
    {
        $created = $this->actingAs($this->admin)->postJson('/api/v1/admin/products', $this->payload())->assertCreated()->assertJsonMissingPath('data.id');
        $publicId = $created->json('data.public_id');
        $this->getJson('/api/v1/admin/products?search=Luna&status=active&availability=unavailable&sort=price_asc')->assertOk()->assertJsonPath('meta.pagination.total', 1)->assertJsonPath('data.0.variant_count', 2);
        $this->getJson("/api/v1/admin/products/$publicId")->assertOk()->assertJsonPath('data.options.0.code', 'color')->assertJsonCount(2, 'data.variants');
    }

    public function test_admin_can_update_product_and_retain_variant_public_id(): void
    {
        $created = $this->actingAs($this->admin)->postJson('/api/v1/admin/products', $this->payload())->json('data');
        $payload = $this->payload();
        $payload['name'] = 'Updated Luna';
        foreach ($payload['variants'] as $index => &$variant) {
            $variant['public_id'] = $created['variants'][$index]['public_id'];
        }
        $this->putJson("/api/v1/admin/products/{$created['public_id']}", $payload)->assertOk()->assertJsonPath('data.name', 'Updated Luna')->assertJsonPath('data.variants.0.public_id', $created['variants'][0]['public_id']);
    }

    public function test_admin_can_soft_delete_product_and_variants(): void
    {
        $created = $this->actingAs($this->admin)->postJson('/api/v1/admin/products', $this->payload())->json('data');
        $this->deleteJson("/api/v1/admin/products/{$created['public_id']}")->assertNoContent();
        $this->assertSoftDeleted('products', ['public_id' => $created['public_id']]);
        $this->assertDatabaseMissing('product_variants', ['product_id' => Product::withTrashed()->where('public_id', $created['public_id'])->value('id'), 'deleted_at' => null]);
    }

    public function test_unauthenticated_product_access_is_rejected(): void
    {
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/admin/products')->assertUnauthorized();
        $this->postJson('/api/v1/admin/products', $this->payload())->assertUnauthorized();
    }

    public function test_invalid_category_and_duplicate_slug_are_rejected(): void
    {
        $invalid = $this->payload();
        $invalid['categories'][0]['category_public_id'] = '01INVALIDPUBLICID0000000000';
        $this->actingAs($this->admin)->postJson('/api/v1/admin/products', $invalid)->assertUnprocessable()->assertJsonPath('meta.errors.0.field', 'categories.0.category_public_id');
        Product::factory()->create(['slug' => 'luna-dress']);
        $this->postJson('/api/v1/admin/products', $this->payload())->assertUnprocessable()->assertJsonPath('meta.errors.0.field', 'slug');
    }

    public function test_duplicate_sku_is_rejected(): void
    {
        ProductVariant::factory()->create(['sku' => 'LUNA-CREAM']);
        $this->actingAs($this->admin)->postJson('/api/v1/admin/products', $this->payload())->assertConflict()->assertJsonPath('meta.errors.0.code', 'sku_conflict');
    }

    public function test_duplicate_option_combination_is_rejected(): void
    {
        $payload = $this->payload();
        $payload['variants'][1]['option_values'] = $payload['variants'][0]['option_values'];
        $this->actingAs($this->admin)->postJson('/api/v1/admin/products', $payload)->assertUnprocessable()->assertJsonPath('meta.errors.0.field', 'variants.1.option_values');
    }

    public function test_default_variant_rules_are_enforced(): void
    {
        $missing = $this->payload();
        $missing['variants'][0]['is_default'] = false;
        $this->actingAs($this->admin)->postJson('/api/v1/admin/products', $missing)->assertUnprocessable();
        $multiple = $this->payload();
        $multiple['variants'][1]['is_default'] = true;
        $this->postJson('/api/v1/admin/products', $multiple)->assertUnprocessable();
        $inactive = $this->payload();
        $inactive['variants'][0]['is_active'] = false;
        $this->postJson('/api/v1/admin/products', $inactive)->assertUnprocessable();
    }

    public function test_invalid_compare_at_price_is_rejected(): void
    {
        $payload = $this->payload();
        $payload['variants'][0]['compare_at_amount'] = $payload['variants'][0]['price_amount'];
        $this->actingAs($this->admin)->postJson('/api/v1/admin/products', $payload)->assertUnprocessable()->assertJsonPath('meta.errors.0.field', 'variants.0.compare_at_amount');
    }

    public function test_cross_product_variant_id_is_rejected_on_update(): void
    {
        $one = $this->actingAs($this->admin)->postJson('/api/v1/admin/products', $this->payload())->json('data');
        $otherPayload = $this->payload();
        $otherPayload['slug'] = 'other';
        foreach ($otherPayload['variants'] as $i => &$variant) {
            $variant['sku'] = 'OTHER-'.$i;
        }
        $two = $this->postJson('/api/v1/admin/products', $otherPayload)->json('data');
        $update = $this->payload();
        $update['variants'][0]['public_id'] = $two['variants'][0]['public_id'];
        $update['variants'][1]['public_id'] = $one['variants'][1]['public_id'];
        $this->putJson("/api/v1/admin/products/{$one['public_id']}", $update)->assertConflict()->assertJsonPath('meta.errors.0.code', 'cross_product_option_values');
    }

    public function test_admin_can_add_an_option_value(): void
    {
        $product = $this->actingAs($this->admin)->postJson('/api/v1/admin/products', $this->payload())->json('data');

        $this->postJson("/api/v1/admin/products/{$product['public_id']}/options/color/values", [
            'label' => 'Navy', 'code' => 'navy', 'swatch_value' => '#000080', 'sort_order' => 2,
        ])->assertCreated()->assertJsonPath('data.code', 'navy')->assertJsonMissingPath('data.id');

        $this->assertDatabaseHas('product_option_values', ['code' => 'navy', 'swatch_value' => '#000080']);
    }

    public function test_admin_can_create_an_option_for_a_product_without_variants(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin)->postJson("/api/v1/admin/products/{$product->public_id}/options", [
            'name' => 'Size', 'code' => 'size', 'sort_order' => 1,
            'values' => [
                ['label' => 'S', 'code' => 's', 'swatch_value' => null, 'sort_order' => 0],
                ['label' => 'M', 'code' => 'm', 'swatch_value' => null, 'sort_order' => 1],
            ],
        ])->assertCreated()->assertJsonPath('data.code', 'size')->assertJsonCount(2, 'data.values')->assertJsonMissingPath('data.id');

        $this->assertDatabaseHas('product_options', ['product_id' => $product->id, 'code' => 'size']);
        $this->assertDatabaseHas('product_option_values', ['code' => 'm']);
    }

    public function test_adding_a_new_option_when_variants_exist_requires_regeneration(): void
    {
        $product = $this->actingAs($this->admin)->postJson('/api/v1/admin/products', $this->payload())->json('data');

        $this->postJson("/api/v1/admin/products/{$product['public_id']}/options", [
            'name' => 'Size', 'code' => 'size', 'sort_order' => 1,
            'values' => [['label' => 'S', 'code' => 's', 'swatch_value' => null, 'sort_order' => 0]],
        ])->assertConflict()->assertJsonPath('meta.errors.0.code', 'variants_require_regeneration');
    }

    public function test_generate_creates_only_missing_variants_and_does_not_duplicate_existing_combinations(): void
    {
        $product = $this->actingAs($this->admin)->postJson('/api/v1/admin/products', $this->payload())->json('data');
        $this->postJson("/api/v1/admin/products/{$product['public_id']}/options/color/values", [
            'label' => 'Navy', 'code' => 'navy', 'swatch_value' => null, 'sort_order' => 2,
        ])->assertCreated();
        $body = [
            'option_values' => ['color' => ['cream', 'black', 'navy']],
            'defaults' => ['price_amount' => 399000, 'compare_at_amount' => null, 'currency' => 'IDR', 'weight_grams' => 400, 'is_active' => true],
            'sku_template' => 'LUNA-{color}',
        ];

        $this->postJson("/api/v1/admin/products/{$product['public_id']}/variants/generate", $body)
            ->assertCreated()->assertJsonCount(1, 'data.created')->assertJsonCount(2, 'data.existing');
        $this->postJson("/api/v1/admin/products/{$product['public_id']}/variants/generate", $body)
            ->assertOk()->assertJsonCount(0, 'data.created')->assertJsonCount(3, 'data.existing');
        $this->assertDatabaseCount('product_variants', 3);
    }

    public function test_generation_rejects_duplicate_sku_and_rolls_back(): void
    {
        $product = $this->actingAs($this->admin)->postJson('/api/v1/admin/products', $this->payload())->json('data');
        $this->postJson("/api/v1/admin/products/{$product['public_id']}/options/color/values", ['label' => 'Navy', 'code' => 'navy', 'sort_order' => 2, 'swatch_value' => null]);

        $this->postJson("/api/v1/admin/products/{$product['public_id']}/variants/generate", [
            'option_values' => ['color' => ['navy']],
            'defaults' => ['price_amount' => 1, 'compare_at_amount' => null, 'currency' => 'IDR', 'weight_grams' => null, 'is_active' => false],
            'sku_template' => 'LUNA-CREAM',
        ])->assertConflict()->assertJsonPath('meta.errors.0.code', 'sku_conflict');
        $this->assertDatabaseCount('product_variants', 2);
    }

    public function test_admin_can_edit_variant_and_changing_options_recomputes_combination(): void
    {
        $product = $this->actingAs($this->admin)->postJson('/api/v1/admin/products', $this->payload())->json('data');
        $this->postJson("/api/v1/admin/products/{$product['public_id']}/options/color/values", ['label' => 'Navy', 'code' => 'navy', 'sort_order' => 2, 'swatch_value' => null]);
        $variant = $product['variants'][1];
        $oldKey = ProductVariant::where('public_id', $variant['public_id'])->value('combination_key');

        $this->putJson("/api/v1/admin/products/{$product['public_id']}/variants/{$variant['public_id']}", [
            'sku' => 'LUNA-NAVY', 'title' => 'Navy special', 'option_values' => ['color' => 'navy'],
            'price_amount' => 419000, 'compare_at_amount' => 459000, 'currency' => 'IDR',
            'barcode' => 'NAVY001', 'weight_grams' => 410, 'is_active' => true,
        ])->assertOk()->assertJsonPath('data.option_values.color', 'navy')->assertJsonPath('data.title', 'Navy special');
        $this->assertNotSame($oldKey, ProductVariant::where('public_id', $variant['public_id'])->value('combination_key'));
    }

    public function test_duplicate_combination_and_cross_product_value_are_rejected(): void
    {
        $product = $this->actingAs($this->admin)->postJson('/api/v1/admin/products', $this->payload())->json('data');
        $variant = $product['variants'][1];

        $this->putJson("/api/v1/admin/products/{$product['public_id']}/variants/{$variant['public_id']}", ['option_values' => ['color' => 'cream']])
            ->assertConflict()->assertJsonPath('meta.errors.0.code', 'variant_combination_conflict');
        $this->putJson("/api/v1/admin/products/{$product['public_id']}/variants/{$variant['public_id']}", ['option_values' => ['color' => 'value-from-another-product']])
            ->assertConflict()->assertJsonPath('meta.errors.0.code', 'variant_combination_conflict');
    }

    public function test_default_variant_rules_are_enforced_by_focused_endpoints(): void
    {
        $product = $this->actingAs($this->admin)->postJson('/api/v1/admin/products', $this->payload())->json('data');
        [$default, $replacement] = $product['variants'];

        $this->putJson("/api/v1/admin/products/{$product['public_id']}/variants/{$default['public_id']}", ['is_active' => false])
            ->assertConflict()->assertJsonPath('meta.errors.0.code', 'default_variant_cannot_be_disabled');
        $this->putJson("/api/v1/admin/products/{$product['public_id']}/variants/{$replacement['public_id']}", ['is_active' => false])->assertOk();
        $this->putJson("/api/v1/admin/products/{$product['public_id']}/variants/{$replacement['public_id']}/default")
            ->assertConflict()->assertJsonPath('meta.errors.0.code', 'inactive_variant_cannot_be_default');
        $this->putJson("/api/v1/admin/products/{$product['public_id']}/variants/{$replacement['public_id']}", ['is_active' => true])->assertOk();
        $this->putJson("/api/v1/admin/products/{$product['public_id']}/variants/{$replacement['public_id']}/default")
            ->assertOk()->assertJsonPath('data.is_default', true);
        $this->assertDatabaseHas('product_variants', ['public_id' => $default['public_id'], 'is_default' => false]);
        $this->assertDatabaseHas('product_variants', ['public_id' => $replacement['public_id'], 'is_default' => true]);
    }

    public function test_focused_variant_endpoints_require_authentication(): void
    {
        $product = $this->actingAs($this->admin)->postJson('/api/v1/admin/products', $this->payload())->json('data');
        $this->app['auth']->forgetGuards();

        $this->postJson("/api/v1/admin/products/{$product['public_id']}/variants/generate", [])->assertUnauthorized();
        $this->putJson("/api/v1/admin/products/{$product['public_id']}/variants/{$product['variants'][0]['public_id']}", [])->assertUnauthorized();
    }

    private function payload(): array
    {
        return ['name' => 'Luna Dress', 'slug' => 'luna-dress', 'short_description' => 'Linen dress', 'description' => 'A dress.', 'brand' => 'Noure', 'status' => 'active', 'published_at' => now()->toISOString(), 'metadata' => null,
            'categories' => [['category_public_id' => $this->category->public_id, 'is_primary' => true, 'sort_order' => 0]], 'images' => [],
            'options' => [['name' => 'Color', 'code' => 'color', 'sort_order' => 0, 'values' => [['label' => 'Cream', 'code' => 'cream', 'swatch_value' => '#fff', 'sort_order' => 0], ['label' => 'Black', 'code' => 'black', 'swatch_value' => '#000', 'sort_order' => 1]]]],
            'variants' => [['sku' => 'LUNA-CREAM', 'title' => 'Cream', 'option_values' => ['color' => 'cream'], 'price_amount' => 399000, 'compare_at_amount' => 449000, 'currency' => 'IDR', 'barcode' => null, 'weight_grams' => 400, 'is_active' => true, 'is_default' => true], ['sku' => 'LUNA-BLACK', 'title' => 'Black', 'option_values' => ['color' => 'black'], 'price_amount' => 409000, 'compare_at_amount' => null, 'currency' => 'IDR', 'barcode' => null, 'weight_grams' => 400, 'is_active' => true, 'is_default' => false]]];
    }
}
