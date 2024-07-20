<?php

namespace App\Libraries;

use App\Shop;
use App\ShopProduct;
use App\Traits\HelperTrait;
use Facades\App\Facades\Mpe as FacadesMpe;


class WortenWS extends MiraklWS implements MarketWSInterface
{
    use HelperTrait;


    function __construct(Shop $shop)
    {
        parent::__construct($shop);
    }


    public function getItemRowProduct(ShopProduct $shop_product, $product_ids_today_orders = null)
    {
        $shop_product->setPriceStock(null, false, $product_ids_today_orders);

        $item_row = [];
        $header_rows = count($this->header_product);
        foreach($this->header_product[$header_rows-1] as $attribute_code) {
            $item_row[$attribute_code] = '';
        }

        $attributes = isset($shop_product->attributes) ? json_decode($shop_product->attributes, true) : [];
        foreach($attributes as $attribute_code => $attribute_value) {
            $item_row[$attribute_code] = $attribute_value;
        }

        $market_category = $shop_product->market_category;
        $name = $shop_product->name ?? FacadesMpe::buildString($shop_product->product->buildTitle());
        $description = $shop_product->longdesc ?? $shop_product->product->buildDescriptionLong4Excel();
        $keywords = $shop_product->product->buildKeywords($name, 90);
        $images = $shop_product->product->getAllUrlImages(6)->toArray();

        $item_row['mp_category'] = $market_category->name ?? $market_category->marketCategoryId ?? null;
        $item_row['product_id'] = $shop_product->mps_sku;       //$shop_product->getMPSSku();
        $item_row['product_name_pt_PT'] = $name;
        $item_row['product_name_es_ES'] = $name;
        $item_row['ean'] = $shop_product->product->ean;
        $item_row['product_description_pt_PT'] = $description;
        $item_row['product_description_es_ES'] = $description;
        $item_row['product-brand'] = $shop_product->product->brand->name;
        $item_row['image1'] = $images[0] ?? '';
        $item_row['image2'] = $images[1] ?? '';
        $item_row['image3'] = $images[2] ?? '';
        $item_row['image4'] = $images[3] ?? '';
        $item_row['image5'] = $images[4] ?? '';

        return $item_row;
    }




}
