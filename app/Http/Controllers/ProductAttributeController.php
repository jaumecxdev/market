<?php

namespace App\Http\Controllers;

use App\Attribute;
use App\Product;
use App\ProductAttribute;
use App\ProviderAttribute;
use App\ProviderAttributeValue;
use App\ProviderProductAttribute;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;


class ProductAttributeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


    public function index(Product $product)
    {
        $provider_product_attributes = $product->provider_product_attributes;

        return view('product_attribute.index', compact('product', 'provider_product_attributes'));
    }


    public function create(Product $product)
    {
        return back()->withErrors('No implementado');

        $provider_attributes = ProviderAttribute::whereCategoryId($product->provider_category_id)->orderBy('name')->get();
        $provider_attributes_values = ProviderAttributeValue::orderBy('name');

        return view('product_attribute.create-edit', compact('product', 'provider_attributes', 'provider_attributes_values'));
    }


    public function store(Request $request, Product $product)
    {
        $validatedData = $request->validate([
            'attribute_id'  => 'required|exists:attributes,id',
            'value'         => 'required|max:255',
        ]);

        ProductAttribute::updateOrCreate([
            'product_id'        => $product->id,
            'attribute_id'      => $validatedData['attribute_id'],
        ],[
            'value'             => $validatedData['value'],
        ]);

        return redirect()->route('products.attributes', [$product])->with('status', 'Atributo creado correctamente.');
    }


    public function edit(Product $product, ProviderProductAttribute $provider_product_attribute)
    {
        $provider_attributes_values = $provider_product_attribute->provider_attribute->provider_attribute_values;

        return view('product_attribute.create-edit', compact('product', 'provider_product_attribute', 'provider_attributes_values'));
    }


    public function update(Request $request, Product $product, ProviderProductAttribute $provider_product_attribute)
    {
        $validatedData = $request->validate([
            'provider_attribute_id'         => 'required|exists:provider_attributes,id',
            'provider_attribute_value_id'   => 'required|exists:provider_attribute_values,id',
            //'value'         => 'required|max:255',
        ]);

        $provider_product_attribute->provider_attribute_value_id = $validatedData['provider_attribute_value_id'];
        $provider_product_attribute->save();

        return redirect()->route('products.attributes', [$product])->with('status', 'Atributo modificado correctamente.');
    }


    public function destroy(Product $product, ProviderProductAttribute $provider_product_attribute)
    {
        try {
            $provider_product_attribute->delete();
        } catch (QueryException $e) {
            return redirect()->route('products.attributes', [$product])->with('status', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->route('products.attributes', [$product])->with('status', $e->getMessage());
        }

        return redirect()->route('products.attributes', [$product])->with('status', 'Atributo de producto eliminado correctamente.');
    }


}
