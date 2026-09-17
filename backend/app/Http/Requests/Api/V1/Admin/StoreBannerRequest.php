<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\V1\CatalogRequest;

class StoreBannerRequest extends CatalogRequest
{
    public function rules(): array
    {
        return $this->bannerRules(false);
    }

    protected function bannerRules(bool $updating): array
    {
        $required = $updating ? 'sometimes' : 'required';

        return [
            'name' => [$required, 'string', 'max:160'], 'placement' => [$required, 'string', 'max:100'],
            'headline' => ['sometimes', 'nullable', 'string', 'max:255'], 'subheading' => ['sometimes', 'nullable', 'string'],
            'cta_label' => ['sometimes', 'nullable', 'string', 'max:120'],
            'cta_url' => ['sometimes', 'nullable', 'string', 'max:2048', 'regex:/^(\/[^\s]*|https?:\/\/[^\s]+)$/i'],
            'desktop_image' => [$updating ? 'sometimes' : 'required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'mobile_image' => ['sometimes', 'nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_mobile_image' => ['sometimes', 'boolean'],
            'alt_text' => ['sometimes', 'nullable', 'string', 'max:255'], 'sort_order' => [$required, 'integer', 'min:0'],
            'is_active' => [$required, 'boolean'], 'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date', 'after:starts_at'],
        ];
    }
}
