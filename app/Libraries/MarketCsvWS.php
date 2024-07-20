<?php

namespace App\Libraries;


use App\Order;
use App\Product;
use App\Shop;
use App\ShopProduct;
use App\Traits\HelperTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;


class MarketCsvWS extends MarketWS implements MarketWSInterface
{
    use HelperTrait;

    protected $filename = null;
    protected $storage_path = null;
    protected $delimiter = "\t";
    protected $tax = null;

    const DEFAULT_CONFIG = [
        // MarketWS
        'header' => null,
        'header_rows' => 1,
        'order_status_ignored' => ['CancelPending'],
        'errors_ignored' => ['21915465', '21920270', '21919189', '21917091'],
        'publish_packs' => [
            'enabled' => true,
            'values' => [2, 10, 50]
        ],
        'functions' => [
            'getBrands'             => false,
            'getCategories'         => true,
            'getAttributes'         => true,
            'getItemRowProduct'     => true,
            'getItemRowOffer'       => true,
            'getItemRowPromo'       => true,
            'getFeed'               => true,
            'getJobs'               => true,
            'getOrders'             => true,
            'getGroups'             => false,
            'getCarriers'           => true,
            'getOrderComments'      => false,
            'postNewProduct'        => true,
            'postUpdatedProduct'    => true,
            'postPriceProduct'      => true,
            'postNewProducts'       => true,
            'postUpdatedProducts'   => true,
            'postPricesStocks'      => true,
            'synchronize'           => true,
            'postGroups'            => false,
            'removeProduct'         => true,
            'postOrderTrackings'    => true,
            'postOrderComment'      => false,
        ],
        'locale' => 'es_ES',
    ];



    public function __construct(Shop $shop)
    {
        parent::__construct($shop);
        /* $this->storage_dir .= $shop->market->code.'/';
        if(!Storage::exists($this->storage_dir))
            Storage::makeDirectory($this->storage_dir); */

        $this->filename = $this->shop->code. '.csv';
        $this->storage_path = storage_path('app/mp/csv/' .$this->filename);
        $this->tax = ($shop->channel == 'b2b') ? 0 : null;  // null == current product tax

        if(!File::exists($this->storage_dir))
            Storage::makeDirectory($this->storage_dir);

       
    }



    /************** PRIVATE FUNCTIONS - BUILDERS ***************/


    private function buildCVSTitle(Product $product)
    {
        $title = str_replace(['ª','®','™'], ['a','',''], $product->buildTitle());
        // ,6 GHz 8ª generación de procesadores Intel® Core™ i5 i5-8265U
        // mb_detect_encoding($product->buildTitle(), 'UTF-8', true);
        return mb_substr(ucwords(mb_strtolower($title)), 0, 150);
    }

    private function buildCSVShortDescription(Product $product)
    {
        return ucwords(mb_strtolower(stripslashes($product->shortdesc)));
    }


    private function buildCSVDescription(Product $product)
    {
        return ucwords(mb_strtolower($product->buildDescription4Mobile()));
    }


    private function buildItemFeed(ShopProduct $shop_product)
    {
        $shop_product->setPriceStock($this->tax);

        $title = $this->buildCVSTitle($shop_product->product);
        $short_description = $this->buildCSVShortDescription($shop_product->product);
        $description = $this->buildCSVDescription($shop_product->product);
        $category_name = $shop_product->product->category ?
            ($shop_product->product->category->path. ' / ' .$shop_product->product->category->name) : '';

        $images = $shop_product->product->getAllUrlImages(10)->toArray();

        if (!$title || !$description || empty($images))
            return null;

        return [
            'id'            => $shop_product->mps_sku,        //$shop_product->getMPSSku(),
            'brand'         => $shop_product->product->brand->name ?? '',
            'category_id'   => $shop_product->product->category_id,
            'category'      => $category_name,
            'status'        => $shop_product->product->status->name ?? '',
            'currency'      => $shop_product->product->currency->code ?? '',
            'name'          => $title,
            'pn'            => $shop_product->product->pn,
            'ean'           => $shop_product->product->ean,
            'upc'           => $shop_product->product->upc,
            'isbn'          => $shop_product->product->isbn,
            'gtin'          => $shop_product->product->gtin,
            'shortdesc'     => $short_description,
            'longdesc'      => $description,
            'weight'        => $shop_product->product->weight,
            'length'        => $shop_product->product->length,
            'width'         => $shop_product->product->width,
            'height'        => $shop_product->product->height,
            'model'         => $shop_product->product->model,
            'price'         => $shop_product->price,
            'tax'           => $shop_product->tax,
            'stock'         => $shop_product->stock,
            'size'          => $shop_product->product->size,
            'color'         => $shop_product->product->color,
            'material'      => $shop_product->product->material,
            'style'         => $shop_product->product->style,
            'gender'        => $shop_product->product->gender,
            'images'        => implode ($this->delimiter, $images),
        ];
    }


    private function buildCsv(Collection $shop_products)
    {
        $fp = fopen($this->storage_path, 'w');
        $columns = ['id', 'brand', 'category_id', 'category', 'status', 'currency', 'name',
            'pn', 'ean', 'upc', 'isbn', 'gtin', 'shortdesc', 'longdesc', 'weight', 'length', 'width', 'height',
            'model', 'price', 'tax', 'stock', 'size', 'color', 'material', 'style', 'gender', 'images'];
        fputcsv($fp, $columns, $this->delimiter);

        $count = 0;
        foreach ($shop_products as $shop_product) {
            if ($item = $this->buildItemFeed($shop_product)) {


                fputcsv($fp, array_values($item), $this->delimiter);
                $count++;
            }
        }
        fclose($fp);

        return [$count, $this->filename];
    }


    /************** PUBLIC FUNCTIONS - GETTERS ***************/

    public function getBrands()
    {
        return null;
    }


    public function getCategories($marketCategoryId = null)
    {
        return false;
    }


    public function getAttributes(Collection $market_categories)
    {
        return false;
    }


    public function getFeed(ShopProduct $shop_product)
    {
        $item = $this->buildItemFeed($shop_product);

        return $item;
    }


    public function getJobs()
    {
        return false;
    }


    public function getOrders()
    {
        return false;
    }


    public function getGroups()
    {
        return false;
    }


    public function getCarriers()
    {
        return false;
    }


    public function getOrderComments(Order $order)
    {
        return false;
    }


    /************ PUBLIC FUNCTIONS - POSTS *******************/


    public function postNewProduct(ShopProduct $shop_product)
    {
        return false;
    }


    public function postUpdatedProduct(ShopProduct $shop_product)
    {
        return false;
    }


    public function postPriceProduct(ShopProduct $shop_product)
    {
        return false;
    }


    public function postNewProducts($shop_products = null)
    {
        $shop_products = $this->getShopProducts4Csv($shop_products);
        if (!$shop_products->count()) return 'No se han encontrado productos nuevos en esta Tienda';

        return $this->buildCsv($shop_products);
    }


    public function postUpdatedProducts($shop_products = null)
    {
        return $this->postNewProducts($shop_products);
    }


    public function postPricesStocks($shop_products = null)
    {
        return $this->postNewProducts($shop_products);
    }


    public function postGroups($shop_products = null)
    {
        return false;
    }


    public function removeProduct($marketProductSku = null)
    {
        return false;
    }


    public function postOrderTrackings(Order $order, $shipment_data)
    {
        return false;
    }


    public function postOrderComment(Order $order, $comment_data)
    {
        return false;
    }


    public function synchronize()
    {
        return null;
    }


    public function removeWithoutStock()
    {
        return null;
    }



    /************* REQUEST FUNCTIONS *********************/


    public function getProduct($marketProductSku)
    {
        return false;
    }


    public function getAllProducts($next_page = null)
    {
        return false;
    }

}
