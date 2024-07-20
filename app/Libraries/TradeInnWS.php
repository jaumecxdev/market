<?php

namespace App\Libraries;

use App\Shop;
use App\ShopProduct;
use Illuminate\Database\Eloquent\Collection;


class TradeInnWS extends MarketFileWS implements MarketWSInterface
{
    const DEFAULT_CONFIG = [
        // MarketWS
        'header_offer' => [
            ['EAN', 'Cantidad', 'Precio compra', 'PVP', 'Descuento']
        ],
        /* 'order_status_ignored' => [],
        'publish_packs' => [
            'enabled' => true,
            'values' => [2, 10, 50]
        ], */
        'functions' => [
            'getBrands'             => false,
            'getCategories'         => false,
            'getAttributes'         => false,
            'getItemRowProduct'     => true,
            'getItemRowOffer'       => true,
            'getItemRowPromo'       => false,
            'getFeed'               => true,
            'getJobs'               => false,
            'getOrders'             => false,
            'getGroups'             => false,
            'getCarriers'           => false,
            'getOrderComments'      => false,
            'postNewProduct'        => false,
            'postUpdatedProduct'    => false,
            'postPriceProduct'      => false,
            'postNewProducts'       => true,
            'postUpdatedProducts'   => true,
            'postPricesStocks'      => true,
            'synchronize'           => false,
            'postGroups'            => false,
            'removeProduct'         => false,
            'postOrderTrackings'    => false,
            'postOrderComment'      => false,
        ],
        //'locale' => 'es_ES',
    ];

    public function __construct(Shop $shop)
    {
        //$this->post_type = 'ftp';   // ftp | url | local
        $this->file_type = 'json';   // csv | xml | json
        //$this->delimiter = ',';
        $this->disk = 'ftp_tradeinn';
        $this->tax = 21;

        parent::__construct($shop);
    }


    public function getItemRowOffer(ShopProduct $shop_product, $extra_data = [])
    {
        if (!isset($shop_product->product->ean)) return null;

        $shop_product->setPriceStock();
        //$description = FacadesMpe::buildString($shop_product->product->buildTitle());

        $item_row = [];
        $header_rows = count($this->header_offer);
        foreach($this->header_offer[$header_rows-1] as $attribute_code) {
            $item_row[$attribute_code] = '';
        }

        $attributes = isset($shop_product->attributes) ? json_decode($shop_product->attributes, true) : [];
        foreach($attributes as $attribute_code => $attribute_value) {
            $item_row[$attribute_code] = $attribute_value;
        }

        $item_row['EAN'] = $shop_product->product->ean;
        $item_row['PartNumber'] = $shop_product->product->pn;
        $item_row['Fabricante'] = $shop_product->product->brand->name;
        $item_row['Cantidad'] = $shop_product->stock;
        $item_row['Precio compra'] = round($shop_product->price / (1 + $this->tax/100), 2);
        $item_row['PVP'] = $shop_product->price;
        $item_row['Descuento'] = 0;
        $item_row['Titulo'] = $shop_product->product->name;

        return $item_row;
    }


    public function getCollectionOffers(Collection $shop_products, $extra_data = [])
    {
        $offers = $shop_products->map(function($shop_product) {
            if (!isset($shop_product->product->ean) || $shop_product->product->ean == '' ) return null;

            $shop_product->setPriceStock();
            $item_row['EAN'] = $shop_product->product->ean;
            $item_row['PartNumber'] = $shop_product->product->pn;
            $item_row['Fabricante'] = $shop_product->product->brand->name;
            $item_row['Cantidad'] = $shop_product->stock;
            $item_row['Precio compra'] = round($shop_product->price / (1 + $this->tax/100), 2);
            $item_row['PVP'] = $shop_product->price;
            $item_row['Descuento'] = 0;
            $item_row['Titulo'] = $shop_product->product->name;

            return $item_row;
        })
        ->reject(function ($item_row) {
            return is_null($item_row);
        });

        return $offers;
    }


}

