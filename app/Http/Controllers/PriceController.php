<?php

namespace App\Http\Controllers;

use App\Price;
use App\Supplier;
use Illuminate\Http\Request;


class PriceController extends Controller
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
        $suppliers = Supplier::orderBy('name', 'asc')->get();
        $actions = [
            'New Supplier Product',
            'Update Supplier Product',
            'New Market Product',
            'Update Market Product',
        ];

        $params = $request->all();
        if (!isset($params['order_by']) || $params['order_by'] == null) {
            $params['order_by'] = 'prices.created_at';
            $params['order'] = 'desc';
        }
        $order_params = array_merge($params, ['order' => ($params['order'] == 'asc') ? 'desc' : 'asc']);
        $prices = Price::filter($params)->paginate(25);

        return view('price.index', compact('suppliers', 'actions', 'prices', 'params', 'order_params'));
    }


    public function price()
    {
        return view('price.price');
    }

}
