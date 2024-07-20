<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Guest\GuestService;
use App\Libraries\MarketWS;
use App\Order;
use App\OrderItem;
use App\OrderShipment;
use App\Traits\HelperTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;


class GuestServiceController extends Controller
{
    use HelperTrait;

    public function __construct()
    {
        $this->middleware('GuestServiceAuth');      //->only(['token']);
    }


    public function orderTrack(Request $request, Order $order)
    {
        try {
            if ($token = $request->input('token')) {
                if ($guest_service = GuestService::firstWhere('token', $token)) {
                    if (!isset($guest_service->supplier_id))
                        $order_items = $order->order_items;
                    else
                        $order_items = $order->order_items()
                            ->select('order_items.*')
                            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
                            ->leftJoin('suppliers', 'suppliers.id', '=', 'products.supplier_id')
                            ->where('suppliers.id', $guest_service->supplier_id)
                            ->get();

                    $order_shipments = $order->order_shipments;
                    $market_carriers = $order->market->market_carriers()->orderBy('name', 'asc')->get();

                    if (!$order_items->count()) {
                        $this->msgAndStorage(__METHOD__, 'Pedido sin líneas o con proveedor diferente',
                            ['Un usuario Guest ha intentado añadir Tracking y no hay Líneas. ¿Cambio de proveedor?',
                            $request, $order, $guest_service, $order_items, $order_shipments, $market_carriers]);
                        return response('Ha ocurrido un error. Consulte con el soporte técnico. info@mpespecialist.com', 400)->header('Content-Type', 'text/plain');
                    }

                    return view('guest.order.track', compact('token', 'order', 'order_items', 'order_shipments', 'market_carriers'));
                }
            }

            $this->nullAndStorage(__METHOD__, [$request, $order]);
            return response('Unauthorized', 401)->header('Content-Type', 'text/plain');

        } catch (Throwable $th) {
            $this->nullWithErrors($th, __METHOD__, [$request, $order]);
            return response('Ha ocurrido un error. Consulte con el soporte técnico. info@mpespecialist.com', 400)->header('Content-Type', 'text/plain');
        }
    }


    public function orderTrackStore(Request $request, Order $order)
    {
        try {
            $validatedData = $request->validate([
                'order_item_id'     => 'nullable|exists:order_items,id',
                'market_carrier_id' => 'required|exists:market_carriers,id',
                'quantity'          => 'required|numeric|gte:0',
                'tracking'          => 'required|max:255',
            ]);

            $validatedData['desc'] = '';
            $validatedData['full'] = $request->has('full') ? 1 : 0;

            $validatedData['market_id'] = $order->market->id;
            $validatedData['shop_id'] = $order->shop->id;
            $validatedData['order_id'] = $order->id;
            //$validatedData['order_item_id'] = $order_item->id;
            $validatedData['quantity'] = $validatedData['quantity'] ?? 0;

            if ($ws = MarketWS::getMarketWS($order->shop)) {
                $res = $ws->postOrderTrackings($order, $validatedData);
                if ($res === true) {
                    OrderShipment::create($validatedData);
                    return back()->with('status', 'Pedido tramitado correctamente.');
                }
                else {
                    $this->nullAndStorage(__METHOD__, [$res, $request, $order]);
                    return response('Ha ocurrido un error. Consulte con el soporte técnico. info@mpespecialist.com', 400)->header('Content-Type', 'text/plain');
                }
            }

            $this->nullAndStorage(__METHOD__, ['No se ha encontrado el Webservice.', $request, $order]);
            return response('Ha ocurrido un error. Consulte con el soporte técnico. info@mpespecialist.com', 400)->header('Content-Type', 'text/plain');

        } catch (Throwable $th) {
            $this->nullWithErrors($th, __METHOD__, [$request, $order]);
            return response('Ha ocurrido un error. Consulte con el soporte técnico. info@mpespecialist.com', 400)->header('Content-Type', 'text/plain');
        }
    }

}
