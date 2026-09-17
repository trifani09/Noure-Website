<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Catalog\AdminProductImportService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ProductImportRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductImportController extends Controller
{
    public function __construct(private readonly AdminProductImportService $imports) {}

    public function preview(ProductImportRequest $request): JsonResponse
    {
        $result = $this->imports->preview($request->file('products'), $request->file('variants'), $request->file('images'), $request->file('media_files', []));

        return response()->json(['data' => $result, 'meta' => (object) [], 'message' => null]);
    }

    public function store(ProductImportRequest $request): JsonResponse
    {
        $result = $this->imports->import($request->file('products'), $request->file('variants'), $request->file('images'), $request->file('media_files', []));

        return response()->json(['data' => $result, 'meta' => (object) [], 'message' => null], 201);
    }

    public function template(string $type): BinaryFileResponse|JsonResponse
    {
        if (! in_array($type, ['products', 'variants', 'images'], true)) {
            return response()->json(['data' => null, 'meta' => ['errors' => [['code' => 'template_not_found', 'message' => 'The requested import template was not found.']]], 'message' => 'The requested resource was not found.'], 404);
        }

        return response()->download(base_path('../docs/import-templates/'.$type.'.csv'), $type.'-template.csv', ['Content-Type' => 'text/csv']);
    }
}
