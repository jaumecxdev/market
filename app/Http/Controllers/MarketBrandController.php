<?php

namespace App\Http\Controllers;

use App\Brand;
use App\Libraries\MarketWS;
use App\Market;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarketBrandController extends Controller
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
    public function index(Request $request, Market $market)
    {
        if (!$market = Auth::user()->checkMarket($market))
            return redirect()->route('welcome')->withErrors('Hay un problema con la cuenta del usuario. Contacte con el administrador.');

        $params = $request->all();
        if (!isset($params['order_by']) || $params['order_by'] == null) {
            $params['order_by'] = 'brands.name';
            $params['order'] = 'asc';
        }
        $order_params = array_merge($params, ['order' => ($params['order'] == 'asc') ? 'desc' : 'asc']);
        $brands = Brand::has('products')->filter($params)->paginate(1000);

        return view('market_brand.index', compact('market', 'params', 'order_params', 'brands'));
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Market $market, Brand $brand)
    {
        return view('market_brand.edit', compact('market',  'brand'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Market $market, Brand $brand)
    {
        $validatedData = $request->validate([
            'market_brand_name' => 'nullable|max:255',
            'market_brand_id'   => 'nullable|exists:market_brands,id',
        ]);

        if (!$validatedData['market_brand_name']) $validatedData['market_brand_id'] = null;

        if ($brand->market_brand($market->id)->count())
            $brand->market_brands()->detach($brand->market_brand($market->id)->first());

        if (($validatedData['market_brand_id']) && ($validatedData['market_brand_id'] != null))
            $brand->market_brands()->attach($validatedData['market_brand_id']);

        return redirect()->route('markets.brands.index', [$market])->with('status', 'Mapping de Marca modificado correctamente.');
    }



    public function get(Market $market)
    {
        $status_response = null;
        $shops = $market->shops()->whereEnabled(1)->get();
        foreach ($shops as $shop) {
            $ws = MarketWS::getMarketWS($shop);
            if ($ws) {
                $response = $ws->getBrands();
                $status_response[] = $response;
                break;
            }
        }

        if ($response !== true) return redirect()->route('markets.brands.list', [$market])
            ->withErrors('Error descargando marcas del MP.<br>' .json_encode($response));

        return redirect()->route('markets.brands.list', [$market])
            ->with('status', 'Marcas descargadas.');
    }



    public function list(Market $market)
    {
        $market_brands = $market->market_brands()->orderBy('name')->get();

        return view('market_brand.list', compact('market', 'market_brands'));
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function auto(Market $market)
    {
        // if ($validatedData['market_category_id']) $category->market_categories()->attach($validatedData['market_category_id']);
        // id, category_id, supplier_id, market_category_id, supplierCategoryId, root_category_id, name, parent
        /* $categories = Category::all(); //market_categories()->where('market_id', $market->id) ::whereNull('market_category_id')->get();
        foreach ($categories as $category) {

            // If no current mapped
            if (!$category->market_category($market->id)->count()) {
                $market_category = MarketCategory::where('market_id', $market->id)
                    ->whereRaw("UPPER(`name`)='" .strtoupper($category->name). "'")->first();
                if ($market_category)
                    $category->market_categories()->attach($market_category);
            }
        }

        return redirect()->route('markets.categories.index', [$market])->with('status', 'Categorias mapeadas.'); */
    }
}
