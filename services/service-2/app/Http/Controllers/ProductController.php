<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProductService;

class ProductController extends Controller
{
    //
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(Request $request)
    {

        $filters = [
            'category_id' => $request->query('category_id'),
            'search' => $request->query('search')
        ];
        
        $perPage = $request->query('per_page', 10);

        $products = $this->productService->getAllProducts($filters, $perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar produk berhasil diambil',
            'data' => $products
        ], 200);
    }

    public function store(StoreProductRequest $request)
    {
        $validatedData = $request->validated();

        $product = $this->productService->createProduct($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Produk berhasil ditambahkan',
            'data' => $product
        ], 201);
    }

    public function show($id)
    {
        $product = $this->productService->getProductById($id);

        if (!$product) {
            return response()->json(['message' => 'Produk tidak ditemukan'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $product
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'category_id' => 'sometimes|integer|exists:categories,id',
            'name'        => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'sometimes|numeric|min:0',
            'stock'       => 'sometimes|integer|min:0'
        ]);

        $product = $this->productService->updateProduct($id, $validatedData);

        if (!$product) {
            return response()->json(['message' => 'Produk tidak ditemukan'], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Produk berhasil diupdate',
            'data' => $product
        ], 200);
    }

    public function destroy($id)
    {
        $deleted = $this->productService->deleteProduct($id);

        if (!$deleted) {
            return response()->json(['message' => 'Produk tidak ditemukan'], 404);
        }

        return response()->json(null, 204); 
    }

    public function checkStock(Request $request, $id)
    {
        $quantity = $request->query('quantity', 1); 

        $result = $this->productService->checkStock($id, $quantity);

        if ($result['status'] === 'not_found') {
            return response()->json(['message' => 'Produk tidak ditemukan'], 404);
        }

        if ($result['status'] === 'insufficient_stock') {
            return response()->json(['message' => 'Stok produk tidak mencukupi'], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Stok tersedia',
            'data' => $result
        ], 200);
    }
}
