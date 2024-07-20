<?php

namespace App\Libraries;


use App\Shop;
use App\ShopProduct;
use Facades\App\Facades\Mpe as FacadesMpe;


class PrestaeduWS extends PrestaWS implements MarketWSInterface
{
    function __construct(Shop $shop)
    {
        parent::__construct($shop);
    }


    public function getItemRowProduct(ShopProduct $shop_product)
    {
        $shop_product->setPriceStock();

        $name = $shop_product->name ?? FacadesMpe::buildString($shop_product->product->buildTitle());
        $description = $shop_product->longdesc ?? FacadesMpe::getText($shop_product->product->buildDescriptionLong4Excel());
        $keywords = $shop_product->product->buildKeywords($name, 90);
        $images = $shop_product->product->getAllUrlImages(9)->toArray();

        $item_row = [];
        $header_rows = count($this->header_product);
        foreach($this->header_product[$header_rows-1] as $attribute_code) {
            $item_row[$attribute_code] = '';
        }

        $attributes = isset($shop_product->attributes) ? json_decode($shop_product->attributes, true) : [];
        foreach($attributes as $attribute_code => $attribute_value) {
            $item_row[$attribute_code] = $attribute_value;
        }

        // PRODUCT
        $item_row['category'] = $shop_product->market_category->name ?? '';
        $item_row['shopSKU'] = $shop_product->mps_sku;      //$shop_product->getMPSSku();
        $item_row['name'] = $name;
        $item_row['prod-description'] = $description;
        $item_row['brand'] = $shop_product->product->brand->name;
        $item_row['mainImage'] = $images[0];
        $item_row['image1'] = $images[1] ?? '';
        $item_row['image2'] = $images[2] ?? '';
        $item_row['image3'] = $images[3] ?? '';
        $item_row['image4'] = $images[4] ?? '';
        $item_row['image5'] = $images[5] ?? '';
        $item_row['image6'] = $images[6] ?? '';
        $item_row['image7'] = $images[7] ?? '';
        $item_row['image8'] = $images[8] ?? '';
        $item_row['mpn'] = $shop_product->product->pn;
        $item_row['ean'] = $shop_product->product->ean;

        // OFFER
        $item_row['sku'] = $shop_product->mps_sku;      //$shop_product->getMPSSku();
        $item_row['product-id'] = $shop_product->product->ean;
        $item_row['product-id-type'] = 'EAN';
        //$item_row['description'] = $this->offer_desc;
        $item_row['internal-description'] = $keywords;
        $item_row['cost'] = $shop_product->getCost();
        $item_row['price'] = $shop_product->price;
        $item_row['quantity'] = $shop_product->stock;
        //$item_row['state'] = '11';
        //$item_row['logistic-class'] = 'gratuito';
        //$item_row['canon'] = 0;
        //$item_row['tipo-iva'] = 21;

        return $item_row;
    }

}
