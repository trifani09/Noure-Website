<?php

namespace Tests\Feature;

use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class NewsletterSubscriptionApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_email_is_normalized_and_subscribed(): void
    {
        $this->postJson('/api/v1/newsletter/subscriptions', [
            'email' => '  NADIA@Example.COM ',
            'source' => 'homepage',
        ])->assertCreated()->assertJsonPath('data.subscribed', true);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'nadia@example.com',
            'status' => 'subscribed',
            'source' => 'homepage',
        ]);
    }

    public function test_duplicate_subscription_is_idempotent_and_reactivates(): void
    {
        NewsletterSubscriber::factory()->create([
            'email' => 'nadia@example.com',
            'status' => 'unsubscribed',
            'unsubscribed_at' => now()->subDay(),
        ]);

        $this->postJson('/api/v1/newsletter/subscriptions', [
            'email' => 'nadia@example.com',
            'source' => 'footer',
        ])->assertOk()->assertJsonPath('message', 'You are subscribed to Noure updates.');

        $this->assertDatabaseCount('newsletter_subscribers', 1);
        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'nadia@example.com',
            'status' => 'subscribed',
            'source' => 'footer',
            'unsubscribed_at' => null,
        ]);
    }

    public function test_invalid_email_and_source_are_rejected(): void
    {
        $this->postJson('/api/v1/newsletter/subscriptions', [
            'email' => 'not-an-email',
            'source' => 'unknown',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonStructure(['meta' => ['errors']]);
    }
}
