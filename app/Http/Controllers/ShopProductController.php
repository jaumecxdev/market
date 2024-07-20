<?php

namespace App\Http\Controllers;

use App\Currency;
use App\Libraries\MarketWS;
use App\Promo;
use App\Shop;
use App\ShopParam;
use App\ShopProduct;
use App\Supplier;
use App\Traits\HelperTrait;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use Facades\App\Facades\ShopProductsExcel as FacadesShopProductsExcel;
use Facades\App\Facades\Mpe as FacadesMpe;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Throwable;


class ShopProductController extends Controller
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
    public function index(Shop $shop, Request $request)
    {
        try {
            $user = Auth::user();
            if (!$shop = $user->checkShop($shop))
                return redirect()->route('welcome')->withErrors('Hay un problema con la cuenta del usuario. Contacte con el administrador.');

            if ($request->input('action') == 'promo')
                return $this->export($shop, $request);

            if ($request->input('action') == 'delete') {
                $shop_products = $shop->shop_products()->whereNull('marketProductSku')->get();
                foreach ($shop_products as $shop_product)
                    $shop_product->delete();
            }

            if ($user->hasRole('seller')) $suppliers = Supplier::orderBy('name', 'asc')->find($user->getSuppliersId());
            else $suppliers = Supplier::orderBy('name', 'asc')->get();

            $params = $request->all();
            if (!isset($params['order_by']) || $params['order_by'] == null) {
                $params['order_by'] = 'shop_products.created_at';
                $params['order'] = 'desc';
            }
            $order_params = array_merge($params, ['order' => ($params['order'] == 'asc') ? 'desc' : 'asc']);

            //$option_selected = $params['repriced'] ?? null;

            if (!$shop_products = ShopProduct::where('is_sku_child', false)->filter($shop, $params)->paginate(100))
                return $this->backWithErrorMsg(__METHOD__, 'Error en los filtros', [$shop, $request->all()]);

            return view('shop_product.index', compact('shop', 'suppliers', 'shop_products', 'params', 'order_params'));

        } catch (Throwable $th) {
            return $this->backWithErrors($th, __METHOD__, [$shop, $request->all()]);
        }
    }




    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
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

        return view('shop_product.edit', compact('shop', 'shop_product', 'currencies'));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
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

        return redirect()->route('shops.shop_products.index', $shop)->with('status', 'Producto modificado correctamente.');
    }


    public function text(Shop $shop, ShopProduct $shop_product)
    {
        try {
            if (!$ws = MarketWS::getMarketWS($shop)) return $this->backWithErrorMsg(__METHOD__, 'Web Service no encontrado', [$shop, $shop_product]);

            if ($excel_attributes = FacadesShopProductsExcel::getAttributes($ws, 'product')) {
                $attributes_array = [];
                foreach ($excel_attributes as $excel_attribute) {
                    $attributes_array[$excel_attribute] = '';
                }
            }

            //$shop_product->name = $shop_product->name ?? FacadesMpe::buildString($shop_product->product->buildTitle());
            //$shop_product->longdesc = $shop_product->longdesc ?? $shop_product->product->buildDescriptionLong4Excel();
            $attributes = '';

            //$ws = MarketWS::getMarketWS($shop);
            //$header = FacadesShopProductsExcel::getHeader($ws, 'products.xlsx', 2);
            if (isset($shop_product->attributes))
                $shop_product->attributes = json_encode(json_decode($shop_product->attributes, true), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            elseif ($ws = MarketWS::getMarketWS($shop)) {
                $attributes_array = $ws->getItemRowProduct($shop_product);
                $shop_product->attributes = json_encode($attributes_array, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

                /* if(Storage::exists($ws->getShopDir().'products.xlsx')) {
                    $excel_attributes = FacadesShopProductsExcel::getAttributes($ws, 'product');
                    if (is_array($excel_attributes)) {
                        $attributes_array = [];
                        foreach ($excel_attributes as $excel_attribute) {
                            $attributes_array[$excel_attribute] = '';
                        }
                        $shop_product->attributes = json_encode($attributes_array, JSON_UNESCAPED_UNICODE);
                    }
                } */
            }

            return view('shop_product.text', compact('shop', 'shop_product'));

        } catch (Throwable $th) {
            return $this->backWithErrors($th, __METHOD__, [$shop, $shop_product]);
        }
    }


    public function update_text(Request $request, Shop $shop, ShopProduct $shop_product)
    {
        $validatedData = $request->validate([
            'name'          => 'nullable|max:255',
            'longdesc'      => 'nullable',      //|max:8192',
            'attributes'    => 'nullable',      //|max:8192',
        ]);

        $validatedData['name'] = ($validatedData['name'] != '') ? $validatedData['name'] : null;
        $validatedData['longdesc'] = ($validatedData['longdesc'] != '') ? $validatedData['longdesc'] : null;
        $validatedData['attributes'] = ($validatedData['attributes'] != '') ? json_decode($validatedData['attributes'], true) : null;
        $shop_product->update($validatedData);

        return redirect()->route('shops.shop_products.index', $shop)->with('status', 'Text modificado correctamente.');
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Shop $shop, ShopProduct $shop_product)
    {
        try {
            if ($shop_product->isUpgradeable()) {
                if ($ws = MarketWS::getMarketWS($shop)) {
                    $res = $ws->removeProduct($shop_product->marketProductSku);
                    if ($res == $shop_product->marketProductSku) {
                        return redirect()->route('shops.shop_products.index', $shop)
                            ->with('status', 'Producto eliminado del Marketplace y de la lista.');
                    }
                    else
                        return redirect()->route('shops.shop_products.index', $shop)
                            ->withErrors(json_encode($res));
                }

                return redirect()->route('shops.shop_products.index', $shop)->withErrors('Este Marketplace no permite eliminar productos.');
            }

            $shop_product->deleteSecure();
            return redirect()->route('shops.shop_products.index', $shop)->with('status', 'Producto quitado de la lista.');

        } catch (QueryException $e) {
            return redirect()->route('shops.shop_products.index', $shop)->with('status', $e->getMessage());
        }
    }


    public function getFeed(Shop $shop, ShopProduct $shop_product)
    {
        $ws = MarketWS::getMarketWS($shop);
        if (!$ws)
            return redirect()->route('shops.shop_products.index', [$shop])->with('status', 'No hay código.');

        $response = $ws->getFeed($shop_product);
        dd($response);

        return view('fake.view.html', compact('response'));
    }


    public function postProduct(Shop $shop, ShopProduct $shop_product)
    {
        $ws = MarketWS::getMarketWS($shop);
        if (!$ws)
            return redirect()->route('shops.shop_products.index', [$shop])->with('status', 'No hay código.');

        $response = $ws->postNewProduct($shop_product);
        if ($response === null) return redirect()->route('shops.shop_products.index', [$shop])->with('status', 'Hay jobs pendientes');
        return redirect()->route('shops.shop_products.index', [$shop])->with('status', json_encode($response));
    }


    public function postUpdated(Shop $shop, ShopProduct $shop_product)
    {
        $ws = MarketWS::getMarketWS($shop);
        if (!$ws)
            return redirect()->route('shops.shop_products.index', [$shop])->with('status', 'No hay código.');

        $response = $ws->postUpdatedProduct($shop_product);
        return redirect()->route('shops.shop_products.index', [$shop])->with('status', json_encode($response));
    }


    public function postPrice(Shop $shop, ShopProduct $shop_product)
    {
        $ws = MarketWS::getMarketWS($shop);
        if (!$ws)
            return redirect()->route('shops.shop_products.index', [$shop])->with('status', 'No hay código.');

        $response = $ws->postPriceProduct($shop_product);
        return redirect()->route('shops.shop_products.index', [$shop])->with('status', json_encode($response));
    }


    public function calculatePrices(Shop $shop)
    {
        $ws = MarketWS::getMarketWS($shop);
        if (!$ws)
            return redirect()->route('shops.shop_products.index', [$shop])->with('status', 'No hay código.');

        $response = $ws->calculatePrices();

        return redirect()->route('shops.shop_products.index', [$shop])->with('status', json_encode($response));
    }


    public function postProducts(Shop $shop)
    {
        $ws = MarketWS::getMarketWS($shop);
        if (!$ws)
            return redirect()->route('shops.shop_products.index', [$shop])->with('status', 'No hay código.');

        $response = $ws->postNewProducts();

        if ($response === null) return redirect()->route('shops.shop_products.index', [$shop])->with('status', 'Hay jobs pendientes');
        return redirect()->route('shops.shop_products.index', [$shop])->with('status', json_encode($response));
    }


    public function postUpdateds(Shop $shop, Collection $shop_products = null)
    {
        $ws = MarketWS::getMarketWS($shop);
        if (!$ws)
            return redirect()->route('shops.shop_products.index', [$shop])->with('status', 'No hay código.');

        $response = $ws->postUpdatedProducts($shop_products);
        return redirect()->route('shops.shop_products.index', [$shop])->with('status', json_encode($response));
    }


    public function postPrices(Shop $shop)
    {
        $ws = MarketWS::getMarketWS($shop);
        if (!$ws)
            return redirect()->route('shops.shop_products.index', [$shop])->with('status', 'No hay código.');

        $response = $ws->postPricesStocks();
        return redirect()->route('shops.shop_products.index', [$shop])->with('status', json_encode($response));
    }


    public function synchronize(Shop $shop)
    {
        $ws = MarketWS::getMarketWS($shop);
        if (!$ws)
            return redirect()->route('shops.shop_products.index', [$shop])->with('status', 'No hay código.');

        $response = $ws->synchronize();
        return redirect()->route('shops.shop_products.index', [$shop])->with('status', json_encode($response));
    }


    public function export(Shop $shop, Request $request)
    {
        if (!$shop = Auth::user()->checkShop($shop))
            return redirect()->route('welcome')->withErrors('Hay un problema con la cuenta del usuario. Contacte con el administrador.');

        if ($request->input('action') == 'promo') {

            if (in_array($shop->market->code, ['ae', 'ebay', 'carrefour', 'worten', 'pccompo', 'pceducacion', 'manomano', 'davedans'])) {
                $params = $request->all();

                return view('shop_product.export', compact('shop', 'params'));
            }
        }

        return back()->withErrors('No hay código de exportación para este Marketplace.');
    }


    public function promo(Shop $shop, Request $request)
    {
        if (!$shop = Auth::user()->checkShop($shop))
            return redirect()->route('welcome')->withErrors('Hay un problema con la cuenta del usuario. Contacte con el administrador.');

        if (in_array($shop->market->code, ['ae', 'ebay'])) {
            $params = $request->all();
            return view('shop_product.promo', compact('shop', 'params'));
        }

        return back()->withErrors('No hay código de exportación para este Marketplace.');
    }


    public function exportProduct(Shop $shop, Request $request)
    {
        try {
            if (in_array($shop->market->code, ['ae','carrefour', 'pccompo', 'worten', 'pceducacion', 'manomano', 'davedans'])) {
                $params = $request->all();
                $shop_products = ShopProduct::whereEnabled(true)->whereIsSkuChild(false)->filter($shop, $params)->get();
                if ($ws = MarketWS::getMarketWS($shop)) {
                    FacadesShopProductsExcel::download($shop_products, $ws, 'product');

                    /* if (in_array($shop->market->code, ['carrefour', 'pccompo', 'worten']))
                        FacadesShopProductsExcel::download($shop_products, $ws, 'products.xlsx');
                    else
                        FacadesShopProductsExcel::download($shop_products, $ws); */
                }
            }
            else
                return back()->withErrors('No hay código de exportación para este Marketplace.');

        } catch (Exception $e) {
            return redirect()->back()->withErrors($e)->withInput();
        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
            return redirect()->back()->withErrors($e)->withInput();
        }
    }


    public function exportJson(Shop $shop, $field, Request $request)
    {
        try {
            $params = $request->all();
            $shop_products = ShopProduct::whereEnabled(true)->whereIsSkuChild(false)->filter($shop, $params)->get();
            if ($ws = MarketWS::getMarketWS($shop)) {
                $new_filename = date('Y-m-d').'-'.$ws->getMarket()->code.'-'.$ws->getShop()->code.'.json';

                $json_array = $shop_products->whereNotNull($field)->pluck($field)->all();
                //$json_array = $shop_products->whereNotNull('pn')->pluck('pn')->all();

                Storage::put($ws->getShopDir().$new_filename, json_encode($json_array));
                return Storage::download($ws->getShopDir().$new_filename);
            }
        } catch (Throwable $th) {
            return redirect()->back()->withErrors($th->getMessage())->withInput();
        }
    }


    public function exportPromo(Shop $shop, Request $request)
    {
        $validatedData = $request->validate([
            'name'                  => 'nullable|max:255',
            'discount'              => 'required|numeric|gte:0',
            'mobile'                => 'nullable|numeric|gte:0',
            'target'                => 'nullable|max:255',
            'extra'                 => 'nullable|numeric|gte:0',
            'limit'                 => 'nullable|numeric|gte:0',
            'begins_at'             => 'nullable',
            'ends_at'               => 'nullable',
        ]);

        $params = $request->all();
        $shop_products = ShopProduct::whereEnabled(true)->whereIsSkuChild(false)->filter($shop, $params)->get();

        if ($request->input('import')) {

            if (isset($validatedData['mobile'])) $validatedData['discount'] += $validatedData['mobile'];
            if (isset($validatedData['extra'])) $validatedData['discount'] += $validatedData['extra'];

            if ($validatedData['begins_at']) {
                $validatedData['begins_at'] = Carbon::createFromFormat('Y-m-d H:i', $validatedData['begins_at'])->format('Y-m-d H:i:s');
            }
            if ($validatedData['ends_at']) {
                $validatedData['ends_at'] = Carbon::createFromFormat('Y-m-d H:i', $validatedData['ends_at'])->format('Y-m-d H:i:s');
            }

            foreach ($shop_products as $shop_product) {

                Promo::updateOrCreate([
                    'shop_id'           => $shop_product->shop_id,
                    'shop_product_id'   => $shop_product->id,
                    'name'              => $validatedData['name'],
                ],[
                    'market_id'         => $shop_product->market_id,
                    'supplier_id'       => $shop_product->product->supplier_id,
                    'product_id'        => $shop_product->product_id,
                    'price'             => null,
                    'discount'          => $validatedData['discount'],
                    'begins_at'         => $validatedData['begins_at'],
                    'ends_at'           => $validatedData['ends_at'],
                ]);
            }
        }

        if ($request->input('update')) {
            $this->postUpdateds($shop, $shop_products);
        }

        try {
            if ($ws = MarketWS::getMarketWS($shop)) {
                FacadesShopProductsExcel::download($shop_products, $ws, 'promo', $validatedData);
            }
            //return 'No hay código de exportacion de promos';

            /* return Excel::download(new ShopProductsExportPromo($shop, $shop_products, $validatedData), 'export_' .
                str_replace(' ', '', $validatedData['name']) . '_for_' . $shop->market->code . '_' . $shop->code . '.xlsx'); */

        } catch (Exception $e) {
            return redirect()->back()->withErrors($e)->withInput();
        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
            return redirect()->back()->withErrors($e)->withInput();
        }
    }


    public function repricing(Shop $shop, Request $request)
    {
        $res = [];
        try {
            $this->resetOldParamPrices($shop);

            if ($fileinputs = $request->file('fileinput')) {
                $fileinput = $fileinputs[0];
                $contents = file_get_contents($fileinput->getRealPath());
                $json_contents = json_decode($contents);

                if  ($ws = MarketWS::getMarketWS($shop)) {
                    foreach($json_contents as $json_product) {
                        $ean = $json_product[0];
                        $prices = $json_product[1];

                        if (!empty($prices)) {
                            // WORTEN && PCCOMPO && CARREFOUR
                            $scrape_field = $ws->getScrapeField();
                            $competitor_price = $ws->extractCompetitorPrice($prices[0]);
                            if ($product = $shop->shop_products()->leftJoin('products', 'products.id', '=', 'shop_products.product_id')
                                ->where($scrape_field, $ean)->first()) {

                                if ($shop_product = $shop->shop_products()->whereProductId($product->id)->first()) {

                                    $old_mpe_price = (float)$shop_product->price;
                                    if ($competitor_price < $old_mpe_price) {
                                        $new_mpe_price = $this->setReprice($shop_product, $competitor_price, 0.1, 10, 1);
                                        if ($new_mpe_price < $shop_product->price)
                                            $res['REPRICING'][] = [
                                                'ean' => $ean,
                                                'competitor_price' => $competitor_price,
                                                'old_mpe_price' => $old_mpe_price,
                                                'new_mpe_price' => $new_mpe_price,
                                                'cost' => $shop_product->cost,
                                                'mp_fee' => $shop_product->param_mp_fee,
                                                'shop_product' => $shop_product->toArray(),
                                            ];
                                        else
                                            $res['REPRICING NO POSSIBLE'][] = [
                                                'ean' => $ean,
                                                'competitor_price' => $competitor_price,
                                                'old_mpe_price' => $old_mpe_price,
                                                'new_mpe_price' => $new_mpe_price,
                                                'cost' => $shop_product->cost,
                                                'mp_fee' => $shop_product->param_mp_fee,
                                                'shop_product' => $shop_product->toArray(),
                                            ];
                                    }
                                    else {
                                        $res['ALREADY GOOD PRICE'][] = [
                                            'ean' => $ean,
                                            'competitor_price' => $competitor_price,
                                            'mpe_price' => $old_mpe_price,
                                            'cost' => $shop_product->cost,
                                            'mp_fee' => $shop_product->param_mp_fee,
                                            'shop_product' => $shop_product->toArray(),
                                        ];

                                        if ($competitor_price - 1 > $shop_product->price) {
                                            $shop_product->param_price = $shop_product->price = $competitor_price - 1;
                                            $shop_product->save();
                                            //$shop_product->logPrice();
                                        }
                                    }
                                }
                                else
                                    $res['SHOP_PRODUCT NOT FOUND'][] = [
                                        'ean' => $ean,
                                        'competitor_price' => $competitor_price,
                                        'json_product' => $json_product,
                                        'product' => $product,
                                    ];
                            }
                            else
                                $res['PRODUCT NOT FOUND'][] = [
                                    'ean' => $ean,
                                    'competitor_price' => $competitor_price,
                                    'json_product' => $json_product,
                                ];
                        }
                    }

                    $msg = isset($res['REPRICING']) ? ('Repricing ' .count($res['REPRICING']). ' products') : 'No repricing possible';
                    Storage::append($shop->getShopDir().date('Y-m-d').__FUNCTION__. '.json', json_encode([$msg, $res]));

                    return $res['REPRICING'] ?? 'No repricing possible';
                }
            }
        }
        catch (Throwable $th) {
            Storage::append('errors/'.__FUNCTION__. '/' .date('Y-m-d'). '.json',
                json_encode([$shop->toArray(), $request->toArray(), $th->getMessage(), $th->getTrace()]));

            return [$res, $th->getMessage(), $th->getTrace()];
        }

        return back()->with('status', $res);
    }


    private function resetOldParamPrices(Shop $shop)
    {
        $shop->shop_products()->update(['param_price' => 0]);
        if ($ws = MarketWS::getMarketWS($shop)) {
            foreach ($shop->shop_products as $shop_product) {
                $shop_product->setPriceStock(null, $ws->cost_is_price);
            }
        }
    }


    // OBSOLETE -> ShopProduct->setReprice
    private function setReprice(ShopProduct $shop_product, $competitor_price, $min_fee = 3, $bfit_min = null, $subtract_amount = 0.05)
    {
        try {
            $shop_product->setPriceStock();
            $price = $shop_product->price;

            if ($price > $competitor_price) {
                $new_price = $competitor_price - $subtract_amount;
                $bfits = FacadesMpe::getBfitsByPrice($shop_product->getCost(), $new_price, $shop_product->param_mps_fee,
                    $shop_product->param_mp_fee, $shop_product->param_mp_fee_addon, $shop_product->tax);

                $bfit_min = $bfit_min ?? $shop_product->param_bfit_min;
                Storage::append($shop_product->shop->getShopDir().date('Y-m-d').__FUNCTION__. '.json',
                    json_encode(['price > competitor_price', $price, $competitor_price, $new_price, $min_fee, $bfit_min, $subtract_amount, $bfits]));
                if ($bfits['fee'] >= $min_fee && $bfits['bfit'] >= $bfit_min) {

                    $shop_product->param_price = $shop_product->price = $new_price;
                    $shop_product->save();
                    //$shop_product->logPrice();

                    return $new_price;
                }
                // $subtract_amount = 0.10
                elseif ($subtract_amount > 0.05) {
                    $new_price = $competitor_price - 0.05;
                    $bfits = FacadesMpe::getBfitsByPrice($shop_product->getCost(), $new_price, $shop_product->param_mps_fee,
                        $shop_product->param_mp_fee, $shop_product->param_mp_fee_addon, $shop_product->tax);

                    $bfit_min = $bfit_min ?? $shop_product->param_bfit_min;
                    Storage::append($shop_product->shop->getShopDir().date('Y-m-d').__FUNCTION__. '.json',
                        json_encode(['subtract_amount > 0.05', $price, $competitor_price, $new_price, $min_fee, $bfit_min, $subtract_amount, $bfits]));
                    if ($bfits['fee'] >= $min_fee && $bfits['bfit'] >= $bfit_min) {

                        $shop_product->param_price = $shop_product->price = $new_price;
                        $shop_product->save();
                        //$shop_product->logPrice();

                        return $new_price;
                    }
                }
            }

            Storage::append($shop_product->shop->getShopDir().date('Y-m-d').__FUNCTION__. '.json',
                    json_encode(['NO Reprice', $price, $competitor_price, $new_price, $min_fee, $bfit_min, $subtract_amount, $bfits]));

            return $price;

        } catch (Throwable $th) {
            Storage::append($shop_product->shop->getShopDir().'errors/'.date('Y-m-d').__FUNCTION__. '.json',
                json_encode([$shop_product, $competitor_price, $min_fee, $bfit_min, $subtract_amount, $th->getMessage(), $th->getTrace()]));
            return 999999999;
        }
    }

}
