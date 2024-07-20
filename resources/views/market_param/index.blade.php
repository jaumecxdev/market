@extends('layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@push('menu')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('markets.market_params.sync', [$market]) }}"
                                                     class="btn btn-danger">IMPORTANTE: SINCRONIZAR TABLAS DESPUÉS DE MODIFICAR</a></li>
@endpush

@section('content')
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-10"><h1>AÑADIR PARÁMETRO PARA EL MARKETPLACE: {{ $market->name }}</h1></div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="row">
                <div class="col-12">

                    <div class="card">
                        <div class="card-body">

                            @include('partials.status')
                            @include('partials.errors')

                            @if (isset($market_param))
                                <form method="post" action="{{ route('markets.market_params.update', [$market, $market_param]) }}">
                                    @method('PATCH')
                            @else
                                <form method="post" action="{{ route('markets.market_params.store', [$market]) }}">
                            @endif
                                @csrf

                                <p>Tarifas para el cálculo de los Precios de productos en este Marketplace CONDICIONADOS
                                    por Marca o Categoría, por este orden de preferencia.</p>

                                <p class="text-success"><b>PARÁMETROS CONDICIONANTES</b></p>

                                <div class="form-group row">
                                    <label for="root_category_id" class="col-sm-2 col-form-label">Marca | Categoría</label>
                                    <div class="col-sm-2">
                                        @include('forms.brand', [
                                            'brand_id' => $market_param->brand_id ?? old('brand_id'),
                                            'brand_name' => $market_param->brand->name ?? old('brand_name')])
                                    </div>
                                    <div class="col-sm-4">
                                        @include('forms.market_category', [
                                            'market_category_id' => $market_param->market_category_id ?? old('market_category_id'),
                                            'market_category_name' => $market_param->market_category->name ?? old('market_category_name')])
                                    </div>
                                    <div class="col-sm-3">
                                        @include('forms.root_category', [
                                            'root_categories' => $root_categories,
                                            'root_category_id' => $market_param->root_category_id ?? old('root_category_id')])
                                    </div>
                                </div>

                                <p class="text-success"><b>TARIFAS DE EXPORTACIÓN A MARKETPLACES: CÁLCULO DEL PRECIO FINAL</b></p>

                                <div class="form-group row">
                                    <label for="fee" class="col-sm-2 col-form-label">% Tarifa | € Fijo añadido | € Mín comisión</label>
                                    <div class="col-sm-2">
                                        @include('forms.fee', ['fee' => $market_param->fee ?? old('fee') ?? 0 ])
                                        <small>Tarifa por venta realizada en %</small>
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.fee_addon', ['fee_addon' => $market_param->fee_addon ?? old('fee_addon') ?? 0 ])
                                        <small>Añadido en €. Ejemplo: PayPal: 0.35</small>
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.bfit_min', ['bfit_min' => $market_param->bfit_min ?? old('bfit_min') ?? 0 ])
                                        <small>Mínima comisión del Marketplace en €. Ejemplo: Amazon: 0.30</small>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="fee" class="col-sm-2 col-form-label">€ Tramo | % Tarifa Tramo</label>
                                    <div class="col-sm-2">
                                        @include('forms.lot', ['lot' => $market_param->lot ?? old('lot') ?? 0 ])
                                        <small>Tramo de cambio de Tarifa, en €</small>
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.lot_fee', ['lot_fee' => $market_param->lot_fee ?? old('lot_fee') ?? 0 ])
                                        <small>Tarifa Tramo Superior %.</small>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <div class="col-sm-2"></div>
                                    <div class="col-sm-10">
                                        <button type="submit" class="btn btn-primary">Guardar tarifa a {{ $market->name }}</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">TARIFAS DEL MARKETPLACE {{ strtoupper($market->name) }}</div>
                        <div class="card-body">
                            <div class="panel panel-default">
                                <div class="panel-body">
                                    <p>Hay {{ count($market_params) }} tarifas</p>
                                    <br>
                                    <table class="table table-striped">
                                        <tr class="table-warning">
                                            <th>#</th>
                                            <th>Marca</th>
                                            <th>Categoría</th>
                                            <th>Categoría Raíz</th>
                                            <th>Tarifa + Fijo</th>
                                            <th>Tramo | Tarifa</th>
                                            <th>Mín Comi</th>
                                            <th>Acciones</th>
                                        </tr>
                                        @foreach($market_params as $market_param)
                                            <tr market_param-id="{{ $market_param->id }}">
                                                <td>{{ $market_param->id }}</td>
                                                <td>{{ $market_param->brand_name ?? '' }}</td>
                                                <td>{{ $market_param->market_category_id ? '('.$market_param->market_category_marketCategoryId.') '.$market_param->market_category_name : '' }}</td>
                                                <td>{{ $market_param->root_category_id ? '('.$market_param->root_category_marketCategoryId.') '.$market_param->root_category_name : '' }}</td>
                                                <td>{{ $market_param->fee != 0 ? $market_param->fee.'%' : '' }}
                                                    {{ $market_param->fee_addon != 0 ? '+ '.$market_param->fee_addon.'€' : '' }}</td>
                                                <td>{{ $market_param->lot != 0 ? $market_param->lot.'€' : '' }}
                                                    {{ $market_param->lot_fee != 0 ? '| '.$market_param->lot_fee.'%' : '' }}</td>
                                                <td>{{ $market_param->bfit_min != 0 ? $market_param->bfit_min.'€' : '' }}</td>
                                                <td class="row">
                                                    <a class="mr-2"
                                                        href="{{ route('markets.market_params.edit', [$market, $market_param]) }}" data-toggle="tooltip" title="Editar">
                                                        <i class="far fa-edit"></i></a>
                                                    <form class="delete" action="{{ route('markets.market_params.destroy', [$market, $market_param]) }}" method="post">
                                                        @method('delete')
                                                        @csrf
                                                        @include('forms.a_delete', ['title' => 'Quitar tarifa'])
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>
    </div>
@endsection
@push('scriptsEnd')
    @include('scripts.jquery-ui')
    @include('scripts.autocomplete-brands')
    @include('scripts.autocomplete-marketcategories', ['market_id' => $market->id])
    @include('scripts.submit-a-delete', ['question' => '¿Estás seguro de quitar esta tarifa?'])
@endpush
