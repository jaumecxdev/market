@extends('layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@push('menu')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('shops.shop_products.text', [$shop, $shop_product]) }}" class="nav-link">Textos</a></li>
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-8"><h1>{{ isset($shop_product) ? 'EDITAR PRODUCTO DE TIENDA: ' .$shop_product->product->name : 'AÑADIR PRODUCTO DE TIENDA' }}</h1></div>
                    <div class="col-sm-4">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('action.shops') }}">Tiendas</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('shops.shop_products.index', [$shop]) }}">Productos</a></li>
                            <li class="breadcrumb-item active">Editar</li>
                        </ol>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">

                            @include('partials.status')
                            @include('partials.errors')

                            <form method="post" action="{{ route('shops.shop_products.update', [$shop, $shop_product]) }}">
                                @method('PATCH')
                                @csrf

                                <div class="form-group row">
                                    <label for="ready" class="col-sm-2 col-form-label">Habilitado</label>
                                    <div class="col-sm-10">
                                        @include('forms.checkbox', ['field_name' => 'enabled', 'value' => $shop_product->enabled ?? old('enabled'), 'label' => 'Si se deshabilita, actualizará al Marketplace con STOCK a 0.'])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="marketProductSku" class="col-sm-2 col-form-label">marketProductSku</label>
                                    <div class="col-sm-4">
                                        @include('forms.market_product_sku', ['marketProductSku' => $shop_product->marketProductSku ?? old('marketProductSku')])
                                        <small>ID de producto del Marketplace</small>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="MPSSku" class="col-sm-2 col-form-label">MPS SKU</label>
                                    <div class="col-sm-4">
                                        @include('forms.mps_sku', ['MPSSku' => $shop_product->mps_sku ?? old('MPSSku')])
                                        <small>SKU de MPe</small>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="market_category_id" class="col-sm-2 col-form-label">Categoría del Marketplace</label>
                                    <div class="col-sm-6">
                                        @include('forms.market_category', ['market_category_id' => $shop_product->market_category_id ?? old('market_category_id'),
                                                                        'market_category_name' => $shop_product->market_category->name ?? old('market_category_name')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="is_sku_child" class="col-sm-2 col-form-label">Producto Hijo</label>
                                    <div class="col-sm-10">
                                        @include('forms.checkbox', ['field_name' => 'is_sku_child', 'value' => $shop_product->is_sku_child ?? old('is_sku_child'),
                                            'label' => '¿Este producto es una variante de un producto padre con talla y color?'])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="set_group" class="col-sm-2 col-form-label">Categoría de Tienda OK?</label>
                                    <div class="col-sm-10">
                                        @include('forms.checkbox', ['field_name' => 'set_group', 'value' => $shop_product->set_group ?? old('set_group'),
                                            'label' => '¿Este producto ya tiene la --categoría de Tienda o GRUPO-- de Marketplace actualizada en este?'])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="param_fee" class="col-sm-2 col-form-label">Margen Bcio | Mpe | Mín | RePrice Mín</label>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'param_fee', 'value' => $shop_product->param_fee ?? old('param_fee')])
                                        <small>Margen de Bcio en %.</small>
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'param_mps_fee', 'value' => $shop_product->param_mps_fee ?? old('param_mps_fee')])
                                        <small>Margen de Bcio Intermediación MPe en %.</small>
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'param_bfit_min', 'value' => $shop_product->param_bfit_min ?? old('param_bfit_min')])
                                        <small>Margen de Bcio MÍNIMO en €.</small>
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'param_reprice_fee_min', 'value' => $shop_product->param_reprice_fee_min ?? old('param_reprice_fee_min')])
                                        <small>RePrice MÍNIMO en %.</small>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="currency_id" class="col-sm-2 col-form-label">Moneda | Precio y Stocks FIJOS</label>
                                    <div class="col-sm-2">
                                        @include('forms.currency_select', ['currencies' => $currencies, 'currency_id' => $shop_product->currency_id ?? old('currency_id')])
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'param_price', 'value' => $shop_product->param_price ?? old('param_price')])
                                        <small>PVP € FIJADO para publicar en el Marketplace.</small>
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'param_stock', 'value' => $shop_product->param_stock ?? old('param_stock')])
                                        <small>Stock € FIJADO para publicar en el Marketplace. Para fijar Stock a 0 -> DESHABILITAR</small>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="param_discount_price" class="col-sm-2 col-form-label">Precio de descuento o oferta</label>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'param_discount_price', 'value' => $shop_product->param_discount_price ?? old('param_discount_price'), 'placeholder' => 'Precio final con descuento'])
                                        <small>PVP € de descuento fijado - Precio tachado</small>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                            </div>
                                        @php
                                            $param_starts_at = isset($shop_product->param_starts_at) ? $shop_product->param_starts_at->format('Y-m-d') : old('param_starts_at');
                                        @endphp
                                        <input type="text" name="param_starts_at" id="param_starts_at" placeholder="yyyy-mm-dd" class="form-control" class="form-control mr-2 mb-2" value="{{ $param_starts_at }}">
                                        </div>
                                        <small>Fechas en las que se aplicarán estos parámetros.</small>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                            </div>
                                            @php
                                                $param_ends_at = isset($shop_product->param_ends_at) ? $shop_product->param_ends_at->format('Y-m-d') : old('param_ends_at');
                                            @endphp
                                            <input type="text" name="param_ends_at" id="param_ends_at"  placeholder="yyyy-mm-dd" class="form-control" class="form-control mr-2 mb-2" value="{{ $param_ends_at }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="cost" class="col-sm-2 col-form-label">Stock mínimo | Máximo</label>
                                    <div class="col-sm-4">
                                        @include('forms.custom', ['field_name' => 'param_stock_min', 'value' => $shop_product->param_stock_min ?? old('param_stock_min')])
                                        <small>Si el producto no llega a este Stock, se publicarán 0 unidades al Marketplace.</small>
                                    </div>
                                    <div class="col-sm-4">
                                        @include('forms.custom', ['field_name' => 'param_stock_max', 'value' => $shop_product->param_stock_max ?? old('param_stock_max')])
                                        <small>Si el producto tiene mas stock que el máximo, se publicará con este stock máximo en el Marketplace</small>
                                    </div>
                                </div>

                                {{-- <div class="form-group row">
                                    <label for="cost" class="col-sm-2 col-form-label">Tarifa del Marketplace % | €</label>
                                    <div class="col-sm-4">
                                        @include('forms.custom', ['field_name' => 'mp_fee', 'value' => $shop_product->param_mp_fee ?? old('mp_fee')])
                                        <small>Si existen tarifas en % y €, estas se sumarán. Ejemplo Ebay+Paypal: 5.90% + 0.35€</small>
                                    </div>
                                    <div class="col-sm-4">
                                        @include('forms.custom', ['field_name' => 'mp_fee_addon', 'value' => $shop_product->param_mp_fee_addon ?? old('mp_fee_addon')])
                                    </div>
                                </div> --}}

                                <div class="form-group row">
                                    <label for="param_canon" class="col-sm-2 col-form-label">Canon | Rappel | Portes</label>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'param_canon', 'value' => $shop_product->param_canon ?? old('param_canon')])
                                        <small>Canon</small>
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'param_rappel', 'value' => $shop_product->param_rappel ?? old('param_rappel')])
                                        <small>Rappel</small>
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'param_ports', 'value' => $shop_product->param_ports ?? old('param_ports')])
                                        <small>Portes</small>
                                    </div>
                                </div>

                                <br>
                                <div class="form-group row">
                                    <div class="col-sm-2"></div>
                                    <div class="col-sm-10">
                                        <a class="btn btn-danger" href="{{ route('shops.shop_products.index', $shop) }}" role="button">Cancelar</a>
                                        <button type="submit" class="btn btn-primary">Guardar producto</button>
                                    </div>
                                </div>

                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
@push('scriptsEnd')
    @include('scripts.jquery-ui')
    @include('scripts.autocomplete-marketcategories', ['market_id' => $shop->market_id])
@endpush
