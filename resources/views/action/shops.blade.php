@extends('layouts.app')

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-8"><h1>Tiendas</h1></div>
                    <div class="col-sm-4">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item active">Operaciones</li>
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

                            <table class="table table-striped">
                                <tr class="table-warning">
                                    <th>Tienda</th>
                                    <th>Acciones</th>
                                </tr>
                                @foreach($shops as $shop)
                                    {{-- @if ($shop->enabled) --}}
                                        <tr shop-id="{{ $shop->id }}">
                                            <td class="{{ $shop->enabled ? '' : 'text-danger' }}">({{ $shop->market->id }} {{ $shop->market->name }}) {{ $shop->id }} {{ $shop->name }}</td>
                                            <td>
                                                <div class="row">
                                                    <a class="mr-2" href="{{ route('shops.shop_params.index', [$shop]) }}"><i class="fas fa-euro-sign nav-icon"></i> Márgenes y parámetros |</a>
                                                    <a class="mr-2" href="{{ route('shops.shop_filters.index', [$shop]) }}"><i class="fas fa-filter"></i> Filtros |</a>
                                                    <a class="mr-2" href="{{ route('shops.shop_groups.index', [$shop]) }}"><i class="fas fa-list nav-icon"></i> Grupos de la Store |</a>
                                                    <a class="mr-2" href="{{ route('shops.shop_products.index', [$shop]) }}"><i class="fas fa-laptop nav-icon"></i> Productos |</a>
                                                    <a class="mr-2" href="{{ route('shops.carriers.get', [$shop]) }}"><i class="fas fa-cloud-download-alt"></i> Descargar Logística</a>
                                                    <a class="mr-2" href="{{ route('shops.get.orders', [$shop]) }}"><i class="fas fa-cloud-download-alt"></i> Descargar Pedidos</a>
                                                    <a class="mr-2" href="{{ route('shops.get.payments', [$shop]) }}"><i class="fas fa-cloud-download-alt"></i> Descargar Cobros</a>
                                                </div>
                                            </td>
                                        </tr>
                                   {{--  @endif --}}
                                @endforeach
                            </table>

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
