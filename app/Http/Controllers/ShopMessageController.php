<?php

namespace App\Http\Controllers;

use App\Libraries\MarketWS;
use App\Shop;
use App\ShopMessage;
use Illuminate\Http\Request;

class ShopMessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Shop $shop, Request $request)
    {
        dd('index', $shop, $request);

        /* $params = $request->all();
        if (!isset($params['order_by']) || $params['order_by'] == null) {
            $params['order_by'] = 'shop_messages.updated_at';
            $params['order'] = 'desc';
        }
        $order_params = array_merge($params, ['order' => ($params['order'] == 'asc') ? 'desc' : 'asc']); */

        /*
        $user = Auth::user();
        if ($user->hasRole('seller')) {
            $shops_id = $user->getShopsId();    // [9,13,14]
            $params['shops_id'] = $shops_id;
            $shops = Shop::filter([])->orderBy('markets.code')->orderBy('shops.code')->find($shops_id);
        }
        else
            $shops = Shop::filter([])->orderBy('markets.code')->orderBy('shops.code')->get();*/

        //$markets = Market::all();
        //$shops = Shop::filter([])->orderBy('markets.code')->orderBy('shops.code')->get();


        /* $shop_messages = $shop->shop_messages()->filter($params)->paginate(50);

        return view('shop_message.index', compact('params', 'shop_messages')); */
    }


    public function get(Shop $shop, Request $request)
    {
        if ($ws = MarketWS::getMarketWS($shop)) {
            $res = $ws->getMessages();
            if ($res === true)
                return redirect()->route('shops.messages.index', [$shop])->with('status', 'Transportes descargados correctamente.');
        }

        return redirect()->route('shops.messages.index', [$shop])
                    ->withErrors('Ha ocurrido un error y no se han podido descargar los mensajes. '.$res ?? null);
    }



    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }


    public function show(Shop $shop, Request $request, ShopMessage $shopMessage)
    {
        dd('show', $shop, $shopMessage);
        //return view('shop_message.show', compact('shopMessage'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\ShopMessage  $shopMessage
     * @return \Illuminate\Http\Response
     */
    public function edit(ShopMessage $shopMessage)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\ShopMessage  $shopMessage
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ShopMessage $shopMessage)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\ShopMessage  $shopMessage
     * @return \Illuminate\Http\Response
     */
    public function destroy(ShopMessage $shopMessage)
    {
        //
    }





}
