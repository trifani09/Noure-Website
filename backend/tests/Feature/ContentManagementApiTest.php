<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\InventoryLevel;
use App\Models\InventoryLocation;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContentManagementApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->admin = User::factory()->create();
    }

    public function test_admin_can_create_update_list_and_delete_banner(): void
    {
        $created = $this->actingAs($this->admin)->post('/api/v1/admin/banners', $this->bannerPayload(), ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('data.name', 'Homepage hero')->json('data');
        $this->getJson('/api/v1/admin/banners')->assertOk()->assertJsonCount(1, 'data');
        $this->putJson("/api/v1/admin/banners/{$created['public_id']}", ['headline' => 'Updated headline', 'is_active' => false])
            ->assertOk()->assertJsonPath('data.headline', 'Updated headline')->assertJsonPath('data.is_active', false);
        $path = Banner::where('public_id', $created['public_id'])->value('desktop_image_path');
        Storage::disk('public')->assertExists($path);
        $this->deleteJson("/api/v1/admin/banners/{$created['public_id']}")->assertNoContent();
        $this->assertSoftDeleted('banners', ['public_id' => $created['public_id']]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_active_banner_is_returned_and_inactive_or_out_of_schedule_banners_are_hidden(): void
    {
        Banner::factory()->create(['headline' => 'Visible', 'is_active' => true, 'starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);
        Banner::factory()->create(['headline' => 'Inactive', 'is_active' => false]);
        Banner::factory()->create(['headline' => 'Future', 'is_active' => true, 'starts_at' => now()->addDay(), 'ends_at' => now()->addDays(2)]);
        Banner::factory()->create(['headline' => 'Expired', 'is_active' => true, 'starts_at' => now()->subDays(2), 'ends_at' => now()->subDay()]);

        $this->getJson('/api/v1/homepage')->assertOk()->assertJsonCount(1, 'data.hero_banners')
            ->assertJsonPath('data.hero_banners.0.headline', 'Visible')->assertJsonMissing(['headline' => 'Inactive'])->assertJsonMissing(['headline' => 'Future']);
    }

    public function test_homepage_returns_active_sections_and_public_catalog_references(): void
    {
        $category = Category::factory()->create(['is_active' => true]);
        $hiddenCategory = Category::factory()->create(['is_active' => false]);
        $product = Product::factory()->create(['status' => 'active', 'published_at' => now()->subHour()]);
        $variant = ProductVariant::factory()->for($product)->create(['is_active' => true, 'is_default' => true]);
        $location = InventoryLocation::factory()->create(['is_active' => true]);
        InventoryLevel::factory()->create(['variant_id' => $variant->id, 'location_id' => $location->id, 'on_hand' => 5, 'reserved' => 0, 'safety_stock' => 0]);
        $section = HomepageSection::factory()->create(['type' => 'featured_products', 'configuration' => ['heading' => 'Featured']]);
        $section->categories()->attach([$category->id => ['sort_order' => 0], $hiddenCategory->id => ['sort_order' => 1]]);
        $section->products()->attach($product->id, ['sort_order' => 0]);
        HomepageSection::factory()->create(['name' => 'Hidden section', 'is_active' => false]);

        $this->getJson('/api/v1/homepage')->assertOk()->assertJsonCount(1, 'data.sections')
            ->assertJsonPath('data.sections.0.products.0.public_id', $product->public_id)
            ->assertJsonPath('data.sections.0.products.0.available', true)->assertJsonCount(1, 'data.sections.0.categories')
            ->assertJsonMissing(['name' => 'Hidden section']);
    }

    public function test_admin_can_manage_structured_homepage_sections(): void
    {
        $category = Category::factory()->create();
        $payload = ['name' => 'Featured categories', 'type' => 'featured_categories', 'is_active' => true, 'sort_order' => 1,
            'configuration' => ['heading' => 'Shop categories'], 'category_public_ids' => [$category->public_id], 'product_public_ids' => []];
        $section = $this->actingAs($this->admin)->postJson('/api/v1/admin/homepage-sections', $payload)
            ->assertCreated()->assertJsonPath('data.categories.0.public_id', $category->public_id)->json('data');
        $payload['name'] = 'Updated categories';
        $this->putJson("/api/v1/admin/homepage-sections/{$section['public_id']}", $payload)->assertOk()->assertJsonPath('data.name', 'Updated categories');
        $this->deleteJson("/api/v1/admin/homepage-sections/{$section['public_id']}")->assertNoContent();
        $this->assertDatabaseMissing('homepage_sections', ['public_id' => $section['public_id']]);
    }

    public function test_banner_validation_rejects_bad_schedule_and_cta(): void
    {
        $payload = $this->bannerPayload();
        $payload['cta_url'] = 'javascript:alert(1)';
        $payload['starts_at'] = now()->addDay()->toISOString();
        $payload['ends_at'] = now()->toISOString();
        $this->actingAs($this->admin)->post('/api/v1/admin/banners', $payload, ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonFragment(['field' => 'cta_url'])->assertJsonFragment(['field' => 'ends_at']);
    }

    public function test_admin_content_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/admin/banners')->assertUnauthorized();
        $this->post('/api/v1/admin/banners', $this->bannerPayload(), ['Accept' => 'application/json'])->assertUnauthorized();
        $this->getJson('/api/v1/admin/homepage-sections')->assertUnauthorized();
    }

    private function bannerPayload(): array
    {
        return ['name' => 'Homepage hero', 'placement' => 'home_hero', 'headline' => 'New collection', 'subheading' => 'Explore Noure',
            'cta_label' => 'Shop now', 'cta_url' => '/products', 'desktop_image' => UploadedFile::fake()->image('hero.webp', 1600, 900),
            'alt_text' => 'Model wearing Noure', 'sort_order' => 0, 'is_active' => true, 'starts_at' => now()->subHour()->toISOString(), 'ends_at' => now()->addWeek()->toISOString()];
    }
}
