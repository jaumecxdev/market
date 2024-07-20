<?php

namespace App\Http\Controllers;

use App\Supplier;
use App\SupplierCategory;
use Illuminate\Http\Request;

class SupplierCategoryController extends Controller
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
    public function index(Request $request, Supplier $supplier)
    {
        $params = $request->all();
        if (!isset($params['order_by']) || $params['order_by'] == null) {
            $params['order_by'] = 'products_count';     //supplier_categories.supplierCategoryId';   //'products_count';
            $params['order'] = 'desc';
        }
        $order_params = array_merge($params, ['order' => ($params['order'] == 'asc') ? 'desc' : 'asc']);
        $params['supplier_id'] = $supplier->id;
        $supplier_categories = SupplierCategory::filter($params)->paginate(300);

        return view('supplier_category.index', compact('supplier', 'params', 'order_params', 'supplier_categories'));
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\SupplierCategory  $supplierCategory
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Supplier $supplier, SupplierCategory $supplierCategory)
    {
        return view('supplier_category.edit', compact('supplier',  'supplierCategory'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\SupplierCategory  $supplierCategory
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Supplier $supplier, SupplierCategory $supplierCategory)
    {
        $validatedData = $request->validate([
            'category_name'         => 'nullable|max:1024',
            'category_id'           => 'nullable|exists:categories,id',
            'name'                  => 'required|max:255',
            'supplierCategoryId'    => 'nullable|max:64',
        ]);

        $supplierCategory->category_id = $validatedData['category_name'] ? $validatedData['category_id'] : null;
        $supplierCategory->name = $validatedData['name'];
        $supplierCategory->supplierCategoryId = $validatedData['supplierCategoryId'];
        $supplierCategory->save();

        return redirect()->route('suppliers.supplier_categories.index', [$supplier])->with('status', 'Mapping de Categoria modificado correctamente.');
    }

}
