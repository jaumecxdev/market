<?php

namespace App\Http\Controllers;

use App\Currency as AppCurrency;
use App\Order;
use App\OrderPayment;
use App\Shop;
use App\Status;
use Facades\App\Facades\MpeExcel as FacadesMpeExcel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;



class OrderPaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->input('action') == 'export')
            return $this->export($request);

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
            $params['order_by'] = 'orders.created_at';
            $params['order'] = 'desc';
        }
        $order_payment_params = array_merge($params, ['order' => ($params['order'] == 'asc') ? 'desc' : 'asc']);
        $shops = Shop::filter([])->orderBy('markets.code')->orderBy('shops.code')->get();
        $order_payments = OrderPayment::filter($params)->paginate(100);

        $total = [];
        $total['invoice_mpe_price'] = 0;
        $total['mp_bfit'] = 0;
        $total['cost'] = 0;
        foreach ($order_payments as $order_payment) {

            $total['invoice_mpe_price'] += $order_payment->invoice_mpe_price;
            $total['mp_bfit'] += $order_payment->mp_bfit;
            $total['cost'] += $order_payment->cost;
        }

        $total['cost'] *= 1.21;
        $total['cost'] = round($total['cost'], 2);

        return view('order_payment.index', compact('params', 'shops', 'statuses', 'order_payments', 'order_payment_params', 'total'));
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\OrderPayment  $orderPayment
     * @return \Illuminate\Http\Response
     */
    public function edit(OrderPayment $order_payment)
    {
        $currencies = AppCurrency::orderBy('name', 'asc')->get();

        return view('order_payment.create-edit', compact('order_payment', 'currencies'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\OrderPayment  $orderPayment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, OrderPayment $order_payment)
    {
        $validatedData = $request->validate([
            /* 'currency_id'   => 'required|exists:currencies,id', */

            'cost'          => 'nullable|numeric|gte:0',
            'price'         => 'nullable|numeric|gte:0',
            'shipping_price'=> 'nullable|numeric|gte:0',
            //'tax'           => 'required|numeric|gte:0',

            'bfit'          => 'nullable|numeric',
            'mps_bfit'      => 'nullable|numeric',
            'mp_bfit'       => 'nullable|numeric',

            'invoice'       => 'nullable|max:64',
            'payment_at'    => 'nullable|date_format:Y-m-d',

            'invoice_mpe'               => 'nullable|max:64',
            'invoice_mpe_price'         => 'nullable|numeric|gte:0',
            'invoice_mpe_created_at'    => 'nullable|date_format:Y-m-d',
        ]);

        $validatedData['fixed'] = $request->has('fixed') ? 1 : 0;
        $validatedData['charget'] = $request->has('charget') ? 1 : 0;
        $validatedData['cost'] = $validatedData['cost'] ?? 0;
        $validatedData['price'] = $validatedData['price'] ?? 0;
        $validatedData['shipping_price'] = $validatedData['shipping_price'] ?? 0;
        $validatedData['bfit'] = $validatedData['bfit'] ?? 0;
        $validatedData['mps_bfit'] = $validatedData['mps_bfit'] ?? 0;
        $validatedData['mp_bfit'] = $validatedData['mp_bfit'] ?? 0;

        OrderPayment::whereId($order_payment->id)->update($validatedData);

        return redirect()->route('order_payments.index')->with('status', 'Cobro modificado correctamente.');
    }


    public function get()
    {
        $errors = [];
        try {
            if (!$latest = OrderPayment::filter([])->latest('orders.created_at')->first()) $orders = Order::all();
            else $orders = Order::where('created_at', '>=', $latest->order->created_at->addDays(-200))->get();
            foreach ($orders as $order) {
                foreach ($order->order_items as $order_item) {

                    $order_payment = OrderPayment::whereOrderId($order->id)->firstWhere('order_item_id', $order_item->id);
                    if (!$order_payment || !$order_payment->fixed) {
                        // fixed, 'charget', 'invoice', 'payment_at', 'invoice_mpe, invoice_mpe_created_at
                        $order_payment = OrderPayment::updateOrCreate([
                            'order_id'          => $order->id,
                            'order_item_id'     => $order_item->id,
                        ],[
                           /*  'currency_id'       => $order_item->currency_id, */
                            'cost'              => $order_item->cost,
                            'price'             => $order_item->price,
                            'shipping_price'    => $order_item->shipping_price,
                            /* 'tax'               => $order_item->tax, */
                            'bfit'              => $order_item->bfit,
                            'mps_bfit'          => $order_item->mps_bfit,
                            'mp_bfit'           => $order_item->mp_bfit,
                        ]);

                        if ($order_payment->invoice_mpe_price == 0) {
                            $order_payment->invoice_mpe_price = $order->price + $order->shipping_price;
                            $order_payment->save();
                        }

                        /* 'order_id', 'order_item_id',
                        'fixed', 'cost', 'price', 'shipping_price', 'bfit', 'mps_bfit ', 'mp_bfit',
                        'charget', 'invoice', 'payment_at',
                        'invoice_mpe', 'invoice_mpe_price', 'invoice_mpe_created_at' */
                    }
                }
            }

        } catch (Throwable $th) {
            $errors[] = [$order_item ?? null, $order ?? null];
            return redirect()->route('order_payments.index')->withErrors('Errores al integrar los pedidos: '.json_encode($errors));
        }

        return redirect()->route('order_payments.index')->with('status', 'Pedidos integrados correctamente.');
    }


    public function export(Request $request)
    {
        try {
            $params = $request->all();
            $order_payments = OrderPayment::filter($params)->get();
            FacadesMpeExcel::download($order_payments);

        } catch (Throwable $th) {
            return redirect()->back()->withErrors($th)->withInput();
        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
            return redirect()->back()->withErrors($e)->withInput();
        }
    }


}
