<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoryApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

    public function test_admin_can_list_and_filter_categories(): void
    {
        $parent = Category::factory()->create(['name' => 'Dresses', 'sort_order' => 1]);
        Category::factory()->create(['parent_id' => $parent->id, 'name' => 'Midi Dresses', 'sort_order' => 2]);
        Category::factory()->create(['parent_id' => $parent->id, 'name' => 'Maxi Dresses', 'sort_order' => 1, 'is_active' => false]);

        $response = $this->actingAs($this->admin)->getJson(
            "/api/v1/admin/categories?parent={$parent->public_id}&search=Dresses&is_active=true&sort=position&per_page=10"
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Midi Dresses')
            ->assertJsonPath('data.0.parent.public_id', $parent->public_id)
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonMissingPath('data.0.id');
    }

    public function test_admin_can_create_a_category_with_an_automatic_slug(): void
    {
        $parent = Category::factory()->create();

        $response = $this->actingAs($this->admin)->postJson('/api/v1/admin/categories', [
            'parent_public_id' => $parent->public_id,
            'name' => 'Evening Dresses',
            'description' => 'Elegant occasionwear.',
            'image_path' => 'categories/evening-dresses.jpg',
            'sort_order' => 4,
            'is_active' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.slug', 'evening-dresses')
            ->assertJsonPath('data.parent.public_id', $parent->public_id)
            ->assertJsonMissingPath('data.id');
        $this->assertDatabaseHas('categories', ['name' => 'Evening Dresses', 'slug' => 'evening-dresses']);
    }

    public function test_admin_can_update_a_category(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin)->putJson("/api/v1/admin/categories/{$category->public_id}", [
            'name' => 'Updated Category',
            'slug' => 'updated-category',
            'is_active' => false,
        ])->assertOk()
            ->assertJsonPath('data.name', 'Updated Category')
            ->assertJsonPath('data.is_active', false);
    }

    public function test_admin_can_soft_delete_a_category(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/admin/categories/{$category->public_id}")
            ->assertNoContent();

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/v1/admin/categories')->assertUnauthorized();
        $this->postJson('/api/v1/admin/categories', ['name' => 'Dresses'])->assertUnauthorized();
    }

    public function test_create_validation_failures_use_the_documented_envelope(): void
    {
        $this->actingAs($this->admin)->postJson('/api/v1/admin/categories', [
            'name' => '',
            'sort_order' => -1,
        ])->assertUnprocessable()
            ->assertJsonPath('data', null)
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonStructure(['meta' => ['errors' => [['code', 'field', 'message']]]]);
    }

    public function test_duplicate_slug_is_rejected(): void
    {
        Category::factory()->create(['slug' => 'dresses']);

        $this->actingAs($this->admin)->postJson('/api/v1/admin/categories', [
            'name' => 'More Dresses',
            'slug' => 'dresses',
        ])->assertUnprocessable()
            ->assertJsonPath('meta.errors.0.field', 'slug');
    }

    public function test_invalid_parent_is_rejected(): void
    {
        $this->actingAs($this->admin)->postJson('/api/v1/admin/categories', [
            'name' => 'Dresses',
            'parent_public_id' => '01INVALIDPUBLICID0000000000',
        ])->assertUnprocessable()
            ->assertJsonPath('meta.errors.0.field', 'parent_public_id');
    }

    public function test_category_with_children_cannot_be_deleted(): void
    {
        $parent = Category::factory()->create();
        Category::factory()->create(['parent_id' => $parent->id]);

        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/admin/categories/{$parent->public_id}")
            ->assertConflict()
            ->assertJsonPath('meta.errors.0.code', 'category_has_children');
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create();
        $product->categories()->attach($category->id, ['is_primary' => true, 'sort_order' => 0]);

        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/admin/categories/{$category->public_id}")
            ->assertConflict()
            ->assertJsonPath('meta.errors.0.code', 'category_has_products');
    }
}
