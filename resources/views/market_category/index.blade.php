@extends('layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@push('menu')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('markets.categories.list', [$market]) }}" class="nav-link">Lista de todas las Categorías</a></li>
    @role('admin')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('markets.categories.get', [$market]) }}" class="nav-link">Descargar Categorías del MP</a></li>
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
                    <div class="col-sm-8"><h1>MAPPING DE CATEGORÍAS: {{ $market->name ?? '' }}</h1></div>
                    <div class="col-sm-4">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item active">Operaciones</li>
                            <li class="breadcrumb-item"><a href="{{ route('action.markets') }}">Marketplaces</a></li>
                            <li class="breadcrumb-item active">Mapping de categorías</li>
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

                            <p>Hay {{ $categories->total() }} categorías</p>
                            {!! $categories->appends($params)->render() !!}
                            <table class="table table-striped">
                                <tr class="table-warning">
                                    <th>Categoría MPS</th>
                                    <th>Categoría MP</th>
                                    <th>Acciones</th>
                                </tr>
                                @foreach($categories as $category)
                                    <tr category-id="{{ $category->id }}">
                                        <td>{{ $category->id }} ({{ $category->code }}) {{ $category->path }} / <b>{{ $category->name }}</b></td>
                                        @if (($category->market_category($market->id)->count()) &&
                                        ($market_category = $category->market_category($market->id)->first()))
                                            <td>{{ $market_category->id }} ({{ $market_category->marketCategoryId }}) {{ $market_category->path }} / <b>{{ $market_category->name }}</b></td>
                                        @else
                                            <td></td>
                                        @endif
                                        <td>
                                            <div class="row">
                                                <a class="mr-2" href="{{ route('markets.categories.edit', [$market, $category]) }}"
                                                   data-toggle="tooltip" title="Editar Mapping">
                                                    <i class="far fa-edit"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                            {!! $categories->appends($params)->render() !!}

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
