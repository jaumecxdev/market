@extends('saas.layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@push('menu')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('saas.shops.shop_params', [$shop]) }}" class="nav-link">Precios y Stocks</a></li>
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('saas.shops.shop_filters', [$shop]) }}" class="nav-link">Filtros</a></li>
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('saas.shops.shop_products.calculate', [$shop]) }}" class="nav-link">Calcular Precios</a></li>
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('saas.shops.shop_products.post.products', [$shop]) }}" class="nav-link">Subir Productos Nuevos</a></li>
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('saas.shops.shop_products.synchronize', [$shop]) }}" class="nav-link">Sincronizar Online</a></li>
@endpush

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-12"><h1>PRODUCTOS: ({{ $shop->market->name }}) {{ $shop->name }}</h1></div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">

                            @include('partials.errors')
                            @include('partials.status')

                            <form method="get" action="{{ route('saas.shops.shop_products', [$shop]) }}" class="form-inline">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            @include('forms.supplier', ['suppliers' => $suppliers, 'supplier_id' => $params['supplier_id'] ?? null])
                                            @include('forms.brand', ['brand_id' => $params['brand_id'] ?? null, 'brand_name' => $params['brand_name'] ?? null])
                                            @include('forms.supplier_category', ['supplier_category_id' => $params['supplier_category_id'] ?? old('supplier_category_id'), 'supplier_category_name' => $params['supplier_category_name'] ?? old('supplier_category_name')])
                                            @include('forms.market_category', ['market_category_id' => $params['market_category_id'] ?? null, 'market_category_name' => $params['market_category_name'] ?? null])
                                        </div>
                                        <div class="form-group">
                                            @include('forms.product', ['item_select' => $params['item_select'] ?? old('item_select'),
                                                'product_id' => $params['product_id'] ?? old('product_id'),
                                                'item_reference' => $params['item_reference'] ?? old('item_reference')])
                                            @include('forms.mps_sku', ['MPSSku' => $params['MPSSku'] ?? null])
                                            @include('forms.market_product_sku', ['marketProductSku' => $params['marketProductSku'] ?? null])
                                            @include('forms.repriced', ['option_selected' => $params['repriced'] ?? null])
                                        </div>
                                        <div class="form-group">
                                            @include('forms.cost_filter', ['cost_min' => $params['cost_min'] ?? null, 'cost_max' => $params['cost_max'] ?? null])
                                            @include('forms.price_filter', ['price_min' => $params['price_min'] ?? null, 'price_max' => $params['price_max'] ?? null])
                                            @include('forms.stock_filter', ['stock_min' => $params['stock_min'] ?? null, 'stock_max' => $params['stock_max'] ?? null])
                                            <a class="mb-2 mr-2" href="{{ route('saas.shops.shop_products', [$shop]) }}">LIMPIAR</a>
                                            <button class="btn btn-success mb-2 mr-2" type="submit" name="action" value="filter">FILTRAR</button>
                                            <button class="btn btn-warning mb-2 mr-2" type="" name="action" value="export">EXPORTAR</button>
                                            <button class="btn btn-danger mb-2" type="" name="action" value="delete">ELIMINAR NO SUBIDOS</button>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <p>Hay {{ $shop_products->total() }} productos</p>
                            {{ $shop_products->appends($params)->render() }}
                            <table class="table table-striped products-list">
                                <tr class="table-warning">
                                    <th></th>
                                    <th>@include('saas.ordersby.shop_products', ['order_by' => 'shop_products.product_id', 'title' => 'IDs'])</th>
                                    <th>@include('saas.ordersby.shop_products', ['order_by' => 'suppliers.name', 'title' => 'Proveedor'])</th>
                                    <th>
                                        @include('saas.ordersby.shop_products', ['order_by' => 'categories.name', 'title' => 'Categoría Prov.'])<br>
                                        @include('saas.ordersby.shop_products', ['order_by' => 'shop_products.market_category_id', 'title' => 'Categoría MP'])<br>
                                        @include('saas.ordersby.shop_products', ['order_by' => 'brands.name', 'title' => 'Marca'])
                                    </th>
                                    <th>
                                        @include('saas.ordersby.shop_products', ['order_by' => 'shop_products.param_fee', 'title' => 'Margen'])<br>
                                        @include('saas.ordersby.shop_products', ['order_by' => 'shop_products.param_mp_fee', 'title' => 'MP'])
                                    </th>
                                    <th>
                                        @include('saas.ordersby.shop_products', ['order_by' => 'products.cost', 'title' => 'Coste'])<br>
                                        @include('saas.ordersby.shop_products', ['order_by' => 'shop_products.param_canon', 'title' => 'Canon'])<br>
                                        @include('saas.ordersby.shop_products', ['order_by' => 'shop_products.param_ports', 'title' => 'Portes'])
                                    </th>
                                    <th>
                                        @include('saas.ordersby.shop_products', ['order_by' => 'shop_products.price', 'title' => 'Precio'])<br>
                                        @include('saas.ordersby.shop_products', ['order_by' => 'shop_products.buybox_price', 'title' => 'BuyBox'])<br>
                                        @include('saas.ordersby.shop_products', ['order_by' => 'shop_products.stock', 'title' => 'Stock'])
                                    </th>
                                    <th>@include('saas.ordersby.shop_products', ['order_by' => 'products.name', 'title' => 'Título'])</th>
                                    <th>
                                        @include('saas.ordersby.shop_products', ['order_by' => 'shop_products.updated_at', 'title' => 'Actual.'])<br>
                                        @include('saas.ordersby.shop_products', ['order_by' => 'shop_products.created_at', 'title' => 'Creado'])
                                    </th>
                                    <th>Acciones</th>
                                </tr>
                                @foreach($shop_products as $shop_product)
                                    <tr shop_product-id="{{ $shop_product->id }}">
                                        <td class="img-list"><img
                                            src="{{ $shop_product->getFirstImageFullUrl() }}"></td>
                                        <td><span class="badge bg-success">{{ $shop_product->product->id }}</span><br>
                                            <span class="font-weight-bold mp-sku {{ ($shop_product->marketProductSku && $shop_product->marketProductSku != 'ERROR') ? '' : 'text-danger' }}">SKU MP: </span>
                                            <a class="mp-sku" href="{{ str_replace ('%marketProductSku', $shop_product->marketProductSku, $shop_product->market_product_url) }}">
                                                {{ $shop_product->marketProductSku }}</a><br>
                                            <small><span class="font-weight-bold">P/N:</span> {{ $shop_product->pn }}</small><br>
                                            <small><span class="font-weight-bold">EAN:</span> {{ $shop_product->ean }}</small>
                                        </td>
                                        <td>
                                            <small>{{ $shop_product->supplier_name }}<br>{{ $shop_product->supplierSku }}<br><span class="badge bg-warning">{{ $shop_product->mps_sku }}</span><br></small>
                                        </td>
                                        <td>
                                            <small>
                                                {!! $shop_product->product->supplier_category_id ? mb_substr($shop_product->product->supplier_category->name, -30) : '---' !!}<br>
                                                {!! $shop_product->market_category ?
                                                    '<span class="text-success">'.($shop_product->market_category->marketCategoryId.' '.$shop_product->market_category->name).'</span>' :
                                                    '<span class="text-danger">NO MARKET CATEGORY</span>' !!}<br>
                                                {{ $shop_product->brand_name ?? null }}
                                            </small>
                                        </td>
                                        <td>
                                            {{-- @php
                                                $bfit = 0;
                                                $mps_bfit = 0;
                                                $mp_bfit = Facades\App\Facades\Mpe::getMarketBfit($shop_product->price, $shop_product->param_mp_fee, $shop_product->param_mp_fee_addon);
                                                if ($shop_product->param_fee > 0) $bfit = Facades\App\Facades\Mpe::getClientBfit($shop_product->price, $shop_product->param_fee, $shop_product->param_bfit_min, $shop_product->tax);
                                                if ($shop_product->param_mps_fee > 0) $mps_bfit = Facades\App\Facades\Mpe::getMpsBfit($shop_product->price, $shop_product->param_mps_fee, $shop_product->param_bfit_min, $shop_product->tax);
                                            @endphp --}}
                                            {{ $shop_product->param_fee }}%&nbsp;<span class="{{ $shop_product->repriced ? 'text-primary' : '' }}">{{ $shop_product->bfit }}€</span><br>
                                            {{ $shop_product->param_mp_fee.'+'.$shop_product->param_mp_lot_fee }}%&nbsp;{{ $shop_product->mp_bfit }}€
                                        </td>
                                        <td>{{ $shop_product->cost }}€<br>
                                            {{ $shop_product->param_canon }}€<br>
                                            {{ $shop_product->param_ports }}€
                                        </td>
                                        <td>
                                            @php
                                                $price = ($shop_product->param_discount_price != 0) ? $shop_product->param_discount_price : $shop_product->price;
                                            @endphp
                                            <span class="{{ ($shop_product->param_price != 0 || $shop_product->param_discount_price != 0) ? 'text-primary' : '' }}">{{ $price }}€</span><br>
                                            <span class="{{ $shop_product->repriced ? 'text-primary' : '' }}">{{ ($shop_product->buybox_price != 0) ? $shop_product->buybox_price.'€' : '---' }}</span><br>
                                            <span class="{{ ($shop_product->param_stock != 0) ? 'text-primary' : '' }}">{{ $shop_product->stock }}</span></td>
                                        <td>
                                            <small>
                                            <a class="mr-2" href="{{ route('products.show', [$shop_product->product_id]) }}">
                                            <span class="{{ ($shop_product->product_ready && $shop_product->enabled) ? '' : 'text-danger' }}">
                                                {!! $shop_product->name ? substr(stripslashes($shop_product->name), 0, 85) : substr(stripslashes($shop_product->product->name), 0, 85) !!}</span></a>
                                            </small>
                                        </td>
                                        <td>
                                            <small>
                                                {{ isset($shop_product->updated_at) ? $shop_product->updated_at->format('Y-m-d H:i') : '' }}<br>
                                                {{ $shop_product->created_at->format('Y-m-d H:i') }}
                                            </small>
                                        </td>
                                        <td style="">
                                            <div class="row">
                                                <a class="mr-2" href="{{ route('saas.shops.shop_products.edit', [$shop, $shop_product]) }}"
                                                   data-toggle="tooltip" title="Editar Producto de la Tienda"><i class="far fa-edit"></i></a>
                                                @if (!$shop_product->marketProductSku)
                                                    <a class="mr-2" href="{{ route('saas.shops.shop_products.post.product', [$shop, $shop_product]) }}"
                                                       data-toggle="tooltip" title="Subir"><i class="fas fa-cloud-upload-alt"></i></a>
                                                @elseif ($shop_product->marketProductSku != 'ERROR')
                                                   {{--  <a class="mr-2" href="{{ route('saas.shops.shop_products.post.updated', [$shop, $shop_product]) }}"
                                                       data-toggle="tooltip" title="Actualizar Ficha"><i class="fas fa-cloud-upload-alt"></i></a> --}}
                                                    {{-- <a class="mr-2" href="{{ route('saas.shops.shop_products.post.price', [$shop, $shop_product]) }}"
                                                       data-toggle="tooltip" title="Actualizar Precio y Stock"><i class="fas fa-cloud-upload-alt"></i></a> --}}
                                                @endif
                                                <form class="delete" action="{{ route('saas.shops.shop_products.destroy', [$shop, $shop_product]) }}" method="post">
                                                    @method('delete')
                                                    @csrf
                                                    @include('forms.button_delete', ['title' => 'Eliminar del Marketplace y de la lista'])
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                            <br>

                            {{ $shop_products->appends($params)->render() }}

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
    @include('scripts.autocomplete-suppliercategories', ['suppliers_id' => $suppliers->pluck('id')])
    @include('scripts.autocomplete-marketcategories', ['market_id' => $shop->market->id])
    @include('scripts.autocomplete-products')
    @include('scripts.submit-delete', ['question' => '¿Estás seguro de eliminar este producto del Marketplace?'])
@endpush
