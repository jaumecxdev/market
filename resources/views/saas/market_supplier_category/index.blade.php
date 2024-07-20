@extends('saas.layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@push('menu')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('saas.markets.market_categories', [$market]) }}" class="nav-link"> Categorías del Marketplace</a></li>
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-12"><h1>MAPPING DE CATEGORÍAS: {{ $market->name ?? '' }}</h1></div>
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

                            <p>Hay {{ $supplier_categories->total() }} categorías</p>
                            {!! $supplier_categories->appends($params)->render() !!}
                            <table class="table table-striped">
                                <tr class="table-warning">
                                    <th>Proveedor</th>
                                    <th>Categoría</th>
                                    <th>Categoría Marketplace</th>
                                    <th>Acciones</th>
                                </tr>
                                @foreach($supplier_categories as $supplier_category)
                                    <tr supplier_category-id="{{ $supplier_category->id }}">
                                        @php
                                            $supplier_category_string = (Str::substr($supplier_category->supplierCategoryId, 0, 64) == Str::substr($supplier_category->name, -64, 64)) ?
                                                $supplier_category->name :
                                                "(".$supplier_category->supplierCategoryId.") <b>".$supplier_category->name."</b>";

                                            $market_categories_query = $supplier_category->market_categories()->wherePivot('market_id', $market->id);
                                            if ($market_categories_query->count()) {
                                                $market_category = $market_categories_query->first();
                                                $market_category_string = "(".$market_category->marketCategoryId.") ".$market_category->path. " / <b>".$market_category->name."</b>";
                                            }
                                            else
                                                $market_category_string = '';
                                        @endphp
                                        <td>{{ $supplier_category->supplier->name }}</td>
                                        <td>{!! $supplier_category_string !!}</td>
                                        <td>{!! $market_category_string !!}</td>

                                        {{-- @if (($supplier_category->market_category($market->id)->count()) &&
                                        ($market_category = $supplier_category->market_category($market->id)->first()))
                                            <td>{{ $market_category->id }} ({{ $market_category->marketCategoryId }}) {{ $market_category->path }} / <b>{{ $market_category->name }}</b></td>
                                        @else
                                            <td></td>
                                        @endif --}}
                                        <td>
                                            <div class="row">
                                                <a class="mr-2" href="{{ route('saas.markets.supplier_categories.edit', [$market, $supplier_category]) }}"
                                                   data-toggle="tooltip" title="Editar Mapping">
                                                    <i class="far fa-edit"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                            {!! $supplier_categories->appends($params)->render() !!}

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
