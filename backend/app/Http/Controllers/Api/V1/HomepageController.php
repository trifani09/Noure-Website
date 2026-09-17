<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\HomepageSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class HomepageController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $banners = Banner::query()->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderBy('sort_order')->get()->groupBy('placement');
        $sections = HomepageSection::query()->where('is_active', true)->orderBy('sort_order')->with([
            'categories' => fn ($query) => $query->where('is_active', true)->orderBy('homepage_section_categories.sort_order'),
            'products' => fn ($query) => $query->publiclyVisible()->orderBy('homepage_section_products.sort_order')->with([
                'images' => fn ($images) => $images->whereNull('variant_id')->orderByDesc('is_primary')->orderBy('sort_order'),
                'variants' => fn ($variants) => $variants->where('is_active', true)->with('inventoryLevels.location'),
            ]),
        ])->get();

        return response()->json(['data' => [
            'hero_banners' => collect($banners->get('home_hero', []))->map(fn ($banner) => $this->banner($banner))->values(),
            'sections' => $sections->map(function ($section) use ($banners) {
                $placement = $section->configuration['placement'] ?? null;

                return ['public_id' => $section->public_id, 'type' => $section->type, 'name' => $section->name, 'sort_order' => $section->sort_order,
                    'configuration' => $section->configuration ?? [],
                    'banners' => $placement ? collect($banners->get($placement, []))->map(fn ($banner) => $this->banner($banner))->values() : [],
                    'categories' => $section->categories->map(fn ($category) => ['public_id' => $category->public_id, 'name' => $category->name, 'slug' => $category->slug, 'description' => $category->description, 'image_url' => $category->image_path ? Storage::disk('public')->url($category->image_path) : null])->values(),
                    'products' => $section->products->map(fn ($product) => $this->product($product))->values()];
            })->values(),
        ], 'meta' => (object) [], 'message' => null]);
    }

    private function banner(object $banner): array
    {
        return ['public_id' => $banner->public_id, 'placement' => $banner->placement, 'headline' => $banner->headline, 'subheading' => $banner->subheading,
            'cta_label' => $banner->cta_label, 'cta_url' => $banner->cta_url, 'desktop_image_url' => Storage::disk('public')->url($banner->desktop_image_path),
            'mobile_image_url' => $banner->mobile_image_path ? Storage::disk('public')->url($banner->mobile_image_path) : null, 'alt_text' => $banner->alt_text];
    }

    private function product(object $product): array
    {
        $variant = $product->variants->firstWhere('is_default', true) ?? $product->variants->sortBy('price_amount')->first();
        $image = $product->images->first();
        $available = $product->variants->contains(fn ($item) => $item->inventoryLevels->contains(fn ($level) => $level->location?->is_active && $level->on_hand - $level->reserved - $level->safety_stock > 0));

        return ['public_id' => $product->public_id, 'name' => $product->name, 'slug' => $product->slug, 'short_description' => $product->short_description,
            'primary_image' => $image ? ['url' => Storage::disk('public')->url($image->path), 'alt_text' => $image->alt_text] : null,
            'price' => $variant ? ['price_amount' => $variant->price_amount, 'compare_at_amount' => $variant->compare_at_amount, 'currency' => $variant->currency] : null,
            'available' => $available];
    }
}
