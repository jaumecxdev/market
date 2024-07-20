@extends('saas.layouts.app')

@push('menu')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('saas.products') }}" class="nav-link">Productos</a></li>
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-12"><h1>TIENDAS: {{ $product->name }}</h1></div>
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
