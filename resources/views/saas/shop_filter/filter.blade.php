@extends('saas.layouts.app')

@push('styles')
@endpush

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-12"><h1>IMPORTAR PRODUCTOS A LA TIENDA: ({{ $shop->market->name }}) {{ $shop->name }}</h1></div>
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

                            <form method="post" action="{{ route('saas.shops.shop_filters.import', [$shop]) }}" class="form-inline">
                                @csrf

                                <div class="row">
                                    <div class="col-sm-12">
                                    <p>Según los filtros, se analizarán {{ $query_products ? $query_products->count() : 0 }} productos.</p>
                                        <br>
                                        <div class="form-group row">
                                            <div class="col-sm-12">
                                                <a class="btn btn-danger" href="{{ route('saas.shops.shop_filters', [$shop]) }}" role="button">Cancelar</a>
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
