@extends('layouts.app')

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-10"><h1>{{ isset($market) ? 'EDITAR MARKETPLACE: ' .$market->name : 'AÑADIR MARKETPLACE' }}</h1></div>
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

                            @if (isset($market))
                                <form method="post" action="{{ route('markets.update', $market->id) }}">
                                    @method('PATCH')
                            @else
                                <form method="post" action="{{ route('markets.store') }}">
                            @endif

                                @csrf

                                <div class="form-group row">
                                    <label for="code" class="col-sm-2 col-form-label">Marketplace</label>
                                    <div class="col-sm-2">
                                        @include('forms.code', ['code' => $market->code ?? old('code')])
                                    </div>
                                    <div class="col-sm-4">
                                        @include('forms.custom', ['field_name' => 'ws', 'placeholder' => 'Fichero WS', 'value' => $market->ws ?? old('ws')])
                                    </div>
                                    <div class="col-sm-4">
                                        @include('forms.name', ['name' => $market->name ?? old('name')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="name" class="col-sm-2 col-form-label">URL del producto</label>
                                    <div class="col-sm-10">
                                        @include('forms.custom', ['field_name' => 'product_url', 'placeholder' => '%marketProductSku',
                                                'value' => $market->product_url ?? old('product_url')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="name" class="col-sm-2 col-form-label">URL del pedido</label>
                                    <div class="col-sm-10">
                                        @include('forms.custom', ['field_name' => 'order_url', 'placeholder' => '%marketOrderId',
                                                'value' => $market->order_url ?? old('order_url')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="name" class="col-sm-2 col-form-label">Requerido</label>
                                    <div class="col-sm-2">
                                        @include('forms.checkbox', ['field_name' => 'pn_required', 'value' => $market->pn_required ?? old('pn_required'),
                                                'label' => 'Part Number'])
                                        @include('forms.checkbox', ['field_name' => 'ean_required', 'value' => $market->ean_required ?? old('ean_required'),
                                            'label' => 'EAN13'])
                                        @include('forms.checkbox', ['field_name' => 'name_required', 'value' => $market->name_required ?? old('name_required'),
                                            'label' => 'Título'])
                                        @include('forms.checkbox', ['field_name' => 'market_category_required', 'value' => $market->market_category_required ?? old('market_category_required'),
                                            'label' => 'Market Category'])
                                        @include('forms.checkbox', ['field_name' => 'images_required', 'value' => $market->images_required ?? old('images_required'),
                                            'label' => 'Images'])
                                        @include('forms.checkbox', ['field_name' => 'attributes_required', 'value' => $market->attributes_required ?? old('attributes_required'),
                                            'label' => 'Attributes'])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="config" class="col-sm-2 col-form-label">Configuración JSON</label>
                                    <div class="col-sm-10">
                                        @include('forms.textarea', ['field_name' => 'config', 'value' => $market->config ?? old('config')])
                                    </div>
                                </div>

                                <br>
                                <div class="form-group row">
                                    <div class="col-sm-2"></div>
                                    <div class="col-sm-10">
                                        <a class="btn btn-danger" href="{{ route('markets.index') }}" role="button">Cancelar</a>
                                        <button type="submit" class="btn btn-primary">Guardar Marketplace</button>
                                    </div>
                                </div>

                            @if (isset($product))</form>@else</form>@endif

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
