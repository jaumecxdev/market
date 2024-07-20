<?php

namespace App\Http\Controllers;

use App\Attribute;
use App\AttributeMarketAttribute;
use App\Libraries\MarketWS;
use App\Market;
use App\MarketCategory;
use App\Product;
use App\ProductAttribute;
use App\Property;
use App\RootCategory;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MarketPropertyController extends Controller
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
        $params = $request->all();
        $properties = Property::filter($market, $params)->paginate(50);

        return view('market_property.index', compact('market', 'params', 'properties'));
    }



    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Market $market, Property $property)
    {
        $property = Property::select('properties.*',
            DB::raw("CONCAT('(', properties.id, ') ', properties.name) AS property_id_name"),
            DB::raw("CONCAT('(', market_attributes.id, ') ', market_attributes.name) AS market_attribute_id_name"),
            DB::raw("CONCAT('(', market_categories.id, ') ', market_categories.name) AS market_category_id_name"),
            DB::raw("CONCAT('(', market_categories.id, ' ', market_categories.name, ') ', market_attributes.id, ' ', market_attributes.name) AS market_category_attribute_name"))
            ->leftJoin('market_attributes', 'properties.market_attribute_id', '=', 'market_attributes.id')
            ->leftJoin('market_categories', 'market_attributes.market_category_id', '=', 'market_categories.id')
            ->where('properties.id', $property->id)
            ->first();

        $attribute_market_attributes = AttributeMarketAttribute::select('attribute_market_attributes.*',
            'categories.name as category_name', 'attributes.name as attribute_name',
            DB::raw("CONCAT('(',categories.name,') ',attributes.name) AS category_attribute_name"))
            ->leftjoin('properties', 'attribute_market_attributes.property_id', '=', 'properties.id')
            ->leftjoin('attributes', 'attribute_market_attributes.attribute_id', '=', 'attributes.id')
            ->leftjoin('categories', 'attributes.category_id', '=', 'categories.id')
            ->where('attribute_market_attributes.market_attribute_id', $property->market_attribute_id)
            ->where('properties.id', $property->id)
            ->get();

        $product_attributes_example = [];
        if (isset($property->market_attribute->market_category)) {
            $categories = $property->market_attribute->market_category->categories;
            $product = Product::whereIn('category_id', $categories->pluck('id')->toArray())->has('product_attributes')->latest()->first();
            if ($product)
                $product_attributes_example = ProductAttribute::select('product_attributes.*',
                    'attributes.name as attribute_name')
                    ->leftJoin('attributes', 'product_attributes.attribute_id', '=', 'attributes.id')
                    ->where('product_attributes.product_id', $product->id)
                    ->get();
        }
        else
            return redirect()->route('markets.properties.edit', [$market, $property])->with('status', 'Este Marketplace no requiere hacer ningún Mapping.');

        return view('market_property.edit', compact('market',  'property',
            'attribute_market_attributes', 'product_attributes_example'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Market $market, Property $property)
    {
        // 'market_id', 'attribute_id', 'market_attribute_id', 'property_id', 'field', 'fixed', 'fixed_value', 'pattern', 'mapping',
        //        'if_exists', 'if_exists_value'
        $validatedData = $request->validate([
            'attribute_id'      => 'nullable|exists:attributes,id',
            'field'             => 'nullable|max:64',
            'fixed'             => 'nullable',
            'fixed_value'       => 'nullable|max:255',
            'pattern'           => 'nullable|max:255',
            'mapping'           => 'nullable|max:255',
            'if_exists'         => 'nullable|max:255',
            'if_exists_value'   => 'nullable|max:255',

        ]);

        AttributeMarketAttribute::create([
            'market_id'             => $market->id,
            'attribute_id'          => $validatedData['attribute_id'],
            'market_attribute_id'   => $property->market_attribute->id,
            'property_id'           => $property->id,
            'field'                 => $validatedData['field'],

            'fixed'                 => isset($validatedData['fixed']) ? 1 : 0,
            'fixed_value'           => $validatedData['fixed_value'],
            'pattern'               => $validatedData['pattern'],
            'mapping'               => $validatedData['mapping'],
            'if_exists'             => $validatedData['if_exists'],
            'if_exists_value'       => $validatedData['if_exists_value'],
        ]);

        /*AttributeMarketAttribute::updateOrCreate([
            'market_id'             => $market->id,
            'attribute_id'          => $validatedData['attribute_id'],
            'market_attribute_id'   => $property->market_attribute->id,
            'property_id'           => $property->id,
        ],[
            'fixed'                 => isset($validatedData['fixed']) ? 1 : 0,
            'fixed_value'           => $validatedData['fixed_value'],
            'pattern'               => $validatedData['pattern'],
            'mapping'               => $validatedData['mapping'],
            'if_exists'             => $validatedData['if_exists'],
            'if_exists_value'       => $validatedData['if_exists_value'],
        ]);*/

        return redirect()->route('markets.properties.edit', [$market, $property])->with('status', 'Mapping creado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Market $market, Property $property)
    {
        try {
            $market_attribute = $property->market_attribute;

            $property->attribute_market_attributes()->delete();
            $property->property_values()->delete();
            $property->delete();

            // If This Market Attribute NO have any other Property, Delete it
            if (!$market_attribute->properties()->count())
                $market_attribute->delete();

        } catch (QueryException $e) {
            return redirect()->route('markets.properties.index', [$market])->with('status', $e->getMessage());
        }

        return redirect()->route('markets.properties.index', [$market])->with('status', 'Property y Attributo eliminados correctamente.');
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function get(Market $market)
    {
        /*
        $shops = $market->shops()->whereEnabled(1)->get();
        foreach ($shops as $shop) {
            $ws = MarketWS::getMarketWS($shop);
            if ($ws)
                $response = $ws->getAttributes();
        }
        return redirect()->route('markets.properties.index', [$market])->with('status', 'Attributos descargados.');*/

        $root_categories = RootCategory::where('market_id', $market->id)->get() ?? null;
        $shops = $market->shops()->filter([])->orderBy('markets.code')->orderBy('shops.code')->whereEnabled(1)->get();

        return view('market_property.roots', compact('market',  'root_categories', 'shops'));
    }


    public function getRoot(Request $request, Market $market)
    {
        if ($rootMarketCategoryId = $request->get('marketCategoryId')) {
            $root_category = RootCategory::where('marketCategoryId', $rootMarketCategoryId)->first();
            $market_categories = $market->market_categories()->whereRootCategoryId($root_category->id);
        }
        else
            $market_categories = $market->market_categories();

        if ($request->get('shop_id')) $shops = [$market->shops()->find($request->get('shop_id'))];
        else $shops = $market->shops()->whereEnabled(1)->get();
        foreach ($shops as $shop) {
            if ($ws = MarketWS::getMarketWS($shop)) {
                $market_categories = $market_categories->whereHas('categories')->get();
                $response = $ws->getAttributes($market_categories);
                break;
            }
        }

        return redirect()->route('markets.properties.get', [$market])
            ->with('status', 'Atributos descargados ('.json_encode($market_categories->pluck('marketCategoryId')->all()).')<br>'.json_encode($response));
    }



    public function auto(Market $market)
    {
        // Get all NO MAPPED market attributes
        $market_attributes = $market->market_attributes()->doesntHave('attribute_market_attributes')->get();
        foreach ($market_attributes as $market_attribute) {

            // Get 1st market attribute MAPPED as same name
            // Is possible than this mapped attribute no match category
            $mapped_market_attribute = $market->market_attributes()
                ->where('name', $market_attribute->name)
                ->has('attribute_market_attributes')
                ->first();

            // Other market attribute with same name has mapped
            if ($mapped_market_attribute) {

                $properties = $market_attribute->properties;
                foreach ($properties as $property) {

                    // Fixed
                    $mapped_attribute_market_attribute = $mapped_market_attribute->attribute_market_attributes->first();
                    if ($mapped_attribute_market_attribute->attribute_id == null) {
                        /*$property = Property::where('market_attribute_id', $market_attribute->id)
                            ->where('name', $mapped_attribute_market_attribute->property->name)->first();*/

                        // id, market_id, attribute_id, market_attribute_id, property_id, fixed, fixed_value, pattern, mapping, if_exists, if_exists_value

                        $attribute_market_attribute = AttributeMarketAttribute::updateOrCreate([
                            'market_id' => $market->id,
                            'attribute_id' => null,
                            'market_attribute_id' => $market_attribute->id,
                            'property_id' => $property->id,
                        ], [
                            'field' => $mapped_attribute_market_attribute->field,
                            'fixed' => $mapped_attribute_market_attribute->fixed,
                            'fixed_value' => $mapped_attribute_market_attribute->fixed_value,
                            'pattern' => $mapped_attribute_market_attribute->pattern,
                            'mapping' => $mapped_attribute_market_attribute->mapping,
                            'if_exists' => $mapped_attribute_market_attribute->if_exists,
                            'if_exists_value' => $mapped_attribute_market_attribute->if_exists_value,
                        ]);

                        Storage::append('mp/ae/mapping/attributes_product.json', json_encode(MarketCategory::findOrFail($market_attribute->market_category_id)->first()));
                        Storage::append('mp/ae/mapping/attributes_product.json', json_encode($market_attribute));
                        Storage::append('mp/ae/mapping/attributes_product.json', json_encode($attribute_market_attribute));
                    } // Mapped by attribute
                    else {
                        $categories_id = $market_attribute->market_category->categories->pluck('id');

                        // If market_category is mapped
                        if ($categories_id->count()) {
                            $attributes = Attribute::whereIn('category_id', $categories_id)
                                ->where('name', $mapped_attribute_market_attribute->attribute->name)
                                ->where('id', '<>', $mapped_attribute_market_attribute->attribute->id)
                                ->get();

                            foreach ($attributes as $attribute) {
                                /*$property = Property::where('market_attribute_id', $market_attribute->id)
                                    ->where('name', $mapped_attribute_market_attribute->property->name)->first();*/

                                if ($property->name != 'sku image url') {

                                    $attribute_market_attribute = AttributeMarketAttribute::updateOrCreate([
                                        'market_id' => $market->id,
                                        'attribute_id' => $attribute->id,
                                        'market_attribute_id' => $market_attribute->id,
                                        'property_id' => $property->id,
                                    ], [
                                        'field' => $mapped_attribute_market_attribute->field,
                                        'fixed' => $mapped_attribute_market_attribute->fixed,
                                        'fixed_value' => $mapped_attribute_market_attribute->fixed_value,
                                        'pattern' => $mapped_attribute_market_attribute->pattern,
                                        'mapping' => $mapped_attribute_market_attribute->mapping,
                                        'if_exists' => $mapped_attribute_market_attribute->if_exists,
                                        'if_exists_value' => $mapped_attribute_market_attribute->if_exists_value,
                                    ]);
                                }


                                Storage::append('mp/ae/mapping/attributes_product.json', json_encode(MarketCategory::findOrFail($market_attribute->market_category_id)->first()));
                                Storage::append('mp/ae/mapping/attributes_product.json', json_encode($market_attribute));
                                Storage::append('mp/ae/mapping/attributes_product.json', json_encode($attribute_market_attribute));
                            }
                        }
                    }
                }
            }



            // Search Match Mapping data in one product with features on same category
            /*else {
                $pattern = '/[^a-zA-Z0-9]/';
                $mapping = 'equal';
                $categories_id = $market_attribute->market_category->categories->pluck('id');


                foreach ($categories_id as $category_id) {

                    $products = $market->shop_products()->select('products.*')
                    ->leftJoin('products', 'shop_products.product_id', '=', 'products.id')
                    ->where('products.category_id', $category_id)
                    ->latest()
                    ->get();

                    foreach ($products as $product) {

                        $product = Product::findOrFail($product->id);
                        if ($product->has('product_attributes')) {
                            //Storage::append('mapping_attributes.json', '$product: ' .$product->id. ' ' .$product->name);

                            $product_attributes = $product->product_attributes;
                            foreach ($product_attributes as $product_attribute) {

                                $properties = $market_attribute->properties()->where('required', true)->get();
                                foreach ($properties as $property) {

                                    if (strlen($product_attribute->value) > 3) {
                                        $property_value_value = $this->attribute_match($pattern, $mapping, $product_attribute->value, $property->property_values);

                                        if ($property_value_value) {

                                            $attribute_market_attribute = AttributeMarketAttribute::updateOrCreate([
                                                'market_id'             => $market->id,
                                                'attribute_id'          => $product_attribute->attribute->id,
                                                'market_attribute_id'   => $market_attribute->id,
                                                'property_id'           => $property->id,
                                            ],[
                                                'fixed'                 => 0,
                                                'fixed_value'           => null,
                                                'pattern'               => $pattern,
                                                'mapping'               => $mapping,
                                                'if_exists'             => null,
                                                'if_exists_value'       => null,
                                            ]);

                                            Storage::append('mp/ae/mapping/attributes_ia.json', json_encode(Category::findOrFail($category_id)->first()));
                                            Storage::append('mp/ae/mapping/attributes_ia.json', json_encode($market_attribute));
                                            Storage::append('mp/ae/mapping/attributes_ia.json', json_encode($attribute_market_attribute));

                                            break;
                                        }
                                    }
                                }
                            }
                        }
                        break;
                    }
                }
            }*/





        }

        return redirect()->route('markets.properties.index', [$market])->with('status', 'Atributos mapeados. Recuerda eliminar los mappings de: sku image url');
    }



    public function destroyMapping(Market $market, Property $property, AttributeMarketAttribute $attribute_market_attribute)
    {
        try {
            $attribute_market_attribute->delete();
        } catch (QueryException $e) {
            return redirect()->route('markets.properties.edit', [$market, $property])->with('status', $e->getMessage());
        }

        return redirect()->route('markets.properties.edit', [$market, $property])->with('status', 'Mapping creado correctamente.');
    }




}
