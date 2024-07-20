<?php

namespace App\Http\Controllers;

use App\Category;
use App\Libraries\MarketWS;
use App\Market;
use App\MarketCategory;
use App\RootCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarketCategoryController extends Controller
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
            $params['order_by'] = 'categories.name';
            $params['order'] = 'asc';
        }
        $order_params = array_merge($params, ['order' => ($params['order'] == 'asc') ? 'desc' : 'asc']);
        //$categories = Category::has('supplier_categories')->filter($params)->paginate(50);
        $categories = Category::has('products')->filter($params)->paginate(300);

        return view('market_category.index', compact('market', 'params', 'order_params', 'categories'));
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Market $market, Category $category)
    {
        return view('market_category.edit', compact('market',  'category'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Market $market, Category $category)
    {
        $validatedData = $request->validate([
            'market_category_name' => 'nullable|max:255',
            'market_category_id'   => 'nullable|exists:market_categories,id',
        ]);

        if (!$validatedData['market_category_name']) $validatedData['market_category_id'] = null;

        if ($category->market_category($market->id)->count())
            $category->market_categories()->detach($category->market_category($market->id)->first());

        if (($validatedData['market_category_id']) && ($validatedData['market_category_id'] != null))
            $category->market_categories()->attach($validatedData['market_category_id']);

        return redirect()->route('markets.categories.index', [$market])->with('status', 'Mapping de Categoria modificado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function get(Market $market)
    {
        $root_categories = RootCategory::where('market_id', $market->id)->get() ?? null;

        return view('market_category.roots', compact('market',  'root_categories'));
    }


    public function getRoot(Request $request, Market $market)
    {
        $status_response = null;
        $marketCategoryId = $request->get('marketCategoryId');
        $shops = $market->shops()->whereEnabled(1)->get();
        foreach ($shops as $shop) {
            if ($ws = MarketWS::getMarketWS($shop)) {
                $response = $ws->getCategories($marketCategoryId);
                $status_response[] = $response;
                break;
            }
        }

        return redirect()->route('markets.categories.list', [$market])
            ->with('status', 'Categoria descargada ('.$marketCategoryId.')<br>'.json_encode($status_response));
    }



    public function list(Market $market)
    {
        $market_categories = $market->market_categories()->orderBy('path')->orderBy('name')->get();

        return view('market_category.list', compact('market', 'market_categories'));
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
        $categories = Category::all(); //market_categories()->where('market_id', $market->id) ::whereNull('market_category_id')->get();
        foreach ($categories as $category) {

            // If no current mapped
            if (!$category->market_category($market->id)->count()) {
                $market_category = MarketCategory::where('market_id', $market->id)
                    ->whereRaw("UPPER(`name`)='" .strtoupper($category->name). "'")->first();
                if ($market_category)
                    $category->market_categories()->attach($market_category);
            }
        }

        return redirect()->route('markets.categories.index', [$market])->with('status', 'Categorias mapeadas.');
    }
}
