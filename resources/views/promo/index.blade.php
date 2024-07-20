@extends('layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@push('menu')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('promos.create') }}" class="nav-link">Añadir promoción</a></li>
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-8"><h1>PROMOCIONES EN MARKETPLACES</h1></div>
                    <div class="col-sm-4">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Productos</a></li>
                            <li class="breadcrumb-item active">Promociones</li>
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

                            <form method="get" action="{{ route('promos.index') }}" class="form-inline">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            @include('forms.market', ['markets' => $markets, 'market_id' => $params['market_id'] ?? null])
                                            @include('forms.shop', ['shops' => $shops, 'shop_id' => $params['shop_id'] ?? null])
                                            @include('forms.supplier', ['suppliers' => $suppliers, 'supplier_id' => $params['supplier_id'] ?? null])
                                            @include('forms.name', ['name' => $params['name'] ?? null])
                                            @include('forms.product', ['item_select' => $params['item_select'] ?? old('item_select'),
                                                'product_id' => $params['product_id'] ?? old('product_id'),
                                                'item_reference' => $params['item_reference'] ?? old('item_reference')])
                                            @include('forms.supplier_sku', ['supplierSku' => $params['supplierSku'] ?? null])
                                            @include('forms.mps_sku', ['MPSSku' => $params['MPSSku'] ?? null])
                                            @include('forms.market_product_sku', ['marketProductSku' => $params['marketProductSku'] ?? null])
                                            @include('forms.cost_filter', ['cost_min' => $params['cost_min'] ?? null, 'cost_max' => $params['cost_max'] ?? null])
                                            @include('forms.price_filter', ['price_min' => $params['price_min'] ?? null, 'price_max' => $params['price_max'] ?? null])
                                            @include('forms.stock_filter', ['stock_min' => $params['stock_min'] ?? null, 'stock_max' => $params['stock_max'] ?? null])
                                            <a class="mr-2 mb-2" href="{{ route('promos.index') }}">LIMPIAR</a>
                                            <button class="btn btn-success mb-2" type="submit" value="FILTRAR">FILTRAR</button>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <p>Hay {{ $promos->total() }} promociones</p>
                            {{ $promos->appends($params)->render() }}
                            <table class="table table-striped">
                                <tr class="table-warning">
                                    <th></th>
                                    <th>@include('ordersby.promos', ['order_by' => 'promos.product_id', 'title' => 'IDs'])</th>
                                    <th>@include('ordersby.promos', ['order_by' => 'products.name', 'title' => 'Título'])</th>
                                    <th>
                                        @include('ordersby.promos', ['order_by' => 'markets.name', 'title' => 'MP'])
                                        @include('ordersby.promos', ['order_by' => 'shops.name', 'title' => 'Tienda'])
                                    </th>
                                    <th>
                                        @include('ordersby.promos', ['order_by' => 'products.cost', 'title' => 'Coste'])
                                        @include('ordersby.promos', ['order_by' => 'promos.price', 'title' => 'Precio'])
                                        Oferta
                                    </th>
                                    <th>@include('ordersby.promos', ['order_by' => 'products.stock', 'title' => 'Stock'])</th>
                                    <th>@include('ordersby.promos', ['order_by' => 'promos.name', 'title' => 'Promo'])</th>
                                    <th>@include('ordersby.promos', ['order_by' => 'promos.begins_at', 'title' => 'Empieza'])</th>
                                    <th>@include('ordersby.promos', ['order_by' => 'promos.ends_at', 'title' => 'Finaliza'])</th>
                                    <th>Acciones</th>
                                </tr>
                                @foreach($promos as $promo)
                                    <tr promo-id="{{ $promo->id }}">
                                        <td style="width:100px"><img class="w-100"
                                             src="{{ $promo->product ? $promo->product->getFirstImageFullUrl() : '' }}"></td>
                                        <td><span class="font-weight-bold">{{ $promo->shop_product ? $promo->shop_product->mps_sku : ($promo->product ? $promo->product->getMPSSku() : null) }}</span><br>
                                            SKU MP:
                                                <a href="{{ str_replace ('%marketProductSku', $promo->marketProductSku, $promo->market_product_url) }}">
                                                {{ $promo->marketProductSku }}</a>
                                                <br>
                                            P/N: {{ $promo->pn }}<br>
                                            EAN: {{ $promo->ean }}
                                        </td>
                                        <td><a class="mr-2" href="{{ $promo->product_id ? route('products.show', [$promo->product_id]) : '' }}">
                                                {!! stripslashes($promo->product_name) !!}</a></td>
                                        <td>{{ $promo->market_name }}<br>{{ $promo->shop_name }}
                                        <td>{{ $promo->cost }}€<br>
                                            <span>{{ $promo->shop_price }}€</span><br>
                                                <span>{{ $promo->offer_price }}€</span>
                                                @isset($promo->discount)
                                                    <span> {{ $promo->discount }}%</span>
                                                @endisset
                                        </td>
                                        <td>{{ $promo->stock }}</td>
                                        <td>{{ $promo->name }}</td>
                                        <td>{{ $promo->begins_at ? $promo->begins_at->format('Y-m-d H:i') : '' }}</td>
                                        <td>{{ $promo->ends_at ? $promo->ends_at->format('Y-m-d H:i') : '' }}</td>
                                        <td style="">
                                            <div class="row">
                                                <a class="mr-2" href="{{ route('promos.edit', [$promo]) }}"
                                                   data-toggle="tooltip" title="Editar Promocioón"><i class="far fa-edit"></i></a>
                                                <a class="mr-2" href="{{ route('promos.copy', [$promo]) }}"
                                                   data-toggle="tooltip" title="Copiar Promocioón"><i class="far fa-copy"></i></a>
                                                <form class="delete" action="{{ route('promos.destroy', [$promo]) }}" method="post">
                                                    @method('delete')
                                                    @csrf
                                                    @include('forms.button_delete', ['title' => 'Quitar de la tienda'])
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                            <br>

                            {{ $promos->appends($params)->render() }}

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
@push('scriptsEnd')
    @include('scripts.jquery-ui')
    @include('scripts.autocomplete-products')
    @include('scripts.submit-delete', ['question' => '¿Estás seguro de eliminar esta promoción?'])
@endpush
