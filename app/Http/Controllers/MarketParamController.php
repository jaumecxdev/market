<?php

namespace App\Http\Controllers;

use App\Market;
use App\MarketParam;
use App\Traits\HelperTrait;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Throwable;

class MarketParamController extends Controller
{
    use HelperTrait;

    public function __construct()
    {
        $this->middleware('auth');
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Market $market)
    {
        $root_categories = $market->root_categories()->orderBy('name')->get();
        $market_params = $market->market_params()->select(
            'market_params.*',
            'market_categories.name as market_category_name',
            'market_categories.marketCategoryId as market_category_marketCategoryId',
            'root_categories.name as root_category_name',
            'root_categories.marketCategoryId as root_category_marketCategoryId',
            'brands.name as brand_name'
        )
        ->leftJoin('brands', 'brands.id', '=', 'market_params.brand_id')
        ->leftJoin('market_categories', 'market_categories.id', '=', 'market_params.market_category_id')
        ->leftJoin('root_categories', 'root_categories.id', '=', 'market_params.root_category_id')
        ->orderBy('market_categories.name')
        ->orderBy('root_categories.name')
        ->orderBy('brands.name')
        ->get();

        return view('market_param.index', compact('market', 'root_categories', 'market_params'));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, Market $market)
    {
        $validatedData = $request->validate([
            'brand_id'              => 'nullable|exists:brands,id',
            'root_category_id'      => 'nullable|exists:root_categories,id',
            'market_category_id'    => 'nullable|exists:market_categories,id',

            'fee'                   => 'nullable|numeric|gte:0',
            'fee_addon'             => 'nullable|numeric|gte:0',

            'lot'                   => 'nullable|numeric|gte:0',
            'lot_fee'               => 'nullable|numeric|gte:0',
            'bfit_min'              => 'nullable|numeric|gte:0',
        ]);

        if (!isset($request->brand_name)) $validatedData['brand_id'] = null;
        if (!isset($request->market_category_name)) $validatedData['market_category_id'] = null;
        $validatedData['fee'] = $validatedData['fee'] ?? 0;
        $validatedData['fee_addon'] = $validatedData['fee_addon'] ?? 0;

        $validatedData['lot'] = $validatedData['lot'] ?? 0;
        $validatedData['lot_fee'] = $validatedData['lot_fee'] ?? 0;
        $validatedData['bfit_min'] = $validatedData['bfit_min'] ?? 0;

        if (!$validatedData['fee'] && !$validatedData['fee_addon'] && !$validatedData['bfit_min'])
            return redirect()->route('markets.market_params.index', [$market])->withErrors(['No se ha añadido ninguna tarifa'])->withInput();

        if (($validatedData['lot'] && !$validatedData['lot_fee']) || (!$validatedData['lot'] && $validatedData['lot_fee']))
            return redirect()->route('markets.market_params.index', [$market])->withErrors(['Hay que rellenar el Tramo y su Tarifa'])->withInput();

        //  'root_category_id',
        MarketParam::updateOrCreate([
            'market_id'         => $market->id,
            'brand_id'          => $validatedData['brand_id'],
            'root_category_id'  => $validatedData['root_category_id'],
            'market_category_id'=> $validatedData['market_category_id'],
            'fee'               => $validatedData['fee'] ?? 0,
            'fee_addon'         => $validatedData['fee_addon'] ?? 0,

            'lot'               => $validatedData['lot'] ?? 0,
            'lot_fee'           => $validatedData['lot_fee'] ?? 0,
            'bfit_min'         => $validatedData['bfit_min'] ?? 0,
        ],[
        ]);

        return redirect()->route('markets.market_params.index', [$market])->with('status', 'Tarifa creado correctamente.');
    }



    public function edit(Market $market, MarketParam $market_param)
    {
        $root_categories = $market->root_categories()->orderBy('name')->get();
        $market_params = $market->market_params()->select(
            'market_params.*',
            'market_categories.name as market_category_name',
            'market_categories.marketCategoryId as market_category_marketCategoryId',
            'root_categories.name as root_category_name',
            'root_categories.marketCategoryId as root_category_marketCategoryId',
            'brands.name as brand_name'
        )
        ->leftJoin('brands', 'brands.id', '=', 'market_params.brand_id')
        ->leftJoin('market_categories', 'market_categories.id', '=', 'market_params.market_category_id')
        ->leftJoin('root_categories', 'root_categories.id', '=', 'market_params.root_category_id')
        ->orderBy('market_categories.name')
        ->orderBy('root_categories.name')
        ->orderBy('brands.name')
        ->get();

        return view('market_param.index', compact('market', 'market_param', 'root_categories', 'market_params'));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Product  $product
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Market $market, MarketParam $market_param)
    {
        $validatedData = $request->validate([
            'brand_id'              => 'nullable|exists:brands,id',
            'root_category_id'      => 'nullable|exists:root_categories,id',
            'market_category_id'    => 'nullable|exists:market_categories,id',

            'fee'                   => 'nullable|numeric|gte:0',
            'fee_addon'             => 'nullable|numeric|gte:0',

            'lot'                   => 'nullable|numeric|gte:0',
            'lot_fee'               => 'nullable|numeric|gte:0',
            'bfit_min'              => 'nullable|numeric|gte:0',
        ]);

        if (!isset($request->brand_name)) $validatedData['brand_id'] = null;
        if (!isset($request->market_category_name)) $validatedData['market_category_id'] = null;
        $validatedData['fee'] = $validatedData['fee'] ?? 0;
        $validatedData['fee_addon'] = $validatedData['fee_addon'] ?? 0;

        $validatedData['lot'] = $validatedData['lot'] ?? 0;
        $validatedData['lot_fee'] = $validatedData['lot_fee'] ?? 0;
        $validatedData['bfit_min'] = $validatedData['bfit_min'] ?? 0;

        if (!$validatedData['fee'] && !$validatedData['fee_addon'] && !$validatedData['bfit_min'])
            return redirect()->route('markets.market_params.index', [$market])->withErrors(['No se ha añadido ninguna tarifa'])->withInput();

            if (($validatedData['lot'] && !$validatedData['lot_fee']) || (!$validatedData['lot'] && $validatedData['lot_fee']))
            return redirect()->route('markets.market_params.index', [$market])->withErrors(['Hay que rellenar el Tramo y su Tarifa'])->withInput();

        $validatedData['market_id'] = $market->id;
        unset($validatedData['brand_name']);
        unset($validatedData['market_category_name']);

        MarketParam::whereId($market_param->id)->update($validatedData);

        return redirect()->route('markets.market_params.index', [$market])->with('status', 'Tarifa modificada correctamente.');
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\MarketParam  $marketParam
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Market $market, MarketParam $marketParam)
    {
        try {
            $marketParam->delete();
        } catch (QueryException $e) {
            return redirect()->route('markets.market_params.index', [$market])->withErrors($e)->withInput();
        }

        return redirect()->route('markets.market_params.index', [$market])->with('status', 'Tarifa eliminada.');
    }


    public function sync(Market $market)
    {
        try {
            $msg = $market->syncParams();

            return redirect()->route('markets.market_params.index', [$market])
                ->with('status', $msg);

        } catch (Throwable $th) {
            return $this->backWithErrors($th, __METHOD__, [$market, $msg]);
        }
    }


}
