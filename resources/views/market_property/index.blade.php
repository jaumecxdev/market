@extends('layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@push('menu')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('markets.properties.get', [$market]) }}" class="nav-link">Descargar Attributos</a></li>
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('markets.properties.auto', [$market]) }}" class="nav-link">Mapeo Auto de Atributos</a></li>
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-8"><h1>MAPPING DE ATTRIBUTOS: {{ $market->name ?? '' }}</h1></div>
                    <div class="col-sm-4">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item active">Operaciones</li>
                            <li class="breadcrumb-item"><a href="{{ route('action.markets') }}">Marketplaces</a></li>
                            <li class="breadcrumb-item active">Mapping de attributos</li>
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

                            <form method="get" action="{{ route('markets.properties.index', [$market]) }}" class="form-inline">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            @include('forms.market_category', ['market_category_id' => $params['market_category_id'] ?? null, 'market_category_name' => $params['market_category_name'] ?? null])
                                            @include('forms.market_attribute_name', ['market_attribute_name' => $params['market_attribute_name'] ?? null])
                                            <a class="mr-2 mb-2" href="{{ route('markets.properties.index', [$market]) }}">LIMPIAR</a>
                                            <button class="btn btn-success mb-2" type="submit" value="FILTRAR">FILTRAR</button>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <p>Hay {{ $properties->total() }} atributos</p>
                            {{ $properties->appends($params)->render() }}
                            <table class="table table-striped">
                                <tr class="table-warning">
                                    <th>(Categoría) Atributo MP</th>
                                    <th>Tipo Atr.</th>
                                    <th>Tipo dato</th>
                                    <th>Requerido? Custom?</th>
                                    <th>Custom value</th>
                                    <th>Mapping Atributo MPS</th>
                                    <th>Acciones</th>
                                </tr>
                                @foreach($properties as $property)
                                    <tr property-id="{{ $property->id }}">
                                        <td>{{ $property->market_category_string }}
                                            {{ $property->market_attribute_id }} <b>{{ $property->market_attribute_name }}</b> {{ $property->id }} <b>{{ $property->name }}</b></td>
                                        <td>{{ $property->type_name == 'type_sku' ? 'SKU' : 'CAT' }}</td>
                                        <td>{{ $property->datatype }}</td>
                                        <td>{{ $property->required ? 'Si' : 'No' }} / {{ $property->custom ? 'Si' : 'No' }}</td>
                                        <td>{{ $property->custom_value_field. ': ' .$property->custom_value }}</td>
                                        <td>
                                            <table class="table">
                                                <tr class="table-warning">
                                                    <th><small>Fijo ?</small></th>
                                                    <th><small>Valor Fijo</small></th>
                                                    <th><small>(Categoría) Atributo MPS</small></th>
                                                    <th><small>Campo</small></th>
                                                    <th><small>Patrón</small></th>
                                                    <th><small>Mapeo</small></th>
                                                    <th><small>Si existe</small></th>
                                                    <th><small>Valor existe</small></th>
                                                </tr>
                                                @if ($attribute_market_attributes = $property->attribute_market_attributes_Extended())
                                                    @foreach($attribute_market_attributes as $attribute_market_attribute)
                                                        <tr>
                                                            <td>{{ $attribute_market_attribute->fixed ? 'Si' : 'No' }}</td>
                                                            <td>{{ $attribute_market_attribute->fixed_value }}</td>
                                                            <td>{{ $attribute_market_attribute->attribute_id ?
                                                            '(' .$attribute_market_attribute->category_name. ') '
                                                            .$attribute_market_attribute->attribute_name : '' }}</td>
                                                            <td>{{ $attribute_market_attribute->field }}</td>
                                                            <td>{{ $attribute_market_attribute->pattern }}</td>
                                                            <td>{{ $attribute_market_attribute->mapping }}</td>
                                                            <td>{{ $attribute_market_attribute->if_exists }}</td>
                                                            <td>{{ $attribute_market_attribute->if_exists_value }}</td>
                                                        </tr>
                                                    @endforeach
                                                @else
                                                    <td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                                                @endif
                                            </table>
                                        </td>
                                        <td>
                                            <div class="row">
                                                <a class="mr-2" href="{{ route('markets.properties.edit', [$market, $property]) }}"
                                                   data-toggle="tooltip" title="Editar Mapping"><i class="far fa-edit"></i></a>
                                                <form class="delete" action="{{ route('markets.properties.destroy', [$market, $property]) }}" method="post">
                                                    @method('delete')
                                                    @csrf
                                                    @include('forms.button_delete',
                                                        ['title' => 'Eliminar Property, incluyendo sus Mappings y PropertyValues. Si esta Property es la última del Atrtibuto, eliminarlo también'])
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                            {{ $properties->appends($params)->render() }}

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
    @include('scripts.submit-delete', ['question' => '¿Estás seguro de eliminar esta property entera, junto con los Mappings y PropertyValues?'])
@endpush
