@extends('layouts.app-web')

@section('content')
    {{-- 1. HERO SECTION & PRODUCT INFO --}}
    <div class="relative bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white overflow-hidden">
        {{-- Background Ornament --}}
        <div
            class="absolute top-0 right-0 -translate-y-1/2 translate-x-1/2 w-96 h-96 bg-indigo-500 rounded-full blur-[100px] opacity-20">
        </div>
        <div
            class="absolute bottom-0 left-0 translate-y-1/2 -translate-x-1/2 w-64 h-64 bg-purple-500 rounded-full blur-[80px] opacity-20">
        </div>

        <div class="relative max-w-7xl mx-auto px-4 py-16 md:py-20">
            {{-- Breadcrumb --}}
            <nav class="flex items-center text-sm text-indigo-200 mb-8 space-x-2">
                <a href="{{ url('/') }}" class="hover:text-white transition">Home</a>
                <span>/</span>
                <span class="text-white font-semibold">{{ $product->name }}</span>
            </nav>

            <div class="flex flex-col md:flex-row items-start gap-10">
                {{-- Product Image --}}
                <div class="w-full md:w-auto shrink-0">
                    <div
                        class="w-40 h-40 md:w-56 md:h-56 bg-white/10 backdrop-blur-md rounded-3xl p-4 shadow-2xl border border-white/10 mx-auto">
                        @if ($product->image)
                            <img src="{{ Storage::url($product->image) }}"
                                class="w-full h-full object-contain drop-shadow-lg transform hover:scale-105 transition duration-500">
                        @else
                            {{-- Placeholder Image --}}
                            <div
                                class="w-full h-full flex items-center justify-center text-6xl font-bold text-white bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl shadow-inner">
                                {{ substr($product->name, 0, 1) }}
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Product Details --}}
                <div class="flex-1 text-center md:text-left">
                    <h1 class="text-4xl md:text-5xl font-extrabold mb-4 tracking-tight">{{ $product->name }}</h1>
                    <p class="text-indigo-100 text-lg mb-6 leading-relaxed max-w-2xl">
                        {{ $product->description ?? 'Layanan streaming premium legal, anti-hold, dan bergaransi penuh selama masa berlangganan.' }}
                    </p>

                    {{-- Badges --}}
                    <div class="flex flex-wrap justify-center md:justify-start gap-3">
                        <div
                            class="flex items-center gap-2 bg-white/10 border border-white/20 px-4 py-2 rounded-full backdrop-blur-sm">
                            <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="font-semibold text-sm">Legal 100%</span>
                        </div>
                        <div
                            class="flex items-center gap-2 bg-white/10 border border-white/20 px-4 py-2 rounded-full backdrop-blur-sm">
                            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                </path>
                            </svg>
                            <span class="font-semibold text-sm">Privasi Aman</span>
                        </div>
                        <div
                            class="flex items-center gap-2 bg-white/10 border border-white/20 px-4 py-2 rounded-full backdrop-blur-sm">
                            <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z">
                                </path>
                            </svg>
                            <span class="font-semibold text-sm">Full Garansi</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. VARIANTS SECTION --}}
    <div class="max-w-7xl mx-auto px-4 py-16 -mt-10 relative z-10">

        @if ($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-8 rounded-r-lg shadow-sm animate-pulse">
                <div class="flex">
                    <div class="shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700 font-bold">{{ $errors->first() }}</p>
                    </div>
                </div>
            </div>
        @endif

        <h2 class="text-2xl font-bold text-gray-900 mb-8 flex items-center gap-2">
            <span class="w-2 h-8 bg-indigo-600 rounded-full"></span>
            Pilih Paket Langganan
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach ($product->variants as $variant)
                {{-- Card Varian --}}
                <div
                    class="group bg-white rounded-2xl shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 border border-gray-100 overflow-hidden flex flex-col relative">

                    @if ($variant->duration_days >= 30)
                        <div
                            class="absolute top-0 right-0 bg-indigo-600 text-white text-[10px] font-bold px-3 py-1 rounded-bl-xl uppercase tracking-wider">
                            Best Seller
                        </div>
                    @endif

                    <div class="p-8 flex-1">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 group-hover:text-indigo-600 transition">
                                    {{ $variant->name }}</h3>
                                <p
                                    class="text-sm text-gray-500 font-medium bg-gray-100 inline-block px-2 py-1 rounded mt-1">
                                    ⏱ {{ $variant->duration_days }} Hari Aktif
                                </p>
                            </div>
                        </div>

                        <div class="flex items-baseline mb-6">
                            <span class="text-sm text-gray-500 font-semibold mr-1">Rp</span>
                            <span
                                class="text-4xl font-extrabold text-gray-900 tracking-tight">{{ number_format($variant->price, 0, ',', '.') }}</span>
                        </div>

                        <hr class="border-gray-100 mb-6">

                        {{-- === BAGIAN DINAMIS: FITUR PAKET === --}}
                        @if ($variant->features)
                            {{-- Jika Admin sudah mengisi fitur via RichEditor --}}
                            <div class="mb-8 text-sm text-gray-600 space-y-2">
                                {{-- Style khusus agar list HTML dari database tampil rapi --}}
                                <div
                                    class="[&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 [&_li]:mb-2 [&_p]:mb-2">
                                    {!! $variant->features !!}
                                </div>
                            </div>
                        @else
                            {{-- FALLBACK: Jika kosong, tampilkan default Hardcoded --}}
                            <ul class="space-y-4 mb-8">
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-green-500 mr-3 shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-600 text-sm">Akun Resmi (Bukan Curian)</span>
                                </li>
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-green-500 mr-3 shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-600 text-sm">Garansi Full {{ $variant->duration_days }}
                                        Hari</span>
                                </li>
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-green-500 mr-3 shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-600 text-sm">Sistem Patungan Otomatis</span>
                                </li>
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-green-500 mr-3 shrink-0" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="text-gray-600 text-sm">1 Device / User</span>
                                </li>
                            </ul>
                        @endif
                        {{-- === END BAGIAN FITUR === --}}

                    </div>

                    <div class="p-6 bg-gray-50 mt-auto border-t border-gray-100">
                        @if (auth()->check())
                            <form action="{{ route('checkout') }}" method="POST">
                                @csrf
                                <input type="hidden" name="product_variant_id" value="{{ $variant->id }}">

                                <div class="relative mb-3">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                        </svg>
                                    </div>
                                    <input type="text" name="promo_code" placeholder="Kode Promo (Opsional)"
                                        class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition uppercase placeholder-gray-400">
                                </div>

                                <button type="submit"
                                    class="w-full py-3.5 rounded-xl font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-lg hover:shadow-indigo-500/30 transition-all transform active:scale-95 flex items-center justify-center gap-2">
                                    <span>Beli Sekarang</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                    </svg>
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}"
                                class="block w-full text-center py-3.5 rounded-xl font-bold bg-gray-800 text-white hover:bg-gray-900 shadow-lg transition transform hover:-translate-y-1">
                                Login untuk Beli
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- 3. FAQ SECTION --}}
    <div class="bg-white py-16 border-t border-gray-100">
        <div class="max-w-4xl mx-auto px-4">
            <h2 class="text-2xl font-bold text-center mb-10 text-gray-900">Pertanyaan Umum</h2>
            <div class="space-y-4">
                <div class="border border-gray-200 rounded-xl p-5 hover:border-indigo-200 transition">
                    <h3 class="font-bold text-gray-800 mb-2">Apakah akun ini legal?</h3>
                    <p class="text-gray-600 text-sm">Tentu saja. Kami berlangganan paket Family/Premium resmi langsung ke
                        penyedia layanan. Bukan akun curian, carding, atau mod.</p>
                </div>
                <div class="border border-gray-200 rounded-xl p-5 hover:border-indigo-200 transition">
                    <h3 class="font-bold text-gray-800 mb-2">Bagaimana sistem garansinya?</h3>
                    <p class="text-gray-600 text-sm">Jika akun bermasalah (back to free/password salah) sebelum masa aktif
                        habis, silakan lapor di dashboard. Kami akan perbaiki atau ganti akun baru.</p>
                </div>
                <div class="border border-gray-200 rounded-xl p-5 hover:border-indigo-200 transition">
                    <h3 class="font-bold text-gray-800 mb-2">Berapa lama proses pengiriman?</h3>
                    <p class="text-gray-600 text-sm">Otomatis! Setelah pembayaran dikonfirmasi sistem, email dan password
                        akun akan langsung muncul di Dashboard kamu.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
