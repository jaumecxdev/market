@extends('saas.layouts.app')

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
                    <div class="col-sm-12"><h1>EDITAR MAPPING CATEGORÍA: ({{ $market->name }}) {{ $supplier_category->name }}</h1></div>
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

                            <form method="post" action="{{ route('saas.markets.supplier_categories.update', [$market, $supplier_category]) }}">
                                @method('PATCH')
                                @csrf

                                <div class="form-group row">
                                    <label for="market_category_id" class="col-sm-2 col-form-label">Categoría del Marketplace</label>
                                    <div class="col-sm-10">
                                        @include('forms.market_category', [
                                            'market_category_id' => $supplier_category->market_categories()->wherePivot('market_id', $market->id)->first()->id ?? old('market_category_id'),
                                            'market_category_name' => $supplier_category->market_categories()->wherePivot('market_id', $market->id)->first()->name ?? old('market_category_name')]
                                        )
                                    </div>
                                </div>

                                <br>
                                <div class="form-group row">
                                    <div class="col-sm-2"></div>
                                    <div class="col-sm-10">
                                        <a class="btn btn-danger" href="{{ route('saas.markets.supplier_categories', [$market]) }}" role="button">Cancelar</a>
                                        <button type="submit" class="btn btn-primary">Guardar Mapping</button>
                                    </div>
                                </div>

                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
@push('scriptsEnd')
    @include('scripts.jquery-ui')
    @include('scripts.autocomplete-marketcategories', ['market_id' => $market->id])
@endpush
