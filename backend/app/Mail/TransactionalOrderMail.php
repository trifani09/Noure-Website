<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransactionalOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Order $order, public readonly string $messageType, public readonly ?string $paymentStatus = null, public readonly ?string $paymentProvider = null, public readonly ?string $paymentMethod = null, public readonly ?int $paidAmount = null, public readonly ?string $paidAt = null) {}

    public function envelope(): Envelope
    {
        $subjects = [
            'order_created' => 'Your Noure order '.$this->order->order_number,
            'payment_paid' => 'Payment received for '.$this->order->order_number,
            'payment_failed' => 'Payment update for '.$this->order->order_number,
            'payment_expired' => 'Payment expired for '.$this->order->order_number,
            'status_processing' => 'Your Noure order is being prepared',
            'status_shipped' => 'Your Noure order has shipped',
            'status_completed' => 'Your Noure order is complete',
            'status_cancelled' => 'Your Noure order was cancelled',
        ];

        return new Envelope(subject: $subjects[$this->messageType] ?? 'Update for '.$this->order->order_number);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.transactional.order', with: [
            'order' => $this->order,
            'messageType' => $this->messageType,
            'paymentStatus' => $this->paymentStatus,
            'paymentProvider' => $this->paymentProvider,
            'paymentMethod' => $this->paymentMethod,
            'paidAmount' => $this->paidAmount,
            'paidAt' => $this->paidAt,
            'storefrontUrl' => rtrim((string) config('services.midtrans.storefront_url'), '/'),
        ]);
    }
}