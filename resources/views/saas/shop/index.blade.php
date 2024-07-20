@extends('saas.layouts.app')

@push('menu')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('saas.shops.create') }}" class="nav-link">Añadir tienda</a></li>
@endpush

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-2"><h1>Tiendas</h1></div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">

                            @include('partials.status')

                            <table class="table table-striped">
                                <tr class="table-warning">
                                    <th>Tienda</th>
                                    <th>Acciones</th>
                                </tr>
                                @foreach($shops as $shop)
                                        <tr shop-id="{{ $shop->id }}">
                                            <td class="{{ $shop->enabled ? '' : 'text-danger' }}">({{ $shop->market->name }}) {{ $shop->name }}</td>
                                            <td>
                                                <div class="row">
                                                    <a class="mr-2" href="{{ route('saas.shops.edit', [$shop]) }}"><i class="fas fa-cog nav-icon"></i> Configuración &nbsp;</a>
                                                    <a class="mr-2" href="{{ route('saas.shops.shop_params', [$shop]) }}"><i class="fas fa-euro-sign nav-icon"></i> Precios y Stocks &nbsp;</a>
                                                    <a class="mr-2" href="{{ route('saas.shops.shop_filters', [$shop]) }}"><i class="fas fa-filter"></i> Filtros &nbsp;</a>
                                                    <a class="mr-2" href="{{ route('saas.shops.shop_products', [$shop]) }}"><i class="fas fa-laptop nav-icon"></i> Productos</a>
                                                </div>
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
