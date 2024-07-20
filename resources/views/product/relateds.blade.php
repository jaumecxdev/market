@extends('layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-9"><h1>RELACIONADOS: {{ $product->name }}</h1></div>
                    <div class="col-sm-3">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('products.show', [$product]) }}">Producto</a></li>
                            <li class="breadcrumb-item active">Relacionados</li>
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

                            <p>Hay {{ count($relateds) }} relacionados</p>

                            <form method="post" action="{{ route('products.storerelated', [$product]) }}" class="form-inline">
                                @csrf
                                @include('forms.supplier_sku', ['supplierSku' => $params['supplierSku'] ?? null])
                                @include('forms.product', ['item_select' => $params['item_select'] ?? old('item_select'),
                                                'product_id' => $params['product_id'] ?? old('product_id'),
                                                'item_reference' => $params['item_reference'] ?? old('item_reference')])
                                <button class="btn btn-success" type="submit" value="AÑADIR">AÑADIR</button>
                            </form>
                            <br>

                            {!! $relateds->render() !!}
                            <table class="table table-striped">
                                <tr class="table-warning">
                                    <th>#</th>
                                    <th>Ok</th>
                                    <th>Fecha</th>
                                    <th>Proveedor</th>
                                    <th>Marca</th>
                                    <th>Categoría</th>
                                    <th>Coste</th>
                                    <th>Stock</th>
                                    <th>Part</th>
                                    <th>EAN</th>
                                    <th>Título</th>
                                    <th>Acciones</th>
                                </tr>
                                @foreach($relateds as $related)
                                    <tr related-id="{{ $related->id }}">
                                        <td>{{ $related->id }}</td>
                                        <td>{{ $related->ready }}</td>
                                        <td>{{ $related->updated_at->format('Y-m-d') }}</td>
                                        <td>{{ $related->supplier ? $related->supplier->name : '' }}</td>
                                        <td>{{ $related->brand ? $related->brand->name : '' }}</td>
                                        <td>{{ $related->category ? $related->category->name : ''}}</td>
                                        <td>{{ $related->cost }}</td>
                                        <td>{{ $related->stock }}</td>
                                        <td>{{ $related->pn }}</td>
                                        <td>{{ $related->ean }}</td>
                                        <td>{{ $related->name }}</td>
                                        <td>
                                            <form class="delete" action="{{ route('products.destroyrelated', [$product, $related]) }}" method="post">
                                                @method('delete')
                                                @csrf
                                                @include('forms.button_delete', ['title' => 'Quitar de la lista'])
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>

                            {!! $relateds->render() !!}
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
    @include('scripts.submit-delete', ['question' => '¿Estás seguro de quitar este relacionado de la lista?'])
@endpush
