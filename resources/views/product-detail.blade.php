@extends('layouts.app-web')

@section('content')
    <div class="bg-white">
        <div class="bg-gray-900 text-white py-16">
            <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row items-center gap-8">
                <div class="w-32 h-32 bg-white rounded-2xl p-2 shadow-lg shrink-0">
                    @if ($product->image)
                        <img src="{{ Storage::url($product->image) }}" class="w-full h-full object-cover rounded-xl">
                    @else
                        <div
                            class="w-full h-full flex items-center justify-center text-4xl font-bold text-gray-800 bg-gray-100 rounded-xl">
                            {{ substr($product->name, 0, 1) }}
                        </div>
                    @endif
                </div>

                <div>
                    <h1 class="text-4xl font-bold mb-2">{{ $product->name }}</h1>
                    <p class="text-gray-400 text-lg">{{ $product->description ?? 'Layanan premium resmi & bergaransi.' }}</p>
                    <div class="mt-4 flex gap-3">
                        <span class="bg-green-500 text-white px-3 py-1 rounded text-sm font-bold">Legal 100%</span>
                        <span class="bg-blue-500 text-white px-3 py-1 rounded text-sm font-bold">Garansi Full</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 py-16">
            <h2 class="text-2xl font-bold text-gray-900 mb-8 border-b pb-4">Pilih Paket Langganan</h2>

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 relative">
                    <strong class="font-bold">Oops!</strong>
                    <span class="block sm:inline">{{ $errors->first() }}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach ($product->variants as $variant)
                    <div
                        class="border border-gray-200 rounded-2xl p-6 hover:shadow-xl transition relative bg-white flex flex-col">

                        <h3 class="text-xl font-bold text-gray-800">{{ $variant->name }}</h3>
                        <p class="text-sm text-gray-500 mb-4">{{ $variant->duration_days }} Hari Aktif</p>

                        <div class="text-3xl font-bold text-indigo-600 mb-6">
                            Rp {{ number_format($variant->price, 0, ',', '.') }}
                        </div>

                        <ul class="space-y-3 mb-8 flex-1">
                            <li class="flex items-center text-gray-600 text-sm">
                                <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                Private / Shared Account
                            </li>
                            <li class="flex items-center text-gray-600 text-sm">
                                <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                Sistem Patungan Otomatis
                            </li>
                            <li class="flex items-center text-gray-600 text-sm">
                                <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                Kuota {{ $variant->total_slots }} Orang/Grup
                            </li>
                        </ul>

                        <div class="mt-auto">
                            @if (auth()->check())
                                <form action="{{ route('checkout') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="product_variant_id" value="{{ $variant->id }}">

                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Punya Kode
                                            Promo?</label>
                                        <input type="text" name="promo_code" placeholder="Masukan kode (Opsional)"
                                            class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500 uppercase">
                                        <p class="text-xs text-gray-500 mt-1">*Kode akan dicek otomatis.</p>
                                    </div>

                                    <button type="submit"
                                        class="w-full py-3 rounded-xl font-bold bg-indigo-600 text-white hover:bg-indigo-700 shadow-lg transition transform hover:scale-105">
                                        Beli Sekarang
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('login') }}"
                                    class="block w-full text-center py-3 rounded-xl font-bold bg-gray-800 text-white hover:bg-gray-900 shadow-lg transition">
                                    Login untuk Beli
                                </a>
                            @endif
                        </div>

                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
