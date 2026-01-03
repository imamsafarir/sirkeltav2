@extends('layouts.app-web')

@section('content')
    <div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8 flex justify-center items-center">
        <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-xl shadow-lg">
            <div class="text-center">
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Isi Saldo Dompet</h2>
                <p class="mt-2 text-sm text-gray-600">
                    Saldo saat ini: <span class="font-bold text-green-600">Rp
                        {{ number_format(Auth::user()->wallet->balance, 0, ',', '.') }}</span>
                </p>
            </div>

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ $errors->first() }}
                </div>
            @endif

            <form class="mt-8 space-y-6" action="{{ route('topup.process') }}" method="POST">
                @csrf
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="amount" class="sr-only">Nominal Top Up</label>
                        <input id="amount" name="amount" type="number" min="10000" required
                            class="appearance-none rounded-none relative block w-full px-3 py-4 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm text-center text-xl font-bold"
                            placeholder="Masukan Nominal (Min 10.000)">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 mt-4">
                    <button type="button" onclick="setAmount(50000)"
                        class="bg-gray-100 hover:bg-gray-200 py-2 rounded text-sm font-semibold text-gray-700">Rp
                        50.000</button>
                    <button type="button" onclick="setAmount(100000)"
                        class="bg-gray-100 hover:bg-gray-200 py-2 rounded text-sm font-semibold text-gray-700">Rp
                        100.000</button>
                </div>

                <div>
                    <button type="submit"
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Lanjut Pembayaran
                    </button>
                </div>
            </form>

            <div class="text-center mt-4">
                <a href="{{ route('dashboard') }}" class="text-sm text-indigo-600 hover:text-indigo-500 font-medium">
                    &larr; Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>

    <script>
        function setAmount(amount) {
            document.getElementById('amount').value = amount;
        }
    </script>
@endsection
