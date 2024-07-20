<?php

namespace App\Http\Controllers;

use App\Market;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class MarketController extends Controller
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
    public function index()
    {
        $markets = Market::orderBy('name', 'asc')->paginate(25);

        return view('market.index', compact('markets'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        return view('market.create-edit');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'code'          => 'required|max:16',
            'name'          => 'required|max:64',
            'ws'            => 'nullable|max:64',
            'product_url'   => 'nullable|max:255',
            'order_url'     => 'nullable|max:255',

            'config'        => 'nullable',  // json
        ]);

        $validatedData['pn_required'] = request('pn_required') ? 1 : 0;
        $validatedData['ean_required'] = request('ean_required') ? 1 : 0;
        $validatedData['name_required'] = request('name_required') ? 1 : 0;
        $validatedData['market_category_required'] = request('market_category_required') ? 1 : 0;
        $validatedData['images_required'] = request('images_required') ? 1 : 0;
        $validatedData['attributes_required'] = request('attributes_required') ? 1 : 0;

        $market = Market::create($validatedData);
        if (!isset($market->config)) {
            $market->config = json_encode(('App\\Libraries\\'.$market->ws)::DEFAULT_CONFIG);
            $market->save();
        }

        return redirect()->route('markets.index')->with('status', 'Marketplace creado correctamente.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Market  $market
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(Market $market)
    {
        return $this->edit($market);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Market  $market
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Market $market)
    {
        return view('market.create-edit', compact('market'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Market  $market
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Market $market)
    {
        $validatedData = $request->validate([
            'code'          => 'required|max:16',
            'name'          => 'required|max:64',
            'ws'            => 'nullable|max:64',
            'product_url'   => 'nullable|max:255',
            'order_url'     => 'nullable|max:255',

            'config'        => 'nullable',  // json
        ]);

        $validatedData['pn_required'] = request('pn_required') ? 1 : 0;
        $validatedData['ean_required'] = request('ean_required') ? 1 : 0;
        $validatedData['name_required'] = request('name_required') ? 1 : 0;
        $validatedData['market_category_required'] = request('market_category_required') ? 1 : 0;
        $validatedData['images_required'] = request('images_required') ? 1 : 0;
        $validatedData['attributes_required'] = request('attributes_required') ? 1 : 0;

        Market::whereId($market->id)->update($validatedData);
        /* if (!isset($market->config)) {
            $market->config = json_encode(('App\\Libraries\\'.$market->ws)::DEFAULT_CONFIG);
            $market->save();
        } */

        return redirect()->route('markets.index')->with('status', 'Marketplace modificado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Market  $market
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Market $market)
    {
        try {
            $market->delete();
        } catch (QueryException $e) {
            return redirect()->route('markets.index')->with('status', $e->getMessage());
        }

        return redirect()->route('markets.index')->with('status', 'Marketplace eliminado.');
    }
}
