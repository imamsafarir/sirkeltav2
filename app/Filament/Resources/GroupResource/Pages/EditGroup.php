<?php

namespace App\Filament\Resources\GroupResource\Pages;

use App\Filament\Resources\GroupResource;
use App\Models\Order;
use App\Notifications\OrderCompletedNotification;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification as FilamentNotification;

class EditGroup extends EditRecord
{
    protected static string $resource = GroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Tombol 1: Hapus Grup (Bawaan)
            Actions\DeleteAction::make(),

            // Tombol 2: KIRIM NOTIFIKASI KE CUSTOMER (Fitur Kamu)
            Actions\Action::make('sendCredentials')
                ->label('Kirim Akun ke Peserta')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Kirim Akun?')
                ->modalDescription('Pastikan Email & Password sudah diisi dengan benar. Semua peserta grup ini akan menerima email.')
                ->visible(fn($record) => in_array($record->status, ['processing', 'completed']))
                ->action(function ($record) {

                    // Ambil semua peserta yang sudah bayar
                    $orders = $record->orders()->whereIn('status', ['paid', 'completed'])->get();

                    foreach ($orders as $order) {
                        // Ubah status order jadi completed
                        $order->update(['status' => 'completed']);

                        // Kirim Notifikasi Email
                        // Pastikan User model punya trait Notifiable, kalau error notifikasi, komen baris ini dulu
                        try {
                            $order->user->notify(new OrderCompletedNotification($order));
                        } catch (\Exception $e) {
                            // Abaikan error email jika setting mail belum benar, biar gak crash
                        }
                    }

                    // Ubah status grup jadi completed
                    $record->update(['status' => 'completed']);

                    // Beritahu Admin bahwa sukses
                    FilamentNotification::make()
                        ->title('Sukses!')
                        ->body('Akun berhasil dikirim ke ' . $orders->count() . ' peserta.')
                        ->success()
                        ->send();
                }),
        ];
    }

    // --- SAYA TAMBAHKAN INI (LOGIKA SINKRONISASI STATUS) ---
    // Ini berjalan otomatis saat tombol "Save" ditekan
    protected function afterSave(): void
    {
        $group = $this->getRecord();

        // 1. Logika Status Processing
        if ($group->status === 'processing') {
            $group->orders()
                ->where('status', 'paid')
                ->update(['status' => 'processing']);
        }

        // 2. Logika Status Completed (UPDATE DURASI & NOTIFIKASI)
        if ($group->status === 'completed') {
            // A. Update Status Order
            $group->orders()
                ->whereIn('status', ['paid', 'processing'])
                ->update(['status' => 'completed']);

            // B. Update Tanggal Expired
            // Kita paksa refresh data varian dulu
            $group->load('productVariant');
            $variant = $group->productVariant;

            // Pastikan varian ada dan punya durasi
            if ($variant && $variant->duration_days > 0) {
                $durasi = (int) $variant->duration_days;
                $newExpired = now()->addDays($durasi);

                // Update Paksa via Query Builder (Bypass Model biar pasti tersimpan)
                \App\Models\Group::where('id', $group->id)->update([
                    'expired_at' => $newExpired
                ]);

                // C. Beri Notifikasi ke Admin (Biar kamu tahu ini BERHASIL)
                FilamentNotification::make()
                    ->title('Durasi Diperpanjang Otomatis!')
                    ->body("Ditambah {$durasi} hari. Expired baru: " . $newExpired->format('d M Y H:i'))
                    ->success()
                    ->send();
            }
        }

        // 3. Logika Expired/Closed
        if (in_array($group->status, ['expired', 'closed'])) {
            $group->orders()
                ->whereIn('status', ['paid', 'pending'])
                ->update(['status' => 'failed']);
        }
    }
}
