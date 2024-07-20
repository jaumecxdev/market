@extends('layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@push('menu')
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
                <div class="col-12">
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
                                            @include('forms.category', ['category_id' => $params['category_id'] ?? old('category_id'), 'category_name' => $params['category_name'] ?? old('category_name')])
                                            @include('forms.brand', ['brand_id' => $params['brand_id'] ?? old('brand_id'), 'brand_name' => $params['brand_name'] ?? old('brand_name')])
                                            @include('forms.product', ['item_select' => $params['item_select'] ?? old('item_select'),
                                                'product_id' => $params['product_id'] ?? old('product_id'),
                                                'item_reference' => $params['item_reference'] ?? old('item_reference')])
                                            @include('forms.supplier_sku', ['supplierSku' => $params['supplierSku'] ?? old('supplierSku')])
                                            @include('forms.mps_sku', ['MPSSku' => $params['MPSSku'] ?? old('MPSSku')])
                                            @include('forms.cost_filter', ['cost_min' => $params['cost_min'] ?? old('cost_min'), 'cost_max' => $params['cost_max'] ?? old('cost_max')])
                                            @include('forms.stock_filter', ['stock_min' => $params['stock_min'] ?? old('stock_min'), 'stock_max' => $params['stock_max'] ?? old('stock_max')])
                                            <a class="mr-2 mb-2" href="{{ route('products.index') }}">LIMPIAR</a>
                                            <button class="btn btn-success mb-2" type="submit" value="FILTRAR">FILTRAR</button>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            @if (method_exists($products, 'total'))
                                <p>Hay {{ $products->total() }} productos</p>
                                {!! $products->appends($params)->render() !!}
                            @endif
                            <table id="table_ordened" class="table table-striped products-list">
                                <tr class="table-warning">
                                    @php
                                        $new_params = $params ?? [];
                                        $new_params['order'] = ($params['order'] == 'asc') ? 'desc' : 'asc';
                                    @endphp
                                    <th></th>
                                    <th>@include('ordersby.products', ['order_by' => 'products.id', 'title' => 'IDs'])</th>
                                    <th>@include('ordersby.products', ['order_by' => 'suppliers.name', 'title' => 'Proveedor'])</th>
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
                                    <th>@include('ordersby.products', ['order_by' => 'products.updated_at', 'title' => 'Actual.'])</th>
                                    <th>@include('ordersby.products', ['order_by' => 'products.created_at', 'title' => 'Creado'])</th>
                                    <th>Acciones</th>
                                </tr>
                                @foreach($products as $product)

                                    <tr product-id="{{ $product->id }}">
                                        <td class="img-list">
                                            @foreach($product->url_images() as $image_url)
                                                <img src="{{ $image_url }}">
                                            @endforeach
                                        </td>
                                        <td><span class="font-weight-bold mps-sku">{{ $product->getMPSSku() }}</span><br>
                                            <span class="{{ ($product->pn || $product->parent_id) ? '' : 'text-danger' }}">P/N: {{ $product->pn ?? $product->parent->pn ?? '' }}</span><br>
                                            <span class="{{ ($product->ean || $product->parent_id) ? '' : 'text-danger' }}">EAN: {{ $product->ean ?? $product->parent->ean ?? '' }}</span></td>
                                        <td>{{ $product->supplier_name }}<br>
                                            {{ $product->supplierSku }}
                                        </td>
                                        <td>{!! $product->supplier_category_id ? mb_substr($product->supplier_category->name, 0, 10) : '-' !!}<br>
                                            {!! $product->category_name ?? $product->parent->category->name ?? '<span class="text-danger">NO_CATEGORY</span>' !!}<br>
                                            {!! $product->brand_name ?? $product->parent->brand->name ?? '<span class="text-danger">NO BRAND</span>' !!}
                                        </td>
                                        <td>
                                            {{ $product->cost }}€<br>
                                            {{ $product->stock }}
                                        </td>
                                        <td>
                                            <a class="mr-2 {{ $product->ready ? '' : 'text-danger' }}" href="{{ route('products.show', [$product]) }}">
                                                {!! substr($product->name, 0, 80) ?? substr($product->parent->name, 0, 100) ?? '' !!}</a></td>
                                        <td>{{ $product->updated_at->format('Y-m-d H:i') }}</td>
                                        <td>{{ $product->created_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            <div class="row">
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
                                                    <a class="mr-2" href="{{ route('products.scrape', [$product]) }}" data-toggle="tooltip" title="Scrape">
                                                        <i class="fas fa-download"></i></a>
                                                    <form class="delete" action="{{ route('products.destroy', [$product]) }}" method="post">
                                                        @method('delete')
                                                        @csrf
                                                        @include('forms.a_delete', ['title' => 'Eliminar'])
                                                    </form>
                                                @endrole
                                            </div>
                                        </td>
                                    </tr>

                                @endforeach
                            </table>
                            <br>
                           {!! method_exists($products, 'total') ? $products->appends($params)->render() : '' !!}

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
    @include('scripts.submit-a-delete', ['question' => '¿Estás seguro de eliminar este producto?'])
@endpush
