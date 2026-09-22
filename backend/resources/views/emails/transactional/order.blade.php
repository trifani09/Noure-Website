@extends('emails.layout', ['title' => $order->order_number])

@section('content')
    @php
        $name = $order->customer?->first_name ?: $order->shipping_address['recipient_name'] ?? 'there';
        $orderUrl = $storefrontUrl . '/payment/' . $order->public_id;
        $money = fn($amount) => number_format((int) $amount, 0, ',', '.') . ' ' . $order->currency;
        $headings = [
            'order_created' => 'Thank you for your order.',
            'payment_paid' => 'Payment received.',
            'payment_failed' => 'Payment was not completed.',
            'payment_expired' => 'Payment has expired.',
            'status_processing' => 'Your order is being prepared.',
            'status_shipped' => 'Your order has shipped.',
            'status_completed' => 'Your order is complete.',
            'status_cancelled' => 'Your order was cancelled.',
        ];
    @endphp
    <p style="margin:0 0 8px;color:#72525f;font-size:11px;font-weight:bold;letter-spacing:2px;text-transform:uppercase;">
        Noure order update</p>
    <h1
        style="margin:0 0 18px;font-family:Georgia,'Times New Roman',serif;font-size:32px;font-weight:normal;line-height:1.2;">
        {{ $headings[$messageType] ?? 'Your order has been updated.' }}</h1>
    <p style="margin:0 0 28px;">Hello {{ $name }},<br>Here is the latest information for order
        <strong>{{ $order->order_number }}</strong>.</p>

    @if ($messageType === 'payment_paid')
        <p style="padding:16px;background:#f8f5ef;">Paid amount:
            <strong>{{ $money($paidAmount ?? $order->grand_total_amount) }}</strong><br>Method:
            {{ $paymentMethod ?? 'Online payment' }} via {{ $paymentProvider ?? 'payment provider' }}<br>Paid:
            {{ $paidAt ?? now()->toISOString() }}</p>
    @elseif (in_array($messageType, ['payment_failed', 'payment_expired'], true))
        <p style="padding:16px;background:#f8f5ef;">Current payment status:
            <strong>{{ $paymentStatus ?? str_replace('payment_', '', $messageType) }}</strong></p>
    @endif

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
        style="border-top:1px solid #e7dfd7;border-bottom:1px solid #e7dfd7;margin:24px 0;">
        @foreach ($order->items as $item)
            <tr>
                <td style="padding:14px 0;border-bottom:1px solid #e7dfd7;">
                    <strong>{{ $item->product_name }}</strong><br><span
                        style="color:#766d68;font-size:13px;">{{ $item->variant_name ?: 'Standard' }} · Qty
                        {{ $item->quantity }}</span></td>
                <td align="right" style="padding:14px 0;border-bottom:1px solid #e7dfd7;">
                    {{ $money($item->total_amount) }}</td>
            </tr>
        @endforeach
        <tr>
            <td style="padding-top:16px;">Subtotal</td>
            <td align="right" style="padding-top:16px;">{{ $money($order->subtotal_amount) }}</td>
        </tr>
        <tr>
            <td>Shipping</td>
            <td align="right">{{ $money($order->shipping_amount) }}</td>
        </tr>
        <tr>
            <td>Discount</td>
            <td align="right">-{{ $money($order->discount_amount) }}</td>
        </tr>
        <tr>
            <td>Tax</td>
            <td align="right">{{ $money($order->tax_amount) }}</td>
        </tr>
        <tr>
            <td style="padding:14px 0;font-weight:bold;">Grand total</td>
            <td align="right" style="padding:14px 0;font-weight:bold;">{{ $money($order->grand_total_amount) }}</td>
        </tr>
    </table>

    <p><strong>Shipping
            address</strong><br>{{ $order->shipping_address['recipient_name'] ?? '' }}<br>{{ $order->shipping_address['line1'] ?? '' }}<br>{{ $order->shipping_address['city'] ?? '' }}
        {{ $order->shipping_address['postal_code'] ?? '' }}<br>{{ $order->shipping_address['country_code'] ?? '' }}</p>
    <p>Order status: <strong>{{ $order->status }}</strong><br>Order date: {{ $order->placed_at?->toDateString() }}</p>

    @if (in_array($messageType, ['order_created', 'payment_failed', 'payment_expired'], true))
        <p style="margin-top:28px;"><a href="{{ $orderUrl }}"
                style="display:inline-block;background:#211c1a;color:#fffdf9;padding:14px 22px;text-decoration:none;font-size:12px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;">{{ $messageType === 'order_created' ? 'View payment' : 'Retry payment' }}</a>
        </p>
    @endif
@endsection
