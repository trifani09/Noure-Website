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

    private function payload(): array
    {
        return ['name' => 'Luna Dress', 'slug' => 'luna-dress', 'short_description' => 'Linen dress', 'description' => 'A dress.', 'brand' => 'Noure', 'status' => 'active', 'published_at' => now()->toISOString(), 'metadata' => null,
            'categories' => [['category_public_id' => $this->category->public_id, 'is_primary' => true, 'sort_order' => 0]], 'images' => [],
            'options' => [['name' => 'Color', 'code' => 'color', 'sort_order' => 0, 'values' => [['label' => 'Cream', 'code' => 'cream', 'swatch_value' => '#fff', 'sort_order' => 0], ['label' => 'Black', 'code' => 'black', 'swatch_value' => '#000', 'sort_order' => 1]]]],
            'variants' => [['sku' => 'LUNA-CREAM', 'title' => 'Cream', 'option_values' => ['color' => 'cream'], 'price_amount' => 399000, 'compare_at_amount' => 449000, 'currency' => 'IDR', 'barcode' => null, 'weight_grams' => 400, 'is_active' => true, 'is_default' => true], ['sku' => 'LUNA-BLACK', 'title' => 'Black', 'option_values' => ['color' => 'black'], 'price_amount' => 409000, 'compare_at_amount' => null, 'currency' => 'IDR', 'barcode' => null, 'weight_grams' => 400, 'is_active' => true, 'is_default' => false]]];
    }
}
