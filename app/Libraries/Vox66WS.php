<?php

namespace App\Libraries;

use App\Attribute;
use App\Product;
use App\ProductAttribute;
use App\Provider;
use App\ProviderSheet;
use App\ProviderUpdate;
use App\Traits\HelperTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use SoapClient;
use Throwable;

class Vox66WS
{
    use HelperTrait;

    private $provider;
    //private $presta_connections;
    //private $shop_urls;

    private $user = "";
    private $password = "";
    private $url = "";
    private $version = "1.2";
    private $client = null;



    function __construct()
    {
        $this->provider = Provider::whereName('vox66api')->first();

        if(!Storage::exists('providers/'))
            Storage::makeDirectory('providers/');

        if(!Storage::exists('providers/vox66api/'))
            Storage::makeDirectory('providers/vox66api/');

        $cookie_container = $this->vox66Login();
        if ($valid = $this->vox66IsSessionValid()) {

            /* $langs = $this->vox66GetLanguageCodes();
          */

            /* $products = Product::whereDoesntHave('images')
                ->whereIn('supplier_id', [1,8,10,11,13,14])
                ->whereNotNull('ean')
                ->whereNull('provider_id')
                ->where('stock', '>', 0)
                ->take(10)
                ->orderBy('id', 'desc')
                ->get(); */



            //Storage::append('providers/'.date('Y-m-d_H-i').'_products.json', json_encode($products));
            //Storage::append('providers/'.date('Y-m-d_H-i').'_products_id.json', json_encode($products->pluck('id')));

            //$ok = $this->vox66InsertAndUpdate($products);
            $skus = $this->vox66GetUpdatedSkus('2000-01-01');

            $sheets = [];
            if (isset($skus->diffgram) && isset($skus->diffgram->dsMyCatalogue_Availability)) {
                foreach ($skus->diffgram->dsMyCatalogue_Availability->MyCatalogue_Availability as $availability) {
                    if ($dsDataSheets = $this->vox66GetDataSheet($availability)) {
                        $product = $this->updateProducts($dsDataSheets);
                        $sheets[] = [$dsDataSheets, $product];
                    }
                }
            }

            //$attribute_names = $this->vox66GetUniqueAttributeNames('es');

        }
        else
            return $this->nullAndStorage('Vox66WS', 'NO session valid.');

    }


    /***********  PRIVATE FUNCTIONS *************/



    private function vox66doRequest($request, $action, $filename)
    {
        try {
            $res = $this->client->__doRequest($request, $this->url, $action, $this->version);
            Storage::put('providers/'.date('Y-m-d_H-i-s').'_'.str_replace(['\\', '::'], ['_','__'], $filename).'_REQUEST.xml', $request);
            Storage::put('providers/'.date('Y-m-d_H-i-s').'_'.str_replace(['\\', '::'], ['_','__'], $filename).'_RESPONSE.xml', $res);

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
            $body = '<Login xmlns="https://wsc.vox66.com/">
                <UserId>'.$this->user.'</UserId>
                <Password>'.$this->password.'</Password>
                </Login>';

            $login_r = $this->vox66request($body);
            $action_l = 'https://wsc.vox66.com/Login';
            $this->client = new SoapClient("https://wsc.vox66.com/Content.asmx?WSDL");

            $this->vox66doRequest($login_r, $action_l, __METHOD__);

            return $this->client->__getCookies();

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    // IsSessionValid
    private function vox66IsSessionValid()
    {
        try {
            $body = '<IsSessionValid xmlns="https://wsc.vox66.com/" />';
            $request = $this->vox66request($body);
            $action = 'https://wsc.vox66.com/IsSessionValid';

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
            $body = '<GetLanguageCodes xmlns="https://wsc.vox66.com/" />';
            $request = $this->vox66request($body);
            $action = 'https://wsc.vox66.com/GetLanguageCodes';
            $xml = $this->vox66doRequest($request, $action, __METHOD__);

            return $xml;

            // OK | KO
            return (string)$xml->Body->MyCatalogue_InsertAndUpdateResponse->MyCatalogue_InsertAndUpdateResult ?? null;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    // MyCatalogue_InsertAndUpdate
    private function vox66InsertAndUpdate(Collection $products)
    {
        try {
            $body = '';
            foreach ($products as $product) {

                $body .= '<MyCatalogue_InsertAndUpdate xmlns="https://wsc.vox66.com/">'.
                    '<YourSku>'.$product->id.'</YourSku>'.
                    '<Manufacturer>'.($product->brand->name ?? null).'</Manufacturer>'.
                    '<PartNumber>'.$product->pn.'</PartNumber>'.
                    '<EAN>'.$product->ean.'</EAN>'.
                    '<ProductName>'.$product->name.'</ProductName>'.
                    '</MyCatalogue_InsertAndUpdate>';
            }

            $request = $this->vox66request($body);
            $action = 'https://wsc.vox66.com/MyCatalogue_InsertAndUpdate';
            $xml = $this->vox66doRequest($request, $action, __METHOD__);

            // OK | KO
            return (string)$xml->Body->MyCatalogue_InsertAndUpdateResponse->MyCatalogue_InsertAndUpdateResult ?? null;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    // MyCatalogue_GetUpdatedSkus
    private function vox66GetUpdatedSkus($last_update)
    {
        try {
            $body = '<MyCatalogue_GetUpdatedSkus xmlns="https://wsc.vox66.com/">
                    <LastUpdate>'.$last_update.'</LastUpdate>
                </MyCatalogue_GetUpdatedSkus>';

            $request = $this->vox66request($body);

            $action = 'https://wsc.vox66.com/MyCatalogue_GetUpdatedSkus';
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
            $body = '<MyCatalogue_GetDataSheet xmlns="https://wsc.vox66.com/"><YourSku>'.$availability->Sku.'</YourSku></MyCatalogue_GetDataSheet>';

            $request = $this->vox66request($body);
            $action = 'https://wsc.vox66.com/MyCatalogue_GetDataSheet';
            $xml = $this->vox66doRequest($request, $action, __METHOD__);

            if ($ean = (string)$xml->Body->MyCatalogue_GetDataSheetResponse->MyCatalogue_GetDataSheetResult->diffgram->dsDataSheets->Names->UpcCode)
                Storage::put('providers/vox66api/'.$ean.'.xml', $xml->asXML());

            return $xml->Body->MyCatalogue_GetDataSheetResponse->MyCatalogue_GetDataSheetResult->diffgram->dsDataSheets;

            /* foreach ($skus as $sku) {
                $body .= '<MyCatalogue_GetDataSheet xmlns="https://wsc.vox66.com/"><YourSku>'.$sku.'</YourSku></MyCatalogue_GetDataSheet>';
            }

            $request = $this->vox66request($body);
            $action = 'https://wsc.vox66.com/MyCatalogue_GetDataSheet';
            $xml = $this->vox66doRequest($request, $action, __METHOD__);

            return $xml->Body->MyCatalogue_GetDataSheetResponse->MyCatalogue_GetDataSheetResult; */

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }


    // MyCatalogue_GetDataSheet
    private function vox66GetDataSheetByLanguage($availability)
    {
        try {
            $body = '<MyCatalogue_GetDataSheet_byLanguage xmlns="https://wsc.vox66.com/">'.
                '<YourSku>'.$availability->Sku.'</YourSku>'.
                '<LanguageCode>es</LanguageCode>'.
                '</MyCatalogue_GetDataSheet_byLanguage>';


            $request = $this->vox66request($body);
            $action = 'https://wsc.vox66.com/MyCatalogue_GetDataSheet_byLanguage';
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
            $body = '<MyCatalogue_GetUniqueAttributeNames xmlns="https://wsc.vox66.com/">
                    <LanguageCode>'.$lang.'</LanguageCode>
                </MyCatalogue_GetUniqueAttributeNames>';

            $request = $this->vox66request($body);
            $action = 'https://wsc.vox66.com/MyCatalogue_GetUniqueAttributeNames';
            $xml = $this->vox66doRequest($request, $action, __METHOD__);

            return $xml->Body->MyCatalogue_GetUniqueAttributeNamesResponse->MyCatalogue_GetUniqueAttributeNamesResult;

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


    private function updateProductAttributes(Product $product, $dsDataSheets_AttributesSearch)
    {
        try {
            if (!isset($product->category_id)) return null;

            foreach ($dsDataSheets_AttributesSearch as $attributeSearch) {

                if ($attributeSearch->AttributeName &&  $attributeSearch->ValueUnit) {
                    $attribute = Attribute::updateOrCreate([     //Attribute::firstOrCreate([
                        'category_id'   => $product->category_id,
                        'name'          => $attributeSearch->AttributeName
                    ],[
                        'type_id'       => null,
                        'code'          => $attributeSearch->AttributeID
                    ]);

                    // id, product_id, attribute_id, name, value
                    ProductAttribute::updateOrCreate([
                        'product_id'    => $product->id,
                        'attribute_id'  => $attribute->id,
                    ],[
                        'value'         => $attributeSearch->ValueUnit
                    ]);
                }
            }

            return true;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$product, $dsDataSheets_AttributesSearch]);
        }

    }


    private function insertProductImages(Product $product, $dsDataSheets_Images)
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
                }

                foreach ($dsDataSheets_Images as $dsDataSheet_Image)
                    $product->updateOrCreateExternalImage($dsDataSheet_Image->URL);

                return true;
            }

            return false;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$product, $dsDataSheets_Images]);
        }
    }


    public function updateProducts($dsDataSheets)
    {
        try {
            $res = [];
            if ($dsDataSheets->Result->Ack == 'OK') {

                $ean = $dsDataSheets->Names->UpcCode;
                if ($products = Product::whereEan($ean)->get()) {

                    $count_updateds = 0;
                    $count_shop_updateds = 0;
                    $provider = Provider::whereName('vox66api')->first();
                    foreach ($products as $product) {
                        if (!isset($product->provider_id)) {

                            $count_updateds++;
                            $product->provider_id = $provider->id;
                            if ($pn = $dsDataSheets->Names->Product_PartNumber && !isset($product->pn))
                                $product->pn = $pn;

                            //$name = $dsDataSheets->Names->Name;
                            if ($name = $dsDataSheets->Names->ExtendedName)
                                $product->name = $name;

                            // <body><![CDATA[ Hagas lo que.....]]><br/></body>
                            if ($shortdesc_xml = simplexml_load_string(file_get_contents($dsDataSheets->Names->MarketingDescription))) {
                                $shortdesc = (string)$shortdesc_xml;
                                $product->shortdesc = $shortdesc;

                                $longdesc = $shortdesc;
                                if ($basic_longdesc = $this->getBasicLongdesc($dsDataSheets->Basic))
                                    $longdesc .= $basic_longdesc;
                            }

                            $product->save();

                            $this->updateProductAttributes($product, $dsDataSheets->AttributesSearch);
                            $this->insertProductImages($product, $dsDataSheets->Images);

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
                        }
                    }

                    ProviderUpdate::create([
                        'products'  => $count_updateds
                    ]);
                }
            }

            Storage::append('providers/updates/'.date('Y-m-d_H').'.json', json_encode($res));

            return $res;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, $dsDataSheets);
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


    public function update()
    {
        try {
            $cookie_container = $this->vox66Login();
            if ($valid = $this->vox66IsSessionValid()) {

                // Insert Product SKUs
                if ($products = Product::select('products.*')
                    ->leftJoin('provider_sheets', 'provider_sheets.ean', '<>', 'products.ean')
                    ->whereIn('products.supplier_id', [1,8,10,11,13,14])
                    ->whereNotNull('products.ean')
                    ->whereNull('products.provider_id')
                    ->where('products.stock', '>', 0)
                    ->orderBy('products.id', 'desc')
                    ->take(100)
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
                    }

                    $ok = $this->vox66InsertAndUpdate($products);
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

                return $skus;
            }

            return 'No Vox66 session valid '.$valid;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, null);
        }
    }

}
