<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\LoginRequest;
use App\Http\Resources\Api\V1\Admin\AdminResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::guard('web')->attempt($request->credentials())) {
            return $this->unauthenticated();
        }

        $request->session()->regenerate();

        return response()->json([
            'data' => (new AdminResource($request->user()))->resolve($request),
            'meta' => (object) [],
            'message' => null,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => (new AdminResource($request->user()))->resolve($request),
            'meta' => (object) [],
            'message' => null,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'data' => null,
            'meta' => (object) [],
            'message' => 'Logged out successfully.',
        ]);
    }

    private function unauthenticated(): JsonResponse
    {
        return response()->json([
            'data' => null,
            'meta' => ['errors' => [[
                'code' => 'unauthenticated',
                'message' => 'Authentication is required.',
            ]]],
            'message' => 'Authentication is required.',
        ], 401);
    }
}
