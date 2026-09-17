<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Catalog\AdminContentService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreHomepageSectionRequest;
use App\Http\Resources\Api\V1\Admin\HomepageSectionResource;
use App\Models\HomepageSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class HomepageSectionController extends Controller
{
    public function __construct(private readonly AdminContentService $content) {}

    public function index(Request $request): JsonResponse
    {
        $sections = HomepageSection::query()->with(['categories' => fn ($query) => $query->orderBy('homepage_section_categories.sort_order'), 'products' => fn ($query) => $query->orderBy('homepage_section_products.sort_order')])->orderBy('sort_order')->get();

        return response()->json(['data' => HomepageSectionResource::collection($sections)->resolve($request), 'meta' => (object) [], 'message' => null]);
    }

    public function store(StoreHomepageSectionRequest $request): JsonResponse
    {
        return $this->resource($request, $this->content->saveSection(null, $request->validated()), 201);
    }

    public function update(StoreHomepageSectionRequest $request, string $public_id): JsonResponse
    {
        $section = HomepageSection::query()->where('public_id', $public_id)->first();
        if (! $section) {
            return $this->notFound();
        }

        return $this->resource($request, $this->content->saveSection($section, $request->validated()));
    }

    public function destroy(string $public_id): Response|JsonResponse
    {
        $section = HomepageSection::query()->where('public_id', $public_id)->first();
        if (! $section) {
            return $this->notFound();
        }
        $section->delete();

        return response()->noContent();
    }

    private function resource(Request $request, HomepageSection $section, int $status = 200): JsonResponse
    {
        return response()->json(['data' => (new HomepageSectionResource($section))->resolve($request), 'meta' => (object) [], 'message' => null], $status);
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['data' => null, 'meta' => ['errors' => [['code' => 'homepage_section_not_found', 'message' => 'The requested homepage section was not found.']]], 'message' => 'The requested resource was not found.'], 404);
    }
}
