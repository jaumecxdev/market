<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use App\Market;
use App\Order;
use App\Product;
use App\Shop;
use App\Supplier;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Traits\HasRoles;

class HomeController extends Controller
{
    use HasRoles;

    public function __construct()
    {
        $this->middleware('auth');
    }


    public function index()
    {
        $user = Auth::user();
        $name = $user->name;
        $roles = $user->roles;

        if ($user->hasRole('saas')) {
            $products_count = Product::whereIn('supplier_id', $user->getSuppliersId())->count();
            $orders_count = Order::whereIn('shop_id', $user->getShopsId())->count();
            $suppliers_count = Supplier::find($user->getSuppliersId())->count();
            $markets_count = Market::find($user->getMarketsId())->count();
            $shops_count = Shop::find($user->getShopsId())->count();
        }
        elseif ($user->hasRole('admin')) {
            $products_count = Product::count();
            $orders_count = Order::count();
            $suppliers_count = Supplier::count();
            $markets_count = Market::count();
            $shops_count = Shop::count();
        }
        else
            return redirect()->route('login')->withErrors('Hay un problema con la cuenta de tu usuario, contacta el administrador.');

        return view('saas.home', compact('name', 'roles', 'shops_count', 'markets_count', 'suppliers_count', 'orders_count', 'products_count'));
    }




}
