@extends('layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@push('menu')
    <li class="nav-item d-none d-sm-inline-block">
        <a href="{{ route('shops.shop_params.index', [$shop]) }}" class="nav-link"><i class="fas fa-euro-sign"></i> Márgenes y parámetros</a>
    </li>
    <li class="nav-item d-none d-sm-inline-block">
        <a href="{{ route('shops.shop_products.index', [$shop]) }}" class="nav-link"><i class="fas fa-laptop"></i> Productos</a>
    </li>
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-8"><h1>FILTROS: ({{ $shop->market->name }}) {{ $shop->name }}</h1></div>
                    <div class="col-sm-4">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item active">Operaciones</li>
                            <li class="breadcrumb-item"><a href="{{ route('action.shops') }}">Tiendas</a></li>
                            <li class="breadcrumb-item active">Filtros</li>
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


                            @if (isset($shop_filter))
                                <form method="post" action="{{ route('shops.shop_filters.update', [$shop, $shop_filter]) }}" class="form-inline">
                                    @method('PATCH')
                            @else
                                <form method="post" action="{{ route('shops.shop_filters.store', [$shop]) }}" class="form-inline">
                            @endif
                                @csrf

                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            @include('forms.status_select', ['statuses' => $statuses, 'status_id' => $shop_filter->status_id ?? old('status_id')])
                                            @include('forms.supplier', ['suppliers' => $suppliers, 'supplier_id' => $shop_filter->supplier_id ?? old('supplier_id')])
                                            @include('forms.category', [
                                                'category_id' => $shop_filter->category_id ?? old('category_id'),
                                                'category_name' => $shop_filter->category->name ?? old('category_name')])
                                            @include('forms.brand', [
                                                'brand_id' => $shop_filter->brand_id ?? old('brand_id'),
                                                'brand_name' => $shop_filter->brand->name ?? old('brand_name')])
                                            @include('forms.supplier_sku', ['supplierSku' => null])
                                            @include('forms.product', [
                                                'item_select' => isset($shop_filter->product_id) ? 'name' : null,
                                                'product_id' => $shop_filter->product_id ?? old('product_id'),
                                                'item_reference' => isset($shop_filter->product_id) ? $shop_filter->product->name : null,
                                                ])
                                            @include('forms.mps_sku', ['MPSSku' => null])
                                            @include('forms.model', ['model' => null])
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
                            <a href="{{ route('shops.shop_filters.filter', [$shop]) }}" class="btn btn-primary">Ejecutar filtros</a>
                            <br><br>

                            <table class="table table-striped">
                                <tr class="table-warning">
                                    <th>Proveedor</th>
                                    <th>Categoría</th>
                                    <th>Marca</th>
                                    <th>Producto</th>
                                    <th>Coste mínimo</th>
                                    <th>Coste máximo</th>
                                    <th>Stock mínimo</th>
                                    <th>Stock máximo</th>
                                    <th>Estado</th>

                                    <th>Limit</th>
                                    <th>Acciones</th>
                                </tr>

                                @foreach($shop_filters as $shop_filter)
                                    <tr>
                                        <td>{{ $shop_filter->supplier_name ?? '' }}</td>
                                        <td>{{ $shop_filter->category_name ?? '' }}</td>
                                        <td>{{ $shop_filter->brand_name ?? '' }}</td>
                                        <td>{{ isset($shop_filter->product_id) ?
                                            '('. $shop_filter->product->getMPSSku() .') '. $shop_filter->product_name : '' }}</td>
                                        <td>{{ $shop_filter->cost_min }}</td>
                                        <td>{{ $shop_filter->cost_max }}</td>
                                        <td>{{ $shop_filter->stock_min }}</td>
                                        <td>{{ $shop_filter->stock_max }}</td>
                                        <td>{{ $shop_filter->status->name ?? '' }}</td>
                                        <td>{{ $shop_filter->limit_products }}</td>
                                        <td class="form-inline">
                                            <a class=""
                                                href="{{ route('shops.shop_filters.edit', [$shop, $shop_filter]) }}" data-toggle="tooltip" title="Editar">
                                                <i class="far fa-edit"></i></a>
                                            <a class="ml-2"
                                                href="{{ route('shops.shop_filters.addtoshop', [$shop, $shop_filter]) }}" data-toggle="tooltip" title="Ejecutar filtro">
                                                    <i class="fas fa-plus-circle"></i></a>
                                            <form class="delete" action="{{ route('shops.shop_filters.destroy', [$shop, $shop_filter]) }}" method="post">
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
    @include('scripts.autocomplete-brands')
    @include('scripts.autocomplete-categories')
    @include('scripts.autocomplete-products')
    @include('scripts.submit-a-delete', ['question' => '¿Estás seguro de eliminar este filtro?'])
@endpush
