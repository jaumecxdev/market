<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use App\Market;
use Illuminate\Support\Facades\Auth;

class MarketController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }


    public function index()
    {
        $user = Auth::user();
        if ($user->hasRole('saas'))
            $markets = Market::find($user->getMarketsId());
        elseif ($user->hasRole('admin'))
            $markets = Market::orderBy('name', 'asc')->get();
        else
            return redirect()->route('/')->withErrors('Hay un problema con la cuenta de tu usuario, contacta el administrador.');

        return view('saas.market.index', compact('markets'));
    }

}
