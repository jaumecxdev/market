<?php

namespace App\Notifications;

use App\Order;
use App\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;
use NotificationChannels\Twitter\Exceptions\CouldNotSendNotification;

class TelegramOrderNotification extends Notification
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
        return [TelegramChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @param $
     * @return TelegramMessage
     */
    public function toTelegram($notifiable)
    {
        Storage::append('orders_contoller.json', json_encode($notifiable));

        $telegram_user_id = $notifiable->telegram->user_id ?? null;
        Storage::append('orders_contoller.json', json_encode($telegram_user_id));
        if (isset($telegram_user_id)) {
            $options = [
                'parse_mode' => 'HTML',
            ];

            $text = $notifiable->notified_type."<b>Pedido de ".$this->order->market->name.' '.$this->order->shop->name."</b>\n\n";
            $text .= "<b>Pedido: </b>".$this->order->marketOrderId ."\n";
            $text .= "<b>Estado: </b>".$this->order->status->name ."\n";
            $text .= "<b>Número de líneas: </b>".$this->order_items->count() ."\n\n";

            foreach ($this->order_items as $order_item) {
                $text .= "<b>Línea: </b>".$order_item->marketItemId ."\n";
                $text .= isset($order_item->product->pn) ? ("<b>Part Number: </b>" .$order_item->product->pn."\n") : '';
                $text .= isset($order_item->product->ean) ? ("<b>EAN13: </b>" .$order_item->product->ean."\n") : '';
                $text .= "<b>Proveedor: </b>".($order_item->product->supplier->name ?? '')."\n";
                $text .= "<b>SKU Prov: </b>".($order_item->product->supplierSku ?? '')."\n";
                $text .= "<b>Artículo: </b>".$order_item->name."\n";
                $text .= "<b>Unidades: </b>".$order_item->quantity. "\n";
                $text .= "<b>Precio: </b>".$order_item->price." ".$order_item->currency->code."\n";
                $text .= "<b>Portes: </b>".$order_item->shipping_price." ".$order_item->currency->code."\n";
                if ($order_item->mps_bfit)
                    $text .= "<b>Comi MPe: </b>".$order_item->mps_bfit." ".$order_item->currency->code."\n";
                if ($order_item->mp_bfit)
                    $text .= "<b>Comi MP: </b>".$order_item->mp_bfit." ".$order_item->currency->code;
                $text .= "\n\n";
            }

            return TelegramMessage::create()
                ->to($telegram_user_id)
                ->options($options)
                ->content($text);
            /*->button('View Invoice', $url)
            ->button('Download Invoice', $url);*/
        }

        return null;
    }


}
