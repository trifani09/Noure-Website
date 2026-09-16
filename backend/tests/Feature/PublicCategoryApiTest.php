<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PublicCategoryApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_category_listing_supports_filters_counts_sorting_and_pagination(): void
    {
        $root = Category::factory()->create(['name' => 'Women', 'slug' => 'women']);
        $dresses = Category::factory()->for($root, 'parent')->create(['name' => 'Dresses', 'slug' => 'dresses', 'sort_order' => 2]);
        Category::factory()->for($root, 'parent')->create(['name' => 'Accessories', 'slug' => 'accessories', 'sort_order' => 1]);
        $product = Product::factory()->create();
        ProductVariant::factory()->for($product)->create(['is_active' => true, 'is_default' => true]);
        $product->categories()->attach($dresses, ['is_primary' => true, 'sort_order' => 0]);

        $this->getJson('/api/v1/categories?parent='.$root->public_id.'&search=Dress&sort=name_desc&include_product_count=true&per_page=1')
            ->assertOk()
            ->assertJsonPath('data.0.public_id', $dresses->public_id)
            ->assertJsonPath('data.0.parent.public_id', $root->public_id)
            ->assertJsonPath('data.0.product_count', 1)
            ->assertJsonPath('data.0.children_count', 0)
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('meta.pagination.per_page', 1)
            ->assertJsonPath('meta.pagination.current_page', 1)
            ->assertJsonPath('meta.pagination.last_page', 1);
    }

    public function test_category_detail_contains_ancestors_and_direct_children(): void
    {
        $root = Category::factory()->create(['slug' => 'women']);
        $parent = Category::factory()->for($root, 'parent')->create(['slug' => 'clothing']);
        $category = Category::factory()->for($parent, 'parent')->create(['slug' => 'dresses']);
        $child = Category::factory()->for($category, 'parent')->create(['slug' => 'midi']);
        Category::factory()->for($category, 'parent')->create(['slug' => 'hidden', 'is_active' => false]);

        $this->getJson('/api/v1/categories/dresses')
            ->assertOk()
            ->assertJsonPath('data.public_id', $category->public_id)
            ->assertJsonPath('data.ancestors.0.public_id', $root->public_id)
            ->assertJsonPath('data.ancestors.1.public_id', $parent->public_id)
            ->assertJsonPath('data.children.0.public_id', $child->public_id)
            ->assertJsonCount(1, 'data.children');
    }

    public function test_inactive_category_is_hidden(): void
    {
        Category::factory()->create(['slug' => 'hidden', 'is_active' => false]);

        $this->getJson('/api/v1/categories')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/categories/hidden')->assertNotFound()->assertJsonPath('meta.errors.0.code', 'category_not_found');
    }

    public function test_soft_deleted_category_is_hidden(): void
    {
        $category = Category::factory()->create(['slug' => 'deleted']);
        $category->delete();

        $this->getJson('/api/v1/categories')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/categories/deleted')->assertNotFound()->assertJsonPath('meta.errors.0.code', 'category_not_found');
    }

    public function test_unknown_parent_returns_category_not_found(): void
    {
        $this->getJson('/api/v1/categories?parent=01K5B2T8GSX9A6GJ3P4CMQ1R7V')
            ->assertNotFound()->assertJsonPath('meta.errors.0.code', 'category_not_found');
    }

    public function test_invalid_category_query_returns_documented_validation_envelope(): void
    {
        $this->getJson('/api/v1/categories?per_page=101&sort=unknown')
            ->assertUnprocessable()
            ->assertJsonPath('data', null)
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonStructure(['meta' => ['errors' => [['code', 'field', 'message']]]]);
    }

    public function test_category_payload_does_not_expose_numeric_ids(): void
    {
        Category::factory()->create(['slug' => 'dresses']);
        $data = $this->getJson('/api/v1/categories/dresses')->assertOk()->json('data');

        $this->assertArrayNotHasKey('id', $data);
        $this->assertArrayNotHasKey('parent_id', $data);
    }
}
