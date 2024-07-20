<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use App\Market;
use App\SupplierCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarketSupplierCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


    public function index(Request $request, Market $market)
    {
        $user = Auth::user();
        if (!$market = $user->checkMarket($market))
            return redirect()->route('welcome')->withErrors('Hay un problema con la cuenta de tu usuario, contacta el administrador.');

        $params = $request->all();
        if (!isset($params['order_by']) || $params['order_by'] == null) {
            $params['order_by'] = 'supplier_categories.name';
            $params['order'] = 'asc';
        }
        $order_params = array_merge($params, ['order' => ($params['order'] == 'asc') ? 'desc' : 'asc']);
        $supplier_categories = SupplierCategory::whereIn('supplier_categories.supplier_id', $user->getSuppliersId())->has('products')->filter($params)->paginate(300);

        return view('saas.market_supplier_category.index', compact('market', 'params', 'order_params', 'supplier_categories'));
    }


    public function edit(Market $market, SupplierCategory $supplier_category)
    {
        return view('saas.market_supplier_category.edit', compact('market',  'supplier_category'));
    }


    public function update(Request $request, Market $market, SupplierCategory $supplier_category)
    {
        $validatedData = $request->validate([
            'market_category_name' => 'nullable|max:255',
            'market_category_id'   => 'nullable|exists:market_categories,id',
        ]);

        if (!$validatedData['market_category_name']) $validatedData['market_category_id'] = null;

        $market_categories_query = $supplier_category->market_categories()->wherePivot('market_id', $market->id);
        if ($market_categories_query->count())
            $market_categories_query->detach();
        //dd($request,  $market,  $supplier_category);

        if (($validatedData['market_category_id']) && ($validatedData['market_category_id'] != null))
            $market_categories_query->attach($validatedData['market_category_id'],
                [
                    'supplier_id' => $supplier_category->supplier_id,
                    'market_id' => $market->id
                ]);

        return redirect()->route('saas.markets.supplier_categories', [$market])->with('status', 'Mapping de Categoria modificado correctamente.');
    }

}
