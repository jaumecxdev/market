<?php

namespace App\Libraries;

use App\Order;
use App\ShopProduct;
use Illuminate\Database\Eloquent\Collection;

interface MarketWSInterface
{

    /************** PUBLIC FUNCTIONS - GETTERS ***************/

    public function getBrands();

    public function getCategories($marketCategoryId = null);

    public function getAttributes(Collection $market_categories);

    public function getFeed(ShopProduct $shop_product);

    public function getJobs();

    public function getOrders();

    public function getGroups();

    public function getCarriers();

    public function getOrderComments(Order $order);


    /************ PUBLIC FUNCTIONS - POSTS *******************/

    public function calculatePrices();

    public function postNewProduct(ShopProduct $shop_product);

    public function postUpdatedProduct(ShopProduct $shop_product);

    public function postPriceProduct(ShopProduct $shop_product);

    public function postNewProducts($shop_products = null);

    public function postUpdatedProducts($shop_products = null);

    public function postPricesStocks($shop_products = null);

    public function postGroups($shop_products = null);

    public function removeProduct($marketProductSku = null);

    public function postOrderTrackings(Order $order, $shipment_data);

    public function postOrderComment(Order $order, $comment_data);

    public function synchronize();

    public function removeWithoutStock();

    /************* REQUEST FUNCTIONS *********************/

    public function getProduct($marketProductSku);

    public function getAllProducts();

}
