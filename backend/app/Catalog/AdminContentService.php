<?php

namespace App\Catalog;

use App\Models\Banner;
use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminContentService
{
    public function createBanner(array $attributes, UploadedFile $desktop, ?UploadedFile $mobile): Banner
    {
        $paths = [$desktop->store('banners', 'public'), $mobile?->store('banners', 'public')];
        try {
            return Banner::query()->create(array_merge($this->bannerAttributes($attributes), ['desktop_image_path' => $paths[0], 'mobile_image_path' => $paths[1]]));
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete(array_filter($paths));
            throw $exception;
        }
    }

    public function updateBanner(Banner $banner, array $attributes, ?UploadedFile $desktop, ?UploadedFile $mobile): Banner
    {
        $newPaths = [$desktop?->store('banners', 'public'), $mobile?->store('banners', 'public')];
        $oldPaths = [];
        try {
            DB::transaction(function () use ($banner, $attributes, $newPaths, &$oldPaths): void {
                $data = $this->bannerAttributes($attributes);
                if ($newPaths[0]) {
                    $oldPaths[] = $banner->desktop_image_path;
                    $data['desktop_image_path'] = $newPaths[0];
                }
                if ($newPaths[1]) {
                    $oldPaths[] = $banner->mobile_image_path;
                    $data['mobile_image_path'] = $newPaths[1];
                }
                if ($attributes['remove_mobile_image'] ?? false) {
                    $oldPaths[] = $banner->mobile_image_path;
                    $data['mobile_image_path'] = null;
                }
                $banner->update($data);
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete(array_filter($newPaths));
            throw $exception;
        }
        Storage::disk('public')->delete(array_filter($oldPaths));

        return $banner->refresh();
    }

    public function deleteBanner(Banner $banner): void
    {
        $paths = [$banner->desktop_image_path, $banner->mobile_image_path];
        $banner->delete();
        Storage::disk('public')->delete(array_filter($paths));
    }

    public function saveSection(?HomepageSection $section, array $attributes): HomepageSection
    {
        return DB::transaction(function () use ($section, $attributes): HomepageSection {
            $section ??= new HomepageSection;
            $section->fill($attributes)->save();
            $categoryIds = Category::query()->whereIn('public_id', $attributes['category_public_ids'])->pluck('id', 'public_id');
            $productIds = Product::query()->whereIn('public_id', $attributes['product_public_ids'])->pluck('id', 'public_id');
            $section->categories()->sync(collect($attributes['category_public_ids'])->mapWithKeys(fn ($id, $index) => [$categoryIds[$id] => ['sort_order' => $index]])->all());
            $section->products()->sync(collect($attributes['product_public_ids'])->mapWithKeys(fn ($id, $index) => [$productIds[$id] => ['sort_order' => $index]])->all());

            return $section->load(['categories' => fn ($query) => $query->orderBy('homepage_section_categories.sort_order'), 'products' => fn ($query) => $query->orderBy('homepage_section_products.sort_order')]);
        });
    }

    private function bannerAttributes(array $attributes): array
    {
        unset($attributes['remove_mobile_image']);

        return $attributes;
    }
}
