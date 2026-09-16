<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Banner;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\DiscountRedemption;
use App\Models\InventoryLevel;
use App\Models\InventoryLocation;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CommerceSchemaTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_product_variant_represents_a_unique_option_combination(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create();
        $product->categories()->attach($category, ['is_primary' => true, 'sort_order' => 0]);
        $color = ProductOption::factory()->for($product)->create(['name' => 'Color', 'code' => 'color']);
        $size = ProductOption::factory()->for($product)->create(['name' => 'Size', 'code' => 'size', 'sort_order' => 1]);
        $cream = ProductOptionValue::factory()->for($color, 'option')->create(['label' => 'Cream', 'code' => 'cream']);
        $small = ProductOptionValue::factory()->for($size, 'option')->create(['label' => 'S', 'code' => 's']);
        $variant = ProductVariant::factory()->for($product)->create([
            'title' => 'Cream / S',
            'combination_key' => $cream->id.':'.$small->id,
        ]);
        $variant->optionValues()->attach([$cream->id, $small->id]);

        $variant->load(['product', 'optionValues.option', 'product.categories']);

        $this->assertSame($product->id, $variant->product->id);
        $this->assertSame(['Cream', 'S'], $variant->optionValues->pluck('label')->all());
        $this->assertSame(1, $variant->product->categories->first()->pivot->is_primary);
        $this->assertTrue(Str::isUlid($variant->public_id));
    }

    public function test_inventory_and_transaction_snapshots_are_cast_and_related(): void
    {
        $level = InventoryLevel::factory()
            ->for(InventoryLocation::factory(), 'location')
            ->create(['on_hand' => 20, 'reserved' => 3, 'safety_stock' => 2]);
        $order = Order::factory()->create();
        $item = OrderItem::factory()->for($order)->create();
        $payment = Payment::factory()->for($order)->create(['metadata' => ['source' => 'test']]);
        $transaction = PaymentTransaction::factory()->for($payment)->create(['response_metadata' => ['safe' => true]]);
        $banner = Banner::factory()->create(['metadata' => ['theme' => 'light']]);

        $this->assertSame(15, $level->available);
        $this->assertSame($order->id, $item->order->id);
        $this->assertSame($order->id, $payment->order->id);
        $this->assertSame($payment->id, $transaction->payment->id);
        $this->assertSame(['source' => 'test'], $payment->metadata);
        $this->assertSame(['safe' => true], $transaction->response_metadata);
        $this->assertSame(['theme' => 'light'], $banner->metadata);
        $this->assertTrue(Str::isUlid($order->public_id));
    }

    public function test_soft_deleted_product_slug_can_be_reused(): void
    {
        $product = Product::factory()->create(['slug' => 'linen-shirt']);
        $product->delete();

        $replacement = Product::factory()->create(['slug' => 'linen-shirt']);

        $this->assertModelExists($replacement);
        $this->assertSame('linen-shirt', $replacement->slug);
    }

    public function test_supporting_factories_create_valid_related_records(): void
    {
        $models = [
            Address::factory()->create(),
            CartItem::factory()->create(),
            DiscountRedemption::factory()->create(),
            InventoryMovement::factory()->create(),
            ProductImage::factory()->create(),
        ];

        foreach ($models as $model) {
            $this->assertModelExists($model);
        }
    }
}
