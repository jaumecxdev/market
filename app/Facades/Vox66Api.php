<?php

namespace App\Facades;

use App\Attribute;
use App\Product;
use App\ProductAttribute;
use App\Provider;
use App\ProviderAttribute;
use App\ProviderAttributeValue;
use App\ProviderCategory;
use App\ProviderProductAttribute;
use App\ProviderSheet;
use App\ProviderUpdate;
use App\Traits\HelperTrait;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;
use SoapClient;
use Throwable;


class Vox66Api
{
    use HelperTrait;

    private $user;  // = env('VOX66_USER');
    private $password;  // = env('VOX66_PASSWORD');
    private $url;   // = env('VOX66_URL');
    private $version;   // = env('VOX66_VERSION');
    private $client = null;


    //self::MANUFACTURER_VOX66_MPE
    const MANUFACTURER_VOX66_MPE = [
        'Hewlett Packard Enterprise'    => 'Hp ent',
        'NewStar'                       => 'Newstar Computer',
        'HP Inc.'                       => 'Hp inc'
    ];


    /***********  PRIVATE FUNCTIONS *************/


    private function vox66doRequest($request, $action, $filename)
    {
        try {
            $res = $this->client->__doRequest($request, $this->url, $action, $this->version);
            //Storage::put('providers/'.date('Y-m-d_H-i-s').'_'.str_replace(['\\', '::'], ['_','__'], $filename).'_REQUEST.xml', $request);
            //Storage::put('providers/'.date('Y-m-d_H-i-s').'_'.str_replace(['\\', '::'], ['_','__'], $filename).'_RESPONSE.xml', $res);

            return simplexml_load_string(str_ireplace(['SOAP-ENV:', 'SOAP:', 'diffgr:'], '', $res));

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$request, $action, $filename]);
        }
    }


    private function vox66request($body)
    {
       /*  $xml = $this->vox66BuildEnvelope();
        $xml->addChild('Body', $body);

        return $xml->asXML(); */
        return '<?xml version="1.0" encoding="utf-8" ?>'.'
            <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '.
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '.
            'xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'.
            '<soap:Body>'.$body.'</soap:Body></soap:Envelope>';
    }


    private function vox66Login()
    {
        try {
            $this->user = config('voxapi.user');
            $this->password = config('voxapi.password');
            $this->url = config('voxapi.url');
            $this->version = config('voxapi.version');

            $body = '<Login xmlns="'.$this->url.'">
                <UserId>'.$this->user.'</UserId>
                <Password>'.$this->password.'</Password>
                </Login>';

            $login_r = $this->vox66request($body);
            $action_l = $this->url.'Login';
            $this->client = new SoapClient($this->url."Content.asmx?WSDL");

            $this->vox66doRequest($login_r, $action_l, __METHOD__);

            //dd($body, $login_r, $action_l, $this, $this->client->__getCookies());

            return $this->client->__getCookies();

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    // IsSessionValid
    private function vox66IsSessionValid()
    {
        try {
            $body = '<IsSessionValid xmlns="'.$this->url.'" />';
            $request = $this->vox66request($body);
            $action = $this->url.'IsSessionValid';

            $xml = $this->vox66doRequest($request, $action, __METHOD__);

            // true | false
            return (Boolean)$xml->Body->IsSessionValidResponse->IsSessionValidResult;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    // GetLanguageCodes
    private function vox66GetLanguageCodes()
    {
        try {
            // NO FUNCIONA: faultcode: Object reference not set to an instance of an object
            $body = '<GetLanguageCodes xmlns="'.$this->url.'" />';
            $request = $this->vox66request($body);
            $action = $this->url.'GetLanguageCodes';
            $xml = $this->vox66doRequest($request, $action, __METHOD__);

            return $xml;

            // OK | KO
            return (string)$xml->Body->MyCatalogue_InsertAndUpdateResponse->MyCatalogue_InsertAndUpdateResult ?? null;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    // MyCatalogue_InsertAndUpdate
    private function vox66InsertAndUpdate(Product $product)
    {
        try {
            $body = '<MyCatalogue_InsertAndUpdate xmlns="'.$this->url.'">'.
                '<YourSku>'.$product->id.'</YourSku>'.
                '<Manufacturer>'.($product->brand->name ?? null).'</Manufacturer>'.
                '<PartNumber>'.$product->pn.'</PartNumber>'.
                '<EAN>'.$product->ean.'</EAN>'.
                '<ProductName>'.str_replace('/', ' ', $product->name).'</ProductName>'.
                '</MyCatalogue_InsertAndUpdate>';

            $request = $this->vox66request($body);
            $action = $this->url.'MyCatalogue_InsertAndUpdate';
            // OK | KO
            if ($xml = $this->vox66doRequest($request, $action, __METHOD__))
                if (isset($xml->Body))
                    return (string)$xml->Body->MyCatalogue_InsertAndUpdateResponse->MyCatalogue_InsertAndUpdateResult ?? null;

            return $this->nullAndStorage(__METHOD__, [$product->toArray(), $body ?? null, isset($xml) ? (!is_bool($xml) ? $xml->asXML() : $xml) : null]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$product->toArray(), $body ?? null, $xml->as ?? null]);
        }
    }


    // MyCatalogue_GetUpdatedSkus
    private function vox66GetUpdatedSkus($last_update)
    {
        try {
            $body = '<MyCatalogue_GetUpdatedSkus xmlns="'.$this->url.'">
                    <LastUpdate>'.$last_update.'</LastUpdate>
                </MyCatalogue_GetUpdatedSkus>';

            $request = $this->vox66request($body);

            $action = $this->url.'MyCatalogue_GetUpdatedSkus';
            $xml = $this->vox66doRequest($request, $action, __METHOD__);

            // Sku list | max 50
            return $xml->Body->MyCatalogue_GetUpdatedSkusResponse->MyCatalogue_GetUpdatedSkusResult;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    // MyCatalogue_GetDataSheet
    private function vox66GetDataSheet($availability)
    {
        try {
            if ($availability->Sku) {
                $body = '<MyCatalogue_GetDataSheet xmlns="'.$this->url.'"><YourSku>'.$availability->Sku.'</YourSku></MyCatalogue_GetDataSheet>';

                $request = $this->vox66request($body);
                $action = $this->url.'MyCatalogue_GetDataSheet';
                $xml = $this->vox66doRequest($request, $action, __METHOD__);

                if ($xml->Body &&
                    $xml->Body->MyCatalogue_GetDataSheetResponse &&
                    $xml->Body->MyCatalogue_GetDataSheetResponse->MyCatalogue_GetDataSheetResult) {

                        $ean = (string)$xml->Body->MyCatalogue_GetDataSheetResponse->MyCatalogue_GetDataSheetResult->diffgram->dsDataSheets->Names->UpcCode;
                        Storage::put('providers/vox66api/'.$ean.'.xml', $xml->asXML());
                        return $xml->Body->MyCatalogue_GetDataSheetResponse->MyCatalogue_GetDataSheetResult->diffgram->dsDataSheets;
                    }
            }

            return $this->nullAndStorage(__METHOD__, [$availability, $xml->asXML() ?? null]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $availability);
        }
    }


    // MyCatalogue_GetDataSheet
    private function vox66GetDataSheetByLanguage($availability)
    {
        try {
            $body = '<MyCatalogue_GetDataSheet_byLanguage xmlns="'.$this->url.'">'.
                '<YourSku>'.$availability->Sku.'</YourSku>'.
                '<LanguageCode>es</LanguageCode>'.
                '</MyCatalogue_GetDataSheet_byLanguage>';

            $request = $this->vox66request($body);
            $action = $this->url.'MyCatalogue_GetDataSheet_byLanguage';
            $xml = $this->vox66doRequest($request, $action, __METHOD__);

            return $xml->Body->MyCatalogue_GetDataSheetResponse->MyCatalogue_GetDataSheetResult;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    //MyCatalogue_GetUniqueAttributeNames
    private function vox66GetUniqueAttributeNames($lang)
    {
        try {
            $body = '<MyCatalogue_GetUniqueAttributeNames xmlns="'.$this->url.'">
                    <LanguageCode>'.$lang.'</LanguageCode>
                </MyCatalogue_GetUniqueAttributeNames>';

            $request = $this->vox66request($body);
            $action = $this->url.'MyCatalogue_GetUniqueAttributeNames';
            if ($xml = $this->vox66doRequest($request, $action, __METHOD__))
                return $xml->Body->MyCatalogue_GetUniqueAttributeNamesResponse->MyCatalogue_GetUniqueAttributeNamesResult;

            return $this->nullAndStorage(__METHOD__, $lang);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    private function getBasicLongdesc($dsDataSheets_Basic)
    {
        try {
            $count = 0;
            $basic_longdesc = '<table class="table table-striped">';
            foreach ($dsDataSheets_Basic as $basic) {
                $basic_longdesc .= '<tr><td><b>'.$basic->Attribute.'</b></td><td>'.$basic->Value.'</td></tr>';
                $count++;
            }
            $basic_longdesc .= '</table>';

            if ($count) return $basic_longdesc;
            return null;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $dsDataSheets_Basic);
        }
    }


    private function updateProductImages(Product $product, $dsDataSheets_Images)
    {
        try {
            if (isset($dsDataSheets_Images) && count($dsDataSheets_Images)) {

                // Remove old images
                if ($product->images->count()) {

                    $images_src_array = $product->images()->pluck('src')
                        ->map(function ($item, $key) use ($product) {
                            return 'public/img/' .$product->id. '/' .$item;
                        })->all();

                    Storage::delete($images_src_array);

                    /* foreach ($product->images as $image) {
                        Storage::delete('public/img/' . $product->id . '/'.$image->src);
                    } */

                    $product->images()->delete();
                }

                $count = 0;
                foreach ($dsDataSheets_Images as $dsDataSheet_Image) {
                    $product->updateOrCreateExternalImage($dsDataSheet_Image->URL);
                    $count++;

                    if ($count > 15) break;
                }

                return true;
            }

            return false;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$product, $dsDataSheets_Images]);
        }
    }


    private function getProviderCategory($provider_id, SimpleXMLElement $dsDataSheets)
    {
        try {
            $provider_category = ProviderCategory::firstOrCreate([
                'provider_id'       => $provider_id,
                'categoryId'        => $dsDataSheets->Names->CategoryID,
            ],[
                'categoryL1'        => $dsDataSheets->Names->CategoryL1,
                'categoryL2'        => $dsDataSheets->Names->CategoryL2,
                'categoryL3'        => $dsDataSheets->Names->CategoryL3,
                'categoryL4'        => $dsDataSheets->Names->CategoryL4,
                'categoryL5'        => ($dsDataSheets->Names->CategoryL5 != '[pending]') ?
                                            $dsDataSheets->Names->CategoryL5 : null,
                'name'              => $dsDataSheets->Names->CategoryL4,
                'enabled'           => 1,
                //'display_order'     => $count_categories,
            ]);

            if (!isset($provider_category->display_order)) {
                $provider_category->display_order = $provider_category->id;
                $provider_category->save();
            }

            return $provider_category;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$provider_id, $dsDataSheets]);
        }
    }


    private function updateProviderProductAttributes($provider_id, $provider_category_id, Product $product, $dsDataSheets_AttributesSearch)
    {
        try {
            foreach ($dsDataSheets_AttributesSearch as $attributeSearch) {

                if ($attributeSearch->AttributeName && $attributeSearch->ValueUnit) {

                    // Provider Attribute
                    $provider_attribute = ProviderAttribute::firstOrCreate([
                        'provider_id'           => $provider_id,
                        'provider_category_id'  => $provider_category_id,
                        'attributeId'           => (string)$attributeSearch->AttributeID,
                    ],[
                        'attributeName'         => (string)$attributeSearch->AttributeName,
                        'name'                  => (string)$attributeSearch->AttributeName,
                        'enabled'               => 1,
                        //'display_order'     => $count_categories,
                    ]);

                    if (!isset($provider_attribute->display_order)) {
                        $provider_attribute->display_order = $provider_attribute->id;
                        $provider_attribute->save();
                    }

                    // Provider Attribuet Value
                    $provider_attribute_value = ProviderAttributeValue::firstOrCreate([
                        'provider_id'           => $provider_id,
                        'provider_category_id'  => $provider_category_id,
                        'provider_attribute_id' => $provider_attribute->id,
                        'valueId'               => (string)$attributeSearch->ValueID,
                        'valueName'             => (string)$attributeSearch->ValueUnit,
                    ],[
                        'name'                  => (string)$attributeSearch->ValueUnit,
                        'enabled'               => 1,
                        //'display_order'     => $count_categories,
                    ]);

                    if (!isset($provider_attribute_value->display_order)) {
                        $provider_attribute_value->display_order = $provider_attribute_value->id;
                        $provider_attribute_value->save();
                    }

                    // Provider Product Attribute
                    $provider_product_attribute = ProviderProductAttribute::firstOrCreate([
                        'provider_id'                   => $provider_id,
                        'product_id'                    => $product->id,
                        'provider_attribute_id'         => $provider_attribute->id,
                        'provider_attribute_value_id'   => $provider_attribute_value->id,
                    ],[]);

                    if (isset($product->category_id)) {

                        // OLD Attribute
                        $attribute = Attribute::firstOrCreate([
                            'category_id'   => $product->category_id,
                            'name'          => (string)$attributeSearch->AttributeName
                        ],[
                            'type_id'       => null,
                            'code'          => (string)$attributeSearch->AttributeID
                        ]);

                        // OLD Product Attribute
                        ProductAttribute::updateOrCreate([
                            'product_id'    => $product->id,
                            'attribute_id'  => $attribute->id,
                        ],[
                            'value'         => (string)$attributeSearch->ValueUnit
                        ]);
                    }
                }
            }

            return true;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$provider_id, $provider_category_id, $product, $dsDataSheets_AttributesSearch]);
        }
    }



    /*************** PUBLIC FUNCTIONS *******************************/


    public function updateProducts($dsDataSheets)
    {
        try {
            $res = [];
            if ($dsDataSheets->Result->Ack == 'OK' && $ean = (string)$dsDataSheets->Names->UpcCode) {

                if ($products = Product::whereEan($ean)->get()) {

                    $manufacturer = (string)$dsDataSheets->Names->Manufacturer;
                    $manufacturer = self::MANUFACTURER_VOX66_MPE[$manufacturer] ?? $manufacturer;
                    ProviderSheet::updateOrCreate([
                        'ean'       => $ean,
                        //'sku'       => $product->id,
                        //
                    ],[
                        'pn'        => (string)$dsDataSheets->Names->Product_PartNumber,
                        'brand'     => $manufacturer,
                        'available' => 1
                    ]);


                    $count_updateds = 0;
                    $count_shop_updateds = 0;
                    $provider = Provider::whereName('vox66api')->first();
                    foreach ($products as $product) {

                        //if (!isset($product->provider_id)) {

                            // Update Product
                            $count_updateds++;
                            $product->provider_id = $provider->id;

                            if ($pn = $dsDataSheets->Names->Product_PartNumber && !isset($product->pn))
                                $product->pn = $pn;

                            //$name = $dsDataSheets->Names->Name;
                            if ($name = $dsDataSheets->Names->ExtendedName)
                                $product->name = mb_substr($name, 0, 255);
                            elseif ($name = $dsDataSheets->Names->Name)
                                $product->name = mb_substr($name, 0, 255);

                            // <body><![CDATA[ Hagas lo que.....]]><br/></body>
                            $marketing_description = $dsDataSheets->Names->MarketingDescription;
                            if ($marketing_description &&
                                !empty($marketing_description) &&
                                $marketing_description != '{}' &&
                                strlen($marketing_description) > 4 &&
                                $shortdesc_xml = simplexml_load_string(file_get_contents($marketing_description))) {

                                $shortdesc = (string)$shortdesc_xml;
                                $product->shortdesc = $shortdesc;

                                $product->longdesc = $shortdesc;
                                if ($basic_longdesc = $this->getBasicLongdesc($dsDataSheets->Basic))
                                    $product->longdesc .= "\n".$basic_longdesc;

                                if (!isset($product->longdesc) ||$product->longdesc == '')
                                    $product->longdesc = $product->name;
                            }

                            $product->save();

                            $this->updateProductImages($product, $dsDataSheets->Images);

                            if ($provider_category = $this->getProviderCategory($provider->id, $dsDataSheets)) {
                                $product->provider_category_id = $provider_category->id;
                                $product->save();
                                $this->updateProviderProductAttributes($provider->id, $provider_category->id, $product, $dsDataSheets->AttributesSearch);
                            }

                            // Update Shop Products
                            if ($shop_products = $product->shop_products) {
                                $count_shop_updateds += $shop_products->count();
                                foreach ($shop_products as $shop_product) {
                                    $shop_product->provider_id = $provider->id;
                                    $shop_product->save();
                                }
                            }

                            $res[] = [
                                'sku'       => $product->id,
                                'ean'       => $product->ean,
                                'pn'        => $product->pn,
                                'brand'     => $product->brand->name ?? null,
                            ];
                        //}


                    }

                    ProviderUpdate::create([
                        'products'  => $count_updateds
                    ]);
                }
            }

            //Storage::append('providers/updates/'.date('Y-m-d_H').'.json', json_encode($res));

            return $res;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$marketing_description ?? null, $dsDataSheets]);
        }
    }


    public function getLocalDataSheet($ean)
    {
        try {
            if ($xml = Storage::get('providers/vox66api/'.$ean.'.xml'))
                return simplexml_load_string($xml)->Body->MyCatalogue_GetDataSheetResponse->MyCatalogue_GetDataSheetResult->diffgram->dsDataSheets;

            return $this->nullAndStorage(__METHOD__, [$ean, $xml]);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    public function getAttributeNames()
    {
        try {
            $cookie_container = $this->vox66Login();
            if ($valid = $this->vox66IsSessionValid()) {

                if ($res = $this->vox66GetUniqueAttributeNames('es')) {
                    if ($attribute_names = $res->diffgram->dsMyCatalogue_UniqueAttributeNames->GetMyContent_GetUniqueAttributeNames) {

                        $provider = Provider::whereName('vox66api')->first();
                        foreach ($attribute_names as $attribute_name) {

                            $provider_category = ProviderCategory::firstOrCreate([
                                'provider_id'       => $provider->id,
                                'categoryId'        => $attribute_name->CategoryID,
                            ],[
                                'categoryL1'        => $attribute_name->CategoryL1,
                                'categoryL2'        => $attribute_name->CategoryL2,
                                'categoryL3'        => $attribute_name->CategoryL3,
                                'categoryL4'        => $attribute_name->CategoryL4,
                                'categoryL5'        => $attribute_name->CategoryL5 ?? null,
                                'name'              => $attribute_name->CategoryL4,
                                'enabled'           => 1,
                                //'display_order'     => $count_categories,
                            ]);

                            $provider_category->display_order = $provider_category->id;
                            $provider_category->save();

                            $provider_attribute = ProviderAttribute::firstOrCreate([
                                'provider_id'           => $provider->id,
                                'provider_category_id'  => $provider_category->id,
                                'attributeId'           => $attribute_name->AttributeID,
                            ],[
                                'attributeName'         => $attribute_name->AttributeName,
                                'name'                  => $attribute_name->AttributeName,
                                'enabled'               => 1,
                                //'display_order'     => $count_categories,
                            ]);

                            $provider_attribute->display_order = $provider_attribute->id;
                            $provider_attribute->save();
                        }

                        return true;
                    }
                }
            }

            return $this->nullAndStorage( __METHOD__, 'No Vox66 session valid '.$valid);

            //return false;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    public function update()
    {
        try {
            $cookie_container = $this->vox66Login();
            if ($valid = $this->vox66IsSessionValid()) {

                // Insert Product SKUs
                if ($products = Product::select('products.*')
                    ->leftJoin('provider_sheets', 'provider_sheets.ean', '=', 'products.ean')
                    ->whereNull('provider_sheets.ean')
                    ->whereIn('products.supplier_id', [1])      //,8,10,11,13,14,38])     //  16,22,23,24,26,27,28,29])
                    ->whereNotNull('products.ean')
                    ->whereNull('products.provider_id')
                    ->where('products.stock', '>', 0)
                    ->orderBy('products.id', 'desc')
                    ->take(1000)
                    ->get()) {

                    foreach ($products as $product) {
                        ProviderSheet::updateOrCreate([
                            'ean'       => $product->ean,
                        ],[
                            'sku'       => $product->id,
                            'pn'        => $product->pn,
                            'brand'     => $product->brand->name ?? null,
                            //'available' => 0
                        ]);

                        $ok = $this->vox66InsertAndUpdate($product);
                    }


                }

                // Ask for Updated Product SKUs
                $skus = [];
                $updated_skus = $this->vox66GetUpdatedSkus('2000-01-01');
                if (isset($updated_skus->diffgram) && isset($updated_skus->diffgram->dsMyCatalogue_Availability)) {
                    foreach ($updated_skus->diffgram->dsMyCatalogue_Availability->MyCatalogue_Availability as $availability) {
                        if ($dsDataSheets = $this->vox66GetDataSheet($availability))
                            $skus = $this->updateProducts($dsDataSheets);
                    }
                }

                //dd($valid, $skus, $products, $updated_skus);

                return $skus;
            }

            return $this->msgAndStorage( __METHOD__, 'No Vox66 session valid '.$valid, $valid);

            //return 'No Vox66 session valid '.$valid;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    public function updateCurrentProducts()
    {
        try {
            //$ean_files = Storage::files('providers/vox66api');
            //foreach ($ean_files as $ean_file) {
            //$ean = pathinfo($ean_file, PATHINFO_FILENAME);

            $skus = [];
            if ($provider_sheets = ProviderSheet::select('provider_sheets.*')
                ->leftJoin('products', 'provider_sheets.ean', '=', 'products.ean')
                ->where('provider_sheets.available', 1)
                ->whereIn('products.supplier_id', [1,8,10,11,13,14,16,22,23,24,26])
                ->whereNotNull('provider_sheets.ean')
                ->where('provider_sheets.ean', '<>', '')
                ->whereNull('products.provider_id')
                ->where('products.stock', '>', 0)
                ->where('products.ready', 1)
                ->get()) {

                foreach ($provider_sheets as $provider_sheet) {

                    if ($contents = Storage::get('providers/vox66api/'.$provider_sheet->ean.'.xml')) {
                        $xml = simplexml_load_string($contents);
                        if ($dsDataSheets = $xml->Body->MyCatalogue_GetDataSheetResponse->MyCatalogue_GetDataSheetResult->diffgram->dsDataSheets)
                            $skus[] = $this->updateProducts($dsDataSheets);
                    }
                }
            }

            return $skus;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$skus ?? null, $provider_sheets ?? null]);
        }
    }

}
