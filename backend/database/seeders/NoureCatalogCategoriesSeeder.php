<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NoureCatalogCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            Category::query()->whereNotIn('slug', ['kerudung', 'pashmina'])->update(['is_active' => false]);

            foreach ([
                ['name' => 'Kerudung', 'slug' => 'kerudung', 'description' => 'Koleksi kerudung Noure untuk melengkapi gaya sehari-hari.', 'sort_order' => 10],
                ['name' => 'Pashmina', 'slug' => 'pashmina', 'description' => 'Koleksi pashmina Noure dengan pilihan bahan dan warna.', 'sort_order' => 20],
            ] as $definition) {
                $category = Category::withTrashed()->firstOrNew(['slug' => $definition['slug']]);
                $category->fill([...$definition, 'parent_id' => null, 'is_active' => true]);
                $category->deleted_at = null;
                $category->save();
            }
        });
    }
}
