<?php

namespace App\Http\Controllers;

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


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        /* $statuses = Status::select('statuses.*',
            DB::raw("CONCAT('(', markets.name, ') ', statuses.name) AS market_name"))
            ->leftJoin('markets', 'statuses.market_id', '=', 'markets.id')
            ->where('statuses.type', 'order')
            ->orderBy('statuses.name')
            ->get(); */

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

        $params = $request->all();
        if (!isset($params['order_by']) || $params['order_by'] == null) {
            $params['order_by'] = 'orders.updated_at';
            $params['order'] = 'desc';
        }
        $order_params = array_merge($params, ['order' => ($params['order'] == 'asc') ? 'desc' : 'asc']);

        $user = Auth::user();
        if ($user->hasRole('seller')) {
            $shops_id = $user->getShopsId();    // [9,13,14]
            $params['shops_id'] = $shops_id;
            $shops = Shop::filter([])->orderBy('markets.code')->orderBy('shops.code')->find($shops_id);
            //$shops = Shop::find($shops_id);
        }
        else
            $shops = Shop::filter([])->orderBy('markets.code')->orderBy('shops.code')->get();

        $orders = Order::filter($params)->paginate(500);

        return view('order.index', compact('params', 'orders', 'shops', 'statuses', 'order_params'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function create()
    {
        return redirect()->route('orders.index')->with('status', 'Aún no se pueden crear pedidos en la plataforma.');
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    // TODO: Edit order addresses & all
    public function store(Request $request)
    {
        return redirect()->route('orders.index')->with('status', 'Aún no se pueden crear pedidos en la plataforma.');
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Order  $order
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(Order $order)
    {
        return view('order.show', compact('order'));
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Order  $order
     * @return \Illuminate\Http\RedirectResponse
     */
    public function edit(Order $order)
    {
        return redirect()->route('orders.index')->with('status', 'Aún no se pueden modificar pedidos en la plataforma.');
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Order  $order
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Order $order)
    {
        return redirect()->route('orders.index')->with('status', 'Aún no se pueden modificar pedidos en la plataforma.');
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Order  $order
     * @return \Illuminate\Http\RedirectResponse
     * @throws
     */
    public function destroy(Order $order)
    {
        return redirect()->route('orders.index')->with('status', 'Aún no se pueden eliminar pedidos en la plataforma.');
    }


    public function send(Order $order)
    {
        // Send Order Item Notifications (Mail & Telegram)
        // SOME notifications FOR EVERY order_item

        $notified_type = 'REENVÍO ';
        $count = $order->sendNotifications($notified_type);

        return redirect()->route('orders.index')->with('status', 'Enviadas '.$count.' notificaciones.');

    /*     $count = 0;
        $shop_name = '(' .$order->market->name. ') ' .$order->shop->name;
        $suppliers = [];
        foreach ($order->order_items as $order_item) {
            if ($supplier = $order_item->product->supplier ?? null)
                $suppliers[$supplier] = true;
        }

        foreach ($order->order_items as $order_item) {

            $supplier = $order_item->product->supplier ?? null;
            if ($supplier) {
                foreach ($supplier->receivers as $receiver) {
                    if ($receiver->is_notificable()) {
                        $receiver->notified_type = $notified_type;
                        $notification_class = 'App\Notifications\\' .$receiver->class;

                        $guest_service = GuestService::firstWhere('supplier_id', $supplier->id);
                        $token = $guest_service ? $guest_service->token : '';
                        $url = route('guest_service.order.track', [$order, $order_item, 'token' => $token]);
                        if ($instance = new $notification_class($order_item, $url)) {
                            $receiver->notify($instance);
                            $count++;
                        }

                    }
                }
            }

            $admin_receivers = Receiver::whereNull('supplier_id')->get();
                if ($admin_receivers) {
                    foreach ($admin_receivers as $receiver) {
                        if ($receiver->is_notificable()) {
                            $receiver->notified_type = $notified_type;
                            Storage::append('notifications/orders_contoller.json', $receiver->toJson());
                            Storage::append('notifications/orders_contoller.json', json_encode([$shop_name => $order->marketOrderId]));
                            $notification_class = 'App\Notifications\\' .$receiver->class;

                            $guest_service = GuestService::firstWhere('supplier_id', null);
                            $token = $guest_service ? $guest_service->token : '';
                            $url = route('guest_service.order.track', [$order, $order_item, 'token' => $guest_service->token]);
                            if ($instance = new $notification_class($order_item, $url)) {
                                $receiver->notify($instance);
                                $count++;
                            }

                        }
                    }
                }
        }

        return redirect()->route('orders.index')->with('status', 'Enviadas '.$count.' notificaciones.'); */
    }


}
