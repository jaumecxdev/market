<?php

namespace App\Libraries;

use App\Brand;
use App\Category;
use App\Currency;
use App\Product;
use App\Status;
use App\Supplier;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Facades\App\Facades\Mpe as FacadesMpe;



/***************************
 *
 *
 *
 *
 *      LLIBRERIA OBSOLETA
 *      REVISAR DE DALT A BAIX ABANS D'UTILITZAR
 *
 *
 *
 *
 *
 */

class SupplierMPSShopWS extends SupplierWS
{
    private $mpsshop_server;
    private $token;
    private $client;


    function __construct(Supplier $supplier)
    {
        parent::__construct($supplier);
        //$this->storage_dir .= 'mpsshop/';
        $this->token = '';

        if (App::environment('local')) $this->mpsshop_server = 'http://127.0.0.1:8000/';        // http://localhost:8080/shop/public/
        else $this->mpsshop_server = 'https://shop.mpespecialist.com/';        // production

        $this->client = new Client(['base_uri' => $this->mpsshop_server]);
    }


    /************** PRIVATE FUNCTIONS - BUILDS & GETTERS ***************/


    private function buildSupplierSku($supplier_product, $offer)
    {
        return ($supplier_product->shop_id. '##' .$supplier_product->id. '##' .$offer->sku);
    }


    private function explodeSupplierSku($supplierSku)
    {
        $params = explode('##',$supplierSku);
        return [intval($params[0]), intval($params[1]), $params[2]];
    }


    private function getBrand($supplier_product)
    {
        $brand = null;
        if ($supplier_product->brand_name)
            $brand = Brand::firstOrCreate([
                'name' => $supplier_product->brand_name
            ], []);

        return $brand;
    }


    private function getStatus($offer)
    {
        // MPS Statuses possible values: Nuevo (new), Usado (used), Remanufacturado (refurbished)
        // Mapping MPSShop -> MPS
        $mps_status_name = $offer->status_name;
        if (in_array($mps_status_name, ['Caja abierta', 'Como nuevo', 'Muy bueno', 'Bueno', 'Acceptable', 'No funciona']))
            $mps_status_name = 'Usado';

        $status = null;
        if ($mps_status_name)
            $status = Status::firstOrCreate(
                [
                    'name'  => $mps_status_name,
                    'type'  => 'product'
                ],
                []
            );

        return $status;
    }


    private function getReady($offer)
    {
        return ($offer->starts_at != null && $offer->starts_at >= now() && ($offer->ends_at == null || $offer->ends_at < now()));
    }


    private function getCurrency($offer)
    {
        $currency = null;
        if ($offer->currency_code && $offer->currency_name)
            $currency = Currency::firstOrCreate(
                ['code'  => $offer->currency_code],
                ['name'  => $offer->currency_name]
            );

        return $currency;
    }


    /************** PRIVATE FUNCTIONS - UPDATE OR CREATE PRODUCTS ***************/


    private function canUpdateOrCreateProduct($supplier_product)
    {
        if (!$supplier_product->category_code)
            return false;

        // MPS Shops categories == MPS Categories
        // If NO exist Category, return NULL & NO create Product
        if(!$category = Category::firstWhere('code', $supplier_product->category_code))
            return false;

        return $category;
    }


    private function updateOrCreateSkuImage(Product $product, $supplier_product, $offer)
    {
        if (isset($offer->src)) {
            $full_url_image = $this->mpsshop_server. 'storage/img/offers/' .$supplier_product->id. '/' .$offer->src;
            $product->updateOrCreateExternalImage($full_url_image, 0);
        }
    }


    private function updateOrCreateImages(Product $product, $supplier_product)
    {
        foreach ($supplier_product->images as $image) {
            $full_url_image = $this->mpsshop_server. 'storage/img/products/' .$supplier_product->id. '/' .$image->src;
            $product->updateOrCreateExternalImage($full_url_image);
        }
    }


    private function createParentProduct(Brand $brand, Category $category, Status $status, Currency $currency, $supplierSku, $supplier_product, $offer)
    {
        //$supplier_params = $this->supplier->getSupplierProductParams($supplierSku, $brand->id, $category->id);
        $product = Product::create(
            [
                'supplier_id'   => $this->supplier->id,
                'parent_id'     => null,
                'brand_id'      => $brand->id ?? null,
                'category_id'   => $category->id,
                'type_id'       => null,
                'status_id'     => $status->id ?? null,
                'currency_id'   => $currency->id ?? null,

                'name'          => FacadesMpe::getString($supplier_product->name),
                'keywords'      => FacadesMpe::getString($supplier_product->keywords),
                'pn'            => $supplier_product->pn,
                'ean'           => $supplier_product->ean,
                'upc'           => $supplier_product->upc,
                'isbn'          => $supplier_product->isbn,
                'gtin'          => null,
                'shortdesc'     => FacadesMpe::getText($supplier_product->shortdesc),
                'longdesc'      => FacadesMpe::getText($supplier_product->longdesc),
                'weight'        => $supplier_product->weight,
                'length'        => $supplier_product->length,
                'width'         => $supplier_product->width,
                'height'        => $supplier_product->height,

                //'ready'         => $this->getReady($offer),
                'supplierSku'   => $supplierSku,
                'model'         => $offer->model,
                // supplier_params 042021
                'cost'          => FacadesMpe::roundFloat($offer->price),
                'tax'           => $offer->tax,
                'stock'         => $offer->stock,

                'size'          => $offer->size,
                'color'         => $offer->color,
                'material'      => $offer->material,
                'style'         => $offer->style,
                'gender'        => $offer->gender,

                'fix_text'      => 0,
            ]
        );

        // Images
        $this->updateOrCreateImages($product, $supplier_product);

        return $product;
    }


    public function createChildProduct(Brand $brand, Category $category, Status $status, Currency $currency, $supplierSku, $parent_id, $offer)
    {
        //$supplier_params = $this->supplier->getSupplierProductParams($supplierSku, $brand->id, $category->id);
        return Product::create(
            [
                'supplier_id'   => $this->supplier->id,     // it's necessary in child product???
                'parent_id'     => $parent_id,
                'brand_id'      => $brand->id ?? null,
                'category_id'   => $category->id,
                'type_id'       => null,
                'status_id'     => $status->id ?? null,
                'currency_id'   => $currency->id ?? null,

                //'ready'         => $this->getReady($offer),
                'supplierSku'   => $supplierSku,
                'model'         => $offer->model,
                // supplier_params 042021
                'cost'          => FacadesMpe::roundFloat($offer->price),
                //'cost'          => $this->getCost(['cost' => $offer->price], $supplier_params),
                'tax'           => $offer->tax,
                'stock'         => $offer->stock,

                'size'          => $offer->size,
                'color'         => $offer->color,
                'material'      => $offer->material,
                'style'         => $offer->style,
                'gender'        => $offer->gender,

                'fix_text'      => 0,
            ]
        );
    }


    private function updateProduct(Status $status, Currency $currency, Product $product, $offer)
    {
        //$supplier_params = $this->supplier->getSupplierProductParams($product->supplierSku, $product->brand_id, $product->category_id);
        $product->update([
            'status_id'     => $status->id ?? null,
            'currency_id'   => $currency->id ?? null,

            //'ready'         => $this->getReady($offer),
            'model'         => $offer->model,
            // supplier_params 042021
            'cost'          => FacadesMpe::roundFloat($offer->price),
            //'cost'          => $this->getCost(['cost' => $offer->price], $supplier_params),
            'tax'           => $offer->tax,
            'stock'         => $offer->stock,
            'size'          => $offer->size,
            'color'         => $offer->color,
            'material'      => $offer->material,
            'style'         => $offer->style,
            'gender'        => $offer->gender,
        ]);

        $product->logPrice(false);

        return $product;
    }


    private function updateFullProduct(Category $category, Product $product, $supplier_product)
    {
        $offer = $supplier_product->offers[0];
        $brand = $this->getBrand($supplier_product);
        $status = $this->getStatus($offer);
        $currency = $this->getCurrency($offer);

        //$supplier_params = $this->supplier->getSupplierProductParams($product->supplierSku, $brand->id ?? null, $category->id);
        $product->update(
            [
                'brand_id'      => $brand->id ?? null,
                'category_id'   => $category->id,
                'type_id'       => null,
                'status_id'     => $status->id ?? null,
                'currency_id'   => $currency->id ?? null,

                //'ready'         => $this->getReady($offer),
                'model'         => $offer->model,
                // supplier_params 042021
                'cost'          => FacadesMpe::roundFloat($offer->price),
                //'cost'          => $this->getCost(['cost' => $offer->price], $supplier_params),
                'tax'           => $offer->tax,
                'stock'         => $offer->stock,
                'size'          => $offer->size,
                'color'         => $offer->color,
                'material'      => $offer->material,
                'style'         => $offer->style,
                'gender'        => $offer->gender,
            ]
        );

        if (!isset($product->parent_id)) {
            $product->update(
                [
                    'name'          => $supplier_product->name,
                    'keywords'      => $supplier_product->keywords,
                    'pn'            => $supplier_product->pn,
                    'ean'           => $supplier_product->ean,
                    'upc'           => $supplier_product->upc,
                    'isbn'          => $supplier_product->isbn,
                    'shortdesc'     => $supplier_product->shortdesc,
                    'longdesc'      => $supplier_product->longdesc,
                    'weight'        => $supplier_product->weight,
                    'length'        => $supplier_product->length,
                    'width'         => $supplier_product->width,
                    'height'        => $supplier_product->height,
                ]
            );
        }

        // Sku Image
        $this->updateOrCreateSkuImage($product, $supplier_product, $offer);

        // Images if it's parent
        if (!isset($product->parent_id))
            $this->updateOrCreateImages($product, $supplier_product);

        $product->logPrice(true);

        return $product;
    }


    private function updateOrCreateProductOffers($category, $supplier_product)
    {
        $processed_ids = [];
        $res = [
            'updateds'      => 0,
            'news'          => 0,
            'no_category'   => [],
        ];

        $res['starts_at'] = date('Y-m-d_H-i-s');
        $parent_product = null;
        $brand = $this->getBrand($supplier_product);
        foreach ($supplier_product->offers as $offer) {

            $status = $this->getStatus($offer);
            $currency = $this->getCurrency($offer);
            $supplierSku = $this->buildSupplierSku($supplier_product, $offer);

            // Exists ?
            $product = Product::firstWhere(['supplier_id' => $this->supplier->id, 'supplierSku' => $supplierSku]);//, 'pn' => $supplier_product->pn]);

            // Update cost & stock & sku params
            if ($product) {
                $updated = $this->updateProduct($status, $currency, $product, $offer);
                if (!isset($updated->id)) $res['no_category'][$updated] = 'No updated mapping';
                else {
                    $res['updateds']++;
                    $processed_ids[] = $updated->id;
                }
            }
            // New product
            else {
                // Create Parent Product
                if (!isset($parent_product)) {
                    $created = $this->createParentProduct($brand, $category, $status, $currency, $supplierSku, $supplier_product, $offer);
                    $parent_product = $created;
                    if (!isset($created->id)) $res['no_category'][$created] = 'No created mapping';
                    else {
                        $res['news']++;
                        $processed_ids[] = $created->id;
                    }
                }
                // $parent_product OK, Add Child Offer
                else {
                    $created = $this->createChildProduct($brand, $category, $status, $currency, $supplierSku, $parent_product->id, $offer);
                    if (!isset($created->id)) $res['no_category'][$created] = 'No created mapping';
                    else {
                        $res['news']++;
                        $processed_ids[] = $created->id;
                    }
                }
            }

            // Sku Image
            $this->updateOrCreateSkuImage($product, $supplier_product, $offer);

            $product->logPrice(true);
        }

        // Resets OLD NOT FOUND product stocks of current supplier
        if (count($processed_ids))
            $this->supplier->products()->whereNotIn('id', $processed_ids)->update(['stock' => 0]);

        $res['ends_at'] = date('Y-m-d_H-i-s');
        return $res;
    }


    /************** PRIVATE FUNCTIONS - API GETTERS ***************/


    private function getAPIGroupId()
    {

        $supplier_group_id = null;
        //$response = $client->get('api/groups?all&token=' .$this->token. '&store_code='. $this->supplier->code);
        $response = $this->client->get('api/groups', ['query' => ['token' => $this->token, 'store_code' => $this->supplier->code]]);

        if ($response->getStatusCode() == '200') {
            $contents = $response->getBody()->getContents();
            Storage::append($this->storage_dir. 'group/' .date('Y-m-d_H-i-s'). '.json', $contents);
            $json_res = json_decode($contents);

            if (!isset($json_res->error)) {
                return $json_res->data[0]->id ?? null;
            }
        }

        return null;
    }


    private function getAPIProducts($supplier_group_id, $url_params)
    {
        if ($supplier_group_id) {
            $response = $this->client->get('api/products',
                ['query' => ['all' => 1, 'token' => $this->token, 'group_id' => $supplier_group_id, $url_params]]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->storage_dir. 'products/' .date('Y-m-d_H-i-s'). '.json', $contents);
                $json_res = json_decode($contents);
                if (!isset($json_res->error)) {
                    return $json_res->data ?? null;
                }
            }
        }

        return null;
    }


    private function getAPIProduct($supplier_group_id, $supplierSku)
    {
        if ($supplier_group_id) {

            list($shop_id, $supplier_product_id, $sku) = $this->explodeSupplierSku($supplierSku);

            $response = $this->client->get('api/products',
                ['query' => ['all' => 1, 'token' => $this->token, 'locale' => 'es', 'group_id' => $supplier_group_id, 'shop_id' => $shop_id, 'sku' => $sku]]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append($this->storage_dir. 'product/' .date('Y-m-d_H-i-s'). '.json', $contents);
                $json_res = json_decode($contents);

                if (!isset($json_res->error)) {
                    return $json_res->data[0] ?? null;
                }
            }
        }

        return null;

    }


    /************** PRIVATE FUNCTIONS - FILTERS ***************/


    private function getFilterUrlParams($supplier_filter)
    {
        $url_params = [];
        if ($supplier_filter->brand_name && $this->supplier->brand_field != null)
            $url_params[] = ['brand_name' => $supplier_filter->brand_name];

        if ($supplier_filter->category_name && $this->supplier->category_field != null)
            $url_params[] = ['category_name' => $supplier_filter->category_name];

        if ($supplier_filter->status_name && $this->supplier->status_field != null)
            $url_params[] = ['status_name' => $supplier_filter->status_name];

        if ($supplier_filter->name && $this->supplier->name_field != null)
            $url_params[] = ['product_name' => $supplier_filter->name];

        if ($supplier_filter->supplierSku && $this->supplier->supplierSku_field != null)
            $url_params[] = ['sku' => $supplier_filter->supplierSku];

        if ($supplier_filter->pn && $this->supplier->pn_field != null)
            $url_params[] = ['pn' => $supplier_filter->pn];

        if ($supplier_filter->ean && $this->supplier->ean_field != null)
            $url_params[] = ['ean' => $supplier_filter->ean];

        if ($supplier_filter->upc && $this->supplier->upc_field != null)
            $url_params[] = ['upc' => $supplier_filter->upc];

        if ($supplier_filter->isbn && $this->supplier->isbn_field != null)
            $url_params[] = ['isbn' => $supplier_filter->isbn];

        if ($supplier_filter->cost_min && $this->supplier->cost_field != null)
            $url_params[] = ['price_min' => $supplier_filter->cost_min];

        if ($supplier_filter->cost_max && $this->supplier->cost_field != null)
            $url_params[] = ['price_max' => $supplier_filter->cost_max];

        if ($supplier_filter->stock_min && $this->supplier->stock_field != null)
            $url_params[] = ['stock_min' => $supplier_filter->stock_min];

        if ($supplier_filter->stock_max && $this->supplier->stock_field != null)
            $url_params[] = ['stock_max' => $supplier_filter->stock_max];

        if ($supplier_filter->limit_products && $this->supplier->limit_products != null) {
            $url_params[] = ['limit' => $supplier_filter->limit_products];
        }

        return $url_params;
    }


    private function getFilterProducts($supplier_filter)
    {
        $res = [];
        $url_params = $supplier_filter ? $this->getFilterUrlParams($supplier_filter) : '';
        $supplier_group_id = $this->getAPIGroupId();
        $json_data = $this->getAPIProducts($supplier_group_id, $url_params);

        foreach ($json_data as $supplier_product) {
            if ($category = $this->canUpdateOrCreateProduct($supplier_product))
                $res = $this->updateOrCreateProductOffers($category, $supplier_product);
        }

        return $res;
    }


    /************** PUBLIC FUNCTIONS ***************/


    public function getProducts()
    {
        $res = [];
        $supplier_filters = $this->supplier->supplier_filters;
        if (!$supplier_filters->count()) {
            $res = $this->getFilterProducts(null);
        }
        else {
            foreach ($supplier_filters as $supplier_filter) {
                $res[] = $this->getFilterProducts($supplier_filter);
            }
        }

        return $res;
    }


    public function getProduct(Product $product)
    {
        $supplier_group_id = $this->getAPIGroupId();
        $supplier_product = $this->getAPIProduct($supplier_group_id, $product->supplierSku);
        if ($category = $this->canUpdateOrCreateProduct($supplier_product))
            return $this->updateFullProduct($category, $product, $supplier_product);

        return null;
    }


    public function getPricesStocks()
    {

    }

}
