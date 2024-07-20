<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use App\Product;
use App\Shop;
use App\ShopFilter;
use App\Status;
use App\Traits\HelperTrait;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShopFilterController extends Controller
{
    use HelperTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }


    public function index(Shop $shop)
    {
        $user = Auth::user();
        if (!$shop = $user->checkShop($shop))
            return redirect()->route('welcome')->withErrors('Hay un problema con la cuenta de tu usuario, contacta el administrador.');

        $suppliers = $user->getSuppliers();
        $statuses = Status::where('type', 'product')->get();
        $shop_filters = $shop->shop_filters()->filter([])->get();

        return view('saas.shop_filter.index', compact('shop', 'shop_filters', 'suppliers', 'statuses'));
    }


    public function store(Request $request, Shop $shop)
    {
        $validatedData = $request->validate([
            'supplier_id'           => 'nullable|exists:suppliers,id',
            'supplier_category_id'  => 'nullable|exists:supplier_categories,id',
            'supplier_brand_id'     => 'nullable|exists:supplier_brands,id',
            'status_id'             => 'nullable|exists:statuses,id',

            'product_id'            => 'nullable|exists:products,id',
            'item_select'           => 'nullable|max:255',
            'item_reference'        => 'nullable|max:255',

            'cost_min'              => 'nullable|numeric|gte:0',
            'cost_max'              => 'nullable|numeric|gte:cost_min',
            'stock_min'             => 'nullable|numeric|gte:0',
            'stock_max'             => 'nullable|numeric|gte:stock_min',
            'limit_products'        => 'nullable|numeric|gte:0',
        ]);

        if (isset($validatedData['item_reference']))
            $suppliers_id = [$validatedData['item_reference']];
        else
            $suppliers_id = $request->user()->getSuppliers();

        if (!isset($validatedData['item_reference']))
            $validatedData['product_id'] = null;
        elseif (!isset($validatedData['product_id']) || $validatedData['item_select'] != 'name') {
            if ($validatedData['item_select'] == 'id')
                $product = Product::whereIn('supplier_id', $suppliers_id)->find($validatedData['item_reference']);
            elseif ($validatedData['item_select'] == 'pn')
                $product = Product::whereIn('supplier_id', $suppliers_id)->where('pn', $validatedData['item_reference'])->first();
            elseif ($validatedData['item_select'] == 'ean')
                $product = Product::whereIn('supplier_id', $suppliers_id)->where('ean', $validatedData['item_reference'])->first();
            elseif ($validatedData['item_select'] == 'upc')
                $product = Product::whereIn('supplier_id', $suppliers_id)->where('upc', $validatedData['item_reference'])->first();
            elseif ($validatedData['item_select'] == 'isbn')
                $product = Product::whereIn('supplier_id', $suppliers_id)->where('isbn', $validatedData['item_reference'])->first();
            elseif ($validatedData['item_select'] == 'gtin')
                $product = Product::whereIn('supplier_id', $suppliers_id)->where('gtin', $validatedData['item_reference'])->first();
            elseif ($validatedData['item_select'] == 'name' && !isset($validatedData['product_id']))
                    $product = Product::whereIn('supplier_id', $suppliers_id)->where('name', 'LIKE', '%' .$validatedData['item_reference']. '%')->first();

            if (!$product)
                return redirect()->route('saas.shops.shop_filters', [$shop])->withErrors('No se ha encontrado este Producto.');
            else
                $validatedData['product_id'] = $product->id;
        }

        if (!isset($validatedData['product_id']) && !isset($validatedData['supplier_category_id']) && !isset($validatedData['supplier_brand_id']) &&
            !isset($validatedData['supplier_id']) && !isset($validatedData['type_id'])  && !isset($validatedData['status_id']) &&
            !isset($validatedData['cost_min']) && !isset($validatedData['cost_max']) && !isset($validatedData['stock_min']) &&
            !isset($validatedData['stock_max']) && !isset($validatedData['limit_products']))
            return redirect()->route('saas.shops.shop_filters', [$shop])->withErrors ('No se han definido filtros.');

        $validatedData['shop_id'] = $shop->id;
        ShopFilter::create($validatedData);

        return redirect()->route('saas.shops.shop_filters', [$shop])->with('status', 'Filtro creado correctamente.');
    }


    public function edit(Shop $shop, ShopFilter $shop_filter)
    {
        $user = Auth::user();
        if (!$shop = $user->checkShop($shop))
            return redirect()->route('welcome')->withErrors('Hay un problema con la cuenta de tu usuario, contacta el administrador.');

        $suppliers = $user->getSuppliers();
        $statuses = Status::where('type', 'product')->get();
        $shop_filters = $shop->shop_filters()->filter([])->get();

        return view('saas.shop_filter.index', compact('shop', 'shop_filter', 'shop_filters', 'suppliers', 'statuses'));
    }


    public function update(Request $request, Shop $shop, ShopFilter $shop_filter)
    {
        $validatedData = $request->validate([
            'supplier_id'           => 'nullable|exists:suppliers,id',
            'supplier_category_id'  => 'nullable|exists:supplier_categories,id',
            'supplier_brand_id'     => 'nullable|exists:supplier_brands,id',
            'status_id'             => 'nullable|exists:statuses,id',

            'product_id'            => 'nullable|exists:products,id',
            'item_select'           => 'nullable|max:255',
            'item_reference'        => 'nullable|max:255',

            'cost_min'              => 'nullable|numeric|gte:0',
            'cost_max'              => 'nullable|numeric|gte:cost_min',
            'stock_min'             => 'nullable|numeric|gte:0',
            'stock_max'             => 'nullable|numeric|gte:stock_min',
            'limit_products'        => 'nullable|numeric|gte:0',
        ]);

        if (isset($validatedData['item_reference']))
            $suppliers_id = [$validatedData['item_reference']];
        else
            $suppliers_id = $request->user()->getSuppliersId();

        if (!isset($validatedData['item_reference']))
            $validatedData['product_id'] = null;
        elseif (!isset($validatedData['product_id']) || $validatedData['item_select'] != 'name') {
            if ($validatedData['item_select'] == 'id')
                $product = Product::whereIn('supplier_id', $suppliers_id)->find($validatedData['item_reference']);
            elseif ($validatedData['item_select'] == 'pn')
                $product = Product::whereIn('supplier_id', $suppliers_id)->where('pn', $validatedData['item_reference'])->first();
            elseif ($validatedData['item_select'] == 'ean')
                $product = Product::whereIn('supplier_id', $suppliers_id)->where('ean', $validatedData['item_reference'])->first();
            elseif ($validatedData['item_select'] == 'upc')
                $product = Product::whereIn('supplier_id', $suppliers_id)->where('upc', $validatedData['item_reference'])->first();
            elseif ($validatedData['item_select'] == 'isbn')
                $product = Product::whereIn('supplier_id', $suppliers_id)->where('isbn', $validatedData['item_reference'])->first();
            elseif ($validatedData['item_select'] == 'gtin')
                $product = Product::whereIn('supplier_id', $suppliers_id)->where('gtin', $validatedData['item_reference'])->first();
            elseif ($validatedData['item_select'] == 'name' && !isset($validatedData['product_id']))
                $product = Product::whereIn('supplier_id', $suppliers_id)->where('name', 'LIKE', '%' .$validatedData['item_reference']. '%')->first();

            if (!$product)
                return redirect()->route('saas.shops.shop_filters', [$shop])->withErrors('No se ha encontrado este Producto.');
            else
                $validatedData['product_id'] = $product->id;
        }

        if (isset($validatedData['supplier_brand_id']) && !isset($request->supplier_brand_name)) $validatedData['supplier_brand_id'] = null;
        if (isset($validatedData['supplier_category_id']) && !isset($request->supplier_category_name)) $validatedData['supplier_category_id'] = null;

        if (!isset($validatedData['product_id']) && !isset($validatedData['supplier_category_id']) && !isset($validatedData['supplier_brand_id']) &&
            !isset($validatedData['supplier_id']) && !isset($validatedData['type_id'])  && !isset($validatedData['status_id']) &&
            !isset($validatedData['cost_min']) && !isset($validatedData['cost_max']) && !isset($validatedData['stock_min']) &&
            !isset($validatedData['stock_max']) && !isset($validatedData['limit_products']))
            return redirect()->route('saas.shops.shop_filters', [$shop])->withErrors ('No se han definido filtros.');

        $validatedData['shop_id'] = $shop->id;
        unset($validatedData['supplier_category_name']);
        unset($validatedData['supplier_brand_name']);
        unset($validatedData['item_select']);
        unset($validatedData['item_reference']);

        ShopFilter::whereId($shop_filter->id)->update($validatedData);

        return redirect()->route('saas.shops.shop_filters', [$shop])->with('status', 'Filtro modificado correctamente.');
    }


    public function destroy(Shop $shop, ShopFilter $shop_filter)
    {
        try {
            $shop_filter->delete();

            return redirect()->route('saas.shops.shop_filters', [$shop])->with('status', 'Filtro eliminado.');

        } catch (QueryException $e) {
            return redirect()->route('saas.shops.shop_filters', [$shop])->with('status', $e->getMessage());
        }
    }


    public function addToShop(Shop $shop, ShopFilter $shop_filter)
    {
        try {
            $shop_filters = new Collection([$shop_filter]);

            if ($query_filter = $shop->getProductsFilters($shop_filters))
                $res = $shop->importSaasFilteredProducts($query_filter);
            else
                $res = 'No hay productos en el filtro.';

            return redirect()->route('shops.shop_products', [$shop])->with('status', json_encode($res));

        } catch (QueryException $e) {
            return redirect()->route('saas.shops.shop_filters', [$shop])->with('status', $e->getMessage());
        }
    }


    public function filter(Shop $shop)       // EXECUTE shop_filter
    {
        try {
            $query_products = $shop->getProductsFilters();

            return view('saas.shop_filter.filter', compact('shop', 'query_products'));

        } catch (QueryException $e) {
            return redirect()->route('saas.shops.shop_filters', [$shop])->with('status', $e->getMessage());
        }
    }


    public function import(Request $request, Shop $shop)       // EXECUTE shop_filter
    {
        try {
            if ($query_filter = $shop->getProductsFilters())
                $res = $shop->importSaasFilteredProducts($query_filter);
            else {
                $res = 'No hay productos en los filtros, todos los productos anulados.';
                $shop->shop_products()->update(['enabled' => false, 'stock' => 0]);
            }

            return redirect()->route('saas.shops.shop_products', [$shop])->with('status', 'Filtros ejecutados. '.json_encode($res) );

        } catch (QueryException $e) {
            return redirect()->route('saas.shops.shop_filters', [$shop])->with('status', $e->getMessage());
        }
    }
}
