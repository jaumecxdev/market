<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Throwable;

trait HelperTrait
{

    public function backWithErrors(Throwable $th, $filename, $info)
    {
        Storage::append('errors/'.date('Y-m-d_H').'_'.str_replace(['\\', '::'], ['_','__'], $filename).'.json',
            json_encode([$th->getMessage(), $info, $info, $th->getCode(), $th->getTrace()]));

        return back()->withErrors('Error inesperado, '.$th->getMessage());
    }


    public function msgWithErrors(Throwable $th, $filename, $info)
    {
        Storage::append('errors/'.date('Y-m-d_H').'_'.str_replace(['\\', '::'], ['_','__'], $filename).'.json',
            json_encode([$th->getMessage(), $info, $info, $th->getCode(), $th->getTrace()]));

        return 'Error inesperado, '.$th->getMessage();
    }


    public function nullWithErrors(Throwable $th, $filename, $info)
    {
        Storage::append('errors/'.date('Y-m-d_H').'_'.str_replace(['\\', '::'], ['_','__'], $filename).'.json',
            json_encode([$th->getMessage(), $info, $th->getCode(), $th->getTrace()]));

        return null;
    }


    public function backWithErrorMsg($filename, $msg, $info)
    {
        Storage::append('errors/'.date('Y-m-d_H').'_'.str_replace(['\\', '::'], ['_','__'], $filename).'.json',
            json_encode([$msg, $info]));

        return back()->withErrors($msg);
    }


    public function msgAndStorage($filename, $msg, $info)
    {
        Storage::append('errors/'.date('Y-m-d_H').'_'.str_replace(['\\', '::'], ['_','__'], $filename).'.json',
            json_encode([$msg, $info]));

        return $msg;
    }


    public function nullAndStorage($filename, $info)
    {
        Storage::append('errors/'.date('Y-m-d_H').'_'.str_replace(['\\', '::'], ['_','__'], $filename).'.json',
            json_encode($info));

        return null;
    }


    public function logStorage($dir, $filename, $info)
    {
        Storage::append($dir.date('Y-m-d_H-i').'_'.str_replace(['\\', '::'], ['_','__'], $filename).'.json',
            json_encode($info));
    }





    public function getIdFromMPSSku($MPSSku)
    {
        return substr($MPSSku, 0, strpos($MPSSku, '_'));
    }


    // USE MPE FACADE
    public function getMPPrice($cost, $fee, $mp_fee, $mp_fee_addon, $iva = 21)
    {
        $price = round(
            (1 + $iva/100) * ($cost + $mp_fee_addon) /
            (1 - $fee/100 - $mp_fee/100 - $mp_fee/100 * $iva/100 ),
            2);

        return $price;
    }


    // USE MPE FACADE
    public function getMPPriceByBfitMin($cost, $bfit, $mp_fee, $mp_fee_addon, $iva)
    {
        // GET Marketplace PRICE "ONLY" WHEN Benefit IS FIXED €
        $price = round(
            (1 + $iva/100) * ($cost + $bfit + $mp_fee_addon) /
            (1 - $mp_fee/100 - $mp_fee/100 * $iva/100 ),
            2);

        return $price;
    }


    // USE MPE FACADE
    static function getBfit($price, $fee, $bfit_min = 0, $iva = 21)
    {
        // GET Benefit
        $bfit = $fee/100 * $price / (1 + $iva/100);
        // if $bfit_min == 0 GET $bfit BY $fee
        // else GET REAL MP $bfit (minimum $bfit_min)
        if ($bfit_min != 0 && $bfit < $bfit_min) $bfit = $bfit_min;

        return $bfit;
    }


    // USE MPE FACADE
    public function getMarketBfit($price, $mp_fee, $mp_fee_addon, $iva = 21)
    {
        // GET Marketplace Benefit
        return $mp_fee / 100 * $price + $mp_fee_addon;
    }


    public function changeAccents($str)
    {
        $str = htmlentities($str, ENT_COMPAT, "UTF-8");
        $str = preg_replace('/&([a-zA-Z])(uml|acute|grave|circ|tilde);/', '$1', $str);

        return html_entity_decode($str);
    }



}
