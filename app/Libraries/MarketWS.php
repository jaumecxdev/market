<?php

namespace App\Libraries;

use App\Shop;
use App\ShopProduct;
use App\Traits\HelperTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Facades\App\Facades\ShopProductsExcel as FacadesShopProductsExcel;
use Throwable;

class MarketWS
{
    use HelperTrait;

    protected $market = null;         // Market Model
    protected $shop = null;           // Shop Model
    protected $storage_dir = null;      // mp/worten/
    protected $shop_dir = null;         // shops/worten_mpeworten/
    protected $config = null;

    protected $patterns = ['/[.+]/', '/[^a-zA-Z0-9]/', '/[^a-zA-Z]/', '/[^0-9\.,]/'];
    protected $mappings = ['equal', 'strpos', 'strpos2'];

    protected $channels = null;
    protected $order_status_ignored = null;
    protected $order_status_auto_response = null;
    protected $errors_ignored = null;
    protected $preparation = 3;
    protected $publish_packs = [
        'enabled'   => false,
        'values'    => [2, 10, 50],
    ];

    protected $default_logo = null;
    protected $supplier_shippings = null;

    public $header_product = null;
    public $header_offer = null;
    public $header_promo = null;
    public $header_rows = null;
    public $functions = null;
    public $cost_is_price = false;
    public $only_stocks = false;

    public $only_parents = true;


    function __construct(Shop $shop)        //, $fee_mps, $fee_mp, $iva)
    {
        try {

            $this->market = $shop->market;
            $this->shop = $shop;

            $this->storage_dir = $shop->storage_dir;
            if(!Storage::exists($this->storage_dir))
                Storage::makeDirectory($this->storage_dir);

            $this->shop_dir = $shop->shop_dir;
            if(!Storage::exists($this->shop_dir))
                Storage::makeDirectory($this->shop_dir);

            $this->preparation = $shop->preparation;    // 3
            if (isset($shop->channel)) $this->channels = explode(',', $shop->channel);           //['WRT_PT_ONLINE'];

            $this->config = json_decode($shop->config);
            if (isset($this->config)) {
                if (isset($this->config->default_logo))
                    $this->default_logo = $this->config->default_logo;

                if (isset($this->config->cost_is_price))
                    $this->cost_is_price = $this->config->cost_is_price;

                if (isset($this->config->only_stocks))
                    $this->only_stocks = $this->config->only_stocks;

                if (isset($this->config->order_status_ignored))
                    $this->order_status_ignored = $this->config->order_status_ignored;  // ['WAITING_DEBIT_PAYMENT', 'SHIPPED', 'RECEIVED', 'STAGING', 'CLOSED'],

                if (isset($this->config->order_status_auto_response))
                    $this->order_status_auto_response = $this->config->order_status_auto_response;

                if (isset($this->config->errors_ignored))
                    $this->errors_ignored = $this->config->errors_ignored;

                if (isset($this->config->publish_packs))
                    $this->publish_packs = $this->config->publish_packs;

                if (isset($this->config->header_rows)) {
                    $this->header_rows = $this->config->header_rows;
                }

                if (isset($this->config->header_product)) {
                    $this->header_product = $this->config->header_product;      // ['mp_category', 'product_id', 'product_name_es_ES', 'ean', 'image1']
                }
                elseif (Storage::exists($this->shop_dir.'products.xlsx')) {
                    $this->header_product = FacadesShopProductsExcel::getHeader($this, 'products.xlsx');
                    $this->config->header_product = $this->header_product;
                    //$this->config->header_rows_ = is_array($this->header) ? count($this->header) : null;
                    $shop->config = json_encode($this->config);
                    $shop->save();
                }

                if (isset($this->config->header_offer))
                    $this->header_offer = $this->config->header_offer;
                elseif (Storage::exists($this->shop_dir.'offers.xlsx')) {
                    $this->header_offer = FacadesShopProductsExcel::getHeader($this, 'offers.xlsx');
                    $this->config->header_offer = $this->header_offer;
                    //$this->config->header_offer_rows = is_array($this->header_offer) ? count($this->header_offer) : null;
                    $shop->config = json_encode($this->config);
                    $shop->save();
                }

                if (isset($this->config->header_promo))
                    $this->header_promo = $this->config->header_promo;
                elseif (Storage::exists($this->shop_dir.'promos.xlsx')) {
                    $this->header_promo = FacadesShopProductsExcel::getHeader($this, 'promos.xlsx');
                    $this->config->header_promo = $this->header_promo;
                    $shop->config = json_encode($this->config);
                    $shop->save();
                }

                if (isset($this->config->functions))
                    $this->functions = $this->config->functions;

                if (isset($this->config->supplier_shippings))
                    $this->supplier_shippings = json_decode(json_encode($this->config->supplier_shippings), true);
            }

        } catch (\Throwable $th) {
            Storage::append($this->storage_dir. 'errors/' .date('Y-m-d'). '_MarketWS_construct.json', json_encode([$th->getMessage(), $th->getTrace()]));
        }
    }


    static function getMarketWS($shop)
    {
        // $ws = MarketWS::getWS('App\Libraries\\' .$market->ws, $shop);
        // $ws = MarketWS::getWS('App\Libraries\\' .$shop->market->ws, $shop);
        try {
            $ws_name = 'App\Libraries\\' .$shop->market->ws;
            //$object_ws = new $ws_name($shop);

            switch ($ws_name) {
                case 'App\Libraries\AliexpressWS':
                    $object_ws = new AliexpressWS($shop);
                    break;
                case 'App\Libraries\AmazonWS':
                    $object_ws = new AmazonWS($shop);
                    break;
                case 'App\Libraries\FacebookWS':
                    $object_ws = new FacebookWS($shop);
                    break;
                case 'App\Libraries\EbayWS':
                    $object_ws = new EbayWS($shop);
                    break;
                case 'App\Libraries\JoomWS':
                    $object_ws = new JoomWS($shop);
                    break;
                case 'App\Libraries\WortenWS':
                    $object_ws = new WortenWS($shop);
                    break;
                case 'App\Libraries\WishWS':
                    $object_ws = new WishWS($shop);
                    break;
                default:
                    $object_ws = new $ws_name($shop);
            }

            return $object_ws;
        }
        catch (Throwable $th) {
            Storage::append('errors/'.date('Y-m-d_H').'_MarketWS_getMarketWS.json',
                json_encode([$th->getMessage(), $shop->toArray(), $th->getCode(), $th->getTrace()]));

            return null;
        }

    }


    public function calculatePrices($setReprice = true)
    {
        //$shop_products = $this->getShopProducts4Csv($shop_products);
        $shop_products = $this->shop->shop_products()
            ->where('is_sku_child', false)
            ->get();

        foreach ($shop_products as $shop_product) {
            $shop_product->setPriceStock();
            if ($setReprice && $shop_product->buybox_price != 0)
                $shop_product->setReprice();
        }

        return 'Precios calculados para '.$shop_products->count().' productos.';
    }


    public function getMarket()
    {
        return $this->market;
    }


    public function getShop()
    {
        return $this->shop;
    }


    public function getStorageDir()
    {
        return $this->storage_dir;
    }


    public function getShopDir()
    {
        return $this->shop->getShopDir();
        return $this->shop_dir;
    }


    public function setDefaultConfig()
    {
        $default_config = ('App\\Libraries\\'.$this->market->ws)::DEFAULT_CONFIG;
        if (isset($default_config)) {
            /* if ($header = FacadesShopProductsExcel::getHeader($this, 'products.xlsx'))
                $default_config['header'] = $header; */
            $this->shop->config = json_encode($default_config);
            $this->shop->save();
        }

        return $this->shop;
    }


    protected function getShopProducts4Create(Collection $shop_products = null)
    {
        if (!isset($shop_products))
            $shop_products = $this->shop->shop_products()
                ->where('enabled', 1)
                ->where('stock', '>', 0)
                ->where('is_sku_child', false)
                ->where(function (Builder $query) {
                    return $query
                        ->whereNull('marketProductSku')
                        ->orWhere('marketProductSku', 'NO PRODUCT');
                })
                ->get();

        return $shop_products;
    }


    protected function getShopProducts4Update(Collection $shop_products = null)
    {
        if (!isset($shop_products))
            $shop_products = $this->shop->shop_products()
                ->whereNotNull('marketProductSku')
                ->whereNotIn('marketProductSku', ShopProduct::SKU_ERROR)
                ->where('is_sku_child', false)
                ->get();

        return $shop_products;
    }


    protected function getShopProducts4Csv(Collection $shop_products = null)
    {
        if (!isset($shop_products))
            $shop_products = $this->shop->shop_products()
                ->where('enabled', 1)
                ->where('stock', '>', 0)
                ->where('is_sku_child', false)
                ->get();

        return $shop_products;
    }


    protected function attribute_match($pattern, $mapping, $product_attribute_value, $property_values)
    {
        $patterned_product_attribute_value = preg_replace($pattern, "", strtoupper($product_attribute_value));
        if ($patterned_product_attribute_value) {
            foreach ($property_values as $property_value) {

                $patterned_market_attribute_value_name = preg_replace($pattern, "", strtoupper($property_value->name));
                if ($patterned_market_attribute_value_name) {
                    switch ($mapping) {
                        case 'equal':
                            if ($patterned_product_attribute_value == $patterned_market_attribute_value_name)
                                return $property_value->value;
                            break;
                        case 'strpos':
                            if (strpos($patterned_market_attribute_value_name, $patterned_product_attribute_value) !== false)
                                return $property_value->value;
                            break;
                        case 'strpos2':
                            if (strpos($patterned_product_attribute_value, $patterned_market_attribute_value_name) !== false)
                                return $property_value->value;
                            break;
                    }
                }
            }
        }

        return null;
    }


    /* protected function getSpainDescription()
    {
        $desc = "# Vendedor y almacén español.\n# Solo vendemos marcas originales.\n".
            "# Envío gratis de 2 a 5 días.\n# Solo realizamos envíos a España peninsular, Andorra y Portugal.\n\n";

        return $desc;
    } */





}
