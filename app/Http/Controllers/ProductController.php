<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductController extends Controller
{
    // Get all products with pagination
   public function index(Request $request)
{
    try {
        // Fetch all products without pagination
        $products = Product::all();

        return response()->json([
            'message' => 'Products fetched successfully',
            'data' => $products,
        ], 200);
    } catch (\Exception $e) {
        Log::error('Error fetching products: ' . $e->getMessage());
        return response()->json([
            'message' => 'Error fetching products',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    public function store(Request $request)
    {
        Log::info('Creating new product:', $request->all());

        try {
            $validatedData = $request->validate($this->rules());

            $product = Product::create($validatedData);

            return response()->json([
                'message' => 'Product created successfully',
                'data' => $product,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error creating product: ', $e->errors());
            return response()->json([
                'message' => 'Validation error creating product',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating product: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error creating product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Get a single product by ID
    public function show($id)
    {
        try {
            $product = Product::findOrFail($id);
            return response()->json([
                'message' => 'Product fetched successfully',
                'data' => $product,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching product: ' . $e->getMessage());
            return response()->json([
                'message' => 'Product not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    // Update a product
    public function update(Request $request, $id)
    {
        Log::info('Updating product ID: ' . $id, $request->all());

        try {
            $product = Product::findOrFail($id);
            $validatedData = $request->validate($this->rules($id));

            $product->update($validatedData);

            return response()->json([
                'message' => 'Product updated successfully',
                'data' => $product,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating product: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error updating product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Delete a product
    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();

            return response()->json([
                'message' => 'Product deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting product: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error deleting product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Import products from an Excel file
   public function import(Request $request)
{
    Log::info('Import method called with request data: ', $request->all());

    try {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        if (!$request->hasFile('file')) {
            return response()->json([
                'message' => 'No file uploaded',
            ], 400);
        }

        $file = $request->file('file');
        Log::info('File uploaded:', ['file' => $file->getClientOriginalName()]);

        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $header = array_shift($rows); // Remove header row
        Log::info('Excel header:', $header);

        DB::beginTransaction();

        $importedProducts = [];

        foreach ($rows as $index => $row) {
            if (empty($row[0])) {
                Log::warning('Skipping row due to missing product_name:', ['index' => $index]);
                continue;
            }

            $productData = [
                'product_name' => $row[0] ?? null,
                'item_code' => $row[1] ?? null, // Ensure this is populated
                'expiry_date' => $row[3] ?? null,
                'buying_cost' => $row[4] ?? 0,
                'sales_price' => $row[5] ?? 0,
                'minimum_price' => $row[6] ?? 0,
                'wholesale_price' => $row[7] ?? 0,
                'barcode' => $row[8] ?? null,
                'mrp' => $row[9] ?? 0,
                'minimum_stock_quantity' => $row[10] ?? 0,
                'opening_stock_quantity' => $row[11] ?? 0,
                'opening_stock_value' => $row[12] ?? 0,
                'category' => $row[13] ?? null,
                'supplier' => $row[14] ?? null,
                'unit_type' => $row[15] ?? null,
                'store_location' => $row[16] ?? null,
                'cabinet' => $row[17] ?? null,
                'row' => $row[18] ?? null,
                'extra_fields' => json_encode([
                    'extra_field_name' => $row[19] ?? null,
                    'extra_field_value' => $row[20] ?? null,
                ]),
            ];

            Log::info('Mapped product data:', $productData);

            // Validate the product data
            $validator = \Validator::make($productData, $this->rules());

            if ($validator->fails()) {
                Log::warning('Validation failed:', ['errors' => $validator->errors()->all()]);
                continue;
            }

            // Create the product
            $product = Product::create($productData);
            $importedProducts[] = $product;
        }

        DB::commit();

        return response()->json([
            'message' => 'Products imported successfully',
            'imported_products' => $importedProducts,
        ], 200);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error importing products:', ['error' => $e->getMessage()]);
        return response()->json([
            'message' => 'Error importing products',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    // Validation rules
    private function rules($id = null)
    {
        return [
            'product_name' => 'required|string|max:255',
            'item_code' => ['nullable', 'string', Rule::unique('products', 'item_code')->ignore($id)],
            'batch_number' => 'nullable|string',
            'expiry_date' => 'nullable|date',
            'buying_cost' => 'required|numeric|min:0',
            'sales_price' => 'required|numeric|min:0',
            'minimum_price' => 'nullable|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'barcode' => ['nullable', 'string', Rule::unique('products', 'barcode')->ignore($id)],
            'mrp' => 'required|numeric|min:0',
            'minimum_stock_quantity' => 'nullable|integer|min:0',
            'opening_stock_quantity' => 'nullable|integer|min:0',
            'opening_stock_value' => 'nullable|numeric|min:0',
            'category' => 'nullable|string',
            'supplier' => 'nullable|string',
            'unit_type' => 'nullable|string',
            'store_location' => 'nullable|string',
            'cabinet' => 'nullable|string',
            'row' => 'nullable|string',
            'extra_fields' => 'nullable|json',
        ];
    }

    // Centralized exception handling
    private function handleException($e, $message, $status = 500)
    {
        Log::error($message . ': ' . $e->getMessage());
        return response()->json(['message' => $message, 'error' => $e->getMessage()], $status);
    }

    
    public function barcode($id)
    {
        $product = Product::findOrFail($id);
        return view('product', compact('product'));
    }
}