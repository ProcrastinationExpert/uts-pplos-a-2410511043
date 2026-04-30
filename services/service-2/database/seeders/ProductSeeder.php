<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductReview;


class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // 1. Buat Kategori
        $kategori = Category::create([
            'name' => 'Elektronik UMKM',
            'slug' => 'elektronik-umkm'
        ]);

        // 2. Buat Produk
        $produk = Product::create([
            'category_id' => $kategori->id,
            'name' => 'Lampu Hias Bambu Tradisional',
            'slug' => Str::slug('Lampu Hias Bambu Tradisional'),
            'description' => 'Lampu tidur estetik buatan tangan pengrajin desa.',
            'price' => 150000,
            'stock' => 25
        ]);

        // 3. Buat Gambar Produk
        ProductImage::create([
            'product_id' => $produk->id,
            'image_url' => 'https://images.unsplash.com/photo-1513506003901-1e6a229e2d15',
            'is_primary' => true
        ]);

        // 4. Buat Review (Simulasi User ID 1 dari User Service)
        ProductReview::create([
            'product_id' => $produk->id,
            'user_id' => 1,
            'rating' => 5,
            'comment' => 'Bagus banget, pengiriman cepat!'
        ]);
    }
}
