<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'SirkelTa') }} - Patungan Akun Premium</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script src="https://cdn.tailwindcss.com"></script>
    @livewireStyles
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="font-sans antialiased text-gray-900 bg-white">

    {{-- === 1. NAVBAR PREMIUM (Sticky & Glass Effect) === --}}
    {{-- Menggunakan x-data untuk efek scroll --}}
    <nav x-data="{ scrolled: false }" @scroll.window="scrolled = (window.pageYOffset > 20)"
        :class="scrolled ? 'bg-white/90 backdrop-blur-md shadow-md border-b border-gray-200' :
            'bg-transparent border-transparent'"
        class="fixed w-full z-50 top-0 start-0 transition-all duration-300" id="navbar">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">

                {{-- Logo --}}
                <a href="{{ url('/') }}" class="flex items-center gap-2 group">
                    <div
                        class="bg-indigo-600 text-white p-2 rounded-xl font-extrabold text-xl shadow-lg group-hover:scale-110 transition duration-300">
                        ST
                    </div>
                    <span class="font-bold text-2xl tracking-tight text-gray-800">Sirkel<span
                            class="text-indigo-600">Ta</span></span>
                </a>

                {{-- Menu Desktop --}}
                <div class="hidden md:flex items-center space-x-8">
                    <a href="{{ url('/') }}#katalog"
                        class="text-gray-600 hover:text-indigo-600 font-medium transition text-sm uppercase tracking-wide">Produk</a>
                    <a href="{{ url('/') }}#live-groups"
                        class="text-gray-600 hover:text-indigo-600 font-medium transition text-sm uppercase tracking-wide">Grup
                        Aktif</a>
                    <a href="{{ url('/') }}#cara-kerja"
                        class="text-gray-600 hover:text-indigo-600 font-medium transition text-sm uppercase tracking-wide">Cara
                        Kerja</a>
                </div>

                {{-- Tombol Login/Dashboard (DESKTOP) --}}
                <div class="hidden md:flex items-center gap-3">
                    @auth
                        <div class="relative group">
                            <button
                                class="flex items-center gap-2 text-gray-700 font-semibold hover:text-indigo-600 transition">
                                <span>Halo, {{ Auth::user()->name }}</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            {{-- Dropdown User --}}
                            <div
                                class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-gray-100 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 transform origin-top-right">
                                {{-- Link ke Admin Dashboard --}}
                                <a href="{{ url('/admin') }}"
                                    class="block px-4 py-3 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-t-xl">
                                    Dashboard
                                </a>
                                {{-- Logout Filament --}}
                                <form method="POST" action="{{ route('filament.admin.auth.logout') }}">
                                    @csrf
                                    <button type="submit"
                                        class="block w-full text-left px-4 py-3 text-sm text-red-600 hover:bg-red-50 rounded-b-xl">
                                        Keluar
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        {{-- Login Filament --}}
                        <a href="{{ route('filament.admin.auth.login') }}"
                            class="text-gray-600 hover:text-indigo-600 font-bold px-4 transition">
                            Masuk
                        </a>

                        {{-- Register Filament (Cek route dulu) --}}
                        @if (Route::has('filament.admin.auth.register'))
                            <a href="{{ route('filament.admin.auth.register') }}"
                                class="bg-indigo-600 text-white px-6 py-2.5 rounded-full font-bold shadow-lg hover:bg-indigo-700 hover:shadow-indigo-500/30 transition transform hover:-translate-y-0.5">
                                Daftar Sekarang
                            </a>
                        @endif
                    @endauth
                </div>

                {{-- Tombol Mobile Menu (Hamburger) --}}
                <div class="md:hidden flex items-center">
                    <button id="mobile-menu-btn" class="text-gray-600 hover:text-indigo-600 focus:outline-none">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16m-7 6h7"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Mobile Menu Dropdown --}}
        <div id="mobile-menu"
            class="hidden md:hidden bg-white border-b border-gray-100 shadow-lg absolute w-full left-0 top-20 transition-all">
            <div class="px-4 py-6 space-y-4">
                <a href="#katalog" class="block text-gray-600 font-medium hover:text-indigo-600">Produk</a>
                <a href="#live-groups" class="block text-gray-600 font-medium hover:text-indigo-600">Grup Aktif</a>
                <a href="#cara-kerja" class="block text-gray-600 font-medium hover:text-indigo-600">Cara Kerja</a>

                <div class="border-t border-gray-100 pt-4 mt-4">
                    @auth
                        <a href="{{ url('/admin') }}"
                            class="block w-full text-center bg-indigo-50 text-indigo-700 py-3 rounded-xl font-bold mb-2">
                            Dashboard Saya
                        </a>
                        {{-- Logout Mobile (Filament Route) --}}
                        <form method="POST" action="{{ route('filament.admin.auth.logout') }}">
                            @csrf
                            <button class="block w-full text-center text-red-600 font-bold py-2">Keluar</button>
                        </form>
                    @else
                        {{-- Login Mobile (Filament Route) --}}
                        <a href="{{ route('filament.admin.auth.login') }}"
                            class="block w-full text-center text-gray-600 font-bold py-2">
                            Masuk
                        </a>

                        {{-- Register Mobile (Filament Route) --}}
                        @if (Route::has('filament.admin.auth.register'))
                            <a href="{{ route('filament.admin.auth.register') }}"
                                class="block w-full text-center bg-indigo-600 text-white py-3 rounded-xl font-bold mt-2">
                                Daftar
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    {{-- === 2. KONTEN HALAMAN === --}}
    <main class="pt-20">
        @yield('content')
    </main>

    {{-- === 3. FOOTER GLOBAL === --}}
    <footer class="bg-gray-50 border-t border-gray-200 pt-16 pb-8">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
                <div class="col-span-1 md:col-span-1">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="bg-indigo-600 text-white p-1 rounded font-bold text-lg">ST</div>
                        <span class="font-bold text-xl text-gray-900">Sirkel<span
                                class="text-indigo-600">Ta</span></span>
                    </div>
                    <p class="text-gray-500 text-sm leading-relaxed">
                        Platform patungan akun premium nomor #1 di Indonesia. Aman, Legal, dan Terpercaya sejak 2024.
                    </p>
                </div>

                <div>
                    <h4 class="font-bold text-gray-900 mb-4">Produk Populer</h4>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li><a href="#" class="hover:text-indigo-600">Netflix Premium</a></li>
                        <li><a href="#" class="hover:text-indigo-600">Spotify Family</a></li>
                        <li><a href="#" class="hover:text-indigo-600">Youtube Premium</a></li>
                        <li><a href="#" class="hover:text-indigo-600">Canva Pro</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold text-gray-900 mb-4">Bantuan</h4>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li><a href="#" class="hover:text-indigo-600">Cara Pembelian</a></li>
                        <li><a href="#" class="hover:text-indigo-600">Ketentuan Garansi</a></li>
                        <li><a href="#" class="hover:text-indigo-600">Kontak Admin</a></li>
                        <li><a href="#" class="hover:text-indigo-600">Lapor Masalah</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold text-gray-900 mb-4">Metode Pembayaran</h4>
                    <div class="flex flex-wrap gap-2">
                        <span class="bg-white border px-2 py-1 rounded text-xs font-bold text-gray-600">QRIS</span>
                        <span class="bg-white border px-2 py-1 rounded text-xs font-bold text-gray-600">Gopay</span>
                        <span class="bg-white border px-2 py-1 rounded text-xs font-bold text-gray-600">Dana</span>
                        <span class="bg-white border px-2 py-1 rounded text-xs font-bold text-gray-600">BCA</span>
                        <span class="bg-white border px-2 py-1 rounded text-xs font-bold text-gray-600">Mandiri</span>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 pt-8 text-center text-sm text-gray-400">
                &copy; {{ date('Y') }} SirkelTa. All rights reserved. Made with ❤️ for community.
            </div>
        </div>
    </footer>

    @livewireScripts

    {{-- Script untuk Mobile Menu --}}
    <script>
        const btn = document.getElementById('mobile-menu-btn');
        const menu = document.getElementById('mobile-menu');

        btn.addEventListener('click', () => {
            menu.classList.toggle('hidden');
        });
    </script>
</body>

</html>
