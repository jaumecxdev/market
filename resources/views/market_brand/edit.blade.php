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
                    <div class="col-sm-8"><h1>EDITAR MAPPING MARCA: ({{ $market->name }}) {{ $brand->name }}</h1></div>
                    <div class="col-sm-4">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item active">Operaciones</li>
                            <li class="breadcrumb-item"><a href="{{ route('action.markets') }}">Marketplaces</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('markets.brands.index', [$market]) }}">Mapping Marcas</a></li>
                            <li class="breadcrumb-item active">Editar</li>
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

                            <form method="post" action="{{ route('markets.brands.update', [$market, $brand]) }}">
                                @method('PATCH')
                                @csrf

                                <div class="form-group row">
                                    <label for="market_brand_id" class="col-sm-2 col-form-label">Marca del MP</label>
                                    <div class="col-sm-10">
                                        @include('forms.market_brand', [
                                            'market_brand_id' => $brand->market_brand($market->id)->first()->id ?? old('market_brand_id'),
                                            'market_brand_name' => $brand->market_brand($market->id)->first()->name ?? old('market_brand_name')]
                                        )
                                    </div>
                                </div>

                                <br>
                                <div class="form-group row">
                                    <div class="col-sm-2"></div>
                                    <div class="col-sm-10">
                                        <a class="btn btn-danger" href="{{ route('markets.brands.index', [$market]) }}" role="button">Cancelar</a>
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
    @include('scripts.autocomplete-marketbrands', ['market_id' => $market->id])
@endpush
