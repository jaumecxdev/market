<?php

namespace App\Libraries;

use App\Shop;
use App\ShopProduct;
use App\Traits\HelperTrait;
use Facades\App\Facades\Mpe as FacadesMpe;
use Throwable;

class DavedansAmazonWS extends AmazonSPWS implements MarketWSInterface
{
    use HelperTrait;


    function __construct(Shop $shop)
    {
        parent::__construct($shop);

        $this->only_parents = false;
    }


    public function getItemRowProduct(ShopProduct $shop_product)
    {
        try {
            if (!$shop_product->is_sku_child) return null;

            $item_row = [];
            $shop_product->setPriceStock();
            if ($this->reprice && $shop_product->buybox_price != 0) {
                $shop_product->setReprice();
            }

            $header_rows = count($this->header_product);
            foreach($this->header_product[$header_rows-1] as $attribute_code) {
                $item_row[$attribute_code] = '';
            }

            $attributes = isset($shop_product->attributes) ? json_decode($shop_product->attributes, true) : [];
            foreach($attributes as $attribute_code => $attribute_value) {
                $item_row[$attribute_code] = $attribute_value;
            }

            $name = $shop_product->name ?? FacadesMpe::buildString($shop_product->product->buildTitle());
            $description = $shop_product->longdesc ?? FacadesMpe::plainTextToHtml($shop_product->product->longdesc);     //   FacadesMpe::getText(
            $keywords = $shop_product->product->buildKeywords($name, 90);
            $images = $shop_product->product->getAllUrlImages(9)->toArray();

            $weight_measure = ['kg', 'g'];
            $lenght_measures = ['mm', 'cm'];

            // Peso
            /* $strpos_weight = strpos($description, 'Peso')+6;
            if ($strpos_weight != 6)
                foreach ($weight_measure as $measure) {
                    $weight = substr($description, $strpos_weight, strpos($description, $measure, $strpos_weight)-$strpos_weight+2);
                    if ($weight || $weight != '') {
                        $weight = str_replace([',', '  '], ['.', ' '], $weight);
                        break;
                    }
                } */

            // Ancho
            /* $strpos_width = strpos($description, 'Ancho')+7;
            if ($strpos_width != 7)
                foreach ($lenght_measures as $measure) {
                    $width = substr($description, $strpos_width, strpos($description, $measure, $strpos_width)-$strpos_width+2);
                    if ($width || $width != '') {
                        $width = str_replace([',', '  '], ['.', ' '], $width);
                        break;
                    }
                } */

            // Altura
            /* $strpos_height = strpos($description, 'Altura')+8;
            if ($strpos_height != 8)
                foreach ($lenght_measures as $measure) {
                    $height = substr($description, $strpos_height, strpos($description, $measure, $strpos_height)-$strpos_height+2);
                    if ($height || $height != '') {
                        $height = str_replace([',', '  '], ['.', ' '], $height);
                        break;
                    }
                } */

            // Profundidad
            /* $strpos_depth = strpos($description, 'Profundidad')+13;
            if ($strpos_depth != 13)
                foreach ($lenght_measures as $measure) {
                    $depth = substr($description, $strpos_depth, strpos($description, $measure, $strpos_depth)-$strpos_depth+2);
                    if ($depth || $depth != '') {
                        $depth = str_replace([',', '  '], ['.', ' '], $depth);
                        break;
                    }
                } */

            // PRODUCT
            $item_row['código ean'] = $shop_product->product->ean;
            $item_row['vendor_sku.value'] = $shop_product->mps_sku;
            $item_row['item_package_quantity.value'] = 1;
            $item_row['size_map.value'] = $shop_product->product->size;
            $item_row['manufacturer.value'] = $shop_product->product->brand->name;
            //$item_row['league_name.value'] = NO APLICA
            $item_row['color.standardized_values'] = $shop_product->product->color;
            $item_row['color.value'] = $shop_product->product->color;
            $item_row['rtip_is_shipped_from_vendor.value'] = 'false';
            $item_row['cost_price.currency'] = 'EUR';
            $item_row['cost_price.value'] = $shop_product->product->cost * 0.80;
            $item_row['rtip_product_description.value'] = $description;
            $item_row['department.value'] = 'Mujer';
            $item_row['model_number.value'] = $shop_product->product->pn ?? '';
            $item_row['model_year.value'] = 2021;
            $item_row['item_package_dimensions.height.unit'] = 'centimeters';
            $item_row['item_package_dimensions.height.value'] = 2.5;
            $item_row['item_package_dimensions.length.unit'] = 'centimeters';
            $item_row['item_package_dimensions.length.value'] = 28;
            $item_row['item_package_dimensions.width.unit'] = 'centimeters';
            $item_row['item_package_dimensions.width.value'] = 28;
            $item_row['item_package_weight.unit'] = 'kilograms';
            $item_row['item_package_weight.value'] = 0.27;
            $item_row['size.value'] = $shop_product->product->size;
            $item_row['country_of_origin.value'] = 'SP';
            $item_row['model_name.value'] = $name;
            $item_row['bullet_point.value'] = '';
            $item_row['bullet_point#2.value'] = '';
            $item_row['bullet_point#3.value'] = '';
            $item_row['bullet_point#4.value'] = '';
            $item_row['bullet_point#5.value'] = '';
            $item_row['collection.value'] = 'Todo el año';
            $item_row['fabric_type.value'] = '90% Poliamida Supplex - 10% Elastano';
            $item_row['list_price.currency'] = 'EUR';
            $item_row['list_price.value_with_tax'] = $shop_product->product->cost * 1.21;
            $item_row['item_weight.unit'] = 'kilograms';
            $item_row['collection.value'] = 0.27;
            $item_row['outer.material.value'] = '90% Poliamida Supplex - 10% Elastano';
            $item_row['brand.value'] = 'moove by davedans';
            $item_row['item_type_name.value'] = $shop_product->market_category->name ?? '';
            $item_row['material_composition.value'] = '';
            $item_row['material_composition#2.value'] = '';
            $item_row['season_start_date.year'] = 2021;
            $item_row['style.value'] = '';


            /* $item_row['mainImage'] = $images[0] ?? '';
            $item_row['image1'] = $images[1] ?? '';
            $item_row['image2'] = $images[2] ?? '';
            $item_row['image3'] = $images[3] ?? '';
            $item_row['image4'] = $images[4] ?? '';
            $item_row['image5'] = $images[5] ?? '';
            $item_row['image6'] = $images[6] ?? '';
            $item_row['image7'] = $images[7] ?? '';
            $item_row['image8'] = $images[8] ?? ''; */

            /* if (isset($weight)) $item_row['weight'] = $item_row['weight'] ?? $weight;
            if (isset($width)) $item_row['width'] = $item_row['width'] ?? $width;
            if (isset($height)) $item_row['height'] = $item_row['height'] ?? $height;
            if (isset($depth)) $item_row['depth'] = $item_row['depth'] ?? $depth; */

            // OFFER
            /* $item_row['sku'] = $shop_product->mps_sku;      //$shop_product->getMPSSku();
            $item_row['product-id'] = $shop_product->product->ean;
            $item_row['product-id-type'] = 'EAN';
            $item_row['description'] = $this->offer_desc;
            $item_row['internal-description'] = $keywords;
            $item_row['price'] = $shop_product->price;
            $item_row['quantity'] = $shop_product->stock;
            $item_row['state'] = '11';
            $item_row['logistic-class'] = 'gratuito';
            $item_row['canon'] = 0;
            $item_row['tipo-iva'] = 21; */

            return $item_row;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$shop_product, $item_row]);
        }
    }



    /* public function getItemRowOffer(ShopProduct $shop_product, $extra_data = ['only_stocks' => false, 'buybox_price' => 0])
    {
        $shop_product->setPriceStock();
        if ($this->reprice && $shop_product->buybox_price != 0) {
            $shop_product->setReprice();
        }

        if ($extra_data['buybox_price']) {
            $shop_product->setBuyBoxPrice($extra_data['buybox_price']);
            if ($shop_product->price > $extra_data['buybox_price'])
                $shop_product->setReprice();
        }

        $description = FacadesMpe::buildString($shop_product->product->buildTitle());

        $item_row = [];
        $header_rows = count($this->header_offer);
        foreach($this->header_offer[$header_rows-1] as $attribute_code) {
            $item_row[$attribute_code] = '';
        }

        $attributes = isset($shop_product->attributes) ? json_decode($shop_product->attributes, true) : [];
        foreach($attributes as $attribute_code => $attribute_value) {
            $item_row[$attribute_code] = $attribute_value;
        }

        $item_row['sku'] = $shop_product->mps_sku;      //$shop_product->getMPSSku();
        $item_row['product-id'] = $shop_product->product->ean;
        $item_row['product-id-type'] = 'EAN';
        $item_row['description'] = $description;
        $item_row['quantity'] = $shop_product->stock;
        $item_row['state'] = '11';
        $item_row['logistic-class'] = 'gratuito';
        $item_row['canon'] = 0;
        $item_row['tipo-iva'] = 21;

        if (!$extra_data['only_stocks']) $item_row['price'] = $shop_product->price;


      if ($shop_product->param_discount_price != 0 && $shop_product->param_starts_at && $shop_product->param_ends_at &&
            $shop_product->param_starts_at->lte(now()) && $shop_product->param_ends_at->gte(now())) {

            $item_row['price'] = $shop_product->price;
            $item_row['discount-start-date'] = $shop_product->param_starts_at->format('Y-m-d').'T00:00:00Z';
            $item_row['discount-end-date'] = $shop_product->param_ends_at->format('Y-m-d').'T00:00:00Z';
            $item_row['discount-price'] = $shop_product->param_discount_price;
        }
        // ALL PRODUCTS -5% DISCOUNT
        else {
            $item_row['price'] = $shop_product->price / 0.95;
            $item_row['discount-start-date'] = now()->format('Y-m-d').'T00:00:00Z';
            $item_row['discount-end-date'] = now()->addDays(2)->format('Y-m-d').'T00:00:00Z';
            $item_row['discount-price'] = $shop_product->price;
        }

        return $item_row;
    } */

}
