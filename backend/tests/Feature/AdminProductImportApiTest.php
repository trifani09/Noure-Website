<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProductImportApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->admin = User::factory()->create();
        Category::factory()->create(['slug' => 'dresses']);
    }

    public function test_admin_can_preview_and_import_product_variants_and_existing_image(): void
    {
        Storage::disk('public')->put('products/luna/front.webp', 'image');
        $files = $this->files(images: null);
        $this->actingAs($this->admin)->post('/api/v1/admin/product-imports/preview', $files, ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('data.summary.detected', 1)->assertJsonPath('data.summary.valid', 1)->assertJsonPath('data.rows.0.variants', 2);

        $response = $this->post('/api/v1/admin/product-imports', $this->files(), ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('data.summary.created_products', 1)->assertJsonPath('data.summary.created_variants', 2)->assertJsonPath('data.summary.imported_images', 1);
        $this->assertDatabaseHas('products', ['slug' => 'luna-dress']);
        $this->assertDatabaseHas('product_variants', ['sku' => 'NOU-LUNA-CREAM-S']);
        $this->assertDatabaseHas('product_images', ['path' => 'products/luna/front.webp']);
        $this->assertNotNull($response->json('data.rows.0'));
    }

    public function test_invalid_rows_are_reported_without_writes(): void
    {
        $files = $this->files(products: "product_reference,name,slug,description,brand,category,status,published_at\nbad,,bad,,,missing,wrong,\n", variants: "product_reference,sku,title,option_values,price_amount,compare_at_amount,currency,weight_grams,is_active\nbad,,Bad,bad-format,-1,0,XX,1,false\n", images: null);

        $this->actingAs($this->admin)->post('/api/v1/admin/product-imports', $files, ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('data.summary.created_products', 0)->assertJsonPath('data.summary.failed', 1);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_duplicate_sku_and_product_slug_are_reported(): void
    {
        Product::factory()->create(['slug' => 'luna-dress']);
        ProductVariant::factory()->create(['sku' => 'NOU-LUNA-CREAM-S']);

        $this->actingAs($this->admin)->post('/api/v1/admin/product-imports/preview', $this->files(images: null), ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('data.summary.invalid', 1)
            ->assertJsonFragment(['code' => 'duplicate_product'])->assertJsonFragment(['code' => 'duplicate_sku']);
    }

    public function test_each_product_is_isolated_when_another_row_fails(): void
    {
        $products = "product_reference,name,slug,description,brand,category,status,published_at\ngood,Good Dress,good-dress,,Noure,dresses,draft,\nbad,Bad Dress,bad-dress,,Noure,missing,draft,\n";
        $variants = "product_reference,sku,title,option_values,price_amount,compare_at_amount,currency,weight_grams,is_active\ngood,GOOD-S,Small,size=s,100,,IDR,100,true\nbad,BAD-S,Small,size=s,100,,IDR,100,true\n";

        $this->actingAs($this->admin)->post('/api/v1/admin/product-imports', $this->files($products, $variants, null), ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('data.summary.created_products', 1)->assertJsonPath('data.summary.failed', 1);
        $this->assertDatabaseHas('products', ['slug' => 'good-dress']);
        $this->assertDatabaseMissing('products', ['slug' => 'bad-dress']);
    }

    public function test_uploaded_image_file_is_imported_to_product_storage(): void
    {
        $images = "product_reference,file_path,variant_sku,alt_text,sort_order,is_primary\nluna,front.png,,Front,0,true\n";
        $files = $this->files(images: $images);
        $files['media_files'] = [UploadedFile::fake()->image('front.png', 600, 800)];

        $this->actingAs($this->admin)->post('/api/v1/admin/product-imports', $files, ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('data.summary.imported_images', 1);
        $image = Product::where('slug', 'luna-dress')->firstOrFail()->images()->firstOrFail();
        Storage::disk('public')->assertExists($image->path);
        $this->assertSame(600, $image->width);
    }

    public function test_import_endpoints_require_authentication(): void
    {
        $this->post('/api/v1/admin/product-imports/preview', $this->files(), ['Accept' => 'application/json'])->assertUnauthorized();
        $this->post('/api/v1/admin/product-imports', $this->files(), ['Accept' => 'application/json'])->assertUnauthorized();
    }

    public function test_xlsx_first_worksheet_is_supported(): void
    {
        $files = $this->files(images: null);
        $files['products'] = $this->xlsx('products.xlsx', [
            ['product_reference', 'name', 'slug', 'description', 'brand', 'category', 'status', 'published_at'],
            ['luna', 'Luna Dress', 'luna-dress', 'A dress', 'Noure', 'dresses', 'draft', ''],
        ]);

        $this->actingAs($this->admin)->post('/api/v1/admin/product-imports/preview', $files, ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('data.summary.valid', 1);
    }

    private function files(?string $products = null, ?string $variants = null, ?string $images = 'default'): array
    {
        $products ??= "product_reference,name,slug,description,brand,category,status,published_at\nluna,Luna Dress,luna-dress,A dress,Noure,dresses,draft,\n";
        $variants ??= "product_reference,sku,title,option_values,price_amount,compare_at_amount,currency,weight_grams,is_active\nluna,NOU-LUNA-CREAM-S,Cream S,color=cream|size=s,399000,449000,IDR,420,true\nluna,NOU-LUNA-BLACK-M,Black M,color=black|size=m,399000,,IDR,420,true\n";
        $result = ['products' => UploadedFile::fake()->createWithContent('products.csv', $products), 'variants' => UploadedFile::fake()->createWithContent('variants.csv', $variants)];
        if ($images !== null) {
            $content = $images === 'default' ? "product_reference,file_path,variant_sku,alt_text,sort_order,is_primary\nluna,products/luna/front.webp,,Front,0,true\n" : $images;
            $result['images'] = UploadedFile::fake()->createWithContent('images.csv', $content);
        }

        return $result;
    }

    private function xlsx(string $name, array $rows): UploadedFile
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'xlsx').'.zip';
        $archive = new \PharData($zipPath, 0, null, \Phar::ZIP);
        $xmlRows = '';
        foreach ($rows as $rowIndex => $row) {
            $cells = '';
            foreach ($row as $columnIndex => $value) {
                $column = chr(65 + $columnIndex);
                $escaped = htmlspecialchars($value, ENT_XML1);
                $cells .= "<c r=\"{$column}".($rowIndex + 1)."\" t=\"inlineStr\"><is><t>{$escaped}</t></is></c>";
            }
            $xmlRows .= '<row r="'.($rowIndex + 1).'">'.$cells.'</row>';
        }
        $archive['xl/worksheets/sheet1.xml'] = '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$xmlRows.'</sheetData></worksheet>';
        unset($archive);
        $xlsxPath = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        rename($zipPath, $xlsxPath);

        return new UploadedFile($xlsxPath, $name, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
