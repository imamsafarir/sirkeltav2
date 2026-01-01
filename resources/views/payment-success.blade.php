@extends('layouts.app-web')

@section('content')
    <div class="min-h-screen bg-gray-50 flex flex-col justify-center items-center px-4">
        <div class="bg-white p-8 rounded-2xl shadow-xl max-w-md w-full text-center">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <h1 class="text-3xl font-bold text-gray-900 mb-2">Pembayaran Berhasil!</h1>
            <p class="text-gray-500 mb-8">
                Terima kasih. Sistem kami telah menerima pembayaran Anda. Status pesanan Anda sekarang sedang
                <span class="font-bold text-gray-800">Menunggu Grup Penuh</span>.
            </p>

            <div class="space-y-3">
                <a href="/admin"
                    class="block w-full bg-indigo-600 text-white py-3 rounded-xl font-bold hover:bg-indigo-700 transition shadow-lg">
                    Cek Status Pesanan
                </a>
                <a href="/"
                    class="block w-full bg-white text-gray-600 py-3 rounded-xl font-bold border border-gray-200 hover:bg-gray-50 transition">
                    Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
@endsection
