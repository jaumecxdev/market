<?php

namespace App\Http\Controllers;

use App\Attribute;
use App\Buyer;
use App\Category;
use App\Brand;
use App\MarketAttribute;
use App\MarketBrand;
use App\MarketCategory;
use App\Product;
use App\PropertyValue;
use App\RootCategory;
use App\SupplierBrand;
use App\SupplierCategory;
use App\Traits\HelperTrait;
use App\Type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AutocompleteController extends Controller
{
    use HelperTrait;

    public function attributes(Request $request)
    {
        $search = $request->get('term');
        $categories_id = json_decode($request->get('categories_id'));

        //\Log::info('categories_id: ' .json_encode($categories_id));

        $attributes = Attribute::select('attributes.id', 'attributes.name', 'categories.name as category_name')
            ->leftJoin('categories', 'attributes.category_id', '=', 'categories.id')
            ->where('attributes.name', 'LIKE', '%'. $search. '%')
            ->whereIn('attributes.category_id', $categories_id)
            ->orderBy('attributes.name', 'asc')
            ->get();

       /* $result = $attributes->map(function ($item, $key) {
            return ['id' => $item['id'], 'name' => $item['name'], 'category_name'];
        });*/
        return response()->json($attributes);
    }


    public function brands(Request $request)
    {
        $search = $request->get('term');
        $brands = Brand::select('id', 'name')
            ->where('name', 'LIKE', '%'. $search. '%')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json($brands);
    }


    public function buyers(Request $request)
    {
        $search = $request->get('term');
        $buyers = Buyer::select('id', 'name')
            ->where('name', 'LIKE', '%'. $search. '%')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json($buyers);
    }


    public function categories(Request $request)
    {
        $search = $request->get('term');
        $categories = Category::select('id', 'code', 'name', 'path')
            ->where('name', 'LIKE', '%'. $search. '%')
            ->where('id', '>', '71')        // NO old categories
            ->orderBy('name', 'asc')
            ->get();

        $result = $categories->map(function ($item, $key) {
            return [
                'id' => $item['id'],
                'value' => $item['name'],
                'label' => $item['id']. ' ('.$item['code']. ') <b>' .$item['name']. '</b> (' .$item['path']. ')'
            ];
        });

        return $result;
    }


    public function supplierBrands(Request $request)
    {
        try {
            $search = $request->get('term');
            $suppliers_id = json_decode($request->get('suppliers_id'), true);

            $supplier_brands = SupplierBrand::select('id', 'name')
                ->where('name', 'LIKE', '%'. $search. '%')
                ->whereIn('supplier_id', $suppliers_id)
                ->orderBy('name', 'asc')
                ->get();

                return response()->json($supplier_brands);

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$request->all(), $suppliers_id ?? null]);
        }
    }


    public function supplierCategories(Request $request)
    {
        try {
            $search = $request->get('term');
            $suppliers_id = json_decode($request->get('suppliers_id'), true);

            $supplier_categories = SupplierCategory::select(
                    'supplier_categories.id',
                    'supplier_categories.supplierCategoryId',
                    'supplier_categories.name',
                    'categories.name as category_name',
                    'suppliers.name as supplier_name')
                ->leftJoin('categories', 'categories.id', '=', 'supplier_categories.category_id')
                ->leftJoin('suppliers', 'suppliers.id', '=', 'supplier_categories.supplier_id')
                ->where('supplier_categories.name', 'LIKE', '%'. $search. '%')
                ->whereIn('supplier_categories.supplier_id', $suppliers_id)
                //->groupBy('supplier_categories.name')
                ->orderBy('supplier_categories.name', 'asc')
                ->get();

            $result = $supplier_categories->map(function ($item, $key) {
                //$category_name = isset($item['category_name']) ? $item['category_name'] : '<span class="text-danger">NO_MAPPING_CATEGORY</span>';
                return [
                    'id' => $item['id'],
                    'value' => $item['name'],
                    'label' => $item['supplier_name'].' ('.$item['supplierCategoryId']. ') <b>' .$item['name']. '</b>'  // ('.$category_name.')'
                ];
            });

            return $result;

        } catch (Throwable $th) {
            return $this->nullWithErrors($th, __METHOD__, [$request->all(), $suppliers_id ?? null]);
        }
    }


    /*public function rootCategories(Request $request)
    {
        $search = $request->get('term');
        $market_id = $request->get('market_id');
        $root_categories = RootCategory::select('id', 'name')
            ->where('name', 'LIKE', '%'. $search. '%')
            ->where('market_id', $market_id)
            ->get();

        return response()->json($root_categories);
    }*/


    public function marketAttributes(Request $request)
    {
        $search = $request->get('term');
        $market_category_id = $request->get('market_category_id');
        $market_attributes = MarketAttribute::select('market_attributes.id', 'market_attributes.name',
                'market_categories.name as market_category_name',
                'properties.name as property_name',
                'properties.id as property_id')
            ->leftJoin('market_categories', 'market_attributes.market_category_id', '=', 'market_categories.id')
            ->leftJoin('properties', 'market_attributes.id', '=', 'properties.market_attribute_id')
            ->where('market_categories.id', $market_category_id)
            ->where('market_attributes.name', 'LIKE', '%'. $search. '%')
            ->orderBy('name', 'asc')
            ->get();

        /*$result = $attributes->map(function ($item, $key) {
            return ['id' => $item['id'], 'name' => $item['name'], 'market_category' => MarketCategory::find($item['market_category_id'])->name];
        });*/
        return response()->json($market_attributes);
    }


    public function marketBrands(Request $request)
    {
        $search = $request->get('term');
        $market_id = $request->get('market_id');
        $market_brands = MarketBrand::select('id', 'name', 'market_id', 'marketBrandId')
            ->where('name', 'LIKE', '%'. $search. '%')
            ->where('market_id', $market_id)
            ->orderBy('name', 'asc')
            ->get();

        $result = $market_brands->map(function ($item, $key) {
            return [
                'id' => $item['id'],
                'value' => $item['name'],
                'label' => $item['id'] . ' (' .$item['marketBrandId']. ') <b>' .$item['name']. '</b>'
            ];
        });

        return $result;

        //return response()->json($market_brands);
    }


    public function marketCategories(Request $request)
    {
        $search = $request->get('term');
        $market_id = $request->get('market_id');
        $market_categories = MarketCategory::select('market_categories.id',
            'market_categories.name',
            'market_categories.marketCategoryId',
            'market_categories.path')
            ->leftJoin('root_categories', 'market_categories.root_category_id', '=', 'root_categories.id')
            ->where('market_categories.market_id', $market_id)
            ->where(function ($query) use($search) {
                $query->where('market_categories.name', 'LIKE', '%' . $search . '%');
                $query->orWhere('root_categories.name', 'LIKE', '%' . $search . '%');
                $query->orWhere('market_categories.marketCategoryId', 'LIKE', '%' . $search . '%');
            })
            ->orderBy('market_categories.name', 'asc')
            ->get();

        Storage::append('mp/pccompo/cats.json', json_encode([$market_categories->toJson(), $search, $market_id]));

        //return response()->json($market_categories);
        $result = $market_categories->map(function ($item, $key) {
            return [
                'id' => $item['id'],
                'value' => $item['name'],
                'label' => '(' .$item['marketCategoryId']. ') <b>' .$item['name']. '</b> (' .$item['path']. ')'
            ];
        });

        return $result;
    }


    public function products(Request $request)
    {
        $search = $request->get('term');
        $supplier_id = $request->get('supplier_id');
        $suppliers_id = null;
        if ($request->has('suppliers_id'))
            $suppliers_id = json_decode($request->get('suppliers_id'), true);
        //Log::info('autocomplet_products ' .$search. ' ' .$supplier_id);
        $products = Product::select('products.id', 'products.name', 'products.supplier_id', 'products.supplierSku',
            'suppliers.name as supplier_name')
            ->leftJoin('suppliers', 'products.supplier_id', '=', 'suppliers.id')
            ->where('products.name', 'LIKE', '%'. $search. '%')
            ->orderBy('products.supplier_id', 'asc')
            ->orderBy('products.name', 'asc');

        if ($supplier_id)
            $products = $products->where('supplier_id', $supplier_id);
        elseif ($suppliers_id)
            $products = $products->whereIn('supplier_id', $suppliers_id);

        $products = $products->get();

        //Log::info('autocomplet_products ' .$search. ' ' .$supplier_id. ' -- PRODUCTS: '.json_encode($products));
        //Log::info('autocomplet_products ' .$search. ' ' .$supplier_id. ' -- PRODUCTS SUPPLIER: '.json_encode($products));

        $result = $products->map(function ($item, $key) {
            return [
                'id' => $item['id'],
                'value' => stripslashes($item['name']),
                'label' => $item['id']. ' <b>(' .$item['supplierSku']. ' ' .$item['supplier_name']. ')</b> ' .stripslashes($item['name'])
            ];
        });

        //Log::info('autocomplet_products ' .$search. ' ' .$supplier_id. ' -- RESULT: '.json_encode($result));

        return $result;

    }


    public function propertyValues(Request $request)
    {
        $property_id = $request->get('term');
        $property_values = PropertyValue::select('id', 'name', 'value')
            ->where('property_id', '=', $property_id)
            ->get();

        return response()->json($property_values);
    }


    // 'market_id', 'name', 'marketCategoryId'
    public function rootCategories(Request $request)
    {
        $search = $request->get('term');
        $market_id = $request->get('market_id');
        $root_categories = RootCategory::select(['root_categories.id', 'root_categories.name', 'root_categories.marketCategoryId'])
            ->where('root_categories.market_id', $market_id)
            ->where('root_categories.name', 'LIKE', '%' . $search . '%')
            ->orderBy('root_categories.name', 'asc')
            ->get();

        $result = $root_categories->map(function ($item, $key) {
            return [
                'id' => $item['id'],
                'value' => $item['name'],
                'label' => $item['marketCategoryId']. ' <b>' .$item['name']. '</b>'
            ];
        });

        return $result;
    }


    public function statuses(Request $request, $type)
    {
        $search = $request->get('term');
        $statuses = Type::select('id', 'name', 'type')
            ->where('type', $type)
            ->where('name', 'LIKE', '%'. $search. '%')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json($statuses);
    }


    public function types(Request $request, $type)
    {
        $search = $request->get('term');
        $types = Type::select('id','name', 'type')
            ->where('type', $type)
            ->where('name', 'LIKE', '%'. $search. '%')
            ->orderBy('name', 'asc')->get();

        return response()->json($types);
    }

}
