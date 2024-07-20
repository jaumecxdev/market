<?php

namespace App\Notifications;

use App\Order;
use App\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twitter\TwitterChannel;
use NotificationChannels\Twitter\TwitterDirectMessage;
use NotificationChannels\Twitter\TwitterStatusUpdate;


class TwitterOrderNotification extends Notification
{
    use Queueable;

    public $order;
    public $order_item;
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
        return [TwitterChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @param $
     * @return TwitterDirectMessage
     * @throws \NotificationChannels\Twitter\Exceptions\CouldNotSendNotification
     */
    public function toTwitter($notifiable)
    {
        $twitter_user_id = $notifiable->twitter->user_id ?? null;
        if (isset($twitter_user_id)) {

            $text = $notifiable->notified_type."Pedido de ".$this->order->market->name.' '.$this->order->shop->name."\n\n";
            $text .= "Pedido: ".$this->order->marketOrderId ."\n";
            $text .= "Estado: ".$this->order->status->name ."\n";
            $text .= "Número de líneas: ".$this->order_items->count() ."\n\n";

            foreach ($this->order_items as $order_item) {
                $text .= "Línea: ".$order_item->marketItemId ."\n";
                $text .= isset($order_item->product->pn) ? ("Part Number: " .$order_item->product->pn."\n") : '';
                $text .= isset($order_item->product->ean) ? ("EAN13: " .$order_item->product->ean."\n") : '';
                $text .= "Proveedor: ".($order_item->product->supplier->name ?? '')."\n";
                $text .= "SKU Prov: ".($order_item->product->supplierSku ?? '')."\n";
                $text .= "Artículo: ".$order_item->name."\n";
                $text .= "Unidades: ".$order_item->quantity. "\n";
                $text .= "Precio: ".$order_item->price." ".$order_item->currency->code."\n";
                $text .= "Portes: ".$order_item->shipping_price." ".$order_item->currency->code."\n";
                if ($order_item->mps_bfit) {
                    $text .= "Comi MPe: ".$order_item->mps_bfit." ".$order_item->currency->code."\n";
                }
                if ($order_item->mp_bfit) {
                    $text .= "Comi MP: ".$order_item->mp_bfit." ".$order_item->currency->code;
                }
                $text .= "\n\n";
            }

            return new TwitterDirectMessage($twitter_user_id, $text);
        }

        return null;
    }


}
