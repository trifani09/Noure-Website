<?php

namespace App\Orders;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminOrderQuery
{
    /** @param array<string, mixed> $filters */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Order::query()->with('customer');
        if (isset($filters['search'])) {
            $query->where('order_number', 'like', '%'.addcslashes(trim($filters['search']), '\\%_').'%');
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }
        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        match ($filters['sort'] ?? 'newest') {
            'oldest' => $query->orderBy('created_at')->orderBy('id'),
            'total_asc' => $query->orderBy('grand_total_amount')->orderBy('id'),
            'total_desc' => $query->orderByDesc('grand_total_amount')->orderByDesc('id'),
            'order_asc' => $query->orderBy('order_number')->orderBy('id'),
            'order_desc' => $query->orderByDesc('order_number')->orderByDesc('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function find(string $publicId): ?Order
    {
        return Order::query()->where('public_id', $publicId)->with(['customer', 'items', 'payments.transactions'])->first();
    }
}
