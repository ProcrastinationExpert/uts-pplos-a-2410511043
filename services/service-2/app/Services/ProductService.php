<?php

namespace App\Services;

use App\Models\Product;

class ProductService
{
    public function getAllProducts($filters = [], $perPage = 10)
    {
        $query = Product::with(['category', 'images']);

        // Jika ada filter kategori, terapkan ke query
        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->paginate($perPage);
    }
}