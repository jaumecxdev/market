@extends('layouts.app')

@push('styles')
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-8"><h1>IMPORTAR PRODUCTOS A LA TIENDA: ({{ $shop->market->name }}) {{ $shop->name }}</h1></div>
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

                            <form method="post" action="{{ route('shops.shop_filters.import', [$shop]) }}" class="form-inline">
                                @csrf

                                <div class="row">
                                    <div class="col-sm-12">
                                    <p>Según los filtros, se analizarán {{ $query_products ? $query_products->count() : 0 }} productos.</p>
                                        <br>
                                        <div class="form-group row">
                                            <div class="col-sm-12">
                                                <a class="btn btn-danger" href="{{ route('shops.shop_filters.index', [$shop]) }}" role="button">Cancelar</a>
                                                <button type="submit" class="btn btn-primary">Analizar e Importar productos</button>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </form>

                        </div>
                    </div>

                </div>
            </div>
        </section>
    </div>
@endsection
@push('scriptsEnd')
@endpush
