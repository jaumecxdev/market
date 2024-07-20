<?php

namespace App\Http\Controllers;

use App\Libraries\MarketWS;
use App\Shop;
use App\ShopGroup;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShopGroupController extends Controller
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
    public function index(Shop $shop)
    {
        if (!$shop = Auth::user()->checkShop($shop))
            return redirect()->route('welcome')->withErrors('Hay un problema con la cuenta del usuario. Contacte con el administrador.');

        $shop_groups_count = $shop->shop_groups()->count();
        $shop_groups = $shop->shop_groups;
        $groups = $shop->groups;

        return view('shop_group.index', compact( 'shop', 'shop_groups_count', 'shop_groups', 'groups'));
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
            'group_id'              => 'required|exists:groups,id',
            'market_category_id'    => 'required|exists:market_categories,id',
        ]);

        // No repeats
        ShopGroup::updateOrCreate(
            [
                'shop_id'               => $shop->id,
                'group_id'              => $validatedData['group_id'],
                'market_category_id'    => $validatedData['market_category_id'],
            ], []
        );

        return redirect()->route('shops.shop_groups.index', [$shop])->with('status', 'Categoría añadida correctamente.');
    }



    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Shop $shop, ShopGroup $shop_group)
    {
        try {
            $shop_group->delete();
        } catch (QueryException $e) {
            return redirect()->route('shops.shop_groups.index', [$shop])->with('status', $e->getMessage());
        }

        return redirect()->route('shops.shop_groups.index', [$shop])->with('status', 'Categoría de grupo eliminada.');
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function get(Shop $shop)
    {
        $ws = MarketWS::getMarketWS($shop);
        if (!$ws)
            return redirect()->route('shops.shop_groups.index', [$shop])->with('status', 'No hay código.');

        $response = $ws->getGroups();
        return redirect()->route('shops.shop_groups.index', [$shop])->with('status', json_encode($response));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function post(Shop $shop)
    {
        if ($shop->market->code == 'ae') {
            $ws = MarketWS::getMarketWS($shop);
            $response = $ws->postGroups();

        }
        else
            return redirect()->route('shops.shop_groups.index', [$shop])->with('status', 'Este Marketplace no requiere subir Grupos.');

        return redirect()->route('shops.shop_groups.index', [$shop])->with('status', 'No hay código.');
    }


}
