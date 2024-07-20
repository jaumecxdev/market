<?php

namespace App\Http\Controllers;

use App\Order;
use App\Libraries\MarketWS;
use App\OrderShipment;
use Illuminate\Http\Request;

class OrderShipmentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Order $order)
    {
        $order_items = $order->order_items;
        $order_shipments = $order->order_shipments;
        $market_carriers = $order->market->market_carriers()->orderBy('name', 'asc')->get();

        return view('order.track', compact('order', 'order_items', 'order_shipments', 'market_carriers'));
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
    public function store(Request $request, Order $order)
    {
        $validatedData = $request->validate([
            'market_carrier_id' => 'nullable|exists:market_carriers,id',
            'tracking'          => 'nullable|max:255',
            'desc'              => 'nullable|max:255',

            'order_item_id'     => 'nullable|exists:order_items,id',
            'quantity'          => 'nullable|numeric|gte:0',
        ]);

        $validatedData['full'] = $request->has('full') ? 1 : 0;
        $validatedData['market_id'] = $order->market->id;
        $validatedData['shop_id'] = $order->shop->id;
        $validatedData['order_id'] = $order->id;
        $validatedData['quantity'] = $validatedData['quantity'] ?? 0;

        $ws = MarketWS::getMarketWS($order->shop);
        if ($ws) {
            $res = $ws->postOrderTrackings($order, $validatedData);
            if ($res === true) {
                OrderShipment::create($validatedData);
                return redirect()->route('orders.shipments', [$order])->with('status', 'Cambiado el estado del pedido correctamente.');
            }
            else
                return redirect()->route('orders.shipments', [$order])
                    ->withErrors('Ha ocurrido un error y no se ha podido cambiar el estado del pedido. '.$res);
        }

        return redirect()->route('orders.shipments', [$order])->withErrors('No hay código.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\OrderShipment  $shipment
     * @return \Illuminate\Http\Response
     */
    public function show(OrderShipment $order_shipment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\OrderShipment  $shipment
     * @return \Illuminate\Http\Response
     */
    public function edit(Order $order, OrderShipment $order_shipment)
    {
        $order_items = $order->order_items;
        $order_shipments = $order->order_shipments;
        $market_carriers = $order->market->market_carriers;

        return view('order.track', compact('order', 'order_shipment', 'order_items', 'order_shipments', 'market_carriers'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\OrderShipment  $order_shipment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Order $order, OrderShipment $order_shipment)
    {
        $validatedData = $request->validate([
            'market_carrier_id' => 'nullable|exists:market_carriers,id',
            'order_item_id'     => 'nullable|exists:order_items,id',
            'tracking'          => 'required|max:255',
            'desc'              => 'nullable|max:255',
            'quantity'          => 'nullable|numeric|gte:0',
        ]);

        $validatedData['full'] = $request->has('full') ? 1 : 0;
        $validatedData['market_id'] = $order->market->id;
        $validatedData['shop_id'] = $order->shop->id;
        $validatedData['order_id'] = $order->id;

        $ws = MarketWS::getMarketWS($order->shop);
        if ($ws) {
            $res = $ws->postOrderTrackings($order, $validatedData);
            if ($res === true) {
                OrderShipment::whereId($order_shipment->id)->update($validatedData);
                return redirect()->route('orders.shipments', [$order])->with('status', 'Tracking modificado correctamente.');
            }
        }

        return redirect()->route('orders.shipments', [$order])
                    ->withErrors('Ha ocurrido un error y no se ha podido modificar el Tracking. '.$res ?? null);
    }


    public function getCarriers(Order $order)
    {
        $ws = MarketWS::getMarketWS($order->shop);
        if ($ws) {
            $res = $ws->getCarriers();
            if ($res === true)
                return redirect()->route('orders.shipments', [$order])->with('status', 'Transportes descargados correctamente.');
        }

        return redirect()->route('orders.shipments', [$order])
                    ->withErrors('Ha ocurrido un error y no se han podido descargar los transportes. '.$res ?? null);
    }
}
