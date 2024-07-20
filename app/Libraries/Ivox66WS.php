<?php

namespace App\Libraries;

use App\Attribute;
use App\Product;
use App\ProductAttribute;
use App\Provider;
use App\ProviderUpdate;
use App\Shop;
use Carbon\Carbon;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class Ivox66WS
{
    private $provider;
    private $presta_connections;
    private $shop_urls;



    function __construct()
    {
        $this->provider = Provider::whereName('vox66')->first();

        $this->presta_connections[0] = DB::connection('prestashop_master');
        $this->presta_connections[1] = DB::connection('prestashop_pro');
        $this->presta_connections[2] = DB::connection('prestashop_electro');
        $this->presta_connections[3] = DB::connection('prestashop_mediamarkt');
        $this->presta_connections[4] = DB::connection('prestashop_thehpshop');

        $this->shop_urls[0] = 'http://shop.idiomund.com/';
        $this->shop_urls[1] = 'https://pro.idiomund.com/';
        $this->shop_urls[2] = 'http://electro.idiomund.com/';
        $this->shop_urls[3] = 'http://media.idiomund.com/';
        $this->shop_urls[4] = 'https://www.thehpshop.com/';
    }


    /***********  PRIVATE FUNCTIONS *************/


    private function getVox66DataBasic(ConnectionInterface $connection, $reference, $ean13)
    {
        Storage::append('supplier/ivox66/log.json', json_encode(date('Y-m-d H:i:s').'_getVox66DataBasic_'.$reference.'_'.$ean13));
        try {
            // name, description, description_short
            return $connection->table('ps_product as p')->select('pl.name as name',
                    'pl.description as description',
                    'pl.description_short as description_short')
                ->leftJoin('ps_product_lang as pl', 'pl.id_product', '=', 'p.id_product')
                ->where('pl.id_lang', 1)
                ->where('pl.id_shop', 1)
                ->where('p.reference', 'LIKE', '%' .$reference. '%')
                ->where('p.ean13', 'LIKE', '%' .$ean13. '%')
                ->first();

        } catch (Throwable $th) {
            Storage::append('supplier/ivox66/errors/'.date('Y-m-d'). '_getVox66DataBasic.json', json_encode([$reference, $ean13, $th->getMessage(), $th->getTrace()]));
            return null;
        }
    }


    private function getVox66DataImages(ConnectionInterface $connection, $reference, $ean13, $shop_url)
    {
        Storage::append('supplier/ivox66/log.json', json_encode(date('Y-m-d H:i:s').'_getVox66DataImages_'.$reference.'_'.$ean13.'_'.$shop_url));
        try {
            // Array image_url
            return $connection->table('ps_product as p')->select(/*'image_shop.cover as cover',*/
                    DB::raw("CONCAT('" .$shop_url. "', image_shop.`id_image`,'-large_default/', pl.`link_rewrite`,'.jpg') AS image_url"))
                ->leftJoin('ps_product_lang as pl', 'pl.id_product', '=', 'p.id_product')
                ->leftJoin('ps_image_shop as image_shop', 'image_shop.id_product', '=', 'p.id_product')
                ->where('pl.id_lang', 1)
                ->where('pl.id_shop', 1)
                ->where('image_shop.id_shop', 1)
                ->where('p.reference', 'LIKE', '%' .$reference. '%')
                ->where('p.ean13', 'LIKE', '%' .$ean13. '%')
                ->orderBy('image_shop.cover', 'desc')
                ->get();

        } catch (Throwable $th) {
            Storage::append('supplier/ivox66/errors/'.date('Y-m-d'). '_getVox66DataImages.json', json_encode([$reference, $ean13, $shop_url, $th->getMessage(), $th->getTrace()]));
            return null;
        }
    }


    private function getVox66DataFeatures(ConnectionInterface $connection, $reference, $ean13)
    {
        Storage::append('supplier/ivox66/log.json', json_encode(date('Y-m-d H:i:s').'_getVox66DataFeatures_'.$reference.'_'.$ean13));
        try {
            return $connection->table('ps_product as p')->select('pfl.name', 'pfvl.value')
                ->leftJoin('ps_product_lang as pl', 'pl.id_product', '=', 'p.id_product')
                ->leftJoin('ps_feature_product as pfp', 'pfp.id_product', '=', 'p.id_product')
                ->leftJoin('ps_feature_lang as pfl', 'pfl.id_feature', '=', 'pfp.id_feature')
                ->leftJoin('ps_feature_value as pfv', 'pfp.id_feature_value', '=', 'pfv.id_feature_value')
                ->leftJoin('ps_feature_value_lang as pfvl', 'pfvl.id_feature_value', '=', 'pfv.id_feature_value')
                ->where('pl.id_lang', 1)
                ->where('pl.id_shop', 1)
                ->where('pfl.id_lang', 1)
                ->where('pfvl.id_lang', 1)
                ->where('p.reference', 'LIKE', '%' .$reference. '%')
                ->where('p.ean13', 'LIKE', '%' .$ean13. '%')
                ->orderBy('name')
                ->orderBy('value')
                ->get();

        } catch (Throwable $th) {
            Storage::append('supplier/ivox66/errors/'.date('Y-m-d'). '_getVox66DataFeatures.json', json_encode([$reference, $ean13, $th->getMessage(), $th->getTrace()]));
            return null;
        }
    }


    private function getVox66Data($reference, $ean13)
    {
        Storage::append('supplier/ivox66/log.json', json_encode(date('Y-m-d H:i:s').'_getVox66Data_'.$reference.'_'.$ean13));
        $vox_data = [];

        $count = 0;
        $vox_raw_data_basic = null;
        while (!isset($vox_raw_data_basic) && $count < 5) {

            // name, description, description_short
            $vox_raw_data_basic = $this->getVox66DataBasic($this->presta_connections[$count], $reference, $ean13);
            $count++;
        }

        if (isset($vox_raw_data_basic)) {

            // title & description
            $name = stripslashes($vox_raw_data_basic->name);
            $description_short = stripslashes($vox_raw_data_basic->description_short);
            $description = stripslashes($vox_raw_data_basic->description);

            $vox_data['title'] = (strlen($name) <= strlen($description_short)) ? $description_short : $name;
            $vox_data['title'] = str_replace(['ª','®','™', '\\', ';'], ['a','','', '', ' -'], $vox_data['title']);

            $vox_data['description'] = empty($description) ? $vox_data['title'] : $description;
            $vox_data['description'] = str_replace(['ª','®','™', '\\', ';'], ['a','','', '', ' -'], $vox_data['description']);
            // Adds <br> before table Idiomund description
            $vox_data['description'] = str_replace('.<table class', '.<br><br><table class', $vox_data['description']);

            // images
            if ($vox_images = $this->getVox66DataImages($this->presta_connections[$count-1], $reference, $ean13, $this->shop_urls[$count-1]))
                if ($vox_images->isNotEmpty()) {
                    $url_images = $vox_images->map(function ($item, $key) {
                        return $item->image_url;
                    });
                    $vox_data['images'] = $url_images->all();
                }

            // features
            if($vox_features = $this->getVox66DataFeatures($this->presta_connections[$count-1], $reference, $ean13))
                if ($vox_features->isNotEmpty()) {
                    $vox_data['features'] = $vox_features;
                }
        }

        // title, description, images, features
        return $vox_data;
    }


    private function insertVox66Images(Product $product, &$images)
    {
        // Los productos tienen: 1 imágen de la VIEW + N imágenes de Vox66, donde N in [0,1,2,...]
        if (count($images) > $product->images()->count())
            $product->updateOrCreateExternalImages($images);
    }


    private function insertVox66Features(Product $product, &$vox_data_features)
    {
        Storage::append('supplier/ivox66/log.json', json_encode(date('Y-m-d H:i:s').'_insertVox66Features_'.$product->ean.'_'.$product->pn));
        foreach ($vox_data_features as $feature) {

            if ($feature->name != null) {
                $attribute = Attribute::firstOrCreate(
                    [
                        'category_id'   => $product->category_id,
                        'name'          => $feature->name
                    ],
                    []
                );

                // id, product_id, attribute_id, name, value
                ProductAttribute::firstOrCreate(
                    [
                        'product_id'    => $product->id,
                        'attribute_id'  => $attribute->id,
                        'value'         => $feature->value,
                    ]
                );
            }
        }
    }


    private function getVox66Updates()
    {
        Storage::append('supplier/ivox66/log.json', json_encode(date('Y-m-d H:i:s').'_getVox66Updates'));
        return $this->presta_connections[0]
            ->table('ps_vox_products as vp')
            ->select('vp.ean as ean',
                'vp.reference as reference',
                'vp.manufacturer as manufacturer',
                'vp.last_update as last_update')
            ->orderBy('vp.last_update', 'desc')
            ->limit('200')
            ->get();
    }


    private function existsVox66Data($vox66_updates, $ean, $reference, $manufacturer)
    {
        try {

            Storage::append('supplier/ivox66/log.json', json_encode(date('Y-m-d H:i:s').'_existsVox66Data_'.$ean.'_'.$reference.'_'.$manufacturer));

            return $vox66_updates->first(function ($vox66_update) use ($ean, $reference, $manufacturer) {
                return (
                    isset($vox66_update) &&
                    (
                        (isset($ean) && !empty($ean) && $vox66_update->ean == $ean) ||
                        (
                            (isset($reference) && !empty($reference) && stripos($vox66_update->reference, $reference) !== false) &&
                            (
                                isset($manufacturer) && !empty($manufacturer) &&
                                (
                                    strtolower($vox66_update->manufacturer) == strtolower($manufacturer) ||
                                    (
                                        substr(strtolower($vox66_update->manufacturer), 0, 2) == 'hp' &&
                                        substr(strtolower($manufacturer), 0, 2) == 'hp'
                                    )
                                )
                            )
                        )
                    )
                );

            });


            /* return $vox66_updates->filter(function ($vox66_update) use ($ean, $reference, $manufacturer) {
                return (
                    $vox66_update->ean == $ean ||
                    (
                        stripos($vox66_update->reference, $reference) &&
                        strtolower($vox66_update->manufacturer) == strtolower($manufacturer)
                    )
                );
            }); */


        } catch (Throwable $th) {

        }
    }


    private function updateByVox66Data(Product $product, $updateTitle = false)
    {
        Storage::append('supplier/ivox66/log.json', json_encode(date('Y-m-d H:i:s').'_updateByVox66Data_'.$product->ean.'_'.$product->pn));
        // title, description, images, features
        $vox_data = $this->getVox66Data($product->pn, $product->ean);

        if (!empty($vox_data)) {

            if ($updateTitle && !$product->fix_text) {
                // str_replace('\\', '', (trim(
                    // addslashes
                $title = (strlen($vox_data['title']) > strlen($product->name)) ? $vox_data['title'] : $product->name;
                $title = str_replace('\\', '', trim($title));
                $description = $vox_data['description'];
                $description = str_replace('\\', '', trim($description));
                $product->update([
                    'name'      => mb_substr($title, 0, 255),
                    'longdesc'  => $description,
                ]);
            }

            // Images
            if (isset($vox_data['images'])) {
                $this->insertVox66Images($product, $vox_data['images']);
            }

            // Features
            if (isset($vox_data['features'])) {
                $this->insertVox66Features($product, $vox_data['features']);
                $product->provider_id = $this->provider->id;
                $product->save();

                return true;
            }

            return false;
        }

        return false;
    }


    /***********  PUBLIC FUNCTIONS *************/


    public function update()
    {
        try {

            $vox66_updates = $this->getVox66Updates();

            /* Storage::append('supplier/ivox66/'.date('Y-m-d'). '_vox66_updates.json', json_encode($vox66_updates));
            return 0; */


            $products = Product::whereNull('provider_id')
                ->where('stock', '>', 0)
                ->whereIn('supplier_id', [1,2,4,5,7,8,10,11,12,13,14])
                ->whereDate('created_at', '>', Carbon::today()->addDays(-100)->toDateString())
                ->get();

            // Update Products
            $updateds = [];
            if ($products->count())
                foreach ($products as $product) {
                    if ($product->ean || ($product->pn && $product->brand_id))
                        $existsVox66Data = $this->existsVox66Data($vox66_updates, $product->ean, $product->pn, $product->brand->name);
                        if (isset($existsVox66Data))
                            if ($this->updateByVox66Data($product, true))
                                $updateds[] = $product->id;


                }


            Storage::append('supplier/ivox66/'.date('Y-m-d'). '_vox66_updateds.json', json_encode($updateds));

            $count_updateds = count($updateds);
            ProviderUpdate::create([
                'products'  => $count_updateds
            ]);

            // Update Shop Products
            if ($count_updateds) {
                $shops = Shop::whereEnabled(1)->whereCode('locura')->get();
                foreach ($shops as $shop) {
                    //if ($ws = MarketWS::getMarketWS($shop)) {
                        $shop_products = $shop->shop_products()
                            ->whereNull('provider_id')
                            ->whereIn('product_id', $updateds)
                            ->update(['provider_id' => $this->provider->id]);

                        //if ($shop_products->count()) {
                            //$ws->postUpdatedProducts($shop_products);
                            foreach ($shop_products as $shop_product) {
                                $shop_product->provider_id = $this->provider->id;
                                $shop_product->save();
                            }

                            ProviderUpdate::create([
                                'products'  => $shop_products->count(),
                                'shop_id'   => $shop->id,
                            ]);
                        //}
                    //}
                }
            }

            foreach ($this->presta_connections as $presta_connection) {
                $presta_connection->disconnect();
            }

            return $count_updateds;

        } catch (Throwable $th) {

            return $th->getMessage();
        }
    }


    // OLD FUNCTION, NOW NO WORKS
    public function setCurrentProvider()
    {
        $products = Product::filter([])->whereNull('products.provider_id')->get();

        $count = 0;
        foreach ($products as $product) {
            if ($product->product_attributes_count) {

                $product->provider_id = $this->provider->id;
                $product->save();

                foreach ($product->shop_products as $shop_product) {
                    $shop_product->provider_id = $this->provider->id;
                    $shop_product->save();
                }

                $count++;
            }
        }
    }


}
