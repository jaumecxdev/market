@extends('layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@push('menu')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('markets.brands.list', [$market]) }}" class="nav-link">Lista de todas las Marcas</a></li>
    @role('admin')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('markets.brands.get', [$market]) }}" class="nav-link">Descargar Marcas del MP</a></li>
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('markets.brands.auto', [$market]) }}" class="nav-link">Mapeo Auto de Marcas</a></li>
    @endrole
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-8"><h1>MAPPING DE MARCAS: {{ $market->name ?? '' }}</h1></div>
                    <div class="col-sm-4">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item active">Operaciones</li>
                            <li class="breadcrumb-item"><a href="{{ route('action.markets') }}">Marketplaces</a></li>
                            <li class="breadcrumb-item active">Mapping de marcas</li>
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

                            <p>Hay {{ $brands->total() }} marcas</p>
                            {!! $brands->appends($params)->render() !!}
                            <table class="table table-striped">
                                <tr class="table-warning">
                                    <th>Marca MPS</th>
                                    <th>Marca MP</th>
                                    <th>Acciones</th>
                                </tr>
                                @foreach($brands as $brand)
                                    <tr brand-id="{{ $brand->id }}">
                                        <td>{{ $brand->id }} <b>{{ $brand->name }}</b></td>
                                        @if (($brand->market_brand($market->id)->count()) &&
                                        ($market_brand = $brand->market_brand($market->id)->first()))
                                            <td>{{ $market_brand->id }} ({{ $market_brand->marketBrandId }}) <b>{{ $market_brand->name }}</b></td>
                                        @else
                                            <td></td>
                                        @endif
                                        <td>
                                            <div class="row">
                                                <a class="mr-2" href="{{ route('markets.brands.edit', [$market, $brand]) }}"
                                                   data-toggle="tooltip" title="Editar Mapping">
                                                    <i class="far fa-edit"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                            {!! $brands->appends($params)->render() !!}

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
