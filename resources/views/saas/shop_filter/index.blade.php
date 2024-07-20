@extends('saas.layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@push('menu')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('saas.shops.shop_params', [$shop]) }}" class="nav-link">Precios y Stocks</a></li>
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('saas.shops.shop_products', [$shop]) }}" class="nav-link">Productos</a></li>
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-12"><h1>FILTROS: ({{ $shop->market->name }}) {{ $shop->name }}</h1></div>
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

                            @if (isset($shop_filter))
                                <form method="post" action="{{ route('saas.shops.shop_filters.update', [$shop, $shop_filter]) }}" class="form-inline">
                                    @method('PATCH')
                            @else
                                <form method="post" action="{{ route('saas.shops.shop_filters.store', [$shop]) }}" class="form-inline">
                            @endif
                                @csrf

                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            @include('forms.status_select', ['statuses' => $statuses, 'status_id' => $shop_filter->status_id ?? old('status_id')])
                                            @include('forms.supplier', ['suppliers' => $suppliers, 'supplier_id' => $shop_filter->supplier_id ?? old('supplier_id')])
                                            @include('forms.supplier_category', [
                                                'supplier_category_id' => $shop_filter->supplier_category_id ?? old('supplier_category_id'),
                                                'supplier_category_name' => $shop_filter->supplier_category->name ?? old('supplier_category_name')
                                            ])
                                            @include('forms.supplier_brand', [
                                                'supplier_brand_id' => $shop_filter->supplier_brand_id ?? old('supplier_brand_id'),
                                                'supplier_brand_name' => $shop_filter->supplier_brand->name ?? old('supplier_brand_name')
                                            ])
                                            @include('forms.product', [
                                                'item_select' => isset($shop_filter->product_id) ? 'name' : null,
                                                'product_id' => $shop_filter->product_id ?? old('product_id'),
                                                'item_reference' => isset($shop_filter->product_id) ? $shop_filter->product->name : null,
                                                ])
                                            @include('forms.cost_filter', ['cost_min' => $shop_filter->cost_min ?? old('cost_min'), 'cost_max' => $shop_filter->cost_max ?? old('cost_max')])
                                            @include('forms.stock_filter', ['stock_min' => $shop_filter->stock_min ?? old('stock_min'), 'stock_max' => $shop_filter->stock_max ?? old('stock_max')])
                                            @include('forms.limit-products', ['limit_products' => $shop_filter->limit_products ?? old('limit_products')])
                                            <button class="btn btn-success mb-2" type="submit">Guardar filtro</button>
                                        </div>
                                    </div>
                                </div>
                            </form>

                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">FILTROS</div>
                        <div class="card-body">

                            <p>Hay {{ count($shop_filters) }} filtros</p>
                            <a href="{{ route('saas.shops.shop_filters.filter', [$shop]) }}" class="btn btn-primary">Ejecutar filtros</a>
                            <br><br>

                            <table class="table table-striped">
                                <tr class="table-warning">
                                    <th>Proveedor<br>SKU</th>
                                    <th>Categoría<br>Marca</th>
                                    <th>Producto</th>
                                    <th></th>
                                    <th>Coste mín<br>máx</th>
                                    <th>Stock mín<br>máx</th>
                                    <th>Estado</th>

                                    <th>Límite</th>
                                    <th>Acciones</th>
                                </tr>

                                @foreach($shop_filters as $shop_filter)
                                    <tr>
                                        <td>{{ $shop_filter->supplier_name ?? '' }}<br>
                                        @if (isset($shop_filter->product_id)) {{ $shop_filter->product->supplierSku }} @endif
                                        </td>
                                        <td style="width:11rem">
                                            <small>
                                                {{ $shop_filter->supplier_category_id ? mb_substr($shop_filter->supplier_category->name, -25) : '' }}<br>
                                                {{ $shop_filter->supplier_brand->name ?? null }}
                                            </small>
                                        </td>
                                        @if(isset($shop_filter->product_id))
                                            <td>
                                                <span class="badge bg-success">{{ $shop_filter->product_id }}</span><br>
                                                <small><span class="font-weight-bold {{ ($shop_filter->product->pn) ? '' : 'text-danger' }}">P/N:</span> {{ $shop_filter->product->pn ?? '' }}</small><br>
                                                <small><span class="font-weight-bold {{ ($shop_filter->product->ean) ? '' : 'text-danger' }}">EAN:</span> {{ $shop_filter->product->ean ?? '' }}</small>
                                            </td>
                                            <td>
                                                <small>
                                                <a class="mr-2 {{ $shop_filter->product->ready ? '' : 'text-danger' }}" href="{{ route('saas.products.show', [$shop_filter->product]) }}">
                                                    {!! substr($shop_filter->product->name, 0, 200) ?? '' !!}</a>
                                                </small>
                                            </td>
                                        @else
                                            <td></td><td></td>
                                        @endif
                                        <td>{{ $shop_filter->cost_min }}<br>{{ $shop_filter->cost_max }}</td>
                                        <td>{{ $shop_filter->stock_min }}<br>{{ $shop_filter->stock_max }}</td>
                                        <td>{{ $shop_filter->status->name ?? '' }}</td>
                                        <td>{{ $shop_filter->limit_products }}</td>
                                        <td class="form-inline">
                                            <a class=""
                                                href="{{ route('saas.shops.shop_filters.edit', [$shop, $shop_filter]) }}" data-toggle="tooltip" title="Editar">
                                                <i class="far fa-edit"></i></a>
                                            <a class="ml-2"
                                                href="{{ route('saas.shops.shop_filters.addtoshop', [$shop, $shop_filter]) }}" data-toggle="tooltip" title="Ejecutar filtro">
                                                    <i class="fas fa-plus-circle"></i></a>
                                            <form class="delete" action="{{ route('saas.shops.shop_filters.destroy', [$shop, $shop_filter]) }}" method="post">
                                                @method('delete')
                                                @csrf
                                                @include('forms.button_delete', ['title' => 'Quitar filtro'])
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>

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
    @include('scripts.autocomplete-suppliercategories', ['suppliers_id' => $suppliers->pluck('id')])
    @include('scripts.autocomplete-products', ['suppliers_id' => $suppliers->pluck('id')])
    @include('scripts.submit-a-delete', ['question' => '¿Estás seguro de eliminar este filtro?'])
@endpush
