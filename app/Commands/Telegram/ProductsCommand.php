<?php


namespace App\Commands\Telegram;


use App\Receiver;
use App\ShopProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Objects\Update;

class ProductsCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected $name = "products";

    /**
     * @var string Command Description
     */
    protected $description = "Obtener listado de productos filtrados por categoría, marca, precio y otros parámetros.\n".
                    "Ejemplo: /products portatiles hp <500 page2";

    /**
     * @inheritdoc
     */
    public function handle()
    {
        $update = $this->getUpdate();
        Storage::append('api/telegram/' .date('Y-m-d'). '_ProductsCommand.json', $update->toJson());
        if ($update->message && $update->message->text) {

            $params = explode(' ', $update->message->text);
            // Remove command
            $params = array_slice($params, 1);
            Storage::append('api/telegram/' .date('Y-m-d'). '_ProductsCommand.json', json_encode($params));

            $shop_products = ShopProduct::select('shop_products.*')
                ->leftJoin('markets', 'shop_products.market_id', '=', 'markets.id')
                ->leftJoin('shops', 'shop_products.shop_id', '=', 'shops.id')
                ->leftJoin('products', 'shop_products.product_id', '=', 'products.id')
                ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->leftJoin('statuses', 'products.status_id', '=', 'statuses.id')
                ->whereNotNull('shop_products.marketProductSku')
                /* ->where('shop_products.marketProductSku', '!=', 'ERROR')
                ->where('shop_products.marketProductSku', '!=', 'NO BRAND') */
                ->whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)
                ->where('is_sku_child', false)
                ->where('shop_products.stock', '>', 0);

            // Filter by supplier_id
            //$receiver = Receiver::whereTelegramUserId(strval($update->message->from->id))->first();
            $receiver = Receiver::join('telegrams', 'receivers.telegram_id', '=', 'telegrams.id')
                ->where('telegrams.user_id', strval($update->message->from->id))
                ->first();

            if (isset($receiver->supplier_id))
                $shop_products = $shop_products->where('products.supplier_id', $receiver->supplier_id);

            $page = 1;
            foreach ($params as $param) {
                if (substr($param, 0,4) == 'page') {
                    if (!$page = intval(substr($param, 4, strlen($param)))) $page = 1;
                }
                else {
                    if (in_array($param[0], ['<', '=', '>'])) {
                        if ($price = floatval(substr($param, 1, strlen($param)-1)))
                            $shop_products = $shop_products->where('shop_products.price', $param[0], $price);
                    }
                    else {
                        $shop_products = $shop_products->where(function (Builder $query) use ($param) {
                            $query
                                ->where('markets.name', 'LIKE', '%' .$param. '%')
                                ->orWhere('shops.name', 'LIKE', '%' .$param. '%')
                                ->orWhere('categories.name', 'LIKE', '%' .$param. '%')
                                ->orWhere('brands.name', 'LIKE', '%' .$param. '%')
                                ->orWhere('statuses.name', 'LIKE', '%' .$param. '%')
                                ->orWhere('products.name', 'LIKE', '%' .$param. '%')
                                ->orWhere('products.id', $param)
                                ->orWhere('products.pn', $param)
                                ->orWhere('products.ean', $param)
                                ->orWhere('products.upc', $param)
                                ->orWhere('products.isbn', $param)
                                ->orWhere('products.gtin', $param)
                                ->orWhere('products.model', $param)
                                ->orWhere('products.color', 'LIKE', '%' .$param. '%')
                                ->orWhere('products.material', 'LIKE', '%' .$param. '%')
                                ->orWhere('products.style', 'LIKE', '%' .$param. '%')
                                ->orWhere('products.gender', 'LIKE', '%' .$param. '%')
                                ->orWhere('shop_products.marketProductSku', $param);
                        });
                    }
                }
            }

            $count = $shop_products->count();
            //$shop_products = $shop_products->orderBy('id', 'desc')->take(5)->get();
            $shop_products = $shop_products->orderBy('products.id', 'desc')->paginate(5, ['*'], 'page', $page);

            Storage::append('api/telegram/' .date('Y-m-d'). '_ProductsCommand_shopproducts.json', json_encode($shop_products->toArray()));
            $this->replyWithMessage(['text' => 'Artículos encontrados: '.$count."\n"]);

            foreach ($shop_products as $shop_product) {
                if (isset($shop_product->market->product_url))
                    $text = str_replace('%marketProductSku', $shop_product->marketProductSku , $shop_product->market->product_url);
                else
                    $text = $shop_product->name. "\n" .
                        $shop_product->market->name. ' ' .$shop_product->shop->name. "\n" .
                        'EAN13: ' .($shop_product->product->ean ?? ''). "\n" .
                        'Price: '. $shop_product->price. ' ' .($shop_product->currency->code ?? '€');
                $this->replyWithMessage(['text' => $text]);
            }
        }
    }
}
