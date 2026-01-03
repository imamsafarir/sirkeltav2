<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Midtrans\Config;
use Midtrans\Snap;
use App\Models\Order; // <--- PENTING: Pakai Model Order

class TopUpWallet extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Isi Saldo';
    protected static ?string $title = 'Top Up Saldo';
    protected static string $view = 'filament.pages.top-up-wallet';

    // Variabel untuk menampung input form
    public ?array $data = [];

    public function mount(): void
    {
        // Inisialisasi form kosong
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Formulir Top Up')
                    ->description('Masukan nominal saldo yang ingin Anda isi.')
                    ->schema([
                        TextInput::make('amount')
                            ->label('Nominal Top Up')
                            ->prefix('Rp')
                            ->numeric()
                            ->minValue(10000)
                            ->required()
                            ->placeholder('Min. 10.000')
                            ->helperText('Saldo akan bertambah otomatis setelah pembayaran berhasil.'),
                    ])
            ])
            ->statePath('data');
    }

    public function create()
    {
        // 1. Ambil Data Form
        $data = $this->form->getState();
        $amount = $data['amount'];

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 2. Buat Invoice Unik (TOPUP-XXX)
        $invoiceNumber = 'TOPUP-' . strtoupper(Str::random(6)) . date('dmY');

        // 3. SIMPAN KE TABEL ORDERS (REVISI BARU)
        // Kita tidak lagi pakai wallet->transactions(), tapi pakai Order::create
        $order = Order::create([
            'user_id' => $user->id,
            'type' => 'topup', // Penanda ini Top Up
            'invoice_number' => $invoiceNumber,
            'amount' => $amount,
            'status' => 'pending',
            'description' => 'Top Up Saldo via Dashboard Admin',
            'group_id' => null,
            'product_variant_id' => null,
        ]);

        // 4. Konfigurasi Midtrans
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');

        $params = [
            'transaction_details' => [
                'order_id' => $invoiceNumber,
                'gross_amount' => (int) $amount,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
            ],
        ];

        try {
            // 5. Minta Snap Token
            $snapToken = Snap::getSnapToken($params);

            // 6. Update Token ke Database Order
            $order->update(['payment_url' => $snapToken]);

            // 7. Redirect Customer ke Halaman Payment Kita
            return redirect()->route('payment.show', $invoiceNumber);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal Memproses')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
