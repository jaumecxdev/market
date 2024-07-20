<?php

namespace App\Http\Controllers;

use App\Product;
use App\ProviderProductAttribute;
use Illuminate\Http\Request;

class ProviderProductAttributeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Product $product)
    {
        $provider_product_attributes = $product->provider_product_attributes->paginate(100);

            //->paginate(1000);

        return view('product_attribute.index', compact('product', 'provider_product_attributes'));
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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\ProviderProductAttribute  $providerProductAttribute
     * @return \Illuminate\Http\Response
     */
    public function show(ProviderProductAttribute $providerProductAttribute)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\ProviderProductAttribute  $providerProductAttribute
     * @return \Illuminate\Http\Response
     */
    public function edit(ProviderProductAttribute $providerProductAttribute)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\ProviderProductAttribute  $providerProductAttribute
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ProviderProductAttribute $providerProductAttribute)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\ProviderProductAttribute  $providerProductAttribute
     * @return \Illuminate\Http\Response
     */
    public function destroy(ProviderProductAttribute $providerProductAttribute)
    {
        //
    }
}
