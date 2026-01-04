<?php

namespace App\Filament\Resources\GroupResource\Pages;

use App\Filament\Resources\GroupResource;
use App\Notifications\OrderCompletedNotification;
use App\Models\Order;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Actions\Action;

class EditGroup extends EditRecord
{
    protected static string $resource = GroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * 1. TETAP DI HALAMAN EDIT
     * Setelah menekan tombol simpan, Admin tidak akan dilempar kembali ke tabel,
     * sehingga bisa memeriksa apakah durasi sudah bertambah atau belum.
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    /**
     * 2. MODIFIKASI TOMBOL SAVE (Dinamis & Realtime)
     * Menggunakan getRawState() agar label, warna, dan ikon tombol
     * berubah langsung saat Admin mengganti pilihan di dropdown status.
     */
    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label(function () {
                $status = $this->form->getRawState()['status'] ?? $this->record->status;

                return match ($status) {
                    'completed' => 'Simpan & Kirim Akun',
                    'processing' => 'Simpan Progres',
                    default => 'Simpan Perubahan',
                };
            })
            ->icon(function () {
                $status = $this->form->getRawState()['status'] ?? $this->record->status;
                return $status === 'completed' ? 'heroicon-m-paper-airplane' : 'heroicon-m-check';
            })
            ->color(function () {
                $status = $this->form->getRawState()['status'] ?? $this->record->status;
                return match ($status) {
                    'completed' => 'success',
                    'processing' => 'info',
                    default => 'primary',
                };
            });
    }

    /**
     * 3. LOGIKA SETELAH SIMPAN
     * Di sini kita menangani update status order peserta, pengiriman email,
     * dan penambahan otomatis masa aktif (durasi).
     */
    protected function afterSave(): void
    {
        $group = $this->getRecord();

        // --- A. LOGIKA JIKA STATUS COMPLETED ---
        if ($group->status === 'completed') {

            // 1. Ambil & Update Semua Order Peserta yang valid (Paid/Processing)
            $orders = $group->orders()->whereIn('status', ['paid', 'processing'])->get();

            foreach ($orders as $order) {
                $order->update(['status' => 'completed']);

                // Kirim Notifikasi Email
                try {
                    if ($order->user && class_exists(OrderCompletedNotification::class)) {
                        $order->user->notify(new OrderCompletedNotification($order));
                    }
                } catch (\Exception $e) {
                    // Log error jika diperlukan, tapi jangan biarkan aplikasi crash
                }
            }

            // 2. UPDATE DURASI (Masa Aktif)
            // Mengambil durasi (hari) dari varian produk yang dipilih
            $group->load('variant');
            $variant = $group->variant;

            if ($variant && $variant->duration_days > 0) {
                // Hitung Tanggal Expired: Hari Ini + Durasi Paket
                $newExpired = now()->addDays((int) $variant->duration_days);

                /**
                 * Kita gunakan updateQuietly agar Filament tidak menjalankan
                 * proses 'saving' berulang kali (mencegah loop tak terbatas).
                 */
                $group->updateQuietly([
                    'expired_at' => $newExpired
                ]);

                FilamentNotification::make()
                    ->title('Grup & Akun Diaktifkan!')
                    ->body("Email terkirim ke peserta. Durasi otomatis diset s.d " . $newExpired->format('d M Y'))
                    ->success()
                    ->send();
            }
        }

        // --- B. LOGIKA JIKA STATUS PROCESSING ---
        if ($group->status === 'processing') {
            $group->orders()
                ->where('status', 'paid')
                ->update(['status' => 'processing']);
        }

        // --- C. LOGIKA JIKA STATUS EXPIRED / CLOSED ---
        if (in_array($group->status, ['expired', 'closed'])) {
            $group->orders()
                ->whereIn('status', ['paid', 'pending', 'processing'])
                ->update(['status' => 'failed']);
        }
    }
}
