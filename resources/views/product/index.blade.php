@extends('layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@push('menu')
        <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('products.create') }}" class="nav-link">Añadir producto</a></li>
    @role('admin')
        <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('promos.index') }}" class="nav-link">Promociones</a></li>
    @endrole
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-10"><h1>Productos</h1></div>
                    <div class="col-sm-2">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item active">Productos</li>
                        </ol>
                    </div>
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

                            <form method="get" action="{{ route('products.index') }}" class="form-inline">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            @include('forms.ready', ['ready' => $params['ready'] ?? old('ready')])
                                            @include('forms.status_select', ['statuses' => $statuses, 'status_id' => $params['status_id'] ?? old('status_id')])
                                            @include('forms.supplier', ['suppliers' => $suppliers, 'supplier_id' => $params['supplier_id'] ?? old('supplier_id')])
                                            @include('forms.brand', ['brand_id' => $params['brand_id'] ?? old('brand_id'), 'brand_name' => $params['brand_name'] ?? old('brand_name')])
                                            @include('forms.supplier_category', ['supplier_category_id' => $params['supplier_category_id'] ?? old('supplier_category_id'), 'supplier_category_name' => $params['supplier_category_name'] ?? old('supplier_category_name')])
                                            @include('forms.category', ['category_id' => $params['category_id'] ?? old('category_id'), 'category_name' => $params['category_name'] ?? old('category_name')])
                                        </div>
                                        <div class="form-group">
                                            @include('forms.product', ['item_select' => $params['item_select'] ?? old('item_select'),
                                                'product_id' => $params['product_id'] ?? old('product_id'),
                                                'item_reference' => $params['item_reference'] ?? old('item_reference')])
                                            @include('forms.supplier_sku', ['supplierSku' => $params['supplierSku'] ?? old('supplierSku')])
                                            @include('forms.mps_sku', ['MPSSku' => $params['MPSSku'] ?? old('MPSSku')])
                                        </div>
                                        <div class="form-group">
                                            @include('forms.cost_filter', ['cost_min' => $params['cost_min'] ?? old('cost_min'), 'cost_max' => $params['cost_max'] ?? old('cost_max')])
                                            @include('forms.stock_filter', ['stock_min' => $params['stock_min'] ?? old('stock_min'), 'stock_max' => $params['stock_max'] ?? old('stock_max')])
                                            <a class="mr-2 mb-2" href="{{ route('products.index') }}">LIMPIAR</a>
                                            <button class="btn btn-success mb-2" type="submit" value="FILTRAR">FILTRAR</button>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <div class="form-inline">
                                <form method="get" action="{{ route('products.export', [http_build_query($params)]) }}">
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <a href="{{ route('products.export', [http_build_query($params)]) }}" class="btn btn-warning mr-2">EXPORTAR</a>
                                        </div>
                                    </div>
                                </form>

                                @if (isset($provider_filters))
                                    @include('product.advanced_filters', ['params' => $params, 'provider_filters' => $provider_filters])
                                @endif
                            </div>

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
                                        <th>@include('ordersby.products', ['order_by' => 'products.id', 'title' => 'IDs'])</th>
                                        <th class="d-none d-lg-block d-xl-block d-xxl-block">
                                            @include('ordersby.products', ['order_by' => 'suppliers.name', 'title' => 'Proveedor'])<br><br><br>
                                        </th>
                                        <th>
                                            @include('ordersby.products', ['order_by' => 'supplier_categories.name', 'title' => 'Cat Prov'])<br>
                                            @include('ordersby.products', ['order_by' => 'categories.name', 'title' => 'Cat MPe'])<br>
                                            @include('ordersby.products', ['order_by' => 'brands.name', 'title' => 'Marca'])
                                        </th>
                                        <th>
                                            @include('ordersby.products', ['order_by' => 'products.cost', 'title' => 'Coste'])<br>
                                            @include('ordersby.products', ['order_by' => 'products.stock', 'title' => 'Stock'])
                                        </th>
                                        <th>@include('ordersby.products', ['order_by' => 'products.name', 'title' => 'Título'])</th>
                                        <th class="d-none d-lg-block d-xl-block d-xxl-block w-9rem">
                                            @include('ordersby.products', ['order_by' => 'products.updated_at', 'title' => 'Actual.'])<br>
                                            @include('ordersby.products', ['order_by' => 'products.created_at', 'title' => 'Creado'])<br><br>
                                        </th>
                                        <th>Acciones</th>
                                    </tr>
                                {{-- </thead> --}}

                                @foreach($products as $product)

                                    <tr product-id="{{ $product->id }}">
                                        <td class="img-list">
                                            <img src="{{ $product->getFirstImageFullUrl() ?? $product->parent->getFirstImageFullUrl() ?? '' }}">
                                        </td>
                                        <td><span class="font-weight-bold mps-sku">{{ $product->getMPSSku() }}</span><br>
                                            <small><span class="font-weight-bold {{ ($product->pn || $product->parent_id) ? '' : 'text-danger' }}">P/N:</span> {{ $product->pn ?? $product->parent->pn ?? '' }}</small><br>
                                                <small><span class="font-weight-bold {{ ($product->ean || $product->parent_id) ? '' : 'text-danger' }}">EAN:</span> {{ $product->ean ?? $product->parent->ean ?? '' }}</small></td>
                                        <td class="d-none d-lg-block d-xl-block d-xxl-block">
                                            <small>
                                                {{ $product->supplier_name }}<br>{{ $product->supplierSku }}
                                            </small>
                                        </td>
                                        <td style="width:11rem">
                                            <small>
                                                {!! $product->supplier_category_id ? mb_substr($product->supplier_category->name, -25) : '---' !!}<br>
                                                {!! $product->category_id ?
                                                        '<span class="text-success">'.mb_substr($product->category_name, -25).'</span>' :
                                                        '<span class="text-danger">NO_CATEGORY</span>' !!}<br>
                                                {!! $product->brand_name ?? $product->parent->brand->name ?? '<span class="text-danger">NO BRAND</span>' !!}
                                            </small>
                                        </td>
                                        <td>
                                            {{ $product->cost }}€<br>
                                            {{ $product->stock }}
                                        </td>
                                        <td>
                                            <small>
                                            <a class="mr-2 {{ $product->ready ? '' : 'text-danger' }}" href="{{ route('products.show', [$product]) }}">
                                                {!! substr($product->name, 0, 120) ?? substr($product->parent->name, 0, 120) ?? '' !!}</a>
                                            </small>
                                        </td>
                                        <td colspan="" class="d-none d-lg-block d-xl-block d-xxl-block">
                                            <small>
                                            {{ $product->updated_at->format('Y-m-d H:i') }}<br>
                                            {{ $product->created_at->format('Y-m-d H:i') }}
                                            </small>
                                        </td>
                                        <td>
                                            <a class="mr-2 {{ isset($product->provider_id) ? '' : 'text-danger' }}"
                                                href="{{ route('products.attributes', [$product]) }}" data-toggle="tooltip" title="Atributos">
                                                <i class="fas fa-align-justify"></i></a>
                                            <a class="mr-2"
                                                href="{{ route('products.relateds', [$product]) }}" data-toggle="tooltip" title="Relacionados">
                                                <i class="fas fa-expand-arrows-alt"></i></a>
                                            <a class="mr-2"
                                                href="{{ route('products.addtoshop', [$product]) }}" data-toggle="tooltip" title="Añadir a Tienda">
                                                    <i class="fas fa-plus-circle"></i></a>

                                            @role('admin')
                                                <a class="mr-2"
                                                href="{{ route('products.shops', [$product]) }}" data-toggle="tooltip" title="Tiendas">
                                                    <i class="fas fa-store"></i></a>
                                                <a class="mr-2" href="{{ route('suppliers.products.get.product', [$product->supplier ?? null, $product]) }}" data-toggle="tooltip" title="Re Importar">
                                                    <i class="fas fa-cloud-download-alt"></i></a>
                                                <a class="" href="{{ route('products.scrape', [$product]) }}" data-toggle="tooltip" title="Scrape">
                                                    <i class="fas fa-download"></i></a>
                                                <form style="display:inline;" class="delete" action="{{ route('products.destroy', [$product]) }}" method="post">
                                                    @method('delete')
                                                    @csrf
                                                    {{-- @include('forms.a_delete', ['title' => 'Eliminar']) --}}
                                                    @include('forms.button_delete', ['title' => 'Eliminar'])
                                                </form>
                                            @endrole
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
    @include('scripts.autocomplete-brands')
    @include('scripts.autocomplete-suppliercategories', ['suppliers_id' => $suppliers->pluck('id')])
    @include('scripts.autocomplete-categories')
    @include('scripts.autocomplete-products')
    @include('scripts.submit-a-delete', ['question' => '¿Estás seguro de eliminar este producto?'])
   {{--  @include('scripts.products-filters') --}}
@endpush
