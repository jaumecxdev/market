@extends('saas.layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@push('menu')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('saas.markets.supplier_categories', [$market]) }}" class="nav-link">Mapping</a></li>
@endpush

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-8"><h1>LISTA DE TODAS LAS CATEGORÍAS DE: {{ $market->name ?? '' }}</h1></div>
                    <div class="col-sm-4">
                        <ol class="breadcrumb float-sm-right">
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">

                            @include('partials.status')

                            <p>Hay {{ count($market_categories) }} categorías</p>
                            <table class="table table-striped">
                                <tr class="table-warning">
                                    <th>Root</th>
                                    <th>Árbol</th>
                                    <th>Categoría</th>
                                </tr>
                                @foreach($market_categories as $market_category)
                                    <tr market_category-id="{{ $market_category->id }}">
                                        <td>{!! isset($market_category->root_category) ? '('.$market_category->root_category->marketCategoryId.') <b>' .$market_category->root_category->name.'</b>' : '' !!}</td>
                                        <td>{{ $market_category->path }}</td>
                                        <td>({{ $market_category->marketCategoryId }}) <b>{{ $market_category->name }}</b></td>
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
