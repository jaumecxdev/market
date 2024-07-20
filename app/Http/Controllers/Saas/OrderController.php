<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use App\Order;
use App\Shop;
use App\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


    public function index(Request $request)
    {
        $user = Auth::user();
        $statuses = Status::select(
                'statuses.id',
                'statuses.market_id',
                DB::raw("CONCAT(statuses.id, ' (', markets.name, ') ', statuses.name) AS name")
            )
            ->leftJoin('markets', 'markets.id', '=', 'statuses.market_id')
            ->whereNotNull('market_id')
            ->where('statuses.type', 'order')
            ->orderBy('markets.name')
            ->get();

        $statuses = $statuses->whereIn('market_id', $user->getMarketsId());

        $params = $request->all();
        if (!isset($params['order_by']) || $params['order_by'] == null) {
            $params['order_by'] = 'orders.updated_at';
            $params['order'] = 'desc';
        }
        $order_params = array_merge($params, ['order' => ($params['order'] == 'asc') ? 'desc' : 'asc']);

        if ($user->hasRole('saas')) {
            $orders = Order::filter($params)->whereIn('shop_id', $user->getShopsId())->paginate(50);
            $shops = Shop::filter([])->orderBy('markets.code')->orderBy('shops.code')->find($user->getShopsId());
        }
        elseif ($user->hasRole('admin')) {
            $orders = Order::filter($params)->paginate(50);
            $shops = Shop::filter([])->orderBy('markets.code')->orderBy('shops.code')->get();
        }
        else
            return redirect()->route('/')->withErrors('Hay un problema con la cuenta de tu usuario, contacta el administrador.');

        return view('saas.order.index', compact('params', 'orders', 'shops', 'statuses', 'order_params'));
    }


    public function show(Order $order)
    {
        return view('saas.order.show', compact('order'));
    }

}
