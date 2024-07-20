@extends('layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@push('menu')
    @role('admin')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('markets.categories.get', [$market]) }}" class="nav-link">Descargar Categorías del MP</a></li>
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('markets.categories.index', [$market]) }}" class="nav-link">Mapping Categorías</a></li>
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('markets.categories.auto', [$market]) }}" class="nav-link">Mapeo Auto de Categorías</a></li>
    @endrole
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-8"><h1>LISTA DE TODAS LAS CATEGORÍAS DE: {{ $market->name ?? '' }}</h1></div>
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

                            <p>Hay {{ count($market_categories) }} categorías</p>
                            <table class="table table-striped">
                                <tr class="table-warning">
                                    <th>#</th>
                                    <th>Root</th>
                                    <th>Categoría</th>
                                    <th>Árbol</th>
                                </tr>
                                @foreach($market_categories as $market_category)
                                    <tr market_category-id="{{ $market_category->id }}">
                                        <td>{{ $market_category->id }}</td>
                                        <td>{!! isset($market_category->root_category) ? '('.$market_category->root_category->marketCategoryId.') <b>' .$market_category->root_category->name.'</b>' : '' !!}</td>
                                        <td>({{ $market_category->marketCategoryId }}) <b>{{ $market_category->name }}</b></td>
                                        <td>{{ $market_category->path }}</td>
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
