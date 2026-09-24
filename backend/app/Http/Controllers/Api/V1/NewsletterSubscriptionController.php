<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreNewsletterSubscriptionRequest;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;

class NewsletterSubscriptionController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreNewsletterSubscriptionRequest $request): JsonResponse
    {
        $subscriber = NewsletterSubscriber::query()->firstOrNew(['email' => $request->validated('email')]);
        $subscriber->fill([
            'status' => 'subscribed',
            'source' => $request->validated('source'),
            'consented_at' => now(),
            'unsubscribed_at' => null,
        ])->save();

        return response()->json([
            'data' => ['subscribed' => true],
            'meta' => (object) [],
            'message' => 'You are subscribed to Noure updates.',
        ], $subscriber->wasRecentlyCreated ? 201 : 200);
    }
}
