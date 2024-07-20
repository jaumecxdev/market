<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use App\Product;
use App\Shop;
use App\ShopParam;
use App\Traits\HelperTrait;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Traits\HasRoles;
use Throwable;


class ShopParamController extends Controller
{
    use HasRoles;
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
        $root_categories = $shop->market->root_categories()->orderBy('name')->get();
        $shop_params = $shop->shop_params;

        return view('saas.shop_param.index', compact('shop', 'suppliers', 'root_categories', 'shop_params'));
    }


    public function store(Request $request, Shop $shop)
    {
        $validatedData = $request->validate([
            'supplier_id'   => 'nullable|exists:suppliers,id',
            'supplierSku'   => 'nullable|max:255',
            'product_id'    => 'nullable|exists:products,id',
            'item_select'   => 'nullable|max:255',
            'item_reference'=> 'nullable|max:255',

            'supplier_brand_id'      => 'nullable|exists:supplier_brands,id',
            'supplier_category_id'   => 'nullable|exists:supplier_categories,id',
            'root_category_id'       => 'nullable|exists:root_categories,id',
            'market_category_id'     => 'nullable|exists:market_categories,id',

            'cost_min'      => 'nullable|numeric|gte:0',
            'cost_max'      => 'nullable|numeric|gte:0',
            'starts_at'     => 'nullable|date_format:Y-m-d',
            'ends_at'       => 'nullable|date_format:Y-m-d',

           /*  'canon'         => 'nullable|numeric', */
            'rappel'        => 'nullable|numeric',
            'ports'         => 'nullable|numeric',

            'fee'               => 'nullable|numeric',
            'bfit_min'          => 'nullable|numeric|gte:0',
            'reprice_fee_min'   => 'nullable|numeric|gte:0',

            'price'         => 'nullable|numeric|gte:0',
            'discount_price'=> 'nullable|numeric|gte:0',
            'stock'         => 'nullable|numeric|gte:0',
            'stock_min'     => 'nullable|numeric|gte:0',
            'stock_max'     => 'nullable|numeric|gte:0',
        ]);

        /* $validatedData['canon'] = $validatedData['canon'] ?? 0; */
        $validatedData['rappel'] = $validatedData['rappel'] ?? 0;
        $validatedData['ports'] = $validatedData['ports'] ?? 0;

        $validatedData['fee'] = $validatedData['fee'] ?? 0;
        $validatedData['bfit_min'] = $validatedData['bfit_min'] ?? 0;
        $validatedData['reprice_fee_min'] = $validatedData['reprice_fee_min'] ?? 0;

        $validatedData['price'] = $validatedData['price'] ?? 0;
        $validatedData['discount_price'] = $validatedData['discount_price'] ?? 0;
        $validatedData['stock'] = $validatedData['stock'] ?? 0;
        $validatedData['stock_min'] = $validatedData['stock_min'] ?? 0;
        $validatedData['stock_max'] = $validatedData['stock_max'] ?? 0;

        if (/* !$validatedData['canon'] && */ !$validatedData['rappel'] && !$validatedData['ports'] &&
            !$validatedData['fee'] && !$validatedData['bfit_min'] &&
            !$validatedData['price'] && !$validatedData['discount_price'] && !$validatedData['stock'] &&
            !$validatedData['stock_min'] && !$validatedData['stock_max'])
            return redirect()->route('saas.shops.shop_params', [$shop])->withErrors ('No se han definido parámetros.');

        if (!isset($validatedData['item_reference'])) $validatedData['product_id'] = null;
        if (isset($validatedData['item_reference'])) {
            if ($validatedData['item_select'] == 'id')
                $validatedData['product_id'] = $validatedData['item_reference'];
            elseif ($validatedData['item_select'] == 'supplierSku')
                $validatedData['supplierSku'] = $validatedData['item_reference'];
            elseif ($validatedData['item_select'] == 'pn')
                $validatedData['pn'] = $validatedData['item_reference'];
            elseif ($validatedData['item_select'] == 'ean')
                $validatedData['ean'] = $validatedData['item_reference'];
            elseif ($validatedData['item_select'] == 'upc')
                $validatedData['upc'] = $validatedData['item_reference'];
            elseif ($validatedData['item_select'] == 'isbn')
                $validatedData['isbn'] = $validatedData['item_reference'];
            elseif ($validatedData['item_select'] == 'gtin')
                $validatedData['gtin'] = $validatedData['item_reference'];
        }

        try {
            ShopParam::updateOrCreate([
                'shop_id'           => $shop->id,

                'supplier_id'       => $validatedData['supplier_id'],
                'supplier_brand_id'          => $validatedData['supplier_brand_id'],
                'supplier_category_id'       => $validatedData['supplier_category_id'],
                'root_category_id'  => $validatedData['root_category_id'],
                'market_category_id'=> $validatedData['market_category_id'],

                'product_id'        => $validatedData['product_id'],
                'supplierSku'       => $validatedData['supplierSku'] ?? null,
                'pn'                => $validatedData['pn'] ?? null,
                'ean'               => $validatedData['ean'] ?? null,
                'upc'               => $validatedData['upc'] ?? null,
                'isbn'              => $validatedData['isbn'] ?? null,
                'gtin'              => $validatedData['gtin'] ?? null,

                'cost_min'          => $validatedData['cost_min'] ?? null,
                'cost_max'          => $validatedData['cost_max'] ?? null,
                'starts_at'         => $validatedData['starts_at'] ?? null,
                'ends_at'           => $validatedData['ends_at'] ?? null,

              /*   'canon'             => $validatedData['canon'], */
                'rappel'            => $validatedData['rappel'],
                'ports'             => $validatedData['ports'],

                'fee'               => $validatedData['fee'],
                'bfit_min'          => $validatedData['bfit_min'],
                'reprice_fee_min'   => $validatedData['reprice_fee_min'],

                'price'             => $validatedData['price'],
                'discount_price'    => $validatedData['discount_price'],
                'stock'             => $validatedData['stock'],
                'stock_min'         => $validatedData['stock_min'],
                'stock_max'         => $validatedData['stock_max'],
            ],[
            ]);

            return redirect()->route('saas.shops.shop_params', [$shop])->with('status', 'Parámetro creado correctamente.');

        } catch (Throwable $th) {
            return redirect()->route('saas.shops.shop_params', [$shop])->withErrors($th->getMessage());
        }
    }


    public function edit(Shop $shop, ShopParam $shop_param)
    {
        $user = Auth::user();
        if (!$shop = $user->checkShop($shop))
            return redirect()->route('welcome')->withErrors('Hay un problema con la cuenta de tu usuario, contacta el administrador.');

        $suppliers = $user->getSuppliers();
        $root_categories = $shop->market->root_categories()->orderBy('name')->get();
        $shop_params = $shop->shop_params;

        return view('saas.shop_param.index', compact('shop', 'shop_param', 'suppliers', 'root_categories', 'shop_params'));
    }


    public function update(Request $request, Shop $shop, ShopParam $shop_param)
    {
        $validatedData = $request->validate([
            'supplier_id'   => 'nullable|exists:suppliers,id',
            'supplierSku'   => 'nullable|max:255',
            'product_id'    => 'nullable|exists:products,id',
            'item_select'   => 'nullable|max:255',
            'item_reference'=> 'nullable|max:255',

            'supplier_brand_id'      => 'nullable|exists:supplier_brands,id',
            'supplier_brand_name'    => 'nullable|max:255',
            'supplier_category_id'   => 'nullable|exists:supplier_categories,id',
            'supplier_category_name' => 'nullable|max:255',
            'root_category_id'      => 'nullable|exists:root_categories,id',
            'market_category_id'    => 'nullable|exists:market_categories,id',
            'market_category_name'  => 'nullable|max:255',

            'starts_at'     => 'nullable|date_format:Y-m-d',
            'ends_at'       => 'nullable|date_format:Y-m-d',

            'cost_min'      => 'nullable|numeric|gte:0',
            'cost_max'      => 'nullable|numeric|gte:0',

           /*  'canon'         => 'nullable|numeric', */
            'rappel'        => 'nullable|numeric',
            'ports'         => 'nullable|numeric',

            'fee'               => 'nullable|numeric',
            'bfit_min'          => 'nullable|numeric|gte:0',
            'reprice_fee_min'   => 'nullable|numeric|gte:0',

            'price'         => 'nullable|numeric|gte:0',
            'discount_price'=> 'nullable|numeric|gte:0',
            'stock'         => 'nullable|numeric|gte:0',
            'stock_min'     => 'nullable|numeric|gte:0',
            'stock_max'     => 'nullable|numeric|gte:0',
        ]);

        try {
            /* $validatedData['canon'] = $validatedData['canon'] ?? 0; */
            $validatedData['rappel'] = $validatedData['rappel'] ?? 0;
            $validatedData['ports'] = $validatedData['ports'] ?? 0;

            $validatedData['fee'] = $validatedData['fee'] ?? 0;
            $validatedData['bfit_min'] = $validatedData['bfit_min'] ?? 0;
            $validatedData['reprice_fee_min'] = $validatedData['reprice_fee_min'] ?? 0;

            $validatedData['price'] = $validatedData['price'] ?? 0;
            $validatedData['discount_price'] = $validatedData['discount_price'] ?? 0;
            $validatedData['stock'] = $validatedData['stock'] ?? 0;
            $validatedData['stock_min'] = $validatedData['stock_min'] ?? 0;
            $validatedData['stock_max'] = $validatedData['stock_max'] ?? 0;

            if (/* !$validatedData['canon'] && */ !$validatedData['rappel'] && !$validatedData['ports'] &&
                !$validatedData['fee'] && !$validatedData['bfit_min'] && !$validatedData['mps_fee'] &&
                !$validatedData['price'] && !$validatedData['discount_price'] && !$validatedData['stock'] &&
                !$validatedData['stock_min'] && !$validatedData['stock_max'])
                return redirect()->route('shops.shop_params.index', [$shop])->withErrors ('No se han definido parámetros.');

            $validatedData['product_id'] = null;
            $validatedData['pn'] = null;
            $validatedData['ean'] = null;
            $validatedData['upc'] = null;
            $validatedData['isbn'] = null;
            $validatedData['gtin'] = null;

            if (!isset($validatedData['item_reference'])) $validatedData['product_id'] = null;
            elseif (!isset($validatedData['product_id']) || $validatedData['item_select'] != 'name') {
                $validatedData['product_id'] = null;

                if ($validatedData['item_select'] == 'id')
                    $validatedData['product_id'] = $validatedData['item_reference'];
                elseif ($validatedData['item_select'] == 'pn')
                    $validatedData['pn'] = $validatedData['item_reference'];
                elseif ($validatedData['item_select'] == 'ean')
                    $validatedData['ean'] = $validatedData['item_reference'];
                elseif ($validatedData['item_select'] == 'upc')
                    $validatedData['upc'] = $validatedData['item_reference'];
                elseif ($validatedData['item_select'] == 'isbn')
                    $validatedData['isbn'] = $validatedData['item_reference'];
                elseif ($validatedData['item_select'] == 'gtin')
                    $validatedData['gtin'] = $validatedData['item_reference'];
                elseif ($validatedData['item_select'] == 'name' && !isset($validatedData['product_id'])) {
                    $product = Product::where('name', 'LIKE', '%' .$validatedData['item_reference']. '%')->first();
                    if ($product) $validatedData['product_id'] = $product->id;
                    else return redirect()->route('saas.shops.shop_params', [$shop])->withErrors('No se ha encontrado este Producto.');
                }
            }

            $validatedData['supplier_brand_id'] = $validatedData['supplier_brand_name'] ? $validatedData['supplier_brand_id'] : null;
            $validatedData['supplier_category_id'] = $validatedData['supplier_category_name'] ? $validatedData['supplier_category_id'] : null;
            $validatedData['market_category_id'] = $validatedData['market_category_name'] ? $validatedData['market_category_id'] : null;

            $validatedData['shop_id'] = $shop->id;
            unset($validatedData['item_select']);
            unset($validatedData['item_reference']);
            unset($validatedData['supplier_brand_name']);
            unset($validatedData['supplier_category_name']);
            unset($validatedData['market_category_name']);

            ShopParam::whereId($shop_param->id)->update($validatedData);

            return redirect()->route('saas.shops.shop_params', [$shop])->with('status', 'Parámetro modificado correctamente.');
        } catch (Throwable $th) {
            return redirect()->route('saas.shops.shop_params', [$shop])->withErrors($th->getMessage())->withInput();
        }
    }


    public function destroy(Shop $shop, ShopParam $shop_param)
    {
        try {
            $shop_param->delete();
            return redirect()->route('saas.shops.shop_params', [$shop])->with('status', 'Parámetro eliminado.');

        } catch (QueryException $e) {
            return redirect()->route('saas.shops.shop_params', [$shop])->withErrors($e)->withInput();
        }
    }


    public function sync(Shop $shop)
    {
        try {
            $msg = $shop->syncParams();

            return redirect()->route('saas.shops.shop_params', [$shop])
                ->with('status', $msg);

        } catch (Throwable $th) {
            return $this->backWithErrors($th, __METHOD__, [$shop, $msg]);
        }
    }


}