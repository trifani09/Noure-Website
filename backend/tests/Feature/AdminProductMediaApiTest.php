<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProductMediaApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Product $product;

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->admin = User::factory()->create();
        $this->product = Product::factory()->create(['slug' => 'luna-dress']);
        $this->variant = ProductVariant::factory()->for($this->product)->create();
    }

    public function test_admin_can_upload_and_list_an_image_with_metadata(): void
    {
        $response = $this->actingAs($this->admin)->postJson("/api/v1/admin/products/{$this->product->public_id}/images", [
            'image' => UploadedFile::fake()->image('front.png', 800, 1000), 'alt_text' => 'Luna dress front', 'sort_order' => 4,
        ])->assertCreated()->assertJsonPath('data.alt_text', 'Luna dress front')->assertJsonPath('data.width', 800)
            ->assertJsonPath('data.height', 1000)->assertJsonPath('data.mime_type', 'image/png')->assertJsonPath('data.is_primary', true)
            ->assertJsonMissingPath('data.id')->assertJsonMissingPath('data.path');

        $image = ProductImage::where('public_id', $response->json('data.public_id'))->firstOrFail();
        Storage::disk('public')->assertExists($image->path);
        $this->getJson("/api/v1/admin/products/{$this->product->public_id}/images")->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_setting_primary_clears_the_previous_primary_in_the_same_scope(): void
    {
        $first = ProductImage::factory()->for($this->product)->create(['variant_id' => null, 'is_primary' => true, 'sort_order' => 0]);
        $second = ProductImage::factory()->for($this->product)->create(['variant_id' => null, 'is_primary' => false, 'sort_order' => 1]);

        $this->actingAs($this->admin)->putJson("/api/v1/admin/products/{$this->product->public_id}/images/{$second->public_id}", ['is_primary' => true])->assertOk()->assertJsonPath('data.is_primary', true);
        $this->assertDatabaseHas('product_images', ['id' => $first->id, 'is_primary' => false]);
        $this->assertDatabaseHas('product_images', ['id' => $second->id, 'is_primary' => true]);
    }

    public function test_image_can_be_assigned_to_a_variant_of_the_same_product(): void
    {
        $image = ProductImage::factory()->for($this->product)->create(['variant_id' => null]);

        $this->actingAs($this->admin)->putJson("/api/v1/admin/products/{$this->product->public_id}/images/{$image->public_id}", ['variant_public_id' => $this->variant->public_id])
            ->assertOk()->assertJsonPath('data.variant_public_id', $this->variant->public_id);
        $this->assertDatabaseHas('product_images', ['id' => $image->id, 'variant_id' => $this->variant->id]);
    }

    public function test_cross_product_variant_assignment_is_rejected(): void
    {
        $image = ProductImage::factory()->for($this->product)->create();
        $otherVariant = ProductVariant::factory()->create();

        $this->actingAs($this->admin)->putJson("/api/v1/admin/products/{$this->product->public_id}/images/{$image->public_id}", ['variant_public_id' => $otherVariant->public_id])
            ->assertConflict()->assertJsonPath('meta.errors.0.code', 'variant_not_found');
    }

    public function test_image_metadata_and_order_can_be_updated(): void
    {
        $image = ProductImage::factory()->for($this->product)->create(['alt_text' => null, 'sort_order' => 5]);

        $this->actingAs($this->admin)->putJson("/api/v1/admin/products/{$this->product->public_id}/images/{$image->public_id}", ['alt_text' => 'Updated alt', 'sort_order' => 0])
            ->assertOk()->assertJsonPath('data.alt_text', 'Updated alt')->assertJsonPath('data.sort_order', 0);
    }

    public function test_deleting_primary_deletes_file_and_promotes_next_image(): void
    {
        Storage::disk('public')->put('products/luna-dress/first.png', 'first');
        $first = ProductImage::factory()->for($this->product)->create(['path' => 'products/luna-dress/first.png', 'is_primary' => true, 'sort_order' => 0]);
        $second = ProductImage::factory()->for($this->product)->create(['is_primary' => false, 'sort_order' => 1]);

        $this->actingAs($this->admin)->deleteJson("/api/v1/admin/products/{$this->product->public_id}/images/{$first->public_id}")->assertNoContent();
        Storage::disk('public')->assertMissing('products/luna-dress/first.png');
        $this->assertDatabaseMissing('product_images', ['id' => $first->id]);
        $this->assertDatabaseHas('product_images', ['id' => $second->id, 'is_primary' => true]);
    }

    public function test_upload_validation_rejects_non_images(): void
    {
        $this->actingAs($this->admin)->postJson("/api/v1/admin/products/{$this->product->public_id}/images", [
            'image' => UploadedFile::fake()->create('payload.php', 10, 'application/x-php'),
        ])->assertUnprocessable()->assertJsonPath('meta.errors.0.field', 'image');
    }

    public function test_media_endpoints_require_authentication(): void
    {
        $image = ProductImage::factory()->for($this->product)->create();
        $this->getJson("/api/v1/admin/products/{$this->product->public_id}/images")->assertUnauthorized();
        $this->postJson("/api/v1/admin/products/{$this->product->public_id}/images", [])->assertUnauthorized();
        $this->putJson("/api/v1/admin/products/{$this->product->public_id}/images/{$image->public_id}", [])->assertUnauthorized();
        $this->deleteJson("/api/v1/admin/products/{$this->product->public_id}/images/{$image->public_id}")->assertUnauthorized();
    }
}
