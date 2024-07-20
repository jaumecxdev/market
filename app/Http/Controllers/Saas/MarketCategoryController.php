<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use App\Market;
use Illuminate\Http\Request;

class MarketCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


    public function index(Request $request, Market $market)
    {
        $market_categories = $market->market_categories()->orderBy('path')->orderBy('name')->get();

        return view('saas.market_category.index', compact('market', 'market_categories'));
    }

}
