<?php

namespace App\Http\Controllers;

use App\Brand;
use App\Product;
use App\Shop;
use App\ShopFilter;
use App\Status;
use App\Supplier;
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


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Shop $shop)
    {
        $user = Auth::user();
        if (!$shop = $user->checkShop($shop))
            return redirect()->route('welcome')->withErrors('Hay un problema con la cuenta del usuario. Contacte con el administrador.');

        if ($user->hasRole('seller')) $suppliers = Supplier::orderBy('name', 'asc')->find($user->getSuppliersId());
        else $suppliers = Supplier::orderBy('name', 'asc')->get();

        $statuses = Status::where('type', 'product')->get();
        $brands = Brand::orderBy('name', 'asc')->get();
        $shop_filters = $shop->shop_filters()->filter([])->get();

        /* $suppliers = Supplier::orderBy('name', 'asc')->get();
        //$shop_filters = $shop->shop_filters()->orderBy('category_id')->get();
        $shop_filters = $shop->shop_filters()
            ->select(
                'shop_filters.*',
                'suppliers.name as supplier_name',
                'categories.name as category_name',
                'brands.name as brand_name',
                'products.name as product_name'
            )
            ->leftJoin('suppliers', 'suppliers.id', '=', 'shop_filters.supplier_id')
            ->leftJoin('categories', 'categories.id', '=', 'shop_filters.category_id')
            ->leftJoin('brands', 'brands.id', '=', 'shop_filters.brand_id')
            ->leftJoin('products', 'products.id', '=', 'shop_filters.product_id')
            ->orderBy('categories.name')
            ->orderBy('brands.name')
            ->orderBy('products.name')
            ->orderBy('suppliers.name')
            ->get(); */

        return view('shop_filter.index', compact('shop', 'shop_filters', 'brands', 'suppliers', 'statuses'));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, Shop $shop)
    {
        $validatedData = $request->validate([
            'supplier_id'           => 'nullable|exists:suppliers,id',
            'brand_id'              => 'nullable|exists:brands,id',
            'category_id'           => 'nullable|exists:categories,id',
            'status_id'             => 'nullable|exists:statuses,id',

            'product_id'            => 'nullable|exists:products,id',
            'item_select'           => 'nullable|max:255',
            'item_reference'        => 'nullable|max:255',
            'supplierSku'           => 'nullable',
            'MPSSku'                => 'nullable',
            'model'                 => 'nullable',

            'cost_min'              => 'nullable|numeric|gte:0',
            'cost_max'              => 'nullable|numeric|gte:cost_min',
            'stock_min'             => 'nullable|numeric|gte:0',
            'stock_max'             => 'nullable|numeric|gte:stock_min',
            'limit_products'        => 'nullable|numeric|gte:0',
        ]);

        $product = null;
        if (!isset($validatedData['item_reference']))
            $validatedData['product_id'] = null;
        elseif (!isset($validatedData['product_id']) || $validatedData['item_select'] != 'name') {
            if ($validatedData['item_select'] == 'pn')
                $product = Product::where('pn', $validatedData['item_reference'])->first();
            elseif ($validatedData['item_select'] == 'ean')
                $product = Product::where('ean', $validatedData['item_reference'])->first();
            elseif ($validatedData['item_select'] == 'upc')
                $product = Product::where('upc', $validatedData['item_reference'])->first();
            elseif ($validatedData['item_select'] == 'isbn')
                $product = Product::where('isbn', $validatedData['item_reference'])->first();
            elseif ($validatedData['item_select'] == 'gtin')
                $product = Product::where('gtin', $validatedData['item_reference'])->first();
            elseif ($validatedData['item_select'] == 'name' && !isset($validatedData['product_id']))
                    $product = Product::where('name', 'LIKE', '%' .$validatedData['item_reference']. '%')->first();

            if (!$product)
                return redirect()->route('shops.shop_filters.index', [$shop])->withErrors('No se ha encontrado este Producto.');
        }

        if ($validatedData['supplierSku'] || $validatedData['MPSSku'] || $validatedData['model']) {
            if ($validatedData['supplierSku'])
                $product = Product::where('supplierSku', $validatedData['supplierSku'])->first();
            if ($validatedData['MPSSku']) {
                if ($shop_product = $shop->shop_products()->where('mps_sku', $validatedData['MPSSku'])->first())
                    $product = $shop_product->product;
                else
                    $product = Product::findOrFail($this->getIdFromMPSSku($validatedData['MPSSku']));
            }
            if ($validatedData['model'])
                $product = Product::where('model', $validatedData['model'])->first();

            if (!$product)
                return redirect()->route('shops.shop_filters.index', [$shop])->withErrors('No se ha encontrado este Producto.');
        }


        if ($product)
            $validatedData['product_id'] = $product->id;

        if (!isset($validatedData['product_id']) &&
            (isset($validatedData['item_reference']) || isset($validatedData['supplierSku']) ||
            isset($validatedData['MPSSku']) || isset($validatedData['model'])))
            return redirect()->route('shops.shop_filters.index', [$shop])->withErrors ('No se ha encontrado ningún producto coincidente.');

        if (!isset($validatedData['product_id']) && !isset($validatedData['brand_id']) && !isset($validatedData['category_id']) &&
            !isset($validatedData['supplier_id']) && !isset($validatedData['type_id'])  && !isset($validatedData['status_id']) &&
            !isset($validatedData['cost_min']) && !isset($validatedData['cost_max']) && !isset($validatedData['stock_min']) &&
            !isset($validatedData['stock_max']) && !isset($validatedData['limit_products']))
            return redirect()->route('shops.shop_filters.index', [$shop])->withErrors ('No se han definido filtros.');

        $validatedData['shop_id'] = $shop->id;
        ShopFilter::create($validatedData);

        return redirect()->route('shops.shop_filters.index', [$shop])->with('status', 'Filtro creado correctamente.');
    }


    public function edit(Shop $shop, ShopFilter $shop_filter)
    {
        $user = Auth::user();
        if (!$shop = $user->checkShop($shop))
            return redirect()->route('welcome')->withErrors('Hay un problema con la cuenta del usuario. Contacte con el administrador.');

        if ($user->hasRole('seller')) $suppliers = Supplier::orderBy('name', 'asc')->find($user->getSuppliersId());
        else $suppliers = Supplier::orderBy('name', 'asc')->get();

        $statuses = Status::where('type', 'product')->get();
        $brands = Brand::orderBy('name', 'asc')->get();
        $shop_filters = $shop->shop_filters()->filter([])->get();

        return view('shop_filter.index', compact('shop', 'shop_filter', 'shop_filters', 'brands', 'suppliers', 'statuses'));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Product  $product
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Shop $shop, ShopFilter $shop_filter)
    {

        $validatedData = $request->validate([
            'supplier_id'           => 'nullable|exists:suppliers,id',
            'brand_id'              => 'nullable|exists:brands,id',
            'category_id'           => 'nullable|exists:categories,id',
            'status_id'             => 'nullable|exists:statuses,id',

            'product_id'            => 'nullable|exists:products,id',
            'item_select'           => 'nullable|max:255',
            'item_reference'        => 'nullable|max:255',
            'supplierSku'           => 'nullable',
            'MPSSku'                => 'nullable',
            'model'                 => 'nullable',

            'cost_min'              => 'nullable|numeric|gte:0',
            'cost_max'              => 'nullable|numeric|gte:cost_min',
            'stock_min'             => 'nullable|numeric|gte:0',
            'stock_max'             => 'nullable|numeric|gte:stock_min',
            'limit_products'        => 'nullable|numeric|gte:0',
        ]);


        if (isset($validatedData['brand_id']) && !isset($request->brand_name)) $validatedData['brand_id'] = null;
        if (isset($validatedData['category_id']) && !isset($request->category_name)) $validatedData['category_id'] = null;

        $product = null;
        if (!isset($validatedData['item_reference']))
            $validatedData['product_id'] = null;
        elseif (!isset($validatedData['product_id']) || $validatedData['item_select'] != 'name') {
            if ($validatedData['item_select'] == 'pn')
                $product = Product::where('pn', $validatedData['item_reference'])->first();
            elseif ($validatedData['item_select'] == 'ean')
                $product = Product::where('ean', $validatedData['item_reference'])->first();
            elseif ($validatedData['item_select'] == 'upc')
                $product = Product::where('upc', $validatedData['item_reference'])->first();
            elseif ($validatedData['item_select'] == 'isbn')
                $product = Product::where('isbn', $validatedData['item_reference'])->first();
            elseif ($validatedData['item_select'] == 'gtin')
                $product = Product::where('gtin', $validatedData['item_reference'])->first();
            elseif ($validatedData['item_select'] == 'name' && !isset($validatedData['product_id']))
                    $product = Product::where('name', 'LIKE', '%' .$validatedData['item_reference']. '%')->first();

            if (!$product)
                return redirect()->route('shops.shop_filters.index', [$shop])->withErrors('No se ha encontrado este Producto.');
        }

        if ($validatedData['supplierSku'] || $validatedData['MPSSku'] || $validatedData['model']) {
            if ($validatedData['supplierSku'])
                $product = Product::where('supplierSku', $validatedData['supplierSku'])->first();
            if ($validatedData['MPSSku']) {
                if ($shop_product = $shop->shop_products()->where('mps_sku', $validatedData['MPSSku'])->first())
                    $product = $shop_product->product;
                else
                    $product = Product::findOrFail($this->getIdFromMPSSku($validatedData['MPSSku']));
            }
            if ($validatedData['model'])
                $product = Product::where('model', $validatedData['model'])->first();

            if (!$product)
                return redirect()->route('shops.shop_filters.index', [$shop])->withErrors('No se ha encontrado este Producto.');
        }

        if ($product)
            $validatedData['product_id'] = $product->id;

        if (!isset($validatedData['product_id']) &&
            (isset($validatedData['item_reference']) || isset($validatedData['supplierSku']) ||
            isset($validatedData['MPSSku']) || isset($validatedData['model'])))
            return redirect()->route('shops.shop_filters.index', [$shop])->withErrors ('No se ha encontrado ningún producto coincidente.');

        if (!isset($validatedData['product_id']) && !isset($validatedData['brand_id']) && !isset($validatedData['category_id']) &&
            !isset($validatedData['supplier_id']) && !isset($validatedData['type_id'])  && !isset($validatedData['status_id']) &&
            !isset($validatedData['cost_min']) && !isset($validatedData['cost_max']) && !isset($validatedData['stock_min']) &&
            !isset($validatedData['stock_max']) && !isset($validatedData['limit_products']))
            return redirect()->route('shops.shop_filters.index', [$shop])->withErrors ('No se han definido filtros.');

        $validatedData['shop_id'] = $shop->id;
        unset($validatedData['category_name']);
        unset($validatedData['brand_name']);
        unset($validatedData['supplierSku']);
        unset($validatedData['item_select']);
        unset($validatedData['item_reference']);
        unset($validatedData['MPSSku']);
        unset($validatedData['model']);

        ShopFilter::whereId($shop_filter->id)->update($validatedData);

        return redirect()->route('shops.shop_filters.index', [$shop])->with('status', 'Filtro modificado correctamente.');
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Shop $shop, ShopFilter $shop_filter)
    {
        try {
            $shop_filter->delete();

            return redirect()->route('shops.shop_filters.index', [$shop])->with('status', 'Filtro eliminado.');

        } catch (QueryException $e) {
            return redirect()->route('shops.shop_filters.index', [$shop])->with('status', $e->getMessage());
        }
    }


    public function addToShop(Shop $shop, ShopFilter $shop_filter)
    {
        try {
            $shop_filters = new Collection([$shop_filter]);

            if ($query_filter = $shop->getProductsFilters($shop_filters))
                $res = $shop->importFilteredProducts($query_filter);
            else
                $res = 'No hay productos en el filtro.';

            return redirect()->route('shops.shop_products.index', [$shop])->with('status', json_encode($res));

        } catch (QueryException $e) {
            return redirect()->route('shops.shop_filters.index', [$shop])->with('status', $e->getMessage());
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function filter(Shop $shop)       // EXECUTE shop_filter
    {
        try {
            $query_products = $shop->getProductsFilters();

            return view('shop_filter.filter', compact('shop', 'query_products'));

        } catch (QueryException $e) {
            return redirect()->route('shops.shop_filters.index', [$shop])->with('status', $e->getMessage());
        }
    }


    public function import(Request $request, Shop $shop)       // EXECUTE shop_filter
    {
        try {
            if ($query_filter = $shop->getProductsFilters())
                $res = $shop->importFilteredProducts($query_filter);
            else {
                $res = 'No hay productos en los filtros, todos los productos anulados.';
                $shop->shop_products()->update(['enabled' => false, 'stock' => 0]);
            }

            return redirect()->route('shops.shop_products.index', [$shop])->with('status', 'Filtros ejecutados. '.json_encode($res) );

        } catch (QueryException $e) {
            return redirect()->route('shops.shop_filters.index', [$shop])->with('status', $e->getMessage());
        }
    }
}
