<?php

namespace App\Facades;

use App\Product;
use App\Traits\HelperTrait;
use Illuminate\Support\Facades\Storage;
use Throwable;

class Mpe
{
    use HelperTrait;

    public function strtolower_utf8($string) {

        $output    = utf8_decode($string);
        $output    = strtolower($output);
        $output    = utf8_encode($output);

        return $output;
    }


    public function trim_ucfirst($string)
    {
        return ucfirst(trim($string));
    }


    public function ucwords_dot($delimiter, $string)
    {
        return implode('. ', array_map([$this, 'trim_ucfirst'], explode($delimiter, $string)));
    }


    public function getFilenameWithoutExt($filename)
    {
        return ucfirst(strtolower(substr($filename, 0, strpos($filename, '.'))));
    }


    public function getString($supplier_product_name)
    {
        // utf8_decode("SoluciÃ³n Ãºtil y apaÃ±ada a UTF-8");   --> Solución útil y apañada a UTF-8
        return mb_substr(str_replace(['\\', ',', '  '], ['', '.', ' '], trim(mb_convert_encoding($supplier_product_name ?? '', 'UTF-8'))), 0, 255);
    }


    public function getText($supplier_product_description)
    {
        return mb_substr(str_replace(['\\'], [''], strip_tags(trim(mb_convert_encoding($supplier_product_description ?? '', 'UTF-8')))), 0, 65535);
    }


    public function getMPSEan($brand_id, $pn)
    {
        try {
            $products = Product::whereNotNull('ean')->whereNotNull('brand_id')->whereNotNull('pn')
                ->whereBrandId($brand_id)->wherePn($pn)->get();

            foreach ($products as $product) {
                if (is_numeric($product->ean) && strlen($product->ean) == 13)
                    return $product->ean;
            }

            return null;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$brand_id, $pn]);
        }
    }


    public function getPnEan($pn, $ean)
    {
        $pn = trim(
            str_replace(['=', '¦', 'ª', '®', '™', '\\', ';', '&amp;', '#039'], ['', '-', 'a', '', '', '', ' -', '', ''],
                $this->changeAccents($pn))
        );

        /* if ($pn == $ean) {
            if (!is_numeric($ean)) $ean = null;
            elseif (strlen($ean) == 13) $pn = null;
        } */

        if ($ean && is_numeric($ean)) {
            $ean = trim(strval($ean));
            $ean_len = strlen($ean);

            if ($ean_len < 13) {
                for ($i=0; $i<13-$ean_len; $i++)
                    $ean = '0'.$ean;
            }
            elseif ($ean_len == 26)
                $ean = mb_substr($ean, 0, 13);
            elseif ($ean_len == 25 || $ean_len == 24)
                $ean = '0'.mb_substr($ean, 0, 12);
        }

        if ((!isset($ean) || $ean == '') && is_numeric($pn)) {
            $pn_len = strlen($pn);
            if ($pn_len == 13) $ean = $pn;
            elseif ($pn_len == 12) $ean = '0'.$pn;
        }

        return [$pn, $ean];
    }


    // NO USE IT -> DEPRECATED
    public function getEAN($ean)
    {
        if ($ean && $ean != '' && strlen($ean) < 13) {
            $ean = trim(strval($ean));               // ean
            for ($i=0; $i<13-strlen($ean); $i++)
                $ean = '0'.$ean;
        }

        return $ean;
    }


    // NO USE IT -> DEPRECATED -> ONLY IN SUPPLIERPRESTASHOPWS
    public function getPn($pn)
    {
        // Expected format: /^[^<>;={}]*$/u
        return trim(
            str_replace(['=', '¦', 'ª', '®', '™', '\\', ';', '&amp;', '#039'], ['', '-', 'a', '', '', '', ' -', '', ''],
                $this->changeAccents($pn))
            );
    }


    public function buildString($string, $length = 255)
    {
        return mb_substr(
            str_replace(
                ['ª', '®', '™', '\\', ';',  ',', '&amp;', '#039'],
                ['a', '',  '',  '',   ' -', '.',  '',      ''],
                trim(mb_convert_encoding($string, 'UTF-8'))
            ), 0, $length);
    }


    public function buildText($text)
    {
        return str_replace(
            ['ª', '®', '™', '\\', ';',  ',', '&amp;', '#039'],
            ['a', '',  '',  '',   ' -', '.',  '',      ''],
            strip_tags(trim(mb_convert_encoding($text, 'UTF-8')))
        );
    }


    public function plainTextToHtml($text)
    {
        return '<p>'.nl2br($text, false).'</p>';
    }


    public function stripAccents($stripAccents){
        return strtr($stripAccents,'àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ','aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
    }


    public function cleanStr($string) {
        // Replaces all spaces with hyphens.
        $string = str_replace(' ', '-', $string);

        // Removes special chars.
        $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string);
        // Replaces multiple hyphens with single one.
        $string = preg_replace('/-+/', '-', $string);

        return $string;
    }


    // supplier_params 042021
    // ALL $cost INCLUDES: CANON, RAPPEL & PORTS
    /*public function getCost($field_cost)
    {
         $cost = $fields['cost'];
        $canon = isset($fields['canon']) ? ($fields['canon'] + $supplier_params['canon']) : $supplier_params['canon'];
        $rappel = isset($fields['rappel']) ? ($fields['rappel'] + $supplier_params['rappel']) : $supplier_params['rappel'];
        $ports = isset($fields['ports']) ? ($fields['ports'] + $supplier_params['ports']) : $supplier_params['ports'];

        return round(
            (floatval($cost) + $canon) * (1 - $rappel / 100) + $ports,
            2);
    }*/


    public function roundFloat($value)
    {
        return round(floatval($value), 2);
    }


    public function roundFloatEsToEn($value)
    {
        return round(floatval(str_replace([',', ' '], ['.', ''], $value)), 2);
    }


    // OBSOLETE -> USE ShopProduct->setMPPrice
    public function getMPPrice($cost, $client_fee, $mps_fee, $mp_fee, $mp_fee_addon, $iva = 21)
    {
        $mp_fee += $mps_fee;
        $price = round(
            (1 + $iva/100) * ($cost + $mp_fee_addon) /
            (1 - $client_fee/100 - $mp_fee/100 - $mp_fee/100 * $iva/100),
            2
        );

        return $price;
    }


    // OBSOLETE -> USE ShopProduct->setMPPrice
    public function getMPPriceV2($cost, $client_fee, $mps_fee, $mp_fee, $mp_lot = 0, $mp_lot_fee = 0,
        $mp_fee_addon = 0, $bfit_min = 0, $mp_bfit_min = 0, $buybox_price = 0, $reprice_fee_min = 0, $iva = 21)
    {
        // PVP = 1,21 * (TRAMO*MP_FEE_1 - TRAMO*MP_FEE_2 + COST + MP_BFIT_ADDON) / (1 - FEE - 1,21*MP_FEE_2)
        $mp_fee += $mps_fee;
        $price = (1 + $iva/100) * ($mp_lot * $mp_fee/100 -  $mp_lot * $mp_lot_fee/100 + $cost + $mp_fee_addon) /
            (1 - $client_fee/100 - $mp_fee/100 - (1 + $iva/100) * $mp_lot_fee/100);

        // $price <= $mp_lot ?
        if ($price <= $mp_lot && $mp_fee != 0)
            return $this->getMPPriceV2($cost, $client_fee, $mps_fee, 0, 0, $mp_fee,
                $mp_fee_addon, $bfit_min, $mp_bfit_min, $iva);

        // mp_bfit_min ?
        $mp_bfit = $this->getMarketBfitV2($price, $mp_fee, $mp_fee_addon, $mp_bfit_min, $mp_lot, $mp_lot_fee);
        if ($mp_bfit < $mp_bfit_min) {
            $price += ($mp_bfit_min - $mp_bfit);
        }

        // bfit_min ?
        if ($bfit_min > 0) {
            if (($client_fee > 0 && $this->getClientBfitV2($price, $client_fee, 0, $buybox_price, $reprice_fee_min, $iva) < $bfit_min) ||
                ($mps_fee > 0 && $this->getMpsBfitV2($price, 0, $mps_fee, 0, $buybox_price, $reprice_fee_min ) < $bfit_min)) {

                $cost += $bfit_min;
                $client_fee = 0;
                $price = $this->getMPPriceV2($cost, $client_fee, $mps_fee, $mp_fee, $mp_lot, $mp_lot_fee,
                    $mp_fee_addon, $bfit_min, $mp_bfit_min, $iva);
            }
        }

        return round($price, 2);
    }


    // OBSOLETE -> USE ShopProduct->setMPPrice
    public function getMPPriceByBfitMin($cost, $bfit, $mp_fee, $mp_fee_addon, $iva = 21)
    {
        // GET Marketplace PRICE "ONLY" WHEN Benefit IS FIXED €
        // BFIT OF CLIENT + MPE (fee + mps_fee)
        //$mp_fee += $mps_fee;
        $price = round(
            (1 + $iva/100) * ($cost + $bfit + $mp_fee_addon) /
            (1 - $mp_fee/100 - $mp_fee/100 * $iva/100),
            2
        );

        return $price;
    }


    // OBSOLETE -> USE ShopProduct->setMPPrice
    public function getMPPriceByBfitMinV2($cost, $client_fee, $mps_fee, $mp_fee, $mp_lot = 0, $mp_lot_fee = 0,
        $mp_fee_addon = 0, $bfit_min = 0, $mp_bfit_min = 0, $iva = 21)
    {
        // GET Marketplace PRICE "ONLY" WHEN Benefit IS FIXED €
        // BFIT OF CLIENT + MPE (fee + mps_fee)
        //$mp_fee += $mps_fee;
        $cost += $bfit_min;
        $client_fee = 0;
        $price = $this->getMPPriceV2($cost, $client_fee, $mps_fee, $mp_fee, $mp_lot, $mp_lot_fee,
            $mp_fee_addon, $bfit_min, $mp_bfit_min, $iva);

        return round($price, 2);
    }


    // OBSOLETE -> USE ShopProduct->setClientBfit OR getClientBfitV2
    public function getClientBfit($price, $fee, $bfit_min = 0, $iva = 21)
    {
        // GET Benefit
        $bfit = $fee/100 * $price / (1 + $iva/100);
        // if $bfit_min == 0 GET $bfit BY $fee
        // else GET REAL MP $bfit (minimum $bfit_min)
        if ($bfit_min != 0 && $bfit < $bfit_min) {
            $bfit = $bfit_min;
        }


        return round($bfit, 2);
    }


    // OBSOLETE -> USE ShopProduct->setClientBfit
    // USED OCASIONALLY BY ORDER MODEL
    public function getClientBfitV2($price, $fee, $bfit_min = 0, $buybox_price = 0, $reprice_fee_min = 0, $iva = 21)
    {
        // GET Benefit
        $bfit = 0;
        if ($fee != 0) {
            if ($price < $buybox_price) $fee = $reprice_fee_min;
            $bfit = $fee/100 * $price / (1 + $iva/100);
            if ($bfit < $bfit_min) $bfit = $bfit_min;     // $bfit_min == 0 -> Get Real Bfit
        }

        return round($bfit, 2);
    }


    // OBSOLETE -> USE ShopProduct->setMpsBfit OR getMpsBfitV2
    public function getMpsBfit($price, $mps_fee, $bfit_min = 0, $iva = 21)
    {
        $mps_bfit = round($mps_fee/100 * $price, 2);

        // if $bfit_min == 0 GET $mps_bfit BY $mps_fee
        // else GET REAL MP $mps_bfit (minimum $bfit_min)
        if ($bfit_min != 0 && $mps_bfit < $bfit_min) {
            $mps_bfit = $bfit_min;
        }

        return $mps_bfit;
    }


    // OBSOLETE -> USE ShopProduct->setMpsBfit
    // USED OCASIONALLY BY ORDER MODEL
    public function getMpsBfitV2($price, $fee, $mps_fee, $bfit_min = 0, $buybox_price = 0, $reprice_fee_min = 0)
    {
        if ($price < $buybox_price) $mps_fee = $reprice_fee_min;
        $mps_bfit = $mps_fee/100 * $price;
        if ($fee == 0 && $mps_bfit < $bfit_min) $mps_bfit = $bfit_min;    // $bfit_min == 0 -> Get Real Mps_Bfit

        return round($mps_bfit, 2);
    }


    // OBSOLETE -> USE ShopProduct->setMarketBfit OR getMarketBfitV2
    public function getMarketBfit($price, $mp_fee, $mp_fee_addon, $iva = 21)
    {
        // GET Marketplace Benefit
        return round($mp_fee / 100 * $price + $mp_fee_addon, 2);
    }


    // OBSOLETE -> USE ShopProduct->setMarketBfit
    // USED OCASIONALLY BY ORDER MODEL
    public function getMarketBfitV2($price, $mp_fee, $mp_fee_addon = 0, $mp_bfit_min = 0, $mp_lot = 0, $mp_lot_fee = 0)
    {
        // GET Marketplace Benefit
        if ($mp_lot == 0 || ($price <= $mp_lot))
            $mp_bfit = $mp_fee / 100 * $price + $mp_fee_addon;
        else {
            $mp_bfit = $mp_fee / 100 * $mp_lot + $mp_fee_addon;
            $mp_bfit += $mp_lot_fee / 100 * ($price - $mp_lot);
        }

        if ($mp_bfit < $mp_bfit_min) $mp_bfit = $mp_bfit_min;

        return round($mp_bfit, 2);
    }


    // Gets Bfit & Fee IF sell al $price
    // Useful x Win ByBox
    public function getBfitsByPrice($cost, $price, $mps_fee, $mp_fee, $mp_fee_addon = 0, $tax = 21)
    {
        $mp_bfit = $price * ($mp_fee/100) + $mp_fee_addon;
        $mps_bfit = $price * ($mps_fee/100);
        $price_without_fee = $price / (1 + $tax/100);
        $bfit = $price_without_fee - $mp_bfit - $mps_bfit - $cost;

        return [
            'bfit'      => round($bfit, 2),                                // €
            'mps_bfit'  => round($mps_bfit, 2),                            // €
            'fee'       => round(100 * ($bfit / $price_without_fee), 2),   // %
        ];
    }


    public function getIdFromMPSSku($MPSSku)
    {
        return substr($MPSSku, 0, strpos($MPSSku, '_'));
    }


    public function changeAccents($str)
    {
        $str = htmlentities($str, ENT_COMPAT, "UTF-8");
        $str = preg_replace('/&([a-zA-Z])(uml|acute|grave|circ|tilde);/', '$1', $str);

        return html_entity_decode($str);
    }


    // OLD -> $product->getMPESimilarProductWithImages()
    public function getMPEProductWithImages($pn, $brand_id, $ean)
    {
        if (isset($ean) && $ean != '')
            return Product::whereHas('images')
                //->whereNotNull('products.ean')
                ->where('products.ean', $ean)
                ->first();
        elseif (isset($pn) && $pn != '' && isset($brand_id))
            return Product::whereHas('images')
                //->whereNotNull('products.pn')
                //->whereNotNull('products.brand_id')
                ->where('products.pn', $pn)
                ->where('products.brand_id', $brand_id)
                ->first();

        return null;
    }


    public function storageTH($filename, Throwable $th, $info)
    {
        Storage::append($filename, json_encode(
            [
                'message'   => $th->getMessage(),
                'code'      => $th->getCode(),
                'line'      => $th->getLine(),
                'file'      => $th->getFile(),
                'trace'     => $th->getTrace(),
                'info'      => $info
            ]));

        return $th->getMessage();
    }


    public function throwableArray(Throwable $th, $name = '', $key = null, $value = null)
    {
        Storage::append('errors/' .date('Y-m-d'). '_'.$name.'.json', json_encode($th->__toString()));
        $resp = [
            'code'      => $th->getCode(),
            'message'   => $th->getMessage(),
        ];

        if (isset($key)) {
            $resp[$key] = $value;
        }

        return $resp;
    }





}
