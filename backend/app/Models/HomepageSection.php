<?php

namespace App\Models;

use Database\Factories\HomepageSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'type', 'is_active', 'sort_order', 'configuration'])]
class HomepageSection extends Model
{
    /** @use HasFactory<HomepageSectionFactory> */
    use HasFactory, HasUlids;

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'homepage_section_categories')->withPivot('sort_order');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'homepage_section_products')->withPivot('sort_order');
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer', 'configuration' => 'array'];
    }
}
