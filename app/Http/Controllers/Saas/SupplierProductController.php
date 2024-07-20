<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use App\Libraries\SupplierWS;
use App\Supplier;
use App\Traits\HelperTrait;
use Throwable;

class SupplierProductController extends Controller
{
    use HelperTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }


    public function get(Supplier $supplier)
    {
        try {
            if ($ws = SupplierWS::getSupplierWS($supplier)) {
                if ($res = $ws->getProducts()) {
                    return redirect()->route('saas.products', ['supplier_id' => $supplier])
                        ->with('status', json_encode($res));
                }

                return redirect()->route('saas.products', ['supplier_id' => $supplier])->withErrors(json_encode($res));
            }

            return back()->withErrors(['No se han podido obtener los productos.']);

        } catch (Throwable $th) {
            return $this->backWithErrors($th, __METHOD__, $supplier);
        }
    }


    public function getPricesStocks(Supplier $supplier)
    {
        try {
            if ($ws = SupplierWS::getSupplierWS($supplier)) {
                if ($res = $ws->getPricesStocks()) {
                    return redirect()->route('saas.products', ['supplier_id' => $supplier])
                        ->with('status', json_encode($res));
                }

                return redirect()->route('saas.products', ['supplier_id' => $supplier])->withErrors(json_encode($res));
            }

            return back()->withErrors(['No se ha podido actualizar los productos.']);

        } catch (Throwable $th) {
            return $this->backWithErrors($th, __METHOD__, $supplier);
        }
    }

}
