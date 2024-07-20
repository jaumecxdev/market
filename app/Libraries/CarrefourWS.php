<?php

namespace App\Libraries;

use App\Shop;
use App\ShopProduct;
use App\Traits\HelperTrait;
use Facades\App\Facades\Mpe as FacadesMpe;


class CarrefourWS extends MiraklWS implements MarketWSInterface
{
    use HelperTrait;


    function __construct(Shop $shop)
    {
        parent::__construct($shop);
    }


    public function getItemRowProduct(ShopProduct $shop_product, $product_ids_today_orders = null)
    {
        $shop_product->setPriceStock(null, false, $product_ids_today_orders);
        if ($this->reprice && $shop_product->buybox_price != 0) {
            $shop_product->setReprice();
        }

        $market_category = $shop_product->market_category;
        $name = $shop_product->name ?? FacadesMpe::buildString($shop_product->product->buildTitle());
        $description = $shop_product->longdesc ?? $shop_product->product->buildDescriptionLong4Excel();
        $keywords = $shop_product->product->buildKeywords($name, 90);
        $images = $shop_product->product->getAllUrlImages(6)->toArray();

        $item_row = [];
        $header_rows = count($this->header_product);
        foreach($this->header_product[$header_rows-1] as $attribute_code) {
            $item_row[$attribute_code] = '';
        }

        $attributes = isset($shop_product->attributes) ? json_decode($shop_product->attributes, true) : [];
        foreach($attributes as $attribute_code => $attribute_value) {
            $item_row[$attribute_code] = $attribute_value;
        }

        $item_row['category'] = $market_category ? ($market_category->path.'/'.$market_category->name) : null;
        $item_row['shopReference'] = $shop_product->mps_sku;  //$shop_product->getMPSSku();
        $item_row['title'] = $name;
        $item_row['ean'] = $shop_product->product->ean;
        $item_row['descripcionAbreviadaPorDefecto'] = $keywords;
        $item_row['descripcionLargaPorDefecto'] = $description;
        $item_row['marca'] = $shop_product->product->brand->name;
        $item_row['imagenGrande1'] = $images[0] ?? '';
        $item_row['imagenGrande2'] = $images[1] ?? '';
        $item_row['imagenGrande3'] = $images[2] ?? '';
        $item_row['imagenGrande4'] = $images[3] ?? '';
        $item_row['imagenGrande5'] = $images[4] ?? '';
        $item_row['imagenGrande6'] = $images[5] ?? '';

        return $item_row;
    }

}
