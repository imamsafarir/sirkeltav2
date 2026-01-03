@extends('layouts.app-web')

@section('content')
    @php
        // Cek apakah ini transaksi TOP UP atau BELI PRODUK
        $isTopUp = Illuminate\Support\Str::startsWith($order->invoice_number, 'TOP');
    @endphp

    <div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">

            <div class="text-center mb-10">
                <h2 class="text-3xl font-extrabold text-gray-900">
                    {{ $isTopUp ? 'Selesaikan Top Up' : 'Selesaikan Pembayaran' }}
                </h2>
                <p class="mt-2 text-gray-600">Invoice: <span class="font-mono font-bold">{{ $order->invoice_number }}</span>
                </p>
                <div class="mt-4 inline-block bg-indigo-50 px-6 py-2 rounded-full">
                    <span class="text-indigo-700 font-bold text-xl">Total: Rp
                        {{ number_format($order->amount, 0, ',', '.') }}</span>
                </div>
            </div>

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 text-center">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="grid grid-cols-1 {{ $isTopUp ? 'md:grid-cols-1 max-w-md mx-auto' : 'md:grid-cols-2' }} gap-8">

                @if (!$isTopUp)
                    <div
                        class="bg-white p-8 rounded-2xl shadow-lg border-2 {{ Auth::user()->wallet->balance >= $order->amount ? 'border-green-400' : 'border-gray-200' }} relative overflow-hidden">
                        @if (Auth::user()->wallet->balance >= $order->amount)
                            <div
                                class="absolute top-0 right-0 bg-green-500 text-white text-xs px-3 py-1 rounded-bl-lg font-bold">
                                Recommended</div>
                        @endif

                        <div class="flex items-center gap-4 mb-6">
                            <div class="bg-green-100 p-3 rounded-full">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
                                    </path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-800">Saldo Dompet</h3>
                                <p class="text-sm text-gray-500">Bayar instan tanpa ribet</p>
                            </div>
                        </div>

                        <div class="mb-6 p-4 bg-gray-50 rounded-xl">
                            <p class="text-gray-500 text-sm">Saldo Anda:</p>
                            <p
                                class="text-2xl font-bold {{ Auth::user()->wallet->balance >= $order->amount ? 'text-green-600' : 'text-red-500' }}">
                                Rp {{ number_format(Auth::user()->wallet->balance, 0, ',', '.') }}
                            </p>
                        </div>

                        @if (Auth::user()->wallet->balance >= $order->amount)
                            <form action="{{ route('payment.wallet', $order->invoice_number) }}" method="POST">
                                @csrf
                                <button type="submit"
                                    class="w-full bg-green-600 text-white py-3 rounded-xl font-bold hover:bg-green-700 transition shadow-lg flex justify-center items-center gap-2 transform hover:scale-105">
                                    Bayar Pakai Saldo
                                </button>
                            </form>
                        @else
                            <div class="space-y-3">
                                <button disabled
                                    class="w-full bg-gray-200 text-gray-400 py-3 rounded-xl font-bold cursor-not-allowed">
                                    Saldo Tidak Cukup
                                </button>
                                <a href="{{ route('topup.form') }}"
                                    class="block w-full text-center border-2 border-indigo-600 text-indigo-600 py-3 rounded-xl font-bold hover:bg-indigo-50 transition">
                                    + Isi Saldo (Top Up)
                                </a>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="bg-white p-8 rounded-2xl shadow-lg border-2 border-indigo-50 relative">
                    {{-- PERBAIKAN 1: Hapus class 'flex' di sini --}}
                    <div id="loading-overlay"
                        class="hidden absolute inset-0 bg-white bg-opacity-80 z-10 justify-center items-center rounded-2xl">
                        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600"></div>
                    </div>

                    <div class="flex items-center gap-4 mb-6">
                        <div class="bg-indigo-100 p-3 rounded-full">
                            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">
                                {{ $isTopUp ? 'Metode Pembayaran' : 'Transfer Langsung' }}
                            </h3>
                            <p class="text-sm text-gray-500">QRIS, Virtual Account, E-Wallet</p>
                        </div>
                    </div>

                    <div class="mb-6">
                        <div class="flex flex-wrap gap-2">
                            <span class="bg-gray-100 text-xs px-3 py-1 rounded-full text-gray-600 font-semibold">BCA</span>
                            <span
                                class="bg-gray-100 text-xs px-3 py-1 rounded-full text-gray-600 font-semibold">Mandiri</span>
                            <span class="bg-gray-100 text-xs px-3 py-1 rounded-full text-gray-600 font-semibold">QRIS</span>
                        </div>
                    </div>

                    <button id="pay-midtrans-btn"
                        class="w-full bg-indigo-600 text-white py-4 rounded-xl font-bold hover:bg-indigo-700 transition shadow-lg flex justify-center items-center gap-2 transform hover:scale-105">
                        {{ $isTopUp ? 'Lanjut Top Up' : 'Bayar Sekarang' }}
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>
                    <p class="text-xs text-center text-gray-400 mt-3">Diproses otomatis oleh Midtrans.</p>
                </div>

            </div>
        </div>
    </div>

    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('midtrans.client_key') }}">
    </script>

    <script type="text/javascript">
        const payButton = document.getElementById('pay-midtrans-btn');
        const loadingOverlay = document.getElementById('loading-overlay');

        // Cek apakah Snap Token sudah ada
        const existingSnapToken = "{{ $order->payment_url ?? '' }}";

        payButton.onclick = function() {
            if (existingSnapToken && existingSnapToken.length > 10) {
                startSnap(existingSnapToken);
                return;
            }

            // PERBAIKAN 2: Tambahkan 'flex' lewat JS saat mau ditampilkan
            loadingOverlay.classList.remove('hidden');
            loadingOverlay.classList.add('flex'); // <--- PENTING

            payButton.disabled = true;

            fetch("{{ route('payment.midtrans', $order->invoice_number) }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    // PERBAIKAN 3: Hapus 'flex' saat disembunyikan lagi
                    loadingOverlay.classList.remove('flex'); // <--- PENTING
                    loadingOverlay.classList.add('hidden');

                    payButton.disabled = false;

                    if (data.error) {
                        alert("Error: " + data.error);
                        return;
                    }
                    startSnap(data.snap_token);
                })
                .catch(error => {
                    console.error('Error:', error);

                    // PERBAIKAN 4: Hapus 'flex' saat error juga
                    loadingOverlay.classList.remove('flex'); // <--- PENTING
                    loadingOverlay.classList.add('hidden');

                    payButton.disabled = false;
                    alert("Gagal memuat pembayaran.");
                });
        };

        function startSnap(token) {
            snap.pay(token, {
                onSuccess: function(result) {
                    window.location.href = "{{ route('dashboard') }}";
                },
                onPending: function(result) {
                    window.location.href = "{{ route('dashboard') }}";
                },
                onError: function(result) {
                    alert("Pembayaran Gagal!");
                    location.reload();
                },
                onClose: function() {
                    console.log('Popup closed');
                }
            });
        }
    </script>
@endsection
