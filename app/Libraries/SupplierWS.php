<?php


namespace App\Libraries;


use App\Supplier;
use App\Libraries\IoutletwebWS;
use App\Traits\HelperTrait;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SupplierWS
{
    use HelperTrait;

    protected $supplier = null;
    protected $storage_dir = null;
    protected $config = null;
    protected $tax_max = 21;        // IVA maximmum for import errors
    protected $currency_id_eur = 1;        // IVA maximmum for import errors

    // If Supplier NO HAVE YOUR OWN CATEGORY MAPPING.
    // Example for Idiomund Blanes: $category_supplier_id = 1
    //protected $category_supplier_id = null;
    //protected $brand_supplier_id = null;
    protected $rejected_categories = [];


    const DEFAULT_CONFIG = [];


    function __construct(Supplier $supplier)
    {
        $this->supplier = $supplier;
        $this->storage_dir = $supplier->storage_dir;
        if(!Storage::exists($this->storage_dir))
            Storage::makeDirectory($this->storage_dir);

        $this->config = json_decode($supplier->config);
        if (isset($this->config)) {
            if (isset($this->config->rejected_categories))
                $this->rejected_categories = $this->config->rejected_categories;
        }
    }


    static function getSupplierWS(Supplier $supplier)
    {
        // $ws = MarketWS::getWS('App\Libraries\\' .$market->ws, $shop);
        // $ws = MarketWS::getWS('App\Libraries\\' .$shop->market->ws, $shop);
        try {
            $ws_name = 'App\Libraries\\' . $supplier->ws;

            switch ($ws_name) {
                case 'App\Libraries\IdiomundWS':
                    $object_ws = new IdiomundWS($supplier);
                    break;
                case 'App\Libraries\IoutletWS':
                    $object_ws = new IoutletWS($supplier);
                    break;
                case 'App\Libraries\IoutletwebWS':
                    $object_ws = new IoutletwebWS($supplier);
                    break;
                case 'App\Libraries\PdastoreWS':
                    $object_ws = new PdastoreWS($supplier);
                    break;
                case 'App\Libraries\IthomsonWS':
                    $object_ws = new IthomsonWS($supplier);
                    break;
                default:
                    $object_ws = new $ws_name($supplier);
            }

            return $object_ws;
        } catch (Throwable $th) {
            return null;
        }
    }


    // OBSOLETE -> USE: FACADESMPE::roundFloat | roundFloatEsToEn
    protected function getProductCost($field_cost)
    {
        // supplier_params 042021
        return round(floatval($field_cost), 2);

        /* $cost = $fields['cost'];
        $canon = isset($fields['canon']) ? ($fields['canon'] + $supplier_params['canon']) : $supplier_params['canon'];
        $rappel = isset($fields['rappel']) ? ($fields['rappel'] + $supplier_params['rappel']) : $supplier_params['rappel'];
        $ports = isset($fields['ports']) ? ($fields['ports'] + $supplier_params['ports']) : $supplier_params['ports'];

        return round(
            (floatval($cost) + $canon) * (1 - $rappel / 100) + $ports,
            2); */
    }


}
