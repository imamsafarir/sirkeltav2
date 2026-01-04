@extends('layouts.app-web')

@section('content')
    {{-- 1. HERO SECTION (Langsung Konten Utama) --}}
    <div class="relative bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white overflow-hidden">
        {{-- Hiasan Background --}}
        <div class="absolute top-0 left-0 w-full h-full opacity-10">
            <div class="absolute right-0 top-0 bg-white w-96 h-96 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2">
            </div>
            <div class="absolute left-0 bottom-0 bg-white w-64 h-64 rounded-full blur-3xl translate-y-1/2 -translate-x-1/2">
            </div>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 py-24 md:py-32 text-center">
            <span
                class="inline-block py-1 px-3 rounded-full bg-white/20 backdrop-blur-sm text-indigo-50 text-sm font-semibold mb-6 border border-white/30">
                🚀 Solusi Hemat Streaming Premium
            </span>
            <h1 class="text-4xl md:text-7xl font-extrabold mb-6 leading-tight tracking-tight">
                Patungan Premium, <br> <span class="text-indigo-200">Harga Minimum.</span>
            </h1>
            <p class="text-indigo-100 text-lg md:text-xl mb-10 max-w-2xl mx-auto font-light leading-relaxed">
                Nikmati akses Netflix, Spotify, dan Youtube Premium resmi dengan sistem sharing yang aman.
                Legal, Bergaransi, dan Otomatis.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="#katalog"
                    class="bg-white text-indigo-600 px-8 py-4 rounded-full font-bold shadow-xl hover:shadow-2xl hover:scale-105 transition transform duration-300 flex items-center justify-center gap-2">
                    <span>Lihat Produk</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </a>
                <a href="#cara-kerja"
                    class="bg-indigo-800/50 backdrop-blur-md border border-indigo-400/30 text-white px-8 py-4 rounded-full font-bold hover:bg-indigo-800 transition">
                    Cara Kerjanya?
                </a>
            </div>
        </div>
    </div>

    {{-- 2. LIVE GROUP MONITOR (Grup dengan User Disamarkan) --}}
    @if (isset($activeGroups) && $activeGroups->count() > 0)
        <div id="live-groups" class="py-16 bg-gray-50 border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                            <span class="relative flex h-3 w-3">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                            </span>
                            Grup Sedang Mencari Anggota
                        </h2>
                        <p class="text-gray-500 text-sm">Real-time update peserta patungan.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($activeGroups as $group)
                        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                            {{-- Header Kartu --}}
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <div class="text-xs font-bold text-indigo-600 uppercase tracking-wide">
                                        #GRUP-{{ $group->id }}</div>
                                    <h3 class="font-bold text-gray-800 text-lg">
                                        {{ $group->variant->product->name ?? 'Produk' }}</h3>
                                    <span class="text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded">
                                        {{ $group->variant->name ?? 'Paket' }}
                                    </span>
                                </div>
                                <div class="bg-green-100 text-green-700 text-xs font-bold px-2 py-1 rounded-lg">
                                    OPEN
                                </div>
                            </div>

                            {{-- Slot Peserta --}}
                            <div class="space-y-3">
                                <p class="text-xs text-gray-400 font-semibold uppercase">Peserta Terdaftar:</p>
                                <div class="flex flex-col gap-2">
                                    {{-- Loop Peserta yang SUDAH Masuk --}}
                                    @foreach ($group->orders as $order)
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-xs">
                                                {{ substr($order->user->name ?? 'U', 0, 1) }}
                                            </div>
                                            <div class="flex-1">
                                                {{-- MASKING NAMA (Privasi) --}}
                                                <p class="text-sm font-medium text-gray-700">
                                                    {{ substr($order->user->name ?? 'User', 0, 3) }}***
                                                </p>
                                            </div>
                                            <span class="text-xs text-green-600 font-bold">Join ✅</span>
                                        </div>
                                    @endforeach

                                    {{-- Loop Slot Kosong --}}
                                    @php
                                        $maxSlots = $group->variant->total_slots ?? 5;
                                        $filled = $group->orders->count();
                                        $empty = max(0, $maxSlots - $filled);
                                    @endphp

                                    @for ($i = 0; $i < $empty; $i++)
                                        <div class="flex items-center gap-3 opacity-50 border-t border-dashed pt-2">
                                            <div
                                                class="w-8 h-8 rounded-full border-2 border-dashed border-gray-300 flex items-center justify-center text-gray-300 text-xs">
                                                +
                                            </div>
                                            <div class="flex-1">
                                                <p class="text-sm font-medium text-gray-400 italic">Menunggu kamu...</p>
                                            </div>
                                        </div>
                                    @endfor
                                </div>
                            </div>

                            {{-- Tombol Join --}}
                            <div class="mt-6">
                                <a href="{{ route('product.show', $group->variant->product->id ?? 1) }}"
                                    class="block w-full text-center bg-indigo-50 hover:bg-indigo-600 hover:text-white text-indigo-700 font-bold py-2 rounded-lg transition text-sm">
                                    Gabung Grup Ini
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- 3. KATALOG PRODUK --}}
    <div id="katalog" class="max-w-7xl mx-auto px-4 py-20">
        <div class="text-center mb-16">
            <span class="text-indigo-600 font-bold tracking-wide uppercase text-sm">Katalog Premium</span>
            <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mt-2">Pilih Layanan Favoritmu</h2>
            <p class="text-gray-500 mt-4 text-lg max-w-2xl mx-auto">Semua akun legal, bergaransi, dan siap pakai. Klik
                produk untuk melihat pilihan durasi.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            @foreach ($products as $product)
                <a href="{{ route('product.show', $product->id) }}"
                    class="group block bg-white rounded-2xl shadow-sm hover:shadow-2xl hover:-translate-y-2 transition duration-300 border border-gray-100 overflow-hidden relative">
                    {{-- Badge Hemat --}}
                    <div class="absolute top-4 left-4 z-10 bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full">
                        Hemat s.d 70%
                    </div>

                    <div class="h-56 bg-gray-50 flex items-center justify-center overflow-hidden relative p-8">
                        @if ($product->image)
                            <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}"
                                class="w-full h-full object-contain drop-shadow-lg group-hover:scale-110 transition duration-500">
                        @else
                            <div
                                class="w-24 h-24 rounded-2xl bg-indigo-100 flex items-center justify-center text-4xl font-bold text-indigo-400">
                                {{ substr($product->name, 0, 1) }}
                            </div>
                        @endif
                    </div>

                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-bold text-gray-900 group-hover:text-indigo-600 transition">
                                {{ $product->name }}
                            </h3>
                            <svg class="w-5 h-5 text-gray-300 group-hover:text-indigo-500 transition" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </div>
                        <p class="text-sm text-gray-500 line-clamp-2 mb-4">
                            {{ $product->description ?? 'Layanan streaming premium legal dan bergaransi.' }}
                        </p>

                        <div class="border-t border-gray-100 pt-4 flex items-center justify-between">
                            <span class="text-xs text-gray-400 font-medium">Mulai dari</span>
                            {{-- Ambil harga terendah variant jika ada --}}
                            @php
                                $minPrice = $product->variants->min('price');
                            @endphp
                            <span class="text-lg font-bold text-indigo-600">
                                Rp {{ number_format($minPrice ?? 0, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    {{-- 4. CARA KERJA --}}
    <div id="cara-kerja" class="bg-gray-900 text-white py-24 relative overflow-hidden">
        {{-- Ornament --}}
        <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-600 rounded-full blur-3xl opacity-20 translate-x-1/2">
        </div>

        <div class="max-w-7xl mx-auto px-4 relative z-10">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold mb-4">Gampang Banget!</h2>
                <p class="text-gray-400">Sistem kami berjalan otomatis 24/7 tanpa perlu menunggu admin manual.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-12 text-center">
                {{-- Langkah 1 --}}
                <div class="relative group">
                    <div
                        class="w-20 h-20 mx-auto bg-indigo-600 rounded-2xl flex items-center justify-center text-3xl font-bold mb-6 shadow-[0_0_20px_rgba(79,70,229,0.5)] group-hover:scale-110 transition duration-300">
                        1
                    </div>
                    <h3 class="text-xl font-bold mb-3">Pilih & Bayar</h3>
                    <p class="text-gray-400 leading-relaxed">
                        Pilih paket layanan yang kamu mau. Bayar instan via QRIS, E-Wallet, atau Transfer Bank (Otomatis).
                    </p>
                </div>

                {{-- Langkah 2 --}}
                <div class="relative group">
                    <div
                        class="w-20 h-20 mx-auto bg-purple-600 rounded-2xl flex items-center justify-center text-3xl font-bold mb-6 shadow-[0_0_20px_rgba(147,51,234,0.5)] group-hover:scale-110 transition duration-300">
                        2
                    </div>
                    <h3 class="text-xl font-bold mb-3">Sistem Mencari Grup</h3>
                    <p class="text-gray-400 leading-relaxed">
                        Kamu akan otomatis dimasukkan ke dalam grup patungan. Jika grup penuh, akun langsung dikirim.
                    </p>
                </div>

                {{-- Langkah 3 --}}
                <div class="relative group">
                    <div
                        class="w-20 h-20 mx-auto bg-green-500 rounded-2xl flex items-center justify-center text-3xl font-bold mb-6 shadow-[0_0_20px_rgba(34,197,94,0.5)] group-hover:scale-110 transition duration-300">
                        3
                    </div>
                    <h3 class="text-xl font-bold mb-3">Login & Enjoy</h3>
                    <p class="text-gray-400 leading-relaxed">
                        Cek Dashboard. Email & Password akun premium muncul di sana. Tinggal login dan nonton!
                    </p>
                </div>
            </div>
        </div>
    </div>

@endsection
