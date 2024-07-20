<?php

namespace App\Http\Controllers;

use App\Libraries\SupplierWS;
use App\Product;
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


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function get(Supplier $supplier)
    {
        try {
            if ($ws = SupplierWS::getSupplierWS($supplier)) {
                if ($res = $ws->getProducts()) {
                    return redirect()->route('products.index', ['supplier_id' => $supplier])
                        ->with('status', json_encode($res));
                }

                return redirect()->route('products.index', ['supplier_id' => $supplier])->withErrors(json_encode($res));
            }

            return back()->withErrors(['No se ha podido obtener los productos.']);

        } catch (Throwable $th) {
            return $this->backWithErrors($th, __METHOD__, $supplier);
        }
    }


    public function getPricesStocks(Supplier $supplier)
    {
        try {
            if ($ws = SupplierWS::getSupplierWS($supplier)) {
                if ($res = $ws->getPricesStocks()) {
                    return redirect()->route('products.index', ['supplier_id' => $supplier])
                        ->with('status', json_encode($res));
                }

                return redirect()->route('products.index', ['supplier_id' => $supplier])->withErrors(json_encode($res));
            }

            return back()->withErrors(['No se ha podido actualizar los productos.']);

        } catch (Throwable $th) {
            return $this->backWithErrors($th, __METHOD__, $supplier);
        }
    }



    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function getProduct(Supplier $supplier, Product $product)
    {
        try {
            if ($ws = SupplierWS::getSupplierWS($supplier)) {
                if ($res = $ws->getProduct($product)) {
                    if (isset($res->id)) {
                        return redirect()->route('products.index',
                            ['supplier_id' => $supplier->id, 'product_id' => $res->id, 'item_select' => 'name', 'item_reference' => $product->name])
                            ->with('status', 'Producto re-importado');
                    }
                }

                return redirect()->route('products.index', ['supplier_id' => $supplier])->withErrors(json_encode($res));
            }

            return back()->withErrors(['No se ha podido ReImportar el producto.']);

        } catch (Throwable $th) {
            return $this->backWithErrors($th, __METHOD__, [$supplier, $product]);
        }
    }

}
