<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Catalog\AdminContentService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreBannerRequest;
use App\Http\Requests\Api\V1\Admin\UpdateBannerRequest;
use App\Http\Resources\Api\V1\Admin\BannerResource;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BannerController extends Controller
{
    public function __construct(private readonly AdminContentService $content) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => BannerResource::collection(Banner::query()->orderBy('placement')->orderBy('sort_order')->get())->resolve($request), 'meta' => (object) [], 'message' => null]);
    }

    public function store(StoreBannerRequest $request): JsonResponse
    {
        $banner = $this->content->createBanner($request->safe()->except(['desktop_image', 'mobile_image']), $request->file('desktop_image'), $request->file('mobile_image'));

        return $this->resource($request, $banner, 201);
    }

    public function update(UpdateBannerRequest $request, string $public_id): JsonResponse
    {
        $banner = Banner::query()->where('public_id', $public_id)->first();
        if (! $banner) {
            return $this->notFound();
        }
        $updated = $this->content->updateBanner($banner, $request->safe()->except(['desktop_image', 'mobile_image']), $request->file('desktop_image'), $request->file('mobile_image'));

        return $this->resource($request, $updated);
    }

    public function destroy(string $public_id): Response|JsonResponse
    {
        $banner = Banner::query()->where('public_id', $public_id)->first();
        if (! $banner) {
            return $this->notFound();
        }
        $this->content->deleteBanner($banner);

        return response()->noContent();
    }

    private function resource(Request $request, Banner $banner, int $status = 200): JsonResponse
    {
        return response()->json(['data' => (new BannerResource($banner))->resolve($request), 'meta' => (object) [], 'message' => null], $status);
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['data' => null, 'meta' => ['errors' => [['code' => 'banner_not_found', 'message' => 'The requested banner was not found.']]], 'message' => 'The requested resource was not found.'], 404);
    }
}
