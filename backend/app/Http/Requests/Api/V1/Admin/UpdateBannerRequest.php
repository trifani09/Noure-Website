<?php

namespace App\Http\Requests\Api\V1\Admin;

class UpdateBannerRequest extends StoreBannerRequest
{
    public function rules(): array
    {
        return $this->bannerRules(true);
    }
}
