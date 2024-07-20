@extends('saas.layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@push('menu')
    <li class="nav-item d-none d-sm-inline-block">
        <a href="{{ route('saas.shops.shop_params.sync', [$shop]) }}" class="btn btn-danger">IMPORTANTE: SINCRONIZAR TABLAS DESPUÉS DE MODIFICAR</a>
    </li>
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('saas.shops.shop_filters', [$shop]) }}" class="nav-link"> Filtros</a></li>
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('saas.shops.shop_products', [$shop]) }}" class="nav-link"> Productos</a></li>
@endpush

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-12"><h1>AÑADIR PARÁMETRO PARA LA TIENDA: ({{ $shop->market->name }}) {{ $shop->name }}</h1></div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="row">
                <div class="col-12">

                    <div class="card">
                        <div class="card-body">

                            @include('partials.status')
                            @include('partials.errors')

                            @if (isset($shop_param))
                                <form method="post" action="{{ route('saas.shops.shop_params.update', [$shop, $shop_param]) }}">
                                    @method('PATCH')
                            @else
                                <form method="post" action="{{ route('saas.shops.shop_params.store', [$shop]) }}">
                            @endif
                                @csrf

                                <p>Parámetros para el cálculo de los Precios y Stocks de productos de esta Tienda CONDICIONADOS
                                    por Producto, Proveedor, Marca o Categoría, por este orden de preferencia.</p>

                                <p class="text-success"><b>PARÁMETROS CONDICIONANTES</b></p>

                                <div class="form-group row">
                                    <label for="supplier_id" class="col-sm-2 col-form-label">Proveedor | Producto</label>
                                    <div class="col-sm-2">
                                        @include('forms.supplier', ['suppliers' => $suppliers, 'supplier_id' => $shop_param->supplier_id ?? old('supplier_id')])
                                    </div>
                                    <div class="col-sm-8">
                                        @include('forms.product', [
                                            'item_select' => (isset($shop_param->pn) ? 'pn' :
                                                (isset($shop_param->ean) ? 'ean' :
                                                (isset($shop_param->upc) ? 'upc' :
                                                (isset($shop_param->isbn) ? 'isbn' :
                                                (isset($shop_param->gtin) ? 'gtin' : 'name'
                                            ))))),
                                            'product_id' => $shop_param->product_id ?? old('product_id'),
                                            'item_reference' => $shop_param->pn ??
                                                $shop_param->ean ??
                                                $shop_param->upc ??
                                                $shop_param->isbn ??
                                                $shop_param->gtin ??
                                                (isset($shop_param->product_id) ? $shop_param->product->name : old('item_reference')) ])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="brand_id" class="col-sm-2 col-form-label">Marca | categorías</label>
                                    <div class="col-sm-2">
                                        @include('forms.supplier_brand', ['supplier_brand_id' => $shop_param->supplier_brand_id ?? old('supplier_brand_id'), 'supplier_brand_name' => $shop_param->supplier_brand->name ?? old('supplier_brand_name')])
                                    </div>
                                    <div class="col-sm-3">
                                        @include('forms.supplier_category', ['supplier_category_id' => $shop_param->supplier_category_id ?? old('supplier_category_id'), 'supplier_category_name' => $shop_param->supplier_category->name ?? old('supplier_category_name')])
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.root_category', [
                                            'root_categories' => $root_categories,
                                            'root_category_id' => $shop_param->root_category_id ?? old('root_category_id')])
                                    </div>
                                    <div class="col-sm-3">
                                        @include('forms.market_category', [
                                            'market_category_id' => $shop_param->market_category_id ?? old('market_category_id'),
                                            'market_category_name' => $shop_param->market_category->name ?? old('market_category_name')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="cost_min" class="col-sm-2 col-form-label">Coste mín | Máx</label>
                                    <div class="col-sm-4 form-inline">
                                        @include('forms.cost_filter', ['cost_min' => $shop_param->cost_min ?? old('cost_min'), 'cost_max' => $shop_param->cost_max ?? old('cost_max')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="starts_at" class="col-sm-2 col-form-label">Fecha inicio | fin</label>
                                    <div class="col-sm-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                            </div>
                                        <input type="text" name="starts_at" id="starts_at" placeholder="yyyy-mm-dd"
                                            class="form-control" class="form-control mr-2"
                                            value="{{ $shop_param->starts_at ?? old('starts_at') }}">
                                        </div>
                                        <small>Fechas en las que se aplicarán estos parámetros.</small>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                            </div>
                                            <input type="text" name="ends_at" id="ends_at"  placeholder="yyyy-mm-dd"
                                                class="form-control mr-2"
                                                value="{{ $shop_param->ends_at ?? old('ends_at') }}">
                                        </div>
                                    </div>
                                </div>

                                <p class="text-success"><b>PARÁMETROS DE EXPORTACIÓN A MARKETPLACES: CÁLCULO DEL PRECIO FINAL Y STOCKS</b></p>

                                <div class="form-group row">
                                    <label for="fee" class="col-sm-2 col-form-label">% Rappel | € Portes</label>
                                    {{-- <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'canon', 'value' => $shop_param->canon ?? old('canon')])
                                        <small>Canon del coste €.</small>
                                    </div> --}}
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'rappel', 'value' => $shop_param->rappel ?? old('rappel')])
                                        <small>Rappel del coste %.</small>
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'ports', 'value' => $shop_param->ports ?? old('ports')])
                                        <small>Portes del coste (sin IVA) €.</small>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="fee" class="col-sm-2 col-form-label">% Bcio | € Bcio Mín | % RePrice Mín</label>
                                    <div class="col-sm-2">
                                        @include('forms.fee', ['fee' => $shop_param->fee ?? old('fee')])
                                        <small>Margen de beneficio %.</small>
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.bfit_min', ['bfit_min' => $shop_param->bfit_min ?? old('bfit_min')])
                                        <small>Beneficio € mínimo por producto.</small>
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.reprice_fee_min', ['reprice_fee_min' => $shop_param->reprice_fee_min ?? old('reprice_fee_min')])
                                        <small>Mínimo % de Beneficio para RePrice.</small>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="fee" class="col-sm-2 col-form-label">€ Precio MP | Stock MP</label>
                                    <div class="col-sm-2">
                                        @include('forms.price', ['price' => $shop_param->price ?? old('price')])
                                        <small>PVP € fijado para esta Tienda</small>
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'discount_price', 'value' => $shop_param->discount_price ?? old('discount_price'), 'placeholder' => 'Precio final con descuento'])
                                        <small>PVP € de descuento fijado - Precio tachado</small>
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.stock', ['stock' => $shop_param->stock ?? old('stock')])
                                        <small>Stock fijado para esta Tienda</small>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="stock_min" class="col-sm-2 col-form-label">Stock mínimo y Máximo</label>
                                    <div class="col-sm-4 form-inline">
                                        @include('forms.stock_filter', ['stock_min' => $shop_param->stock_min ?? old('stock_min'), 'stock_max' => $shop_param->stock_max ?? old('stock_max')])
                                        <small>No se publicará en la Tienda si tiene menos de STOCK MÍNIMO.<br>Si Stock > STOCK MÁXIMO luego Stock Tienda = STOCK MÁXIMO.</small>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <div class="col-sm-2"></div>
                                    <div class="col-sm-10">
                                        <button type="submit" class="btn btn-primary">Guardar parámetro a ({{ $shop->market->name }}) {{ $shop->name }}</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">PARÁMETROS DE LA TIENDA ({{ strtoupper($shop->market->name) }}) {{ strtoupper($shop->name) }}</div>
                        <div class="card-body">
                            <div class="panel panel-default">
                                <div class="panel-body">
                                    <p>Hay {{ count($shop_params) }} parámetros</p>
                                    <br>
                                    <table class="table table-striped">
                                        <tr class="table-warning">
                                            <th>#</th>
                                            <th>Proveedor</th>
                                            <th>Producto</th>
                                            <th>Marca</th>
                                            <th>Root | Cat MP | Cat</th>
                                            <th>Coste Mín<br>Max</th>
                                            <th>Fecha inicio<br>Fin</th>
                                            <th>Rappel<br>Ports</th>
                                            <th>Bcio<br>Mín<br>RePrice</th>
                                            <th>PVP<br>Descuento<br>Stock fijo</th>
                                            <th>Stock Min<br>Max</th>
                                            <th>Acciones</th>
                                        </tr>
                                        @foreach($shop_params as $shop_param)
                                            <tr shop_param-id="{{ $shop_param->id }}">
                                                <td>{{ $shop_param->id }}</td>
                                                <td>{{ $shop_param->supplier->name ?? null }}</td>
                                                <td>{{ isset($shop_param->supplierSku) ? ('SKU: '.$shop_param->supplierSku) :
                                                    (isset($shop_param->product_id) ? substr($shop_param->product->name ?? null, 0, 20) :
                                                    (isset($shop_param->pn) ? ('PN: '.$shop_param->pn) :
                                                    (isset($shop_param->ean) ? ('EAN: '.$shop_param->ean) :
                                                    (isset($shop_param->upc) ? ('UPC: '.$shop_param->upc) :
                                                    (isset($shop_param->isbn) ? ('ISBN: '.$shop_param->isbn) :
                                                    (isset($shop_param->gtin) ? ('GTIN: '.$shop_param->gtin) :
                                                    '' )))))) }}</td>
                                                <td>{{ $shop_param->supplier_brand_id ? $shop_param->supplier_brand->name : '' }}</td>
                                                <td>{{ $shop_param->root_category_id ? '('.$shop_param->root_category->marketCategoryId.') '.$shop_param->root_category->name : '' }}
                                                    {{ $shop_param->market_category_id ? '| ('.$shop_param->market_category->marketCategoryId.') '.$shop_param->market_category->name : '' }}
                                                    {{ $shop_param->supplier_category_id ? '| '.$shop_param->supplier_category_id.' ('.$shop_param->supplier_category->code.') '.$shop_param->supplier_category->name : '' }}</td>
                                                <td>{{ $shop_param->cost_min ? $shop_param->cost_min.' €' : '' }}<br>
                                                    {{ $shop_param->cost_max ? $shop_param->cost_max.' €' : '' }}</td>
                                                <td>{{ isset($shop_param->starts_at) ? $shop_param->starts_at->format('Y-m-d') : '' }}<br>
                                                    {{ isset($shop_param->ends_at) ? $shop_param->ends_at->format('Y-m-d') : '' }}</td>
                                                <td>{{ $shop_param->rappel != 0 ? $shop_param->rappel.' %' : '' }}<br>
                                                    {{ $shop_param->ports != 0 ? $shop_param->ports.' €' : '' }}</td>
                                                <td>{{ $shop_param->fee != 0 ? $shop_param->fee.' %' : '' }}<br>
                                                    {{ $shop_param->bfit_min != 0 ? $shop_param->bfit_min.' €' : '' }}<br>
                                                    {{ $shop_param->reprice_fee_min != 0 ? $shop_param->reprice_fee_min.' %' : '' }}</td>
                                                <td>{{ $shop_param->price != 0 ? $shop_param->price.' €' : '' }}<br>
                                                    {{ $shop_param->discount_price != 0 ? $shop_param->discount_price.' €' : '' }}<br>
                                                    {{ $shop_param->stock != 0 ? $shop_param->stock : '' }}
                                                </td>
                                                <td>{{ $shop_param->stock_min != 0 ? $shop_param->stock_min : '' }}<br>
                                                    {{ $shop_param->stock_max != 0 ? $shop_param->stock_max : '' }}</td>
                                                <td class="row">
                                                    <a class="mr-2"
                                                        href="{{ route('saas.shops.shop_params.edit', [$shop, $shop_param]) }}" data-toggle="tooltip" title="Editar">
                                                        <i class="far fa-edit"></i></a>
                                                    {{-- <form class="delete" action="{{ route('saas.shops.shop_params.destroy', [$shop, $shop_param]) }}" method="post">
                                                        @method('delete')
                                                        @csrf
                                                        @include('forms.a_delete', ['title' => 'Quitar parámetro'])
                                                    </form> --}}

                                                    <form class="delete" action="{{ route('saas.shops.shop_params.destroy', [$shop, $shop_param]) }}" method="post">
                                                        @method('delete')
                                                        @csrf
                                                        @include('forms.button_delete', ['title' => 'Quitar parámetro'])
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>
    </div>
@endsection
@push('scriptsEnd')
    @include('scripts.jquery-ui')
    @include('scripts.autocomplete-supplierbrands', ['suppliers_id' => $suppliers->pluck('id')])
    @include('scripts.autocomplete-marketcategories', ['market_id' => $shop->market_id])
    @include('scripts.autocomplete-suppliercategories', ['suppliers_id' => $suppliers->pluck('id')])
    @include('scripts.autocomplete-products', ['suppliers_id' => $suppliers->pluck('id')])
    @include('scripts.submit-a-delete', ['question' => '¿Estás seguro de quitar este parámetro?'])

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.15/jquery.mask.min.js"></script>
    <script>
        $(function() {
            $('#starts_at').mask('0000-00-00');
            $('#ends_at').mask('0000-00-00');
        });
    </script>

@endpush
