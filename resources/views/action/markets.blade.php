@extends('layouts.app')

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-8"><h1>Marketplaces</h1></div>
                    <div class="col-sm-4">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item active">Operaciones</li>
                            <li class="breadcrumb-item active">Marketplaces</li>
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
                                    <th>Market place</th>
                                    <th>Acciones</th>
                                </tr>
                                @foreach($markets as $market)
                                    <tr market-id="{{ $market->id }}">
                                        <td>{{ $market->id }} {{ $market->name }}</td>
                                        <td>
                                            <div class="row">
                                                @hasanyrole('user|owner|admin')
                                                <a class="mr-2" href="{{ route('markets.market_params.index', [$market]) }}"><i class="fas fa-euro-sign nav-icon"></i> Tarifas | </a>
                                                @endhasanyrole
                                                <a class="mr-2" href="{{ route('markets.brands.index', [$market]) }}"><i class="fas fa-list nav-icon"></i> Mapping Marcas |</a>
                                                <a class="mr-2" href="{{ route('markets.categories.index', [$market]) }}"><i class="fas fa-list nav-icon"></i> Mapping Categorías |</a>
                                                <a class="mr-2" href="{{ route('markets.properties.index', [$market]) }}"><i class="fas fa-list nav-icon"></i> Mapping Atributos</a>
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
