<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        // Ambil produk yang aktif saja
        $products = Product::where('is_active', true)->get();

        return view('home', compact('products'));
    }

    public function show(Product $product)
    {
        // Tampilkan detail produk beserta variannya
        // Eager load 'variants' biar query ringan
        $product->load('variants');

        return view('product-detail', compact('product'));
    }
}
