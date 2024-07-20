<?php

namespace App\Notifications;

use App\Order;
use App\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MailOrderNotification extends Notification
{
    use Queueable;

    public $order;
    public $order_items;
    public $url;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Order $order, Collection $order_items, $url = null)
    {
        $this->order = $order;
        $this->order_items = $order_items;
        $this->url = $url;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        if ($notifiable->email) {
            return (new MailMessage)
                ->subject($notifiable->notified_type.'Pedido '.$this->order->marketOrderId)
                ->markdown('emails.order', ['order' => $this->order, 'order_items' => $this->order_items, 'url' => $this->url]);
        }

        return null;
    }

}
