<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentSuccessNotification extends Notification
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
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pembayaran Diterima - ' . $this->order->invoice_number)
            ->greeting('Hai, ' . $notifiable->name)
            ->line('Pembayaran kamu sebesar Rp ' . number_format($this->order->amount) . ' telah kami terima.')
            ->line('Status pesanan: MENUNGGU GRUP PENUH.')
            ->line('Kami akan memberitahu kamu segera setelah kuota terpenuhi.')
            ->action('Cek Status Pesanan', url('/my-orders'));
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
            'title' => 'Pembayaran Sukses',
            'message' => 'Pembayaran ' . $this->order->invoice_number . ' diterima. Mohon tunggu grup penuh.',
            'type' => 'info'
        ];
    }
}
