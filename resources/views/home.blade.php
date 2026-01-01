@extends('layouts.app-web')

@section('content')
    <div class="bg-indigo-600 text-white py-20">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h1 class="text-4xl md:text-6xl font-bold mb-4">Patungan Premium, <br>Harga Minimum.</h1>
            <p class="text-indigo-100 text-lg mb-8 max-w-2xl mx-auto">
                Nikmati akses Netflix, Spotify, dan Youtube Premium resmi tanpa bikin dompet jebol.
                Aman, Legal, dan Bergaransi.
            </p>
            <a href="#katalog"
                class="bg-white text-indigo-600 px-8 py-3 rounded-full font-bold shadow-lg hover:bg-gray-100 transition">
                Lihat Produk
            </a>
        </div>
    </div>

    <div id="katalog" class="max-w-7xl mx-auto px-4 py-16">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900">Pilih Layanan Favoritmu</h2>
            <p class="text-gray-500 mt-2">Klik produk untuk melihat pilihan paket.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            @foreach ($products as $product)
                <a href="{{ route('product.show', $product->id) }}"
                    class="group block bg-white rounded-2xl shadow-sm hover:shadow-xl transition duration-300 border border-gray-100 overflow-hidden">
                    <div class="h-48 bg-gray-100 flex items-center justify-center overflow-hidden relative">
                        @if ($product->image)
                            <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}"
                                class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                        @else
                            <span class="text-4xl font-bold text-gray-300">{{ substr($product->name, 0, 1) }}</span>
                        @endif

                        <div class="absolute top-3 right-3 bg-green-500 text-white text-xs px-2 py-1 rounded-full shadow">
                            Resmi
                        </div>
                    </div>

                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-1 group-hover:text-indigo-600 transition">
                            {{ $product->name }}</h3>
                        <p class="text-sm text-gray-500 line-clamp-2">
                            {{ $product->description ?? 'Layanan streaming premium terbaik.' }}</p>

                        <div
                            class="mt-4 w-full text-center bg-indigo-50 text-indigo-700 py-2 rounded-lg font-semibold group-hover:bg-indigo-600 group-hover:text-white transition duration-300">
                            Lihat Paket
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
@endsection
