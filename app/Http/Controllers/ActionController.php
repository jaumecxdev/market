<?php

namespace App\Http\Controllers;

use App\Market;
use App\Shop;
use App\Supplier;
use Illuminate\Support\Facades\Auth;


class ActionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


    public function suppliers()
    {
        $suppliers = Supplier::orderBy('name', 'asc')->get();

        return view('action.suppliers', compact('suppliers'));
    }


    public function markets()
    {
        $user = Auth::user();
        if ($user->hasRole('seller')) $markets = Market::find($user->getMarketsId());
        else $markets = Market::orderBy('name', 'asc')->get();

        return view('action.markets', compact('markets'));
    }


    public function shops()
    {
        $user = Auth::user();
        if ($user->hasRole('seller')) $shops = Shop::filter([])->orderBy('markets.code')->orderBy('shops.code')->find($user->getShopsId());
        else $shops = Shop::filter([])->orderBy('markets.code')->orderBy('shops.code')->get();

        return view('action.shops', compact('shops'));
    }


}
