<?php

namespace App\Http\Controllers\Saas;

use App\Currency;
use App\Http\Controllers\Controller;
use App\Libraries\MarketWS;
use App\Shop;
use App\ShopParam;
use App\ShopProduct;
use App\Traits\HelperTrait;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use Facades\App\Facades\ShopProductsExcel as FacadesShopProductsExcel;
use Throwable;


class ShopProductController extends Controller
{
    use HelperTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }


    public function index(Request $request, Shop $shop)
    {
        try {
            $user = Auth::user();
            if (!$shop = $user->checkShop($shop))
                return redirect()->route('welcome')->withErrors('Hay un problema con la cuenta de tu usuario, contacta el administrador.');

            if ($request->input('action') == 'export')
                return $this->export($request, $shop);

            if ($request->input('action') == 'delete') {
                $shop_products = $shop->shop_products()->whereNull('marketProductSku')->get();
                foreach ($shop_products as $shop_product)
                    $shop_product->delete();
            }

            $suppliers = $user->getSuppliers();
            $params = $request->all();
            if (!isset($params['order_by']) || $params['order_by'] == null) {
                $params['order_by'] = 'shop_products.created_at';
                $params['order'] = 'desc';
            }
            $order_params = array_merge($params, ['order' => ($params['order'] == 'asc') ? 'desc' : 'asc']);

            //$option_selected = $params['repriced'] ?? null;

            if (!$shop_products = ShopProduct::where('is_sku_child', false)->filter($shop, $params)->paginate(100))
                return $this->backWithErrorMsg(__METHOD__, 'Error en los filtros', [$shop, $request->all()]);

            return view('saas.shop_product.index', compact('shop', 'suppliers', 'shop_products', 'params', 'order_params'));

        } catch (Throwable $th) {
            return $this->backWithErrors($th, __METHOD__, [$shop, $request->all()]);
        }
    }


    public function edit(Shop $shop, ShopProduct $shop_product)
    {
        $currencies = Currency::orderBy('name')->get();
        /*$fixeds = [
            0   => 'Ningún valor fijo',
            1   => 'Fijar coste',                           // cost
            2   => 'Fijar precio del Marketplace',          // price
            3   => 'Fijar % margen de beneficio MPS',       // fee_mp2
            4   => 'Fijar % comisión del Marketplace',      // fee_mp
            5   => 'Fijar € margen de beneficio MPS',       // benefit_mps
            6   => 'Fijar toddo',
        ];*/

        return view('saas.shop_product.edit', compact('shop', 'shop_product', 'currencies'));
    }


    public function update(Request $request, Shop $shop, ShopProduct $shop_product)
    {
        $validatedData = $request->validate([
            'market_category_id'    => 'nullable|exists:market_categories,id',
            'currency_id'           => 'nullable|exists:currencies,id',
            'marketProductSku'      => 'nullable|max:255',
            'MPSSku'                => 'nullable|max:64',

            'param_fee'             => 'nullable|numeric|gte:0',
            'param_mps_fee'         => 'nullable|numeric|gte:0',
            'param_bfit_min'        => 'nullable|numeric|gte:0',
            'param_reprice_fee_min' => 'nullable|numeric|gte:0',

            'param_price'           => 'nullable|numeric|gte:0',
            'param_stock'           => 'nullable|numeric|gte:0',
            'param_stock_min'       => 'nullable|numeric|gte:0',
            'param_stock_max'       => 'nullable|numeric|gte:0',

            'param_canon'           => 'nullable|numeric|gte:0',
            'param_rappel'          => 'nullable|numeric|gte:0',
            'param_ports'           => 'nullable|numeric|gte:0',

            'param_starts_at'       => 'nullable|date_format:Y-m-d',
            'param_ends_at'         => 'nullable|date_format:Y-m-d',
            'param_discount_price'  => 'nullable|numeric|gte:0',

            /* 'mp_fee'                => 'nullable|numeric|gte:0',
            'mp_fee_addon'          => 'nullable|numeric|gte:0', */
        ]);

        $validatedData['enabled'] = $request->has('enabled') ? 1 : 0;

        $validatedData['param_fee'] = $validatedData['param_fee'] ?? 0;
        $validatedData['param_mps_fee'] = $validatedData['param_mps_fee'] ?? 0;
        $validatedData['param_bfit_min'] = $validatedData['param_bfit_min'] ?? 0;
        $validatedData['param_reprice_fee_min'] = $validatedData['param_reprice_fee_min'] ?? 0;

        $validatedData['param_price'] = $validatedData['param_price'] ?? 0;
        $validatedData['param_stock'] = $validatedData['param_stock'] ?? 0;
        $validatedData['param_stock_min'] = $validatedData['param_stock_min'] ?? 0;
        $validatedData['param_stock_max'] = $validatedData['param_stock_max'] ?? 0;

        $validatedData['param_canon'] = $validatedData['param_canon'] ?? 0;
        $validatedData['param_rappel'] = $validatedData['param_rappel'] ?? 0;
        $validatedData['param_ports'] = $validatedData['param_ports'] ?? 0;

        $validatedData['param_discount_price'] = $validatedData['param_discount_price'] ?? 0;

        $validatedData['is_sku_child'] = $request->has('is_sku_child') ? 1 : 0;
        $validatedData['set_group'] = $request->has('set_group') ? 1 : 0;

        // ADD SHOP_PARAM?
        $shop_params = [];
        $count_param = 0;
        $fields_shop_params_queries = $shop->getFieldsShopParamsQueries();
        foreach (ShopParam::VALUE_FIELDS as $shop_param_value_field) {

            if ($validatedData[ShopProduct::VALUE_PARAM_FIELDS[$count_param]]) {

                $shop_param = $fields_shop_params_queries[$shop_param_value_field] ?
                    $shop_product->getShopParam($shop_param_value_field, $fields_shop_params_queries[$shop_param_value_field]) :
                    null;

                if ($shop_param != $validatedData[ShopProduct::VALUE_PARAM_FIELDS[$count_param]])
                    $shop_params[$shop_param_value_field] = $validatedData[ShopProduct::VALUE_PARAM_FIELDS[$count_param]];
            }

            $count_param++;
        }

        if (count($shop_params)) {
            $shop_param = ShopParam::updateOrCreate([
                'shop_id'       => $shop->id,
                'product_id'    => $shop_product->product->id,
            ],
            []);
            $shop_param->update($shop_params);
        }

        $validatedData['mps_sku'] = $validatedData['MPSSku'];
        unset($validatedData['MPSSku']);
        $shop_product->update($validatedData);

        return redirect()->route('saas.shops.shop_products', $shop)->with('status', 'Producto modificado correctamente.');
    }


    public function destroy(Shop $shop, ShopProduct $shop_product)
    {
        try {
            if ($shop_product->isUpgradeable()) {
                if ($ws = MarketWS::getMarketWS($shop)) {
                    $res = $ws->removeProduct($shop_product->marketProductSku);
                    if ($res == $shop_product->marketProductSku) {
                        return redirect()->route('saas.shops.shop_products', $shop)
                            ->with('status', 'Producto eliminado del Marketplace y de la lista.');
                    }
                    else
                        return redirect()->route('saas.shops.shop_products', $shop)
                            ->withErrors(json_encode($res));
                }

                return redirect()->route('saas.shops.shop_products', $shop)->withErrors('Este Marketplace no permite eliminar productos.');
            }

            $shop_product->deleteSecure();
            return redirect()->route('saas.shops.shop_products', $shop)->with('status', 'Producto quitado de la lista.');

        } catch (QueryException $e) {
            return redirect()->route('saas.shops.shop_products', $shop)->with('status', $e->getMessage());
        }
    }


    public function calculatePrices(Shop $shop)
    {
        $setReprice = true;
        if ($config_json = json_decode($shop->config))
            $setReprice = $config_json->reprice ?? true;

        $ws = MarketWS::getMarketWS($shop);
        if (!$ws)
            return redirect()->route('saas.shops.shop_products', [$shop])->with('status', 'No hay código.');

        $response = $ws->calculatePrices($setReprice);

        return redirect()->route('saas.shops.shop_products', [$shop])->with('status', json_encode($response));
    }


    public function postProduct(Shop $shop, ShopProduct $shop_product)
    {
        $ws = MarketWS::getMarketWS($shop);
        if (!$ws)
            return redirect()->route('saas.shops.shop_products', [$shop])->with('status', 'No hay código.');

        $response = $ws->postNewProduct($shop_product);
        if ($response === null) return redirect()->route('saas.shops.shop_products', [$shop])->with('status', 'Hay jobs pendientes');
        return redirect()->route('saas.shops.shop_products', [$shop])->with('status', json_encode($response));
    }


    public function postProducts(Shop $shop)
    {
        $ws = MarketWS::getMarketWS($shop);
        if (!$ws)
            return redirect()->route('saas.shops.shop_products', [$shop])->with('status', 'No hay código.');

        $response = $ws->postNewProducts();

        if ($response === null) return redirect()->route('saas.shops.shop_products', [$shop])->with('status', 'Hay jobs pendientes');
        return redirect()->route('saas.shops.shop_products', [$shop])->with('status', json_encode($response));
    }


    public function synchronize(Shop $shop)
    {
        $ws = MarketWS::getMarketWS($shop);
        if (!$ws)
            return redirect()->route('saas.shops.shop_products', [$shop])->with('status', 'No hay código.');

        $response = $ws->synchronize();
        return redirect()->route('saas.shops.shop_products', [$shop])->with('status', json_encode($response));
    }


    public function export(Request $request, Shop $shop)
    {
        try {
            $params = $request->all();
            $shop_products = ShopProduct::whereEnabled(true)->whereIsSkuChild(false)->filter($shop, $params)->get();
            if ($ws = MarketWS::getMarketWS($shop))
                FacadesShopProductsExcel::downloadShopProduct($shop_products, $ws);

            return back()->withErrors('No hay código de exportación para este Marketplace.');

        } catch (Exception $e) {
            return redirect()->back()->withErrors($e)->withInput();
        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
            return redirect()->back()->withErrors($e)->withInput();
        }
    }

}
