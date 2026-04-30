<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
}
