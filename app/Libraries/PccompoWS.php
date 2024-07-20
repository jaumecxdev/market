<?php

namespace App\Libraries;

use App\Shop;
use App\ShopProduct;
use App\Traits\HelperTrait;
use Facades\App\Facades\Mpe as FacadesMpe;


class PccompoWS extends MiraklWS implements MarketWSInterface
{
    use HelperTrait;


    function __construct(Shop $shop)
    {
        parent::__construct($shop);
    }


    public function getItemRowProduct(ShopProduct $shop_product, $product_ids_today_orders = null)
    {
        $shop_product->setPriceStock(null, false, $product_ids_today_orders);
        if ($this->reprice && $shop_product->buybox_price != 0)
            $shop_product->setReprice();

        $item_row = [];
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
        $strpos_weight = strpos($description, 'Peso')+6;
        if ($strpos_weight != 6)
            foreach ($weight_measure as $measure) {
                $weight = substr($description, $strpos_weight, strpos($description, $measure, $strpos_weight)-$strpos_weight+2);
                if ($weight || $weight != '') {
                    $weight = str_replace([',', '  '], ['.', ' '], $weight);
                    break;
                }
            }

        // Ancho
        $strpos_width = strpos($description, 'Ancho')+7;
        if ($strpos_width != 7)
            foreach ($lenght_measures as $measure) {
                $width = substr($description, $strpos_width, strpos($description, $measure, $strpos_width)-$strpos_width+2);
                if ($width || $width != '') {
                    $width = str_replace([',', '  '], ['.', ' '], $width);
                    break;
                }
            }

        // Altura
        $strpos_height = strpos($description, 'Altura')+8;
        if ($strpos_height != 8)
            foreach ($lenght_measures as $measure) {
                $height = substr($description, $strpos_height, strpos($description, $measure, $strpos_height)-$strpos_height+2);
                if ($height || $height != '') {
                    $height = str_replace([',', '  '], ['.', ' '], $height);
                    break;
                }
            }

        // Profundidad
        $strpos_depth = strpos($description, 'Profundidad')+13;
        if ($strpos_depth != 13)
            foreach ($lenght_measures as $measure) {
                $depth = substr($description, $strpos_depth, strpos($description, $measure, $strpos_depth)-$strpos_depth+2);
                if ($depth || $depth != '') {
                    $depth = str_replace([',', '  '], ['.', ' '], $depth);
                    break;
                }
            }

        // PRODUCT
        $item_row['category'] = $shop_product->market_category->name ?? '';
        $item_row['shopSKU'] = $shop_product->mps_sku;  //$shop_product->getMPSSku();
        $item_row['name'] = $name;
        $item_row['prod-description'] = $description;
        $item_row['brand'] = $shop_product->product->brand->name;
        $item_row['mainImage'] = $images[0] ?? '';
        $item_row['image1'] = $images[1] ?? '';
        $item_row['image2'] = $images[2] ?? '';
        $item_row['image3'] = $images[3] ?? '';
        $item_row['image4'] = $images[4] ?? '';
        $item_row['image5'] = $images[5] ?? '';
        $item_row['image6'] = $images[6] ?? '';
        $item_row['image7'] = $images[7] ?? '';
        $item_row['image8'] = $images[8] ?? '';
        $item_row['mpn'] = $shop_product->product->pn ?? '';
        $item_row['ean'] = $shop_product->product->ean;

        if (isset($weight)) $item_row['weight'] = $item_row['weight'] ?? $weight;
        if (isset($width)) $item_row['width'] = $item_row['width'] ?? $width;
        if (isset($height)) $item_row['height'] = $item_row['height'] ?? $height;
        if (isset($depth)) $item_row['depth'] = $item_row['depth'] ?? $depth;

        // OFFER
        $item_row['sku'] = $shop_product->mps_sku;      //$shop_product->getMPSSku();
        $item_row['product-id'] = $shop_product->product->ean;
        $item_row['product-id-type'] = 'EAN';
        $item_row['description'] = $this->offer_desc;
        $item_row['internal-description'] = $keywords;
        $item_row['price'] = $shop_product->price;
        $item_row['quantity'] = $shop_product->stock;
        $item_row['state'] = $this->state_codes->{$shop_product->product->status->marketStatusName} ?? '11';
        $item_row['logistic-class'] = 'gratuito';
        $item_row['canon'] = 0;
        $item_row['tipo-iva'] = 21;

        return $item_row;
    }



    public function getItemRowOffer(ShopProduct $shop_product, $extra_data = ['only_stocks' => false, 'buybox_price' => 0], $product_ids_today_orders)
    {
        $shop_product->setPriceStock(null, false, $product_ids_today_orders);
        if ($this->reprice && $shop_product->buybox_price != 0)
            $shop_product->setReprice();

        /* if ($extra_data['buybox_price']) {
            $shop_product->setBuyBoxPrice($extra_data['buybox_price']);
            if ($shop_product->price > $extra_data['buybox_price'])
                $shop_product->setReprice();
        } */

        //$description = '';  //$shop_product->longdesc ?? $shop_product->product->buildDescriptionLong4Excel(500);

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
        $item_row['state'] = $this->state_codes->{$shop_product->product->status->marketStatusName} ?? '11';


        //$item_row['logistic-class'] = 'gratuito';
        // Politica envíos
        // gratuito: 24 | libre: 48h
        if ($this->supplier_shippings)
            $item_row['logistic-class'] = $this->supplier_shippings[$shop_product->product->supplier_id] ?? $this->supplier_shippings[0] ?? 'gratuito';
        else
            $item_row['logistic-class'] = $this->shop->shipping;      // 'gratuito' */
        //$item_row['logistic-class'] = $this->shop->shipping;



        $item_row['canon'] = 0;
        $item_row['tipo-iva'] = 21;

        if (!$extra_data['only_stocks']) $item_row['price'] = $shop_product->price;


        // OFERTA EPSON ECOTANK
        /* if ($shop_product->product->ean == 8715946677224) {
            $item_row['price'] = (float)1100;
            $item_row['discount-price'] = (float)799;
            //$start_date = Carbon::createFromTimeString('2021-06-09 23:00:00')->toDateTime();
            $item_row['discount-start-date'] = '2021-06-09';
            //$end_date = Carbon::createFromTimeString('2021-06-30 23:00:00')->toDateTime();
            $item_row['discount-end-date'] = '2021-08-31';
            $item_row['quantity'] = 2;
        }
        // eq() equals, ne() not equals, gt() greater than, gte() greater than or equals, lt() less than, lte() less than or equals
        else */if ($shop_product->param_discount_price != 0 && $shop_product->param_starts_at && $shop_product->param_ends_at &&
            $shop_product->param_starts_at->lte(now()) && $shop_product->param_ends_at->gte(now())) {

            $item_row['price'] = $shop_product->price;
            /* $item_row['total-price'] = $shop_product->price;
            $item_row['origin-price'] = $shop_product->price; */

            $item_row['discount-start-date'] = $shop_product->param_starts_at->format('Y-m-d').'T00:00:00Z';
            $item_row['discount-end-date'] = $shop_product->param_ends_at->format('Y-m-d').'T00:00:00Z';
            $item_row['discount-price'] = $shop_product->param_discount_price;


            /* $StartDate = $Sale->addChild('StartDate', $shop_product->param_starts_at->format('Y-m-d').'T00:00:00Z');
            $EndDate = $Sale->addChild('EndDate', $shop_product->param_ends_at->format('Y-m-d').'T00:00:00Z');

            $SalePrice = $Sale->addChild('SalePrice', $shop_product->param_discount_price);
            $SalePrice->addAttribute('currency', $shop_product->currency->code); */
        }
        // ALL PRODUCTS -5% DISCOUNT
        else {
            $item_row['price'] = $shop_product->price / 0.95;
            $item_row['discount-start-date'] = now()->format('Y-m-d').'T00:00:00Z';
            $item_row['discount-end-date'] = now()->addDays(2)->format('Y-m-d').'T00:00:00Z';
            $item_row['discount-price'] = $shop_product->price;
        }


        /* elseif ($shop_product->product->ean == 4719331956783) {
            $item_row['price'] = (float)2300;
            $item_row['discount-price'] = (float)2150;
            //$start_date = Carbon::createFromTimeString('2021-06-09 23:00:00')->toDateTime();
            $item_row['discount-start-date'] = '2021-06-09';
            //$end_date = Carbon::createFromTimeString('2021-06-30 23:00:00')->toDateTime();
            $item_row['discount-end-date'] = '2021-07-31';
            $item_row['quantity'] = 1;
        } */

        return $item_row;
    }

}
