<?php


namespace App\Commands\Telegram;


use App\Receiver;
use App\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Actions;
use Telegram\Bot\Api;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Objects\Update;

class OrdersCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected $name = "orders";

    /**
     * @var string Command Description
     */
    protected $description = "Obtener listado de los últimos pedidos filtrados por Marketplace, precio, producto y otros parámetros.\n".
                    "Ejemplo: /orders ebay >100 page2";

    /**
     * @inheritdoc
     */
    public function handle()
    {
        $update = $this->getUpdate();
        Storage::append('api/telegram/' .date('Y-m-d'). '_OrdersCommand.json', $update->toJson());

        // $user_is_notifiable = true;
        //$receiver = Receiver::whereTelegramUserId(strval($update->message->from->id))->first();
        $receiver = Receiver::join('telegrams', 'receivers.telegram_id', '=', 'telegrams.id')
            ->where('telegrams.user_id', strval($update->message->from->id))
            ->first();

        if ($receiver && $update->message && $update->message->text) {

            $params = explode(' ', $update->message->text);
            // Remove command
            $params = array_slice($params, 1);

            $orders = Order::select('orders.*')
                ->leftJoin('markets', 'orders.market_id', '=', 'markets.id')
                ->leftJoin('order_items', 'orders.id', '=', 'order_items.order_id')
                ->leftJoin('products', 'order_items.product_id', '=', 'products.id');

            // Filter by supplier_id
            if (isset($receiver->supplier_id))
                $orders = $orders->where('products.supplier_id', $receiver->supplier_id);

            $page = 1;
            foreach ($params as $param) {
                if (substr($param, 0,4) == 'page') {
                    if (!$page = intval(substr($param, 4, strlen($param)))) $page = 1;
                }
                else {
                    if (in_array($param[0], ['<', '=', '>'])) {
                        if ($price = floatval(substr($param, 1, strlen($param)-1)))
                            $orders = $orders->where('order_items.price', $param[0], $price);
                    }
                    else {
                        $orders = $orders->where(function (Builder $query) use ($param) {
                            $query
                                ->where('orders.marketOrderId', $param)
                                ->orWhere('orders.sellerId', $param)
                                ->orWhere('markets.name', 'LIKE', '%' .$param. '%')
                                ->orWhere('order_items.name', 'LIKE', '%' .$param. '%')
                                ->orWhere('order_items.MpsSku', 'LIKE', '%' .$param. '%')
                                ->orWhere('products.pn', $param)
                                ->orWhere('products.ean', $param)
                                ->orWhere('products.upc', $param)
                                ->orWhere('products.isbn', $param)
                                ->orWhere('products.gtin', $param)
                                ->orWhere('products.model', $param);
                        });
                    }
                }
            }

            $count = $orders->count();
            $orders = $orders->orderBy('orders.updated_at', 'desc')->paginate(5, ['*'], 'page', $page);
            Storage::append('api/telegram/' .date('Y-m-d'). '_OrdersCommand_ORDERS.json', $orders->toJson());

            $text = "Pedidos encontrados: ".$count."\n\n";
            foreach ($orders as $order) {
                $text .= "Actualizado el ".$order->updated_at."\n";
                $text .= ($order->market->name ?? '').' '.($order->shop->name ?? '')."\n";
                $text .= "Estado: ".($order->status->name ?? '')."\n";
                $link = str_replace('%marketOrderId', $order->marketOrderId , ($order->market->order_url ?? ''));
                $text .= $link;
                $text .= "\n";

                //<b>Portes:</b>   {{ $order_item->shipping_price }} {{ $order_item->currency->code }}<br>
                foreach ($order->order_items as $order_item) {
                    $text .= "Unidades: ".$order_item->quantity.' - Precio: '.$order_item->price." ".$order_item->currency->code."\n";
                    $text .= "Portes: ".$order_item->shipping_price." ".$order_item->currency->code."\n";
                    $text .= $order_item->product->name ?? '';
                    $text .= "\n";
                }
                $text .= "\n";
            }
            Storage::append('api/telegram/' .date('Y-m-d'). '__OrdersCommand_TEXT.json', $text);
            $this->replyWithMessage(['text' => $text, 'disable_web_page_preview' => true]);
        }
    }
}
