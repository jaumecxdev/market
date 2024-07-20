@extends('saas.layouts.app')

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-12"><h1>{{ isset($supplier) ? 'EDITAR PROVEEDOR: ' .$supplier->name : 'AÑADIR PROVEEDOR' }}</h1></div>
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

                            @if (isset($supplier))
                                <form method="post" action="{{ route('suppliers.update', $supplier->id) }}">
                                    @method('PATCH')
                            @else
                                <form method="post" action="{{ route('suppliers.store') }}">

                            @endif
                                @csrf

                                <div class="form-group row">
                                    <label for="code" class="col-sm-2 col-form-label">Code</label>
                                    <div class="col-sm-6">
                                        @include('forms.code', ['code' => $supplier->code ?? old('code')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="name" class="col-sm-2 col-form-label">Nombre</label>
                                    <div class="col-sm-6">
                                        @include('forms.name', ['name' => $supplier->name ?? old('name')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="locale" class="col-sm-2 col-form-label">Localización</label>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'locale', 'value' => $supplier->locale ?? old('locale')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="type_import" class="col-sm-2 col-form-label">Tipo Import</label>
                                    <div class="col-sm-4">
                                        @include('forms.type_import', ['type_import' => $supplier->type_import ?? old('type_import')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="ws" class="col-sm-2 col-form-label">WS File</label>
                                    <div class="col-sm-4">
                                        @include('forms.custom', ['field_name' => 'ws', 'value' => $supplier->ws ?? old('ws')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="config" class="col-sm-2 col-form-label">Configuración JSON</label>
                                    <div class="col-sm-10">
                                        @include('forms.textarea', ['field_name' => 'config', 'value' => $supplier->config ?? old('config')])
                                    </div>
                                </div>

                                <br>
                                <div class="form-group row">
                                    <div class="col-sm-2"></div>
                                    <div class="col-sm-10">
                                        <a class="btn btn-danger" href="{{ route('saas.suppliers') }}" role="button">Cancelar</a>
                                        <button type="submit" class="btn btn-primary">Guardar proveedor</button>
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
