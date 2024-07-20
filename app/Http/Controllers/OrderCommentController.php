<?php

namespace App\Http\Controllers;

use App\Libraries\MarketWS;
use App\Order;
use App\OrderComment;
use Illuminate\Http\Request;

class OrderCommentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Order $order)
    {


        //$order_items = $order->order_items;
        $order_comments = $order->order_comments;



        return view('order.comments', compact('order', 'order_comments'));
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
            'comment'           => 'required|max:255',
        ]);

        $validatedData['market_id'] = $order->market->id;
        $validatedData['shop_id'] = $order->shop->id;
        $validatedData['order_id'] = $order->id;

        $ws = MarketWS::getMarketWS($order->shop);
        if ($ws) {
            $res = $ws->postOrderComment($order, $validatedData);
            if ($res === true) {
                OrderComment::create($validatedData);
                return redirect()->route('orders.comments', [$order])->with('status', 'Comentario enviado correctamente.');
            }
            else
                return redirect()->route('orders.comments', [$order])
                    ->withErrors('Ha ocurrido un error y no se ha podido enviar el comentario. '.$res);
        }

        return redirect()->route('orders.comments', [$order])->withErrors('No hay código.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\OrderComment  $orderComment
     * @return \Illuminate\Http\Response
     */
    public function show(OrderComment $orderComment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\OrderComment  $orderComment
     * @return \Illuminate\Http\Response
     */
    public function edit(OrderComment $orderComment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\OrderComment  $orderComment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, OrderComment $orderComment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\OrderComment  $orderComment
     * @return \Illuminate\Http\Response
     */
    public function destroy(OrderComment $orderComment)
    {
        //
    }



    public function get(Order $order)
    {
        $ws = MarketWS::getMarketWS($order->shop);
        if ($ws) {
            $res = $ws->getOrderComments($order);
            return redirect()->route('orders.comments', [$order])->with('status', 'Resultado: '.$res);
        }

        return redirect()->route('orders.comments', [$order])
                    ->withErrors('Ha ocurrido un error y no se han podido descargar los comentarios.');
    }
}
