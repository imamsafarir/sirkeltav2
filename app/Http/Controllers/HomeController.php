<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        // 1. Ambil Produk untuk Katalog
        $products = \App\Models\Product::where('is_active', true)->get();

        // 2. Ambil Produk BESERTA Grup-nya (Open & Full)
        $productsWithGroups = \App\Models\Product::where('is_active', true)
            ->whereHas('variants.groups')
            ->with(['variants.groups' => function ($query) {
                $query->whereIn('status', ['open', 'full', 'completed'])
                    // PERBAIKAN: Gunakan 'CASE WHEN' agar kompatibel dengan PostgreSQL
                    ->orderByRaw("CASE status
                          WHEN 'open' THEN 1
                          WHEN 'full' THEN 2
                          WHEN 'completed' THEN 3
                          ELSE 4
                      END")
                    ->latest()
                    ->take(6);
            }, 'variants.groups.orders.user'])
            ->get();

        return view('home', compact('products', 'productsWithGroups'));
    }

    public function show(Product $product)
    {
        // Tampilkan detail produk beserta variannya
        // Eager load 'variants' biar query ringan
        $product->load('variants');

        return view('product-detail', compact('product'));
    }
}
