<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\InventoryLevel;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StoreSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $settings = StoreSetting::query()->first();
        $threshold = $settings?->low_stock_threshold ?? 5;
        $currency = $settings?->default_currency ?? 'IDR';
        $paidOrders = Order::query()->where('payment_status', 'paid');
        $statusCounts = Order::query()->select('status', DB::raw('COUNT(*) as aggregate'))->groupBy('status')->pluck('aggregate', 'status')->map(fn ($count) => (int) $count);
        $stock = InventoryLevel::query()->selectRaw('SUM(CASE WHEN GREATEST(on_hand - reserved - safety_stock, 0) = 0 THEN 1 ELSE 0 END) as out_of_stock')->selectRaw('SUM(CASE WHEN GREATEST(on_hand - reserved - safety_stock, 0) BETWEEN 1 AND ? THEN 1 ELSE 0 END) as low_stock', [$threshold])->first();
        $bestSellers = OrderItem::query()->join('orders', 'orders.id', '=', 'order_items.order_id')->where('orders.payment_status', 'paid')->select('order_items.product_name', 'order_items.sku')->selectRaw('SUM(order_items.quantity) as quantity_sold')->selectRaw('SUM(order_items.total_amount) as revenue')->groupBy('order_items.product_name', 'order_items.sku')->orderByDesc('quantity_sold')->limit(5)->get()->map(fn (OrderItem $item) => ['product_name' => $item->product_name, 'sku' => $item->sku, 'quantity_sold' => (int) $item->quantity_sold, 'revenue' => (int) $item->revenue]);
        $movements = InventoryMovement::query()->with(['inventoryLevel.variant.product', 'inventoryLevel.location', 'actor'])->latest()->limit(8)->get()->map(fn (InventoryMovement $movement) => ['id' => $movement->id, 'product' => $movement->inventoryLevel->variant->product->name, 'variant' => $movement->inventoryLevel->variant->title, 'sku' => $movement->inventoryLevel->variant->sku, 'location' => $movement->inventoryLevel->location->name, 'quantity_delta' => $movement->quantity_delta, 'movement_type' => $movement->movement_type, 'reason' => $movement->reason, 'actor' => $movement->actor?->name, 'created_at' => $movement->created_at?->utc()->toISOString()]);

        return response()->json(['data' => ['currency' => $currency, 'sales' => ['today' => (int) (clone $paidOrders)->whereDate('placed_at', today())->sum('grand_total_amount'), 'month' => (int) (clone $paidOrders)->whereBetween('placed_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('grand_total_amount')], 'orders_by_status' => $statusCounts, 'pending_payment' => Order::query()->whereIn('payment_status', ['unpaid', 'pending'])->count(), 'inventory' => ['low_stock' => (int) ($stock->low_stock ?? 0), 'out_of_stock' => (int) ($stock->out_of_stock ?? 0), 'threshold' => $threshold], 'new_customers' => ['today' => Customer::query()->whereDate('created_at', today())->count(), 'month' => Customer::query()->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count()], 'best_sellers' => $bestSellers, 'recent_inventory_movements' => $movements], 'meta' => (object) [], 'message' => null]);
    }
}
