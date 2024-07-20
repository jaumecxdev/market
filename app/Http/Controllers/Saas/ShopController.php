<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use App\Market;
use App\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ShopController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }


    public function index()
    {
        $user = Auth::user();
        if ($user->hasRole('saas'))
            $shops = Shop::filter([])->orderBy('markets.code')->orderBy('shops.code')->find($user->getShopsId());
        elseif ($user->hasRole('admin'))
            $shops = Shop::filter([])->orderBy('markets.code')->orderBy('shops.code')->get();
        else
            return redirect()->route('/')->withErrors('Hay un problema con la cuenta de tu usuario, contacta el administrador.');

        return view('saas.shop.index', compact('shops'));
    }


    public function create()
    {
        $markets = Market::whereIn('code', ['worten', 'carrefour', 'pccompo', 'perfumes'])->get();
        $config = [
            'sku_type'      => 'id_pn_ean',
            'sku_types'     => ['id_pn_ean', 'ean', 'pn', 'sku_prov'],
            'tax_rate'      => 21,
            'offer_desc'    => 'Producto 100% nuevo, a estrenar, con entrega en domicilio a pie de calle.',
            'state_codes'   => (object) ['New' => 10, 'Used' => '', 'Refurbished' => ''],
            'reprice'       => false,
            'all_categories_are_root'   => false,
            'only_stocks'   => false,
            'csv'           => false
        ];

        return view('saas.shop.create-edit', compact('markets', 'config'));
    }


    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'market_id'     => 'required|exists:markets,id',
            'name'          => 'required|max:64',
            'marketShopId'  => 'nullable|max:64',
            'token'         => 'nullable',
            'preparation'   => 'nullable|max:64',
            'shipping'      => 'nullable|max:64',
            'sku_type'      => 'nullable|max:64',
            'locale'        => 'nullable|max:64',
            'channel'       => 'nullable|max:64',
            'tax_rate'      => 'nullable|max:64',
            'offer_desc'    => 'nullable|max:256',

            'status_new'    => 'nullable|max:64',
            'status_used'   => 'nullable|max:64',
            'status_refurbished' => 'nullable|max:64',
        ]);

        try {
            $validatedData['code'] = mb_strtolower(str_replace([' ', '\\', '::'], ['', '_','__'], $validatedData['name']));
            if (Shop::whereCode($validatedData['code'])->count())
                return redirect()->route('saas.shops.create')->withErrors ('Esta tienda ya existe, escoge otro nombre.');

            $validatedData['enabled'] = $request->has('enabled') ? 1 : 0;
            $validatedData['endpoint'] = Shop::whereMarketId($validatedData['market_id'])->first()->endpoint;

            $shop = Shop::create($validatedData);

            $config = [
                'sku_type'      => $validatedData['sku_type'],
                'tax_rate'      => $validatedData['tax_rate'],
                'offer_desc'    => $validatedData['offer_desc'],
                'state_codes'   => (object) [
                        'New' => $validatedData['status_new'],
                        'Used' => $validatedData['status_used'],
                        'Refurbished' => $validatedData['status_refurbished']
                ],
                'reprice'       => $request->has('reprice') ? true : false,
                'all_categories_are_root'   => $request->has('all_categories_are_root') ? true : false,
                'only_stocks'   => ($request->input('only_stocks') == 'only_stocks') ? true : false,
                'csv'           => $request->has('csv') ? true : false
            ];

            $shop->config = json_encode(array_replace(('App\\Libraries\\'.$shop->market->ws)::DEFAULT_CONFIG, $config));
            $shop->save();

            return redirect()->route('saas.shops')->with('status', 'Tienda creada correctamente.');

        } catch (Throwable $th) {
            Storage::append('errors/' .date('y-m-d_H'). '_shop_store.json', json_encode([$th->getMessage(), $shop->toArray(), $request->toArray()]));
            return redirect()->route('saas.shops.edit', [$shop])->withErrors ('Los datos introducidos no son correctos.');
        }

    }


    public function show(Shop $shop)
    {
        return $this->edit($shop);
    }


    public function edit(Shop $shop)
    {
        $markets = Market::whereIn('code', ['worten', 'carrefour', 'pccompo', 'perfumes'])->get();
        $config = [
            'sku_type'      => 'id_pn_ean',
            'sku_types'     => ['id_pn_ean', 'ean', 'pn', 'sku_prov'],
            'tax_rate'      => 21,
            'offer_desc'    => 'Producto 100% nuevo, a estrenar, con entrega en domicilio a pie de calle.',
            'state_codes'   => (object) ['New' => 10, 'Used' => '', 'Refurbished' => ''],
            'reprice'       => false,
            'all_categories_are_root'   => false,
            'only_stocks'   => false,
            'csv'           => false
        ];

        if ($config_json = json_decode($shop->config)) {
            if (isset($config_json->sku_type))
               $config['sku_type'] = $config_json->sku_type;

            if (isset($config_json->tax_rate))
                $config['tax_rate'] = $config_json->tax_rate;

            if (isset($config_json->offer_desc))
                $config['offer_desc'] = $config_json->offer_desc;

            if (isset($config_json->state_codes))
                $config['state_codes'] = $config_json->state_codes;

            if (isset($config_json->reprice))
                $config['reprice'] = $config_json->reprice;

            if (isset($config_json->all_categories_are_root))
                $config['all_categories_are_root'] = $config_json->all_categories_are_root;

            if (isset($config_json->only_stocks))
                $config['only_stocks'] = $config_json->only_stocks;

            if (isset($config_json->csv))
                $config['csv'] = $config_json->csv;

           /*
           if (isset($config_json->offer_desc_used))
               $this->offer_desc_used = $config_json->offer_desc_used;

           if (isset($config_json->offer_desc_refurbished))
               $this->offer_desc_refurbished = $config_json->offer_desc_refurbished;

            */
       }

        return view('saas.shop.create-edit', compact('shop', 'markets', 'config'));
    }


    public function update(Request $request, Shop $shop)
    {
        $validatedData = $request->validate([
            'market_id'     => 'required|exists:markets,id',
            'name'          => 'required|max:64',
            'marketShopId'  => 'nullable|max:64',
            'token'         => 'nullable',
            'preparation'   => 'nullable|max:64',
            'shipping'      => 'nullable|max:64',
            'sku_type'      => 'nullable|max:64',
            'locale'        => 'nullable|max:64',
            'channel'       => 'nullable|max:64',
            'tax_rate'      => 'nullable|max:64',
            'offer_desc'    => 'nullable|max:256',

            'status_new'    => 'nullable|max:64',
            'status_used'   => 'nullable|max:64',
            'status_refurbished' => 'nullable|max:64',
        ]);

        try {
            $config = [
                'sku_type'      => $validatedData['sku_type'],
                'tax_rate'      => $validatedData['tax_rate'],
                'offer_desc'    => $validatedData['offer_desc'],
                'state_codes'   => (object) [
                        'New' => $validatedData['status_new'],
                        'Used' => $validatedData['status_used'],
                        'Refurbished' => $validatedData['status_refurbished']
                ],
                'reprice'       => $request->has('reprice') ? true : false,
                'all_categories_are_root'   => $request->has('all_categories_are_root') ? true : false,
                'only_stocks'   => ($request->input('only_stocks') == 'only_stocks') ? true : false,
                'csv'           => $request->has('csv') ? true : false
            ];

            if (!isset($shop->config))
                $shop_config = ('App\\Libraries\\'.$shop->market->ws)::DEFAULT_CONFIG;
            else
                $shop_config = json_decode($shop->config, true);

            $validatedData['config'] = json_encode(array_replace($shop_config, $config));
            $validatedData['enabled'] = $request->has('enabled') ? 1 : 0;
            $validatedData['endpoint'] = Shop::whereMarketId($validatedData['market_id'])->first()->endpoint;
            unset($validatedData['sku_type']);
            unset($validatedData['tax_rate']);
            unset($validatedData['offer_desc']);
            unset($validatedData['status_new']);
            unset($validatedData['status_used']);
            unset($validatedData['status_refurbished']);
            unset($validatedData['enabled']);

            Shop::whereId($shop->id)->update($validatedData);

            return redirect()->route('saas.shops')->with('status', 'Tienda modificada correctamente.');

        } catch (Throwable $th) {
            Storage::append('errors/' .date('y-m-d_H'). '_shop_update.json', json_encode([$th->getMessage(), $shop->toArray(), $request->toArray()]));
            return redirect()->route('saas.shops.edit', [$shop])->withErrors ('Los datos introducidos no son correctos.');
        }
    }


}
