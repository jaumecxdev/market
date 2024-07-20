@extends('layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@push('menu')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('prices.price') }}" class="nav-link">Calcular precios</a></li>
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-10"><h1>Precios</h1></div>
                    <div class="col-sm-2">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item active">Precios</li>
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

                            <form method="get" action="{{ route('prices.index') }}" class="form-inline">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            @include('forms.supplier', ['suppliers' => $suppliers, 'supplier_id' => $params['supplier_id'] ?? null])
                                            @include('forms.category', ['category_id' => $params['category_id'] ?? null, 'category_name' => $params['category_name'] ?? null])
                                            @include('forms.brand', ['brand_id' => $params['brand_id'] ?? null, 'brand_name' => $params['brand_name'] ?? null])
                                            @include('forms.product', ['item_select' => $params['item_select'] ?? old('item_select'),
                                                'product_id' => $params['product_id'] ?? old('product_id'),
                                                'item_reference' => $params['item_reference'] ?? old('item_reference')])
                                            @include('forms.supplier_sku', ['supplierSku' => $params['supplierSku'] ?? null])
                                            @include('forms.mps_sku', ['MPSSku' => $params['MPSSku'] ?? null])
                                            @include('forms.market_product_sku', ['marketProductSku' => $params['marketProductSku'] ?? null])
                                            @include('forms.cost_filter', ['cost_min' => $params['cost_min'] ?? null, 'cost_max' => $params['cost_max'] ?? null])
                                            @include('forms.price_filter', ['price_min' => $params['price_min'] ?? null, 'price_max' => $params['price_max'] ?? null])
                                            @include('forms.stock_filter', ['stock_min' => $params['stock_min'] ?? null, 'stock_max' => $params['stock_max'] ?? null])
                                            @include('forms.action', ['actions' => $actions, 'action_name' => $params['action_name'] ?? null])
                                            <a class="mr-2 mb-2" href="{{ route('prices.index') }}">LIMPIAR</a>
                                            <button class="btn btn-success mb-2" type="submit" value="FILTRAR">FILTRAR</button>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <p>Hay {{ $prices->total() }} productos</p>
                            {{ $prices->appends($params)->render() }}
                            <table class="table table-striped">
                                <tr class="table-warning">
                                    <th></th>
                                    <th>@include('ordersby.prices', ['order_by' => 'prices.product_id', 'title' => 'IDs'])</th>
                                    <th>@include('ordersby.prices', ['order_by' => 'suppliers.name', 'title' => 'Proveedor'])</th>
                                    <th>
                                        @include('ordersby.prices', ['order_by' => 'markets.name', 'title' => 'MP'])<br>
                                        @include('ordersby.prices', ['order_by' => 'shops.name', 'title' => 'Tienda'])
                                    </th>
                                    <th>
                                        @include('ordersby.prices', ['order_by' => 'categories.name', 'title' => 'Categoría'])<br>
                                        @include('ordersby.prices', ['order_by' => 'brands.name', 'title' => 'Marca'])
                                    </th>
                                    <th>
                                        @include('ordersby.prices', ['order_by' => 'prices.bfit', 'title' => 'Cliente'])<br>
                                        @include('ordersby.prices', ['order_by' => 'prices.mps_bfit', 'title' => 'Mpe'])<br>
                                        @include('ordersby.prices', ['order_by' => 'prices.mp_bfit', 'title' => 'MP'])
                                    </th>
                                    <th>
                                        @include('ordersby.prices', ['order_by' => 'prices.cost', 'title' => 'Coste'])<br>
                                        @include('ordersby.prices', ['order_by' => 'prices.price', 'title' => 'Precio'])
                                    </th>
                                    <th>@include('ordersby.prices', ['order_by' => 'prices.stock', 'title' => 'Stock'])</th>
                                    <th>@include('ordersby.prices', ['order_by' => 'prices.name', 'title' => 'Acción'])</th>
                                    <th>@include('ordersby.prices', ['order_by' => 'products.name', 'title' => 'Título'])</th>
                                    <th>@include('ordersby.prices', ['order_by' => 'prices.created_at', 'title' => 'Creado'])</th>
                                </tr>
                                @foreach($prices as $price)
                                    <tr price-id="{{ $price->id }}">
                                        <td style="width:100px"><img class="w-100"
                                             src="{{ $price->getFirstImageFullUrl() }}">
                                        </td>
                                        <td><span class="font-weight-bold">{{ $price->product->getMPSSku() }}</span><br>
                                            SKU MP: <a href="{{ str_replace ('%marketProductSku', $price->marketProductSku, $price->market_product_url) }}">{{ $price->marketProductSku }}</a><br>
                                            P/N: {{ $price->pn }}<br>
                                            EAN: {{ $price->ean }}
                                        </td>
                                        <td>{{ $price->supplier_name }}<br>
                                            {{ $price->supplierSku }}
                                        </td>
                                        <td>{{ $price->market_name }}<br>
                                            {{ $price->shop_name }}<br>
                                            <a href="{{ str_replace ('%marketProductSku', $price->market_product_sku, $price->market_product_url) }}">
                                                {{ $price->market_product_sku }}</a>
                                        </td>
                                        <td>{{ $price->category_name }}<br>
                                            {{ $price->brand_name }}
                                        </td>
                                        <td>{{ $price->bfit }}€<br>
                                            {{ $price->mps_bfit }}€<br>
                                            {{ $price->mp_bfit }}€</td>
                                        <td>{{ $price->cost }}€<br>
                                            {{ $price->price }}€</td>
                                        <td>{{ $price->stock }}</td>
                                        <td>{{ $price->name }}</td>
                                        <td class="{{ $price->product_ready ? '' : 'text-danger' }}">
                                            <a class="mr-2" href="{{ route('products.show', [$price->product_id]) }}">
                                                {!! stripslashes($price->product_name) !!}</a></td>
                                        <td>{{ $price->created_at->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @endforeach
                            </table>
                            <br>

                            {{ $prices->appends($params)->render() }}

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
@push('scriptsEnd')
    @include('scripts.jquery-ui')
    @include('scripts.autocomplete-brands')
    @include('scripts.autocomplete-categories')
    @include('scripts.autocomplete-products')
    @include('scripts.submit-delete', ['question' => '¿Estás seguro de quitar de la tienda este producto?'])
@endpush
