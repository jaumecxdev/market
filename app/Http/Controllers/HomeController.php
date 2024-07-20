<?php

namespace App\Http\Controllers;

use App\Brand;
use App\Buyer;
use App\Category;
use App\Market;
use App\Order;
use App\Product;
use App\Shop;
use App\Supplier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Traits\HasRoles;

class HomeController extends Controller
{
    use HasRoles;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }


    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        if (Auth::user()->hasRole('seller'))
            return redirect()->route('action.shops');

        if (Auth::user()->hasRole('saas'))
            return redirect()->route('saas');


        $counts = null;
        $counts['products'] = Product::count();
        $counts['orders'] = Order::count();
        $counts['buyers'] = Buyer::count();
        //$counts['costs'] = Product::sum('cost');
        $counts['costs'] = Product::select(DB::raw('sum(products.cost * products.stock) as cost_stock'))
            ->value('cost_stock');

        $counts['suppliers'] = Supplier::count();
        $counts['markets'] = Market::count();
        $counts['shops'] = Shop::count();
        $counts['categories'] = Category::count();
        $counts['brands'] = Brand::count();

        $counts['orders_price'] = Order::sum('price');
        $counts['orders_price_months'] = Order::select(
            DB::raw('sum(price) as price'),
            DB::raw("DATE_FORMAT(created_at,'%M %Y') as month")
        )
            ->groupBy('month')
            ->get()->toArray();
        $counts['orders_price_markets'] = Order::select('markets.name as market_name',
            DB::raw('sum(price) as price')
        )
            ->leftjoin('markets', 'orders.market_id', '=', 'markets.id')
            ->groupBy('markets.id')
            ->get()->toArray();

        $test = 10000;

        return view('home', compact('counts', 'test'));
    }


    public function json()
    {
        $counts = null;
        $counts['orders_price_months'] = Order::select(
            DB::raw('sum(price) as price'),
            DB::raw("DATE_FORMAT(created_at,'%b %y') as month")
        )
            ->groupBy('month')
            ->get()->toArray();
        $counts['orders_price_markets'] = Order::select('markets.name as market_name',
            DB::raw('sum(price) as price')
        )
            ->leftjoin('markets', 'orders.market_id', '=', 'markets.id')
            ->groupBy('markets.id')
            ->get()->toArray();

        return json_encode($counts);
    }

}
