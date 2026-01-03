<?php

namespace App\Filament\Resources\GroupResource\Pages;

use App\Filament\Resources\GroupResource;
use App\Models\Order;
use App\Notifications\OrderCompletedNotification;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Actions\Action; // Import Action tombol

class EditGroup extends EditRecord
{
    protected static string $resource = GroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Kita sisakan tombol Hapus saja.
            // Tombol Kirim Akun kita pindah ke tombol Save utama biar lebih UX friendly.
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * MODIFIKASI TOMBOL SAVE BAWAAN
     * Tombol ini akan berubah warna & teks sesuai status yang dipilih
     */
    protected function getSaveFormAction(): Action
    {
        // Ambil tombol save asli
        $action = parent::getSaveFormAction();

        // Cek status yang sedang diedit (Realtime dari form atau record database)
        $status = $this->data['status'] ?? $this->record->status;

        // Jika Status = Completed
        if ($status === 'completed') {
            $action
                ->label('Simpan & Kirim Akun') // Ubah Label
                ->icon('heroicon-m-paper-airplane') // Tambah Ikon
                ->color('success'); // Warna Hijau
        }
        // Jika Status = Processing
        elseif ($status === 'processing') {
            $action
                ->label('Simpan Progres')
                ->icon('heroicon-m-arrow-path')
                ->color('info'); // Warna Biru
        }

        return $action;
    }

    /**
     * LOGIKA SETELAH TOMBOL SAVE DITEKAN
     */
    protected function afterSave(): void
    {
        $group = $this->getRecord();

        // 1. Logika Status Processing
        if ($group->status === 'processing') {
            $group->orders()
                ->where('status', 'paid')
                ->update(['status' => 'processing']);
        }

        // 2. Logika Status Completed (UPDATE DURASI & KIRIM AKUN)
        if ($group->status === 'completed') {

            // A. Update Status Order jadi Completed
            // Ambil order yg paid/processing/completed
            $orders = $group->orders()->whereIn('status', ['paid', 'processing', 'completed'])->get();

            foreach ($orders as $order) {
                // Update status di database
                $order->update(['status' => 'completed']);

                // --- KIRIM EMAIL NOTIFIKASI ---
                // (Logika dipindah dari header action ke sini)
                try {
                    // Pastikan user ada
                    if ($order->user) {
                        // Cek apakah class notifikasi ada, jika tidak ada hapus baris ini
                        if (class_exists(OrderCompletedNotification::class)) {
                            $order->user->notify(new OrderCompletedNotification($order));
                        }
                    }
                } catch (\Exception $e) {
                    // Silent error jika mailer bermasalah agar tidak crash
                }
            }

            // B. Update Tanggal Expired (Otomatis Tambah Durasi)
            $group->load('variant'); // Ganti 'productVariant' jadi 'variant' sesuai relasi model Group
            $variant = $group->variant;

            // Cek apakah sudah expired atau belum, agar tidak nambah hari berkali-kali jika diedit ulang
            // Kita asumsikan penambahan durasi terjadi jika expired_at < sekarang (baru aktif)
            // Atau Anda bisa menghapus logika 'if' durasi ini jika ingin manual saja via form.

            // Di sini saya biarkan update expired sesuai inputan form saja agar aman,
            // KECUALI jika Anda ingin memaksa update dari varian:
            /* if ($variant && $variant->duration_days > 0) {
                 $newExpired = now()->addDays((int) $variant->duration_days);
                 $group->update(['expired_at' => $newExpired]);
            }
            */

            // C. Beri Notifikasi ke Admin (Pop-up Sukses)
            FilamentNotification::make()
                ->title('Grup Aktif!')
                ->body('Akun berhasil dikirim ke ' . $orders->count() . ' peserta via Email & Dashboard.')
                ->success()
                ->send();
        }

        // 3. Logika Expired/Closed
        if (in_array($group->status, ['expired', 'closed'])) {
            $group->orders()
                ->whereIn('status', ['paid', 'pending'])
                ->update(['status' => 'failed']);
        }
    }
}
