<?php

namespace App\Libraries;


use App\Order;
use App\Shop;
use App\ShopProduct;
use App\Traits\HelperTrait;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Collection;


class FacebookWS extends MarketWS implements MarketWSInterface
{
    use HelperTrait;

    const DEFAULT_CONFIG = [
        // MarketWS
        'header' => null,
        'header_rows' => 1,
        'order_status_ignored' => [],
        'errors_ignored' => [],
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


    function __construct(Shop $shop)        //, $fee_mps = 5, $fee_mp = 6, $iva = 21)
    {
        parent::__construct($shop);     //, $fee_mps, $fee_mp, $iva);
        /* $this->storage_dir .= $shop->market->code.'/';
        if(!Storage::exists($this->storage_dir))
            Storage::makeDirectory($this->storage_dir); */
    }


    /************** PRIVATE FUNCTIONS ***************/



    /************** PRIVATE FUNCTIONS - GET SERVICES ***************/


    /************** PRIVATE FUNCTIONS - GETTERS ***************/


    private function getAllCategoriesRequest()
    {
        /*
         141	Cámaras y ópticas
         222	Electrónica
         5181	Maletas y bolsos de viaje
         922	Material de oficina
         2092	Software
         469	Salud y belleza	// X Scooters Eléctricos
         */

        $client = new Client();
        $xls_file = 'https://www.google.com/basepages/producttype/taxonomy-with-ids.es-ES.xls';
        $res = $client->request('GET', $xls_file);

        // $res->getHeader('content-type')[0]; // 'text/csv'
        if ($res->getStatusCode() == '200') {

            /* $local_xls_file = $this->storage_dir. 'categories.xls';

            Storage::put($local_xls_file, $res->getBody());
            Excel::import(new MarketCategoryFacebookImport, storage_path('app/' .$local_xls_file), null, \Maatwebsite\Excel\Excel::XLS); */
            return null;
        }
        else
            return false;
    }


    /************** PRIVATE FUNCTIONS - BUILDERS ***************/


    private function buildItemFeed(ShopProduct $shop_product)
    {
        $shop_product->setPriceStock();

        $images = $shop_product->product->getAllUrlImages(5)->toArray();
        $item = [
            'id'                        => $shop_product->mps_sku,      //$shop_product->getMPSSku(),
            'title'                     => mb_substr($shop_product->product->buildTitle(), 0, 150),
            'description'               => $shop_product->product->buildDescription4Html(),
            'availability'              => 'in stock',
            'inventory'                 => $shop_product->stock,
            'condition'                 => 'new',
            'price'                     => $shop_product->price,
            'link'                      => 'https://www.facebook.com/pg/locurainformatika/shop',
            'image_link'                => $images[0],
            'brand'                     => $shop_product->product->ean,
            'google_product_category'   => $shop_product->market_category->marketCategoryId,
        ];

        return $item;
    }


    private function buildCsv(Collection $shop_products)
    {
        $filename = storage_path('app/public/csv/facebook.csv');
        $fp = fopen($filename, 'w');
        $columns = ['id', 'title', 'description', 'availability', 'inventory', 'condition', 'price', 'link', 'image_link', 'brand', 'google_product_category'];
        fputcsv($fp, $columns, ';');

        $count_news = 0;
        foreach ($shop_products as $shop_product) {
            $item = $this->buildItemFeed($shop_product);
            fputcsv($fp, array_values($item), ';');
            $count_news++;
        }
        fclose($fp);



        return $count_news;
    }


    /************** PRIVATE FUNCTIONS - POSTS ***************/


    private function postProducts(Collection $shop_products, $job_type = 'ReviseFixedPriceItem')
    {
        $products_result['count'] = $shop_products->count();
        $count_products = $this->buildCsv($shop_products, $job_type);

        return $count_products;
    }


    /************** PRIVATE FUNCTIONS - SAVES & UPDATES ***************/



    /************** PUBLIC FUNCTIONS - GETTERS ***************/


    public function getBrands()
    {
        return null;
    }


    public function getCategories($marketCategoryId = null)
    {
        $this->getAllCategoriesRequest();

        return true;
    }


    public function getAttributes(Collection $market_categories)
    {
        return false;
    }


    public function getFeed(ShopProduct $shop_product)
    {
        return $this->buildItemFeed($shop_product);
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
        if (!$shop_products->count()) return 'No se han encontrado productos en esta Tienda';

        return $this->postProducts($shop_products);
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


    public function getAllProducts()
    {
        return false;
    }


}
