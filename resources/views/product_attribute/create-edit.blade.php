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
                    <div class="col-sm-10"><h1>{{ isset($provider_product_attribute) ? 'EDITAR ATRIBUTO: ' .$provider_product_attribute->provider_attribute->name : 'AÑADIR ATRIBUTO' }}</h1></div>
                    <div class="col-sm-2">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
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

                            @if (isset($provider_product_attribute))
                                <form method="post" action="{{ route('products.attributes.update', [$product, $provider_product_attribute->id]) }}">
                                    @method('PATCH')
                            @else
                                <form method="post" action="{{ route('products.attributes.store', [$product]) }}">
                            @endif
                                @csrf

                                    @if (!isset($provider_product_attribute))
                                        <div class="form-group row">
                                            <label for="attribute_id" class="col-sm-2 col-form-label">Atributo</label>
                                            <div class="col-sm-4">
                                                @include('forms.select_id', ['field_name' => 'attribute_id', 'placeholder' => 'Atributo de categoría',
                                                    'options' => $provider_attributes, 'option_id' => $provider_attributes->id ?? old('attribute_id')])
                                            </div>
                                        </div>
                                    @else
                                        <input type="hidden" id="provider_attribute_id" name="provider_attribute_id" value="{{ $provider_product_attribute->provider_attribute_id }}">
                                    @endif

                                    <div class="form-group row">
                                        <label for="provider_attribute_value_id" class="col-sm-2 col-form-label">Valor</label>
                                        <div class="col-sm-4">
                                            @include('forms.select_id', ['field_name' => 'provider_attribute_value_id', 'placeholder' => 'Valor del Atributo',
                                                'options' => $provider_attributes_values, 'option_id' => $provider_product_attribute->provider_attribute_value_id ?? old('provider_attribute_value_id')])
                                        </div>
                                    </div>

                                    <br>
                                    <div class="form-group row">
                                        <div class="col-sm-2"></div>
                                        <div class="col-sm-10">
                                            <a class="btn btn-danger" href="{{ route('products.index') }}" role="button">Cancelar</a>
                                            <button type="submit" class="btn btn-primary">Guardar atributo</button>
                                        </div>
                                    </div>

                            @if (isset($provider_product_attribute))</form>@else</form>@endif

                        </div>
                    </div>
                </div>
            </div>

            {{-- @if (isset($provider_attribute_value))
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">POSIBLES VALORES DE ATRIBUTOS</div>
                            <div class="card-body">

                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="panel panel-default">
                                                <div class="panel-body">
                                                    <table class="table table-striped">
                                                        <tr>
                                                            <th>Valor</th>
                                                        </tr>
                                                        @foreach($posible_product_attributes as $posible_product_attribute)
                                                            <tr>
                                                                <td>{{ $posible_product_attribute->value }}</td>
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
            @endif --}}

        </section>
    </div>
@endsection
@push('scriptsEnd')
    @include('scripts.jquery-ui')
@endpush
