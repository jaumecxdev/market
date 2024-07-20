@extends('layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@push('menu')
    @role('admin')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('markets.brands.get', [$market]) }}" class="nav-link">Descargar Marcas del MP</a></li>
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('markets.brands.index', [$market]) }}" class="nav-link">Mapping Marcas</a></li>
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
                    <div class="col-sm-8"><h1>LISTA DE TODAS LAS MARCAS DE: {{ $market->name ?? '' }}</h1></div>
                    <div class="col-sm-4">
                        <ol class="breadcrumb float-sm-right">
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

                            <p>Hay {{ count($market_brands) }} marcas</p>
                            <table class="table table-striped">
                                <tr class="table-warning">
                                    <th>#</th>
                                    <th>Marca</th>
                                </tr>
                                @foreach($market_brands as $market_brand)
                                    <tr market_brand-id="{{ $market_brand->id }}">
                                        <td>{{ $market_brand->id }}</td>
                                        <td>({{ $market_brand->marketBrandId }}) <b>{{ $market_brand->name }}</b></td>
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
