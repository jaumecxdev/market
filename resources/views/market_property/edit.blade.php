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
                <div class="row">
                    <div class="col-sm-8"><h4>EDITAR MAPPING ATRIBUTO:
                        {{ $property->market_category_id_name }} {{ $property->market_attribute_id_name }} {{ $property->property_id_name }}
                        </h4>
                    </div>
                    <div class="col-sm-4">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item active">Operaciones</li>
                            <li class="breadcrumb-item"><a href="{{ route('action.markets') }}">Marketplaces</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('markets.properties.index', [$market]) }}">Mapping Attributos</a></li>
                            <li class="breadcrumb-item active">Editar</li>
                        </ol>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-sm-8">Requerido? {{ $property->required }} | Custom? {{ $property->custom }}</div>
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

                            <form method="post" action="{{ route('markets.properties.update', [$market, $property]) }}">
                                @method('PATCH')
                                @csrf

                                <div class="form-group row">
                                    <label for="fixed" class="col-sm-2 col-form-label">Es un fijo ?</label>
                                    <div class="col-sm-10">
                                        @include('forms.fixed_checkbox', ['fixed' => old('fixed')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="fixed_value" class="col-sm-2 col-form-label">Valor fijo (Si fijo)</label>
                                    <div class="col-sm-10">
                                        @include('forms.fixed_value', ['fixed_value' => old('fixed_value')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="attribute" class="col-sm-2 col-form-label">Atributo del MPS</label>
                                    <div class="col-sm-10">
                                        @include('forms.attribute', ['attribute_id' => old('attribute_id'), 'attribute_name' => old('attribute_name')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="field" class="col-sm-2 col-form-label">Campo del producto</label>
                                    <div class="col-sm-10">
                                        @include('forms.field', ['field' => old('field')])
                                        <small class="form-text text-muted">Ejemplos: brand, category, status, name, shortdesc, longdesc, pn, ean, model, public_url_sku_image, size, color, material, style, gender, weight, length, width, height</small>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="pattern" class="col-sm-2 col-form-label">Patrón</label>
                                    <div class="col-sm-10">
                                        @include('forms.pattern', ['pattern' => '/[^A-Za-z0-9\.,?! ]/'])
                                        <small class="form-text text-muted">(ALL):&nbsp;&nbsp;&nbsp;&nbsp;/[^A-Za-z0-9\.,?! ]/</small>
                                        <small class="form-text text-muted">(NO POINTS):&nbsp;&nbsp;&nbsp;&nbsp;/[.+]/</small>
                                        <small class="form-text text-muted">(NO SPACES):&nbsp;&nbsp;&nbsp;&nbsp;/[^a-zA-Z0-9\.,]/</small>
                                        <small class="form-text text-muted">(NO POINT NO SPACES):&nbsp;&nbsp;&nbsp;&nbsp;/[^a-zA-Z0-9]/</small>
                                        <small class="form-text text-muted">NO NUMBERS:&nbsp;&nbsp;&nbsp;&nbsp;/[^a-zA-Z]/</small>
                                        <small class="form-text text-muted">ONLY NUMBERS:&nbsp;&nbsp;&nbsp;&nbsp;/[^0-9\.,]/</small>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="mapping" class="col-sm-2 col-form-label">Mapeo</label>
                                    <div class="col-sm-10">
                                        @include('forms.mapping', ['mapping' => 'equal'])
                                        <small class="form-text text-muted">Valores: equal&nbsp;&nbsp;&nbsp;&nbsp;strpos (busca valor de producto dentro de valores de MP)&nbsp;&nbsp;&nbsp;&nbsp; strpos2 (valores de MP dentro de producto)</small>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="if_exists" class="col-sm-2 col-form-label">Si existe este valor...</label>
                                    <div class="col-sm-10">
                                        @include('forms.if_exists', ['if_exists' => old('if_exists')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="if_exists_value" class="col-sm-2 col-form-label">Assignar este valor</label>
                                    <div class="col-sm-10">
                                        @include('forms.if_exists_value', ['if_exists_value' => old('if_exists_value')])
                                    </div>
                                </div>

                                <br>
                                <div class="form-group row">
                                    <div class="col-sm-2"></div>
                                    <div class="col-sm-10">
                                        <a class="btn btn-danger" href="{{ route('markets.properties.index', [$market]) }}" role="button">Cancelar</a>
                                        <button type="submit" class="btn btn-primary">Añadir Mapping</button>
                                    </div>
                                </div>

                            </form>

                        </div>
                    </div>
                </div>
            </div>
            <br>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">ATTRIBUTOS</div>
                        <div class="card-body">
                            <div class="panel panel-default">
                                <div class="panel-body">
                                    <p>Hay {{ count($attribute_market_attributes) }} atributos</p>
                                    <br>

                                    <table class="table table-striped">
                                        <tr>
                                            <th>Fijo ?</th>
                                            <th>Valor Fijo</th>
                                            <th>(Categoría) Atributo MPS</th>
                                            <th>Campo del Producto</th>
                                            <th>Patrón</th>
                                            <th>Mapeo</th>
                                            <th>Si existe</th>
                                            <th>Valor existe</th>
                                            <th>Acciones</th>
                                        </tr>

                                        @foreach($attribute_market_attributes as $attribute_market_attribute)
                                            <tr>
                                                <td>{{ $attribute_market_attribute->fixed ? 'Si' : 'No' }}</td>
                                                <td>{{ $attribute_market_attribute->fixed_value }}</td>
                                                <td>{{ $attribute_market_attribute->category_attribute_name }}</td>
                                                <td>{{ $attribute_market_attribute->field }}</td>
                                                <td>{{ $attribute_market_attribute->pattern }}</td>
                                                <td>{{ $attribute_market_attribute->mapping }}</td>
                                                <td>{{ $attribute_market_attribute->if_exists }}</td>
                                                <td>{{ $attribute_market_attribute->if_exists_value }}</td>
                                                <td>
                                                    <form class="delete" action="{{ route('markets.properties.mapping.destroy', [$market, $property, $attribute_market_attribute]) }}" method="post">
                                                        @method('delete')
                                                        @csrf
                                                        @include('forms.button_delete', ['title' => 'Eliminar'])
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
            <br>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">POSIBLES VALORES DE ATRIBUTOS</div>
                        <div class="card-body">

                                <div class="row">
                                    <div class="col-sm-5">
                                        <div class="panel panel-default">
                                            <div class="panel-body">
                                                <table class="table table-striped">
                                                    <tr>
                                                        <th>Nombre de atributo del MP</th>
                                                        <th>Valor</th>
                                                    </tr>
                                                    @foreach($property->property_values as $property_value)
                                                        <tr>
                                                            <td>{{ $property_value->name }}</td>
                                                            <td>{{ $property_value->value }}</td>
                                                        </tr>
                                                    @endforeach
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-sm-5">
                                        <div class="panel panel-default">
                                            <div class="panel-body">
                                                <table class="table table-striped">
                                                    <tr>
                                                        <th>Nombre de atributo del MPS</th>
                                                        <th>Valor</th>
                                                    </tr>
                                                    @foreach($product_attributes_example as $product_attribute_example)
                                                        <tr>
                                                            <td>{{ $product_attribute_example->attribute_name}}</td>
                                                            <td>{{ $product_attribute_example->value }}</td>
                                                        </tr>
                                                    @endforeach
                                                </table>
                                            </div>
                                        </div>
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
    @include('scripts.autocomplete-attributes')
    @include('scripts.submit-delete', ['question' => '¿Estás seguro de eliminar este Mapping?'])
@endpush
