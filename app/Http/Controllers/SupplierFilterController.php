<?php

namespace App\Http\Controllers;

use App\Brand;
use App\Status;
use App\Supplier;
use App\SupplierFilter;
use App\Type;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class SupplierFilterController extends Controller
{
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
        $supplier_filters = $supplier->supplier_filters()->get();
        $brands = Brand::orderBy('name', 'asc')->get();
        $types = Type::where('type', 'product')->get();
        $statuses = Status::where('type', 'product')->get();

        return view('supplier_filter.index', compact('supplier', 'supplier_filters', 'brands', 'types', 'statuses'));
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
            'brand_name'            => 'nullable|max:255',
            'category_name'         => 'nullable|max:255',
            'type_name'             => 'nullable|max:255',
            'status_id'             => 'nullable|exists:statuses,id',

            'supplierSku'           => 'nullable|max:64',
            'item_select'           => 'nullable|max:255',
            'item_reference'        => 'nullable|max:255',
            'model'                 => 'nullable|max:255',

            'cost_min'              => 'nullable|numeric|gte:0',
            'cost_max'              => 'nullable|numeric|gte:0',
            'stock_min'             => 'nullable|numeric|gte:0',
            'stock_max'             => 'nullable|numeric|gte:0',
            'field_name'            => 'nullable|max:64',
            'field_operator'        => 'nullable|max:3',
            'field_string'          => 'nullable|max:64',
            'field_integer'         => 'nullable|numeric|gte:0',
            'field_float'           => 'nullable|numeric|gte:0',
            'limit_products'        => 'nullable|numeric|gte:0',
        ]);

        if (isset($validatedData['status_id'])) {
            $status = Status::find($validatedData['status_id']);
            $validatedData['status_name'] = $status->name ?? null;
        }

        if (isset($validatedData['field_name']) && !isset($validatedData['field_operator']))
            return redirect()->route('suppliers.supplier_filters.index', [$supplier])->withErrors('Falta escoger el operador de comparación.');

        if (isset($validatedData['item_reference']) && $validatedData['item_reference'] != null) {
            if ($validatedData['item_select'] == 'pn')
                $validatedData['pn'] = $validatedData['item_reference'];
            elseif ($validatedData['item_select'] == 'ean')
                $validatedData['ean'] = $validatedData['item_reference'];
            elseif ($validatedData['item_select'] == 'upc')
                $validatedData['upc'] = $validatedData['item_reference'];
            elseif ($validatedData['item_select'] == 'isbn')
                $validatedData['isbn'] = $validatedData['item_reference'];
            /*elseif ($validatedData['item_select'] == 'gtin')
                $validatedData['gtin'] = $validatedData['item_reference'];*/
            elseif ($validatedData['item_select'] == 'name')
                $validatedData['name'] = $validatedData['item_reference'];
        }

        $validatedData['supplier_id'] = $supplier->id;
        SupplierFilter::create($validatedData);

        return redirect()->route('suppliers.supplier_filters.index', [$supplier])->with('status', 'Filtro creado correctamente.');
    }


    public function edit(Supplier $supplier, SupplierFilter $supplier_filter)
    {
        $supplier_filters = $supplier->supplier_filters()->get();
        $brands = Brand::orderBy('name', 'asc')->get();
        $types = Type::where('type', 'product')->get();
        $statuses = Status::where('type', 'product')->get();

        return view('supplier_filter.index', compact('supplier', 'supplier_filter', 'supplier_filters', 'brands', 'types', 'statuses'));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Product  $product
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Supplier $supplier, SupplierFilter $supplier_filter)
    {


        $validatedData = $request->validate([
            'brand_name'            => 'nullable|max:255',
            'category_name'         => 'nullable|max:255',
            'status_id'             => 'nullable|max:255',

            'supplierSku'           => 'nullable|max:64',
            'item_select'           => 'nullable|max:255',
            'item_reference'        => 'nullable|max:255',
            'model'                 => 'nullable|max:255',

            'cost_min'              => 'nullable|numeric|gte:0',
            'cost_max'              => 'nullable|numeric|gte:0',
            'stock_min'             => 'nullable|numeric|gte:0',
            'stock_max'             => 'nullable|numeric|gte:0',
            'field_name'            => 'nullable|max:64',
            'field_operator'        => 'nullable|max:3',
            'field_string'          => 'nullable|max:64',
            'field_integer'         => 'nullable|numeric|gte:0',
            'field_float'           => 'nullable|numeric|gte:0',
            'limit_products'        => 'nullable|numeric|gte:0',
        ]);


        if (isset($validatedData['status_id'])) {
            $status = Status::find($validatedData['status_id']);
            $validatedData['status_name'] = $status->name ?? null;
        }
        else $validatedData['status_name'] = null;

        if (isset($validatedData['field_name']) && !isset($validatedData['field_operator']))
            return redirect()->route('suppliers.supplier_filters.index', [$supplier])->withErrors('Falta escoger el operador de comparación.');

        if (isset($validatedData['item_reference']) && $validatedData['item_reference'] != null) {
            $validatedData['name'] = null;
            $validatedData['pn'] = null;
            $validatedData['ean'] = null;
            $validatedData['upc'] = null;
            $validatedData['isbn'] = null;

            if ($validatedData['item_select'] == 'name')
                $validatedData['name'] = $validatedData['item_reference'];
            elseif ($validatedData['item_select'] == 'pn')
                $validatedData['pn'] = $validatedData['item_reference'];
            elseif ($validatedData['item_select'] == 'ean')
                $validatedData['ean'] = $validatedData['item_reference'];
            elseif ($validatedData['item_select'] == 'upc')
                $validatedData['upc'] = $validatedData['item_reference'];
            elseif ($validatedData['item_select'] == 'isbn')
                $validatedData['isbn'] = $validatedData['item_reference'];
            /*elseif ($validatedData['item_select'] == 'gtin')
                $validatedData['gtin'] = $validatedData['item_reference'];*/
        }

        $validatedData['supplier_id'] = $supplier->id;
        unset($validatedData['status_id']);
        unset($validatedData['brand_id']);
        unset($validatedData['category_id']);
        unset($validatedData['item_select']);
        unset($validatedData['item_reference']);
        unset($validatedData['product_id']);

        SupplierFilter::whereId($supplier_filter->id)->update($validatedData);

        return redirect()->route('suppliers.supplier_filters.index', [$supplier])->with('status', 'Filtro creado correctamente.');
    }



    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Supplier $supplier, SupplierFilter $supplier_filter)
    {
        try {
            $supplier_filter->delete();
        } catch (QueryException $e) {
            return redirect()->route('suppliers.supplier_filters.index', [$supplier])->with('status', $e->getMessage());
        }

        return redirect()->route('suppliers.supplier_filters.index', [$supplier])->with('status', 'Filtro eliminado.');
    }
}
