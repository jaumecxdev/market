@extends('layouts.app')

@push('menu')
    <a class="mr-2" href="{{ route('products.index') }}">| Productos</a>
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-10"><h1>TIENDAS: {{ $product->name }}</h1></div>
                    <div class="col-sm-2">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('products.show', [$product]) }}">Producto</a></li>
                            <li class="breadcrumb-item active">Tiendas</li>
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

                            <p>Hay {{ $shop_products_count }} Tiendas</p>
                            <br>
                            <table class="table table-striped">
                                <tr class="table-warning">
                                    <th>Tienda</th>
                                </tr>
                                @foreach($shop_products as $shop_product)
                                    <tr shop_product-id="{{ $shop_product->id }}">
                                        <td>({{ $shop_product->market->name }}) {{ $shop_product->shop->name }}</td>
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
