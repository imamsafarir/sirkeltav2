<x-filament-panels::page>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="p-6 bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                Saldo Anda
            </h5>
            <p class="font-normal text-gray-700 dark:text-gray-400">
                Dompet Digital
            </p>
            <div class="mt-4 text-3xl font-extrabold text-indigo-600 dark:text-indigo-400">
                Rp {{ number_format(Auth::user()->wallet->balance, 0, ',', '.') }}
            </div>
        </div>

    </div>

    <form wire:submit="create">
        {{ $this->form }}

        <div class="mt-6 flex justify-end">
            <x-filament::button type="submit" size="lg">
                Lanjut Pembayaran
            </x-filament::button>
        </div>
    </form>

</x-filament-panels::page>
