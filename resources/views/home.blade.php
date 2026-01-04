@extends('layouts.app-web')

@section('content')
    {{-- 1. HERO SECTION --}}
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

    {{-- 2. LIVE GROUP MONITOR --}}
    @if (isset($productsWithGroups) && $productsWithGroups->count() > 0)
        <div id="live-groups" class="py-16 bg-gray-50 border-b border-gray-200" x-data="{ activeTab: {{ $productsWithGroups->first()->id }} }">
            <div class="max-w-7xl mx-auto px-4">

                <div class="text-center mb-10">
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Monitor Grup Real-time</h2>
                    <p class="text-gray-500">Pilih layanan di bawah untuk melihat ketersediaan slot.</p>
                </div>

                {{-- A. NAVIGASI PRODUK (TAB) --}}
                <div class="flex flex-nowrap overflow-x-auto gap-4 pb-6 mb-6 justify-start md:justify-center no-scrollbar"
                    style="scrollbar-width: none; -ms-overflow-style: none;">
                    @foreach ($productsWithGroups as $product)
                        <button @click="activeTab = {{ $product->id }}"
                            class="group flex flex-col items-center gap-3 min-w-[100px] transition-all duration-300 focus:outline-none">

                            {{-- Icon Wrapper --}}
                            <div class="w-16 h-16 rounded-2xl flex items-center justify-center p-3 transition-all duration-300 shadow-sm border-2"
                                :class="activeTab === {{ $product->id }} ?
                                    'bg-white border-indigo-600 ring-2 ring-indigo-100 scale-110 shadow-lg' :
                                    'bg-white border-gray-200 group-hover:border-indigo-300 group-hover:scale-105'">

                                @if ($product->image)
                                    <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}"
                                        class="w-full h-full object-contain">
                                @else
                                    <span class="text-xl font-bold transition-colors duration-300"
                                        :class="activeTab === {{ $product->id }} ? 'text-indigo-600' : 'text-gray-400'">
                                        {{ substr($product->name, 0, 1) }}
                                    </span>
                                @endif
                            </div>

                            {{-- Nama Produk --}}
                            <span class="text-xs font-bold transition-colors duration-300"
                                :class="activeTab === {{ $product->id }} ? 'text-indigo-600' : 'text-gray-500'">
                                {{ $product->name }}
                            </span>
                        </button>
                    @endforeach
                </div>

                {{-- B. KONTEN GRUP --}}
                <div class="min-h-[300px]">
                    @foreach ($productsWithGroups as $product)
                        <div x-show="activeTab === {{ $product->id }}"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">

                            <div class="flex justify-between items-center mb-6 px-1">
                                <h3 class="font-bold text-gray-800 text-lg">Grup {{ $product->name }}</h3>
                                <a href="{{ route('product.show', $product->id) }}"
                                    class="text-sm font-bold text-indigo-600 hover:underline flex items-center gap-1">
                                    Buat Grup Baru <span class="text-lg">&rarr;</span>
                                </a>
                            </div>

                            @php
                                $allGroups = $product->variants->flatMap->groups
                                    ->sortBy(function ($group) {
                                        return $group->status === 'open' ? 0 : 1;
                                    })
                                    ->take(6);
                            @endphp

                            @if ($allGroups->count() > 0)
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                                    @foreach ($allGroups as $group)
                                        @php
                                            $maxSlots = $group->variant->total_slots ?? 5;
                                            $filled = $group->orders->count();
                                            $empty = max(0, $maxSlots - $filled);
                                            $percentage = $maxSlots > 0 ? ($filled / $maxSlots) * 100 : 0;
                                            $isFull = $filled >= $maxSlots || $group->status !== 'open';
                                        @endphp

                                        {{-- CARD GRUP --}}
                                        <div
                                            class="relative rounded-xl border p-4 transition duration-300 flex flex-col justify-between
                                            {{ $isFull ? 'bg-gray-50 border-gray-200 opacity-90' : 'bg-white border-indigo-100 shadow-sm hover:shadow-md hover:border-indigo-300' }}">

                                            <div>
                                                {{-- Label Status --}}
                                                <div class="absolute top-3 right-3 z-10">
                                                    @if ($isFull)
                                                        <span
                                                            class="bg-gray-200 text-gray-600 text-[10px] font-bold px-2 py-1 rounded-full border border-gray-300">
                                                            FULL
                                                        </span>
                                                    @else
                                                        <span
                                                            class="bg-green-100 text-green-600 text-[10px] font-bold px-2 py-1 rounded-full border border-green-200 animate-pulse">
                                                            TERSEDIA
                                                        </span>
                                                    @endif
                                                </div>

                                                {{-- Info Produk --}}
                                                <div class="mb-3 pr-24">
                                                    <h4 class="font-bold text-gray-800 text-sm truncate"
                                                        title="{{ $group->variant->name }}">
                                                        {{ $group->variant->name }}
                                                    </h4>
                                                    <p class="text-[10px] text-gray-400 font-mono">ID: #{{ $group->id }}
                                                    </p>
                                                </div>

                                                {{-- Progress Bar --}}
                                                <div class="mb-4">
                                                    <div class="flex justify-between text-[10px] font-semibold mb-1">
                                                        <span class="{{ $isFull ? 'text-gray-500' : 'text-indigo-600' }}">
                                                            {{ $isFull ? 'Slot Penuh' : 'Sisa ' . $empty . ' Slot' }}
                                                        </span>
                                                        <span
                                                            class="text-gray-400">{{ $filled }}/{{ $maxSlots }}</span>
                                                    </div>
                                                    <div class="w-full bg-gray-200 rounded-full h-1.5 overflow-hidden">
                                                        <div class="h-1.5 rounded-full {{ $isFull ? 'bg-gray-400' : 'bg-indigo-500' }}"
                                                            style="width: {{ $isFull ? 100 : $percentage }}%"></div>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Footer: Avatar & Tombol --}}
                                            <div class="flex items-center justify-between gap-3 mt-2">
                                                {{-- Avatar Stack --}}
                                                <div class="flex -space-x-2 overflow-hidden py-1">
                                                    @foreach ($group->orders->take(4) as $order)
                                                        {{-- PERBAIKAN: Title di-masking (substr 0,3 + ***) --}}
                                                        <div class="w-7 h-7 rounded-full ring-2 ring-white flex items-center justify-center text-[9px] font-bold text-white shrink-0 cursor-help
                                                            {{ $isFull ? 'bg-gray-400' : 'bg-indigo-500' }}"
                                                            title="{{ substr($order->user->name, 0, 3) }}***">
                                                            {{ substr($order->user->name, 0, 1) }}
                                                        </div>
                                                    @endforeach

                                                    @if (!$isFull)
                                                        @for ($i = 0; $i < min(3, $empty); $i++)
                                                            <div
                                                                class="w-7 h-7 rounded-full ring-2 ring-white bg-white border border-dashed border-gray-300 flex items-center justify-center shrink-0 text-gray-300">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2" d="M12 4v16m8-8H4" />
                                                                </svg>
                                                            </div>
                                                        @endfor
                                                    @endif
                                                </div>

                                                {{-- Action Button --}}
                                                <div class="shrink-0">
                                                    @if ($isFull)
                                                        <button disabled
                                                            class="text-xs font-bold text-gray-400 bg-gray-100 px-4 py-2 rounded-lg cursor-not-allowed">
                                                            Full
                                                        </button>
                                                    @else
                                                        <a href="{{ route('product.show', $product->id) }}"
                                                            class="text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 px-4 py-2 rounded-lg transition shadow-sm hover:shadow-md flex items-center gap-1">
                                                            Join
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>

                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-12 bg-white rounded-xl border border-dashed border-gray-300">
                                    <p class="text-gray-500 text-sm">Belum ada grup aktif untuk layanan ini.</p>
                                    <a href="{{ route('product.show', $product->id) }}"
                                        class="text-indigo-600 font-bold text-sm hover:underline mt-2 inline-block">Jadilah
                                        yang pertama membuat!</a>
                                </div>
                            @endif
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
                            @php $minPrice = $product->variants->min('price'); @endphp
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
        <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-600 rounded-full blur-3xl opacity-20 translate-x-1/2"></div>

        <div class="max-w-7xl mx-auto px-4 relative z-10">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold mb-4">Gampang Banget!</h2>
                <p class="text-gray-400">Sistem kami berjalan otomatis 24/7 tanpa perlu menunggu admin manual.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-12 text-center">
                <div class="relative group">
                    <div
                        class="w-20 h-20 mx-auto bg-indigo-600 rounded-2xl flex items-center justify-center text-3xl font-bold mb-6 shadow-[0_0_20px_rgba(79,70,229,0.5)] group-hover:scale-110 transition duration-300">
                        1</div>
                    <h3 class="text-xl font-bold mb-3">Pilih & Bayar</h3>
                    <p class="text-gray-400 leading-relaxed">Pilih paket layanan yang kamu mau. Bayar instan via QRIS,
                        E-Wallet, atau Transfer Bank (Otomatis).</p>
                </div>
                <div class="relative group">
                    <div
                        class="w-20 h-20 mx-auto bg-purple-600 rounded-2xl flex items-center justify-center text-3xl font-bold mb-6 shadow-[0_0_20px_rgba(147,51,234,0.5)] group-hover:scale-110 transition duration-300">
                        2</div>
                    <h3 class="text-xl font-bold mb-3">Sistem Mencari Grup</h3>
                    <p class="text-gray-400 leading-relaxed">Kamu akan otomatis dimasukkan ke dalam grup patungan. Jika
                        grup penuh, akun langsung dikirim.</p>
                </div>
                <div class="relative group">
                    <div
                        class="w-20 h-20 mx-auto bg-green-500 rounded-2xl flex items-center justify-center text-3xl font-bold mb-6 shadow-[0_0_20px_rgba(34,197,94,0.5)] group-hover:scale-110 transition duration-300">
                        3</div>
                    <h3 class="text-xl font-bold mb-3">Login & Enjoy</h3>
                    <p class="text-gray-400 leading-relaxed">Cek Dashboard. Email & Password akun premium muncul di sana.
                        Tinggal login dan nonton!</p>
                </div>
            </div>
        </div>
    </div>
@endsection
