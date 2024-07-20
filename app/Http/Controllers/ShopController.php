<?php

namespace App\Http\Controllers;

use App\Libraries\MarketWS;
use App\Market;
use App\Shop;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ShopController extends Controller
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
    public function index()
    {
        $shops = Shop::filter([])->orderBy('markets.code')->orderBy('shops.code')->paginate(100);

        return view('shop.index', compact('shops'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        $markets = Market::all();

        return view('shop.create-edit', compact('markets'));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // 'enabled'
        // 'header_url'
        // 'payment', 'preparation', 'shipping', 'return', 'channel'
        $validatedData = $request->validate([
            'market_id'     => 'required|exists:markets,id',
            'code'          => 'required|max:16',
            'name'          => 'required|max:64',
            'locale'        => 'nullable|max:64',
            'marketShopId'  => 'nullable|max:64',
            'marketSellerId'=> 'nullable|max:64',
            'endpoint'      => 'nullable|max:255',
            'store_url'     => 'nullable|max:255',
            'redirect_url'  => 'nullable|max:255',
            'header_url'    => 'nullable|max:255',
            'country'       => 'nullable|max:64',
            'site'          => 'nullable|max:64',
            'app_name'      => 'nullable|max:255',
            'app_version'   => 'nullable|max:255',
            'client_id'     => 'nullable',
            'client_secret' => 'nullable',
            'dev_id'        => 'nullable',
            'dev_secret'    => 'nullable',
            'token'         => 'nullable',
            'refresh'       => 'nullable',

            'preparation'   => 'nullable|max:64',
            'shipping'      => 'nullable|max:64',
            'return'        => 'nullable|max:64',
            'payment'       => 'nullable|max:64',
            'channel'       => 'nullable|max:64',

            'config'        => 'nullable',  // json
        ]);

        $validatedData['enabled'] = $request->has('enabled') ? 1 : 0;
        $shop = Shop::create($validatedData);
        if (!isset($shop->config)) {
            $shop->config = json_encode(('App\\Libraries\\'.$shop->market->ws)::DEFAULT_CONFIG);
            $shop->save();
        }

        return redirect()->route('shops.index')->with('status', 'Tienda creada correctamente.');
    }


    public function show(Shop $shop)
    {
        return $this->edit($shop);
    }


    public function edit(Shop $shop)
    {
        $markets = Market::all();

        return view('shop.create-edit', compact('shop', 'markets'));
    }


    public function update(Request $request, Shop $shop)
    {
        try {
            $validatedData = $request->validate([
                'market_id'     => 'required|exists:markets,id',
                'code'          => 'required|max:16',
                'name'          => 'required|max:64',
                'locale'        => 'nullable|max:64',
                'marketShopId'  => 'nullable|max:64',
                'marketSellerId'=> 'nullable|max:64',
                'endpoint'      => 'nullable|max:255',
                'store_url'     => 'nullable|max:255',
                'redirect_url'  => 'nullable|max:255',
                'header_url'    => 'nullable|max:255',
                'country'       => 'nullable|max:64',
                'site'          => 'nullable|max:64',
                'app_name'      => 'nullable|max:255',
                'app_version'   => 'nullable|max:255',
                'client_id'     => 'nullable',
                'client_secret' => 'nullable',
                'dev_id'        => 'nullable',
                'dev_secret'    => 'nullable',
                'token'         => 'nullable',
                'refresh'       => 'nullable',

                'preparation'   => 'nullable|max:64',
                'shipping'      => 'nullable|max:64',
                'return'        => 'nullable|max:64',
                'payment'       => 'nullable|max:64',
                'channel'       => 'nullable|max:64',

                'config'        => 'nullable',  // json
            ]);

            $validatedData['enabled'] = $request->has('enabled') ? 1 : 0;

            Shop::whereId($shop->id)->update($validatedData);

            return redirect()->route('shops.index')->with('status', 'Tienda modificada correctamente.');

        } catch (Throwable $th) {
            Storage::append('errors/' .date('y-m-d_H'). '_shop_update.json', json_encode([$th->getMessage(), $shop->toArray(), $request->toArray()]));
            return redirect()->route('shops.edit', [$shop])->withErrors ('Los datos introducidos no son correctos.');
        }
    }


    public function destroy(Shop $shop)
    {
        try {
            $shop->delete();
        } catch (QueryException $e) {
            return redirect()->route('shops.index')->with('status', $e->getMessage());
        }

        return redirect()->route('shops.index')->with('status', 'Tienda eliminada.');
    }


    public function getJobs(Shop $shop)
    {
        $ws = MarketWS::getMarketWS($shop);
        if (!$ws)
            return redirect()->route('shops.shop_products.index', [$shop])->with('status', 'No hay código.');

        $response = $ws->getJobs();
        return redirect()->route('shops.shop_products.index', [$shop])->with('status', json_encode($response));
    }


    public function getCarriers(Shop $shop)
    {
        $ws = MarketWS::getMarketWS($shop);
        if ($ws) {
            $res = $ws->getCarriers();
            if ($res === true)
                return redirect()->route('shops.shop_products.index', [$shop])->with('status', 'Transportes descargados correctamente.');
        }

        return redirect()->route('shops.shop_products.index', [$shop])
                    ->withErrors('Ha ocurrido un error y no se han podido descargar los transportes. '.$res ?? null);
    }


    public function getOrders(Shop $shop) {

        $ws = MarketWS::getMarketWS($shop);
        if (!$ws)
            return redirect()->route('shops.shop_products.index', ['shop_id' => $shop])->with('status', 'No hay código.');

        $response = $ws->getOrders();

        return redirect()->route('orders.index', ['shop_id' => $shop])->with('status', json_encode($response));
    }



    public function getPayments(Shop $shop) {

        if ($ws = MarketWS::getMarketWS($shop)) {
            $response = $ws->getPayments();

        return redirect()->route('order_payments.index', ['shop_id' => $shop])->with('status', json_encode($response));
        }

        return redirect()->route('shops.shop_products.index', ['shop_id' => $shop])->with('status', 'No hay código.');
    }




}
