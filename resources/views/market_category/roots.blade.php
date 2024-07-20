@extends('layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-8"><h1>DESCARGAR CATEGORÍAS DE: {{ $market->name ?? '' }}</h1></div>
                    <div class="col-sm-4">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item active">Operaciones</li>
                            <li class="breadcrumb-item"><a href="{{ route('action.markets') }}">Marketplaces</a></li>
                            <li class="breadcrumb-item active">Descargar categorías árbol</li>
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

                            <p>Hay {{ count($root_categories) }} categorías árbol</p>
                            <table class="table table-striped">
                                <tr class="table-warning">
                                    <th>Categoría árbol</th>
                                    <th>Acciones</th>
                                </tr>
                                <tr>
                                    <td><b>TODAS</b></td>
                                    <td>
                                        <a class="mr-2" href="{{ route('markets.categories.get.root', [$market, null]) }}"
                                           data-toggle="tooltip" title="Descargar Categoría Árbol">
                                            <i class="fas fa-cloud-download-alt"></i></a>
                                    </td>
                                </tr>
                                @foreach($root_categories as $root_category)
                                    <tr root_category-id="{{ $root_category->id }}">
                                        <td>{{ $root_category->id }} ({{ $root_category->marketCategoryId }}) <b>{{ $root_category->name }}</b></td>
                                        <td>
                                            <a class="mr-2" href="{{ route('markets.categories.get.root', [$market, 'marketCategoryId' => $root_category->marketCategoryId]) }}"
                                               data-toggle="tooltip" title="Descargar Categoría Árbol">
                                                <i class="fas fa-cloud-download-alt"></i></a>
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
