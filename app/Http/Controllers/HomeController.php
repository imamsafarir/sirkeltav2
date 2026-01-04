<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        // 1. Ambil Produk
        $products = \App\Models\Product::where('is_active', true)->get();

        // 2. Ambil Grup yang sedang OPEN (untuk ditampilkan di Live Monitor)
        $activeGroups = \App\Models\Group::with(['variant.product', 'orders.user'])
            ->where('status', 'open')
            ->where('expired_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->take(6) // Tampilkan 6 grup terbaru saja biar rapi
            ->get();

        return view('home', compact('products', 'activeGroups'));
    }

    public function show(Product $product)
    {
        // Tampilkan detail produk beserta variannya
        // Eager load 'variants' biar query ringan
        $product->load('variants');

        return view('product-detail', compact('product'));
    }
}
