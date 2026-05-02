<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Str;

class ProductService
{
    public function getAllProducts($filters = [], $perPage = 10)
    {
        $query = Product::with(['category', 'images']);

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->paginate($perPage);
    }
    public function createProduct(array $data)
    {
        $data['slug'] = Str::slug($data['name']);
        return Product::create($data);
    }
    public function getProductById($id)
    {
        return Product::with(['category', 'images', 'reviews'])->find($id);
    }
    public function updateProduct($id, array $data)
    {
        $product = Product::find($id);
        if (!$product) {
            return null;
        }

        $product->update($data);
        return $product;
    }
    public function deleteProduct($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return false;
        }

        $product->delete();
        return true;
    }
    public function checkStock($id, $requestedQuantity)
    {
        $product = Product::find($id);
        
        if (!$product) {
            return ['status' => 'not_found'];
        }

        if ($product->stock >= $requestedQuantity) {
            return [
                'status' => 'available',
                'price' => $product->price,
                'stock' => $product->stock
            ];
        }
        return ['status' => 'insufficient_stock'];
    }
}