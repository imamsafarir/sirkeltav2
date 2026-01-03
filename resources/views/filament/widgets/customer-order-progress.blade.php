<x-filament::widget>
    <x-filament::section>

        @if (!$order)
            <div class="text-center py-4">
                <p class="text-gray-500 dark:text-gray-400">Belum ada pesanan aktif.</p>
                <a href="/" class="text-indigo-600 font-bold hover:underline">Belanja Sekarang &rarr;</a>
            </div>
        @else
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                <div>
                    <h2 class="text-lg font-bold text-gray-800 dark:text-white">Status Pesanan Terkini</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{-- PERBAIKAN DI SINI: Cek Tipe Order --}}
                        Produk: <span class="font-bold" style="color: #4f46e5;">
                            @if ($order->type === 'topup')
                                Top Up Saldo
                            @else
                                {{-- Gunakan tanda tanya (?) biar aman kalau data produk terhapus --}}
                                {{ $order->variant?->product?->name ?? 'Produk Tidak Ditemukan' }}
                            @endif
                        </span>
                        <br class="md:hidden">
                        <span class="hidden md:inline">|</span>
                        Invoice: {{ $order->invoice_number }}
                    </p>
                </div>

                <div class="mt-2 md:mt-0">
                    @if ($order->status === 'pending')
                        <x-filament::button tag="a" href="{{ route('payment.show', $order->invoice_number) }}"
                            color="success" icon="heroicon-m-credit-card" style="background-color: #22c55e;">
                            Bayar Sekarang
                        </x-filament::button>
                    @elseif($order->status === 'completed' || $order->status === 'paid')
                        <span class="px-3 py-1 text-xs font-bold rounded-full"
                            style="background-color: #dcfce7; color: #15803d;"> Selesai </span>
                    @endif
                </div>
            </div>

            @php
                $progress = 0;
                $status = $order->status;
                if ($status === 'pending') {
                    $progress = 10;
                }
                if ($status === 'paid') {
                    $progress = 40;
                }
                if ($status === 'processing') {
                    $progress = 70;
                }
                if ($status === 'completed') {
                    $progress = 100;
                }

                $colorGray = '#e5e7eb';
                $colorGreen = '#22c55e';
                $colorTextGray = '#9ca3af';
                $colorTextGreen = '#16a34a';
            @endphp

            <div class="relative w-full mb-8 mt-4">
                <div class="absolute top-1/2 left-0 w-full h-2 rounded-full -translate-y-1/2"
                    style="background-color: {{ $colorGray }};"></div>
                <div class="absolute top-1/2 left-0 h-2 rounded-full -translate-y-1/2 transition-all duration-1000 shadow-md"
                    style="width: {{ $progress }}%; background-color: {{ $colorGreen }};"></div>
                <div class="relative flex justify-between w-full">
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 flex items-center justify-center rounded-full z-10 border-2 border-white dark:border-gray-800 shadow-sm"
                            style="background-color: {{ $progress >= 10 ? $colorGreen : $colorGray }}; color: {{ $progress >= 10 ? 'white' : '#6b7280' }};">
                            1 </div>
                        <span class="text-xs font-bold mt-2"
                            style="color: {{ $progress >= 10 ? $colorTextGreen : $colorTextGray }};"> Order </span>
                    </div>
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 flex items-center justify-center rounded-full z-10 border-2 border-white dark:border-gray-800 shadow-sm"
                            style="background-color: {{ $progress >= 40 ? $colorGreen : $colorGray }}; color: {{ $progress >= 40 ? 'white' : '#6b7280' }};">
                            2 </div>
                        <span class="text-xs font-bold mt-2"
                            style="color: {{ $progress >= 40 ? $colorTextGreen : $colorTextGray }};"> Bayar </span>
                    </div>
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 flex items-center justify-center rounded-full z-10 border-2 border-white dark:border-gray-800 shadow-sm"
                            style="background-color: {{ $progress >= 70 ? $colorGreen : $colorGray }}; color: {{ $progress >= 70 ? 'white' : '#6b7280' }};">
                            3 </div>
                        <span class="text-xs font-bold mt-2 text-center"
                            style="color: {{ $progress >= 70 ? $colorTextGreen : $colorTextGray }};"> Proses </span>
                    </div>
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 flex items-center justify-center rounded-full z-10 border-2 border-white dark:border-gray-800 shadow-sm"
                            style="background-color: {{ $progress >= 100 ? $colorGreen : $colorGray }}; color: {{ $progress >= 100 ? 'white' : '#6b7280' }};">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                        </div>
                        <span class="text-xs font-bold mt-2"
                            style="color: {{ $progress >= 100 ? $colorTextGreen : $colorTextGray }};"> Selesai </span>
                    </div>
                </div>
            </div>

            <div
                class="mt-6 p-4 rounded-lg border text-sm text-center bg-gray-50 dark:bg-gray-800 dark:border-gray-700">
                @if ($status === 'pending')
                    <p class="font-bold text-lg" style="color: #ef4444;">⏳ Menunggu Pembayaran</p>
                    <p class="text-gray-600 dark:text-gray-300 mt-1">Silakan klik tombol "Bayar Sekarang" di atas untuk
                        menyelesaikan transaksi.</p>
                @elseif($status === 'paid')
                    <p class="font-bold text-lg" style="color: #3b82f6;">💰 Pembayaran Dikonfirmasi!</p>
                    <p class="text-gray-600 dark:text-gray-300 mt-1">Admin akan segera memproses pesanan Anda.</p>
                @else
                    {{-- Status lainnya tetap sama --}}
                    <p class="font-bold text-lg" style="color: #8b5cf6;">⚙️ Sedang Diproses</p>
                @endif
            </div>
        @endif
    </x-filament::section>
</x-filament::widget>
