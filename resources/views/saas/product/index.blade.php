@extends('saas.layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@push('menu')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('saas.products.create') }}" class="nav-link">Añadir producto</a></li>
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-12"><h1>Productos</h1></div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="row">
                <div class="col-12 col-sm-12">
                    <div class="card">
                        <div class="card-body">

                            @include('partials.status')
                            @include('partials.errors')

                            <form method="get" action="{{ route('saas.products') }}" class="form-inline">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            @include('forms.ready', ['ready' => $params['ready'] ?? old('ready')])
                                            @include('forms.status_select', ['statuses' => $statuses, 'status_id' => $params['status_id'] ?? old('status_id')])
                                            @include('forms.supplier', ['suppliers' => $suppliers, 'supplier_id' => $params['supplier_id'] ?? old('supplier_id')])
                                            @include('forms.supplier_brand', ['supplier_brand_id' => $params['supplier_brand_id'] ?? old('supplier_brand_id'), 'supplier_brand_name' => $params['supplier_brand_name'] ?? old('supplier_brand_name')])
                                            @include('forms.supplier_category', ['supplier_category_id' => $params['supplier_category_id'] ?? old('supplier_category_id'), 'supplier_category_name' => $params['supplier_category_name'] ?? old('supplier_category_name')])
                                        </div>
                                        <div class="form-group">
                                            @include('forms.product', ['item_select' => $params['item_select'] ?? old('item_select'),
                                                'product_id' => $params['product_id'] ?? old('product_id'),
                                                'item_reference' => $params['item_reference'] ?? old('item_reference')])
                                            @include('forms.cost_filter', ['cost_min' => $params['cost_min'] ?? old('cost_min'), 'cost_max' => $params['cost_max'] ?? old('cost_max')])
                                            @include('forms.stock_filter', ['stock_min' => $params['stock_min'] ?? old('stock_min'), 'stock_max' => $params['stock_max'] ?? old('stock_max')])
                                            <a class="mr-2 mb-2" href="{{ route('saas.products') }}">LIMPIAR</a>
                                            <button class="btn btn-success mb-2" type="submit" value="FILTRAR">FILTRAR</button>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            {{-- <div class="form-inline">
                                <form method="get" action="{{ route('saas.products.export', [http_build_query($params)]) }}">
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <a href="{{ route('saas.products.export', [http_build_query($params)]) }}" class="btn btn-warning mr-2">EXPORTAR</a>
                                        </div>
                                    </div>
                                </form>
                            </div> --}}

                            <p>Hay {{ $products->total() }} productos</p>
                            {!! $products->appends($params)->render() !!}
                            <table id="table_ordened" class="table table-striped products-list">
                                {{-- <thead class=""> --}}
                                    <tr class="table-warning">
                                        @php
                                            $new_params = $params ?? [];
                                            $new_params['order'] = ($params['order'] == 'asc') ? 'desc' : 'asc';
                                        @endphp
                                        <th></th>
                                        <th>@include('saas.ordersby.products', ['order_by' => 'products.id', 'title' => 'IDs'])</th>
                                        <th class="d-none d-lg-block d-xl-block d-xxl-block">
                                            @include('saas.ordersby.products', ['order_by' => 'suppliers.name', 'title' => 'Proveedor'])<br>
                                            @include('saas.ordersby.products', ['order_by' => 'products.supplierSku', 'title' => 'SKU'])
                                        </th>
                                        <th>
                                            @include('saas.ordersby.products', ['order_by' => 'supplier_categories.name', 'title' => 'Categoría'])<br>
                                            @include('saas.ordersby.products', ['order_by' => 'supplier_brands.name', 'title' => 'Marca'])
                                        </th>
                                        <th>
                                            @include('saas.ordersby.products', ['order_by' => 'products.cost', 'title' => 'Coste'])<br>
                                            @include('saas.ordersby.products', ['order_by' => 'products.stock', 'title' => 'Stock'])
                                        </th>
                                        <th>@include('saas.ordersby.products', ['order_by' => 'products.name', 'title' => 'Título'])</th>
                                        <th class="d-none d-lg-block d-xl-block d-xxl-block">
                                            @include('saas.ordersby.products', ['order_by' => 'products.updated_at', 'title' => 'Actual.'])<br>
                                            @include('saas.ordersby.products', ['order_by' => 'products.created_at', 'title' => 'Creado'])
                                        </th>
                                        <th>Acciones</th>
                                    </tr>
                                {{-- </thead> --}}

                                @foreach($products as $product)

                                    <tr product-id="{{ $product->id }}">
                                        <td class="img-list">
                                            <img src="{{ $product->getFirstImageFullUrl() ?? $product->parent->getFirstImageFullUrl() ?? '' }}">
                                        </td>
                                        <td><span class="badge bg-success">{{ $product->id }}</span><br>
                                            <small><span class="font-weight-bold {{ ($product->pn || $product->parent_id) ? '' : 'text-danger' }}">P/N:</span> {{ $product->pn ?? $product->parent->pn ?? '' }}</small><br>
                                                <small><span class="font-weight-bold {{ ($product->ean || $product->parent_id) ? '' : 'text-danger' }}">EAN:</span> {{ $product->ean ?? $product->parent->ean ?? '' }}</small>
                                        </td>
                                        <td class="d-none d-lg-block d-xl-block d-xxl-block">
                                            <small>
                                                {{ $product->supplier_name }}<br>{{ $product->supplierSku }}
                                            </small>
                                        </td>
                                        <td style="width:11rem">
                                            <small>
                                                {!! $product->supplier_category_id ? mb_substr($product->supplier_category->name, -30) : '---' !!}<br>
                                                {!! $product->supplier_brand_id ? '<span class="text-success">'.$product->supplier_brand->name.'</span>' : '<span class="text-danger">NO BRAND</span>' !!}
                                            </small>
                                        </td>
                                        <td>
                                            {{ $product->cost }}€<br>
                                            {{ $product->stock }}
                                        </td>
                                        <td>
                                            <small>
                                            <a class="mr-2 {{ $product->ready ? '' : 'text-danger' }}" href="{{ route('saas.products.show', [$product]) }}">
                                                {!! substr($product->name, 0, 200) ?? substr($product->parent->name, 0, 200) ?? '' !!}</a>
                                            </small>
                                        </td>
                                        <td colspan="" class="d-none d-lg-block d-xl-block d-xxl-block">
                                            <small>
                                            {{ $product->updated_at->format('Y-m-d H:i') }}<br>
                                            {{ $product->created_at->format('Y-m-d H:i') }}
                                            </small>
                                        </td>
                                        <td>
                                            <a class="mr-2"
                                                href="{{ route('saas.products.shops', [$product]) }}" data-toggle="tooltip" title="Tiendas">
                                                <i class="fas fa-store"></i></a>
                                            <form style="display:inline;" class="delete" action="{{ route('saas.products.destroy', [$product]) }}" method="post">
                                                @method('delete')
                                                @csrf
                                                @include('forms.button_delete', ['title' => 'Eliminar'])
                                            </form>
                                        </td>
                                    </tr>

                                @endforeach

                            </table>
                            <br>
                            {!! $products->appends($params)->render() !!}

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
    @include('scripts.submit-a-delete', ['question' => '¿Estás seguro de eliminar este producto?'])
@endpush

