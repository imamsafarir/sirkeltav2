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

    // 1. TETAP DI HALAMAN EDIT
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    // 2. MODIFIKASI TOMBOL SAVE
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

    // 3. LOGIKA SETELAH SIMPAN
    protected function afterSave(): void
    {
        $group = $this->getRecord();

        // --- A. LOGIKA JIKA STATUS COMPLETED ---
        if ($group->status === 'completed') {

            // 1. Update Order
            $orders = $group->orders()->whereIn('status', ['paid', 'processing'])->get();

            foreach ($orders as $order) {
                $order->update(['status' => 'completed']);
                try {
                    if ($order->user && class_exists(OrderCompletedNotification::class)) {
                        $order->user->notify(new OrderCompletedNotification($order));
                    }
                } catch (\Exception $e) {
                }
            }

            // 2. Update Durasi
            $group->load('variant');
            $variant = $group->variant;

            if ($variant && $variant->duration_days > 0) {
                $newExpired = now()->addDays((int) $variant->duration_days);

                $group->updateQuietly([
                    'expired_at' => $newExpired
                ]);

                FilamentNotification::make()
                    ->title('Grup Diaktifkan!')
                    ->body("Durasi otomatis diset s.d " . $newExpired->format('d M Y'))
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

        // --- C. LOGIKA JIKA STATUS EXPIRED / CLOSED (PERBAIKAN UTAMA DISINI) ---
        if (in_array($group->status, ['expired', 'closed'])) {

            // PERBAIKAN: Masukkan 'completed' ke dalam pencarian.
            // Tujuannya agar pesanan yang sudah selesai pun ikut kadaluarsa.
            // Dengan status 'expired', slot grup dianggap kosong oleh sistem ID Recycling.

            $group->orders()
                ->whereIn('status', ['paid', 'pending', 'processing', 'completed']) // <--- Tambah 'completed'
                ->update(['status' => 'expired']); // Ubah jadi expired
        }
    }
}
