<?php

namespace App\Http\Controllers\Api\V1;

use App\Customer\CustomerAddressService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCustomerAddressRequest;
use App\Http\Resources\Api\V1\AddressResource;
use App\Models\Address;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerAddressController extends Controller
{
    public function __construct(private readonly CustomerAddressService $addresses) {}

    public function index(Request $request): JsonResponse
    {
        $addresses = $this->customer()->addresses()->latest('id')->get();

        return response()->json(['data' => AddressResource::collection($addresses)->resolve($request), 'meta' => (object) [], 'message' => null]);
    }

    public function store(StoreCustomerAddressRequest $request): JsonResponse
    {
        $address = $this->addresses->create($this->customer(), $request->validated());

        return $this->resourceResponse($request, $address, 'Address created successfully.', 201);
    }

    public function show(Request $request, string $public_id): JsonResponse
    {
        $address = $this->owned($public_id);
        if (! $address) return $this->notFound();

        return $this->resourceResponse($request, $address);
    }

    public function update(StoreCustomerAddressRequest $request, string $public_id): JsonResponse
    {
        $address = $this->owned($public_id);
        if (! $address) return $this->notFound();
        $address = $this->addresses->update($address, $request->validated());

        return $this->resourceResponse($request, $address, 'Address updated successfully.');
    }

    public function destroy(string $public_id): JsonResponse
    {
        $address = $this->owned($public_id);
        if (! $address) return $this->notFound();
        $this->addresses->delete($address);

        return response()->json(null, 204);
    }

    private function customer(): Customer
    {
        return Auth::guard('customer')->user();
    }

    private function owned(string $publicId): ?Address
    {
        return $this->customer()->addresses()->where('public_id', $publicId)->first();
    }

    private function resourceResponse(Request $request, Address $address, ?string $message = null, int $status = 200): JsonResponse
    {
        return response()->json(['data' => (new AddressResource($address))->resolve($request), 'meta' => (object) [], 'message' => $message], $status);
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['data' => null, 'meta' => ['errors' => [['code' => 'address_not_found', 'message' => 'The requested address was not found.']]], 'message' => 'The requested address was not found.'], 404);
    }
}