<?php

namespace App\Http\Controllers;

use App\Market;
use App\Product;
use App\Shop;
use App\Supplier;
use App\SupplierParam;
use App\Traits\HelperTrait;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Spatie\Permission\Traits\HasRoles;
use Throwable;

class SupplierParamController extends Controller
{
    use HasRoles;
    use HelperTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Supplier $supplier)
    {
        $markets = Market::all();
        $shops = Shop::filter([])->orderBy('markets.code')->orderBy('shops.code')->get();
        $supplier_params = $supplier->supplier_params()->orderBy('category_id')->get();      //->whereNull('shop_id');

        return view('supplier_param.index', compact('supplier', 'markets', 'shops', 'supplier_params'));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, Supplier $supplier)
    {
        $validatedData = $request->validate([
            'supplierSku'   => 'nullable|numeric|gte:0',
            'product_id'    => 'nullable|exists:products,id',
            'item_select'   => 'nullable|max:255',
            'item_reference'=> 'nullable|max:255',

            'brand_id'      => 'nullable|exists:brands,id',
            'brand_name'    => 'nullable|max:255',
            'category_id'   => 'nullable|exists:categories,id',
            'category_name' => 'nullable|max:255',

            'cost_min'      => 'nullable|numeric|gte:0',
            'cost_max'      => 'nullable|numeric|gte:0',
            'starts_at'     => 'nullable|date_format:Y-m-d',
            'ends_at'       => 'nullable|date_format:Y-m-d',

            /* 'canon'         => 'nullable|numeric|gte:0', */
            'rappel'        => 'nullable|numeric|gte:0',
            'ports'         => 'nullable|numeric|gte:0',

            'price'         => 'nullable|numeric|gte:0',
            'discount_price'=> 'nullable|numeric|gte:0',
            'stock'         => 'nullable|numeric|gte:0',
        ]);

        if (!isset($validatedData['supplierSku'])) {
            if (!isset($validatedData['item_reference'])) {
                $validatedData['product_id'] = null;
            } elseif (!isset($validatedData['product_id']) || $validatedData['item_select'] != 'name') {

                $validatedData['product_id'] = null;
                $query = Product::whereSupplierId($supplier->id);
                if ($validatedData['item_select'] == 'pn') {
                    $validatedData['pn'] = $validatedData['item_reference'];
                    $product = $query->where('pn', $validatedData['item_reference'])->first();
                } elseif ($validatedData['item_select'] == 'ean') {
                    $validatedData['ean'] = $validatedData['item_reference'];
                    $product = $query->where('ean', $validatedData['item_reference'])->first();
                } elseif ($validatedData['item_select'] == 'upc') {
                    $validatedData['upc'] = $validatedData['item_reference'];
                    $product = $query->where('upc', $validatedData['item_reference'])->first();
                } elseif ($validatedData['item_select'] == 'isbn') {
                    $validatedData['isbn'] = $validatedData['item_reference'];
                    $product = $query->where('isbn', $validatedData['item_reference'])->first();
                } elseif ($validatedData['item_select'] == 'gtin') {
                    $validatedData['gtin'] = $validatedData['item_reference'];
                    $product = $query->where('gtin', $validatedData['item_reference'])->first();
                } elseif ($validatedData['item_select'] == 'name') {
                    $product = $query->where('name', 'LIKE', '%' .$validatedData['item_reference']. '%')->first();
                    if ($product) $validatedData['product_id'] = $product->id;
                }

                if (!$product) {
                    return redirect()->route('suppliers.supplier_params.index', [$supplier])->withErrors('No se ha encontrado este Producto.');
                }
            }
        }
        else {
            $validatedData['product_id'] = null;
        }

        if (!isset($request->category_name)) $validatedData['category_id'] = null;
        if (!isset($request->brand_name)) $validatedData['brand_id'] = null;

        if (!$validatedData['rappel'] && !$validatedData['ports'] && !$validatedData['price'] && !$validatedData['stock'])
            return redirect()->route('suppliers.supplier_params.index', [$supplier])->withErrors(['No se ha añadido ningún parámetro.'])->withInput();

        SupplierParam::updateOrCreate([
            'supplier_id'       => $supplier->id,
            'brand_id'          => $validatedData['brand_id'],
            'category_id'       => $validatedData['category_id'],

            'product_id'        => $validatedData['product_id'],
            'supplierSku'       => $validatedData['supplierSku'],
            'pn'                => $validatedData['pn'] ?? null,
            'ean'               => $validatedData['ean'] ?? null,
            'upc'               => $validatedData['upc'] ?? null,
            'isbn'              => $validatedData['isbn'] ?? null,
            'gtin'              => $validatedData['gtin'] ?? null,

            'cost_min'          => $validatedData['cost_min'] ?? null,
            'cost_max'          => $validatedData['cost_max'] ?? null,
            'starts_at'         => $validatedData['starts_at'] ?? null,
            'ends_at'           => $validatedData['ends_at'] ?? null,

            /* 'canon'             => $validatedData['canon'] ?? 0, */
            'rappel'            => $validatedData['rappel'] ?? 0,
            'ports'             => $validatedData['ports'] ?? 0,

            'price'             => $validatedData['price'] ?? 0,
            'discount_price'    => $validatedData['discount_price'] ?? 0,
            'stock'             => $validatedData['stock'] ?? 0,
        ],[
        ]);

        return redirect()->route('suppliers.supplier_params.index', [$supplier])->with('status', 'Parámetro creado correctamente.');
    }




    public function edit(Supplier $supplier, SupplierParam $supplier_param)
    {
        $markets = Market::all();
        $shops = Shop::filter([])->orderBy('markets.code')->orderBy('shops.code')->get();
        $supplier_params = $supplier->supplier_params->whereNull('shop_id');

        return view('supplier_param.index', compact('supplier', 'supplier_param', 'markets', 'shops', 'supplier_params'));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Product  $product
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Supplier $supplier, SupplierParam $supplier_param)
    {
        $validatedData = $request->validate([
            'supplierSku'   => 'nullable|numeric|gte:0',
            'product_id'    => 'nullable|exists:products,id',
            'item_select'   => 'nullable|max:255',
            'item_reference'=> 'nullable|max:255',

            'brand_id'      => 'nullable|exists:brands,id',
            'brand_name'    => 'nullable|max:255',
            'category_id'   => 'nullable|exists:categories,id',
            'category_name' => 'nullable|max:255',

            'cost_min'      => 'nullable|numeric|gte:0',
            'cost_max'      => 'nullable|numeric|gte:0',
            'starts_at'     => 'nullable|date_format:Y-m-d',
            'ends_at'       => 'nullable|date_format:Y-m-d',

           /*  'canon'         => 'nullable|numeric|gte:0', */
            'rappel'        => 'nullable|numeric|gte:0',
            'ports'         => 'nullable|numeric|gte:0',

            'price'         => 'nullable|numeric|gte:0',
            'discount_price'=> 'nullable|numeric|gte:0',
            'stock'         => 'nullable|numeric|gte:0',
        ]);

        $validatedData['pn'] = $validatedData['ean'] = $validatedData['upc'] = $validatedData['isbn'] = $validatedData['gtin'] = null;
        if (!isset($validatedData['supplierSku'])) {
            if (!isset($validatedData['item_reference'])) {
                $validatedData['product_id'] = null;
            } elseif (!isset($validatedData['product_id']) || $validatedData['item_select'] != 'name') {

                $validatedData['product_id'] = null;
                $query = Product::whereSupplierId($supplier->id);
                if ($validatedData['item_select'] == 'pn') {
                    $validatedData['pn'] = $validatedData['item_reference'];
                    $product = $query->where('pn', $validatedData['item_reference'])->first();
                } elseif ($validatedData['item_select'] == 'ean') {
                    $validatedData['ean'] = $validatedData['item_reference'];
                    $product = $query->where('ean', $validatedData['item_reference'])->first();
                } elseif ($validatedData['item_select'] == 'upc') {
                    $validatedData['upc'] = $validatedData['item_reference'];
                    $product = $query->where('upc', $validatedData['item_reference'])->first();
                } elseif ($validatedData['item_select'] == 'isbn') {
                    $validatedData['isbn'] = $validatedData['item_reference'];
                    $product = $query->where('isbn', $validatedData['item_reference'])->first();
                } elseif ($validatedData['item_select'] == 'gtin') {
                    $validatedData['gtin'] = $validatedData['item_reference'];
                    $product = $query->where('gtin', $validatedData['item_reference'])->first();
                } elseif ($validatedData['item_select'] == 'name') {
                    $product = $query->where('name', 'LIKE', '%' .$validatedData['item_reference']. '%')->first();
                    if ($product) $validatedData['product_id'] = $product->id;
                }

                if (!$product) {
                    return redirect()->route('suppliers.supplier_params.index', [$supplier])->withErrors('No se ha encontrado este Producto.');
                }
            }
        }
        else {
            $validatedData['product_id'] = null;
        }

        if (!isset($request->category_name)) $validatedData['category_id'] = null;
        if (!isset($request->brand_name)) $validatedData['brand_id'] = null;

        $validatedData['cost_min'] = $validatedData['cost_min'] ?? null;
        $validatedData['cost_max'] = $validatedData['cost_max'] ?? null;
        $validatedData['starts_at'] = $validatedData['starts_at'] ?? null;
        $validatedData['ends_at'] = $validatedData['ends_at'] ?? null;

        /* $validatedData['canon'] = $validatedData['canon'] ?? 0; */
        $validatedData['rappel'] = $validatedData['rappel'] ?? 0;
        $validatedData['ports'] = $validatedData['ports'] ?? 0;

        $validatedData['price'] = $validatedData['price'] ?? 0;
        $validatedData['discount_price'] = $validatedData['discount_price'] ?? 0;
        $validatedData['stock'] = $validatedData['stock'] ?? 0;

        if (!$validatedData['rappel'] && !$validatedData['ports'] && !$validatedData['price'] && !$validatedData['stock'])
            return redirect()->route('suppliers.supplier_params.index', [$supplier])->withErrors(['No se ha añadido ningún parámetro.'])->withInput();

        $validatedData['supplier_id'] = $supplier->id;
        unset($validatedData['item_select']);
        unset($validatedData['item_reference']);
        unset($validatedData['brand_name']);
        unset($validatedData['category_name']);

        SupplierParam::whereId($supplier_param->id)->update($validatedData);

        return redirect()->route('suppliers.supplier_params.index', [$supplier])->with('status', 'Parámetro modificado correctamente.');
    }






    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\SupplierParam  $supplierParam
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Supplier $supplier, SupplierParam $supplier_param)
    {
        try {
            $supplier_param->delete();
        } catch (QueryException $e) {
            return redirect()->route('suppliers.supplier_params.index', [$supplier])->withErrors($e)->withInput();
        }

        return redirect()->route('suppliers.supplier_params.index', [$supplier])->with('status', 'Parámetro eliminado.');
    }



    public function sync(Supplier $supplier)
    {
        try {
            $msg = $supplier->syncParams();

            return redirect()->route('suppliers.supplier_params.index', [$supplier])
                ->with('status', $msg);

        } catch (Throwable $th) {
            return $this->backWithErrors($th, __METHOD__, [$supplier, $msg]);
        }
    }



}
