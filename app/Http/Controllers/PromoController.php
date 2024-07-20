<?php

namespace App\Http\Controllers;

use App\Market;
use App\Product;
use App\Promo;
use App\Shop;
use App\Supplier;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Traits\HelperTrait;
use Illuminate\Support\Carbon;

class PromoController extends Controller
{
    use HelperTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }


    public function index(Request $request)
    {
        $markets = Market::orderBy('name', 'asc')->get();
        $shops = Shop::filter([])->orderBy('markets.code')->orderBy('shops.code')->get();
        $suppliers = Supplier::orderBy('name', 'asc')->get();

        $params = $request->all();
        if (!isset($params['order_by']) || $params['order_by'] == null) {
            $params['order_by'] = 'products.created_at';
            $params['order'] = 'desc';
        }
        $order_params = array_merge($params, ['order' => ($params['order'] == 'asc') ? 'desc' : 'asc']);
        $promos = Promo::filter($params)->paginate(50);

        return view('promo.index', compact('markets', 'shops', 'suppliers', 'params', 'order_params', 'promos'));
    }


    public function create()
    {
        $shops = Shop::filter([])->orderBy('markets.code')->orderBy('shops.code')->get();
        $suppliers = Supplier::orderBy('name', 'asc')->get();

        return view('promo.create-edit', compact('shops', 'suppliers'));
    }


    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'shop_id'           => 'nullable|exists:shops,id',
            'supplier_id'       => 'nullable|exists:suppliers,id',
            'name'              => 'required|max:255',
            'product_id'        => 'nullable|exists:products,id',
            'item_select'       => 'nullable|max:255',
            'item_reference'    => 'nullable|max:255',
            'supplierSku'       => 'nullable|max:255',
            'MPSSku'            => 'nullable|max:255',
            'marketProductSku'  => 'nullable|max:255',
            'price'             => 'nullable|numeric|gte:0',
            'discount'          => 'nullable|numeric|gte:0',
            'begins_at'         => 'nullable',
            'ends_at'           => 'nullable',
        ]);

        $shop = null;
        if ($validatedData['shop_id']) {
            $shop = Shop::findOrFail($validatedData['shop_id']);
            if ($shop) $validatedData['market_id'] = $shop->market_id;
        }

        $product = null;
        if (!isset($validatedData['item_reference'])) $validatedData['product_id'] = null;
        if (isset($validatedData['item_reference']) && $validatedData['item_reference'] != null) {
            if ($validatedData['item_select'] == 'pn')
                $product = Product::where('pn', $validatedData['pn'])->first();
            elseif ($validatedData['item_select'] == 'ean')
                $product = Product::where('ean', $validatedData['ean'])->first();
            elseif ($validatedData['item_select'] == 'upc')
                $product = Product::where('upc', $validatedData['upc'])->first();
            elseif ($validatedData['item_select'] == 'isbn')
                $product = Product::where('isbn', $validatedData['isbn'])->first();
            elseif ($validatedData['item_select'] == 'gtin')
                $product = Product::where('gtin', $validatedData['gtin'])->first();
            elseif ($validatedData['item_select'] == 'name')
                if (isset($validatedData['product_id']) && $validatedData['product_id'] != null)
                    $product = Product::find($validatedData['product_id']);
                else
                    $product = Product::where('name', 'LIKE', '%' .$validatedData['item_reference']. '%')->first();
        }

        if ($validatedData['supplierSku']) {
            $product = Product::where('supplierSku', $validatedData['supplierSku'])->first();
        }
        if ($validatedData['MPSSku']) {
            if ($shop && $shop_product = $shop->shop_products()->where('mps_sku', $validatedData['MPSSku'])->first())
                $product = $shop_product->product;
            else
                $product = Product::find($this->getIdFromMPSSku($validatedData['MPSSku']));
        }

        //$shop_product = null;
        if ($product) {
            $validatedData['product_id'] = $product->id;
            $validatedData['supplier_id'] = $product->supplier_id;
            /* if ($shop) {
                $shop_product = ShopProduct::where('shop_id', $shop->id)
                    ->where('product_id', $product->id)
                    ->first();
            } */
        }
        /* elseif ($shop) {
            if ($validatedData['marketProductSku']) {
                $shop_product = ShopProduct::where('shop_id', $shop->id)
                    ->where('marketProductSku', $validatedData['marketProductSku'])
                    ->first();
            }
        } */

       /*  if ($shop_product) {
            $validatedData['shop_product_id'] = $shop_product->id;
            $validatedData['product_id'] = $shop_product->product_id;
            $validatedData['supplier_id'] = $shop_product->product->supplier_id;
        } */

        if ($validatedData['begins_at']) {
            $validatedData['begins_at'] = Carbon::createFromFormat('Y-m-d H:i', $validatedData['begins_at'])->format('Y-m-d H:i:s');
        }
        if ($validatedData['ends_at']) {
            $validatedData['ends_at'] = Carbon::createFromFormat('Y-m-d H:i', $validatedData['ends_at'])->format('Y-m-d H:i:s');
        }

        Promo::create($validatedData);

        return redirect()->route('promos.index')->with('status', 'Producto creado correctamente.');
    }


    public function edit(Promo $promo)
    {
        $shops = Shop::filter([])->orderBy('markets.code')->orderBy('shops.code')->get();
        $suppliers = Supplier::orderBy('name', 'asc')->get();

        return view('promo.create-edit', compact('shops', 'suppliers', 'promo'));
    }


    public function copy(Promo $promo)
    {
        $shops = Shop::filter([])->orderBy('markets.code')->orderBy('shops.code')->get();
        $suppliers = Supplier::orderBy('name', 'asc')->get();

        $copy = $promo;

        return view('promo.create-edit', compact('shops', 'suppliers', 'copy'));
    }


    public function update(Request $request, Promo $promo)
    {
        $validatedData = $request->validate([
            'shop_id'           => 'nullable|exists:shops,id',
            'supplier_id'       => 'nullable|exists:suppliers,id',
            'name'              => 'required|max:255',
            'product_id'        => 'nullable|exists:products,id',
            'item_select'       => 'nullable|max:255',
            'item_reference'    => 'nullable|max:255',
            'supplierSku'       => 'nullable|max:255',
            'MPSSku'            => 'nullable|max:255',
            'marketProductSku'  => 'nullable|max:255',
            'price'             => 'nullable|numeric|gte:0',
            'discount'          => 'nullable|numeric|gte:0',
            'begins_at'         => 'nullable',
            'ends_at'           => 'nullable',
        ]);

        $shop = null;
        if ($validatedData['shop_id']) {
            $shop = Shop::findOrFail($validatedData['shop_id']);
            if ($shop) $validatedData['market_id'] = $shop->market_id;
        }

        $product = null;
        if (!isset($validatedData['item_reference'])) $validatedData['product_id'] = null;
        if (isset($validatedData['item_reference']) && $validatedData['item_reference'] != null) {
            if ($validatedData['item_select'] == 'pn')
                $product = Product::where('pn', $validatedData['pn'])->first();
            elseif ($validatedData['item_select'] == 'ean')
                $product = Product::where('ean', $validatedData['ean'])->first();
            elseif ($validatedData['item_select'] == 'upc')
                $product = Product::where('upc', $validatedData['upc'])->first();
            elseif ($validatedData['item_select'] == 'isbn')
                $product = Product::where('isbn', $validatedData['isbn'])->first();
            elseif ($validatedData['item_select'] == 'gtin')
                $product = Product::where('gtin', $validatedData['gtin'])->first();
            elseif ($validatedData['item_select'] == 'name')
                if (isset($validatedData['product_id']) && $validatedData['product_id'] != null)
                    $product = Product::find($validatedData['product_id']);
                else
                    $product = Product::where('name', 'LIKE', '%' .$validatedData['item_reference']. '%')->first();
        }

        if ($validatedData['supplierSku']) {
            $product = Product::where('supplierSku', $validatedData['supplierSku'])->first();
        }
        if ($validatedData['MPSSku']) {
            if ($shop && $shop_product = $shop->shop_products()->where('mps_sku', $validatedData['MPSSku'])->first())
                $product = $shop_product->product;
            else
                $product = Product::findOrFail($this->getIdFromMPSSku($validatedData['MPSSku']));
        }

        //$shop_product = null;
        if ($product) {
            $validatedData['product_id'] = $product->id;
            $validatedData['supplier_id'] = $product->supplier_id;
           /*  if ($shop) {
                $shop_product = ShopProduct::where('shop_id', $shop->id)
                    ->where('product_id', $product->id)
                    ->first();
            } */
        }
        /* elseif ($shop) {
            if ($validatedData['marketProductSku']) {
                $shop_product = ShopProduct::where('shop_id', $shop->id)
                    ->where('marketProductSku', $validatedData['marketProductSku'])
                    ->first();
            }
        } */

        /* if ($shop_product) {
            $validatedData['shop_product_id'] = $shop_product->id;
            $validatedData['product_id'] = $shop_product->product_id;
            $validatedData['supplier_id'] = $shop_product->product->supplier_id;
        } */

        if ($validatedData['begins_at']) {
            $validatedData['begins_at'] = Carbon::createFromFormat('Y-m-d H:i', $validatedData['begins_at'])->format('Y-m-d H:i:s');
        }
        if ($validatedData['ends_at']) {
            $validatedData['ends_at'] = Carbon::createFromFormat('Y-m-d H:i', $validatedData['ends_at'])->format('Y-m-d H:i:s');
        }

        unset($validatedData['item_select']);
        unset($validatedData['item_reference']);
        unset($validatedData['supplierSku']);
        unset($validatedData['MPSSku']);
        //unset($validatedData['marketProductSku']);

        Promo::whereId($promo->id)->update($validatedData);

        return redirect()->route('promos.index')->with('status', 'Producto modificado correctamente.');
    }


    public function destroy(Promo $promo)
    {
        try {
            $promo->delete();
        } catch (QueryException $e) {
            return redirect()->route('promos.index')->with('status', $e->getMessage());
        }

        return redirect()->route('promos.index')->with('status', 'Promoción eliminada.');
    }

}
