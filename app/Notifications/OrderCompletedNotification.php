<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCompletedNotification extends Notification
{
    use Queueable;

    public $order;
    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order)
    {
        //
        $this->order = $order;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Kirim ke Database (Lonceng) DAN Email
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $group = $this->order->group;

        return (new MailMessage)
            ->subject('HORE! Pesanan ' . $group->variant->product->name . ' Siap!')
            ->greeting('Halo, ' . $notifiable->name)
            ->line('Grup patungan kamu sudah penuh dan akun sudah siap.')
            ->line('-----------------------------------------')
            ->line('EMAIL AKUN: ' . $group->account_email)
            ->line('PASSWORD: ' . $group->account_password)
            ->line('CATATAN: ' . $group->additional_info)
            ->line('-----------------------------------------')
            ->action('Lihat Detail Pesanan', url('/my-orders')) // Nanti diarahkan ke frontend
            ->line('Terima kasih sudah patungan di SIRKELTA!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'title' => 'Pesanan Selesai',
            'message' => 'Akun ' . $this->order->group->variant->product->name . ' kamu sudah siap. Cek sekarang!',
            'type' => 'success'
        ];
    }
}
