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
                    <div class="col-sm-8"><h1>EDITAR MAPPING CATEGORÍA DE PROVEEDOR: ({{ $supplier->name }}) {{ $supplierCategory->name }}</h1></div>
                    <div class="col-sm-4">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item active">Operaciones</li>
                            <li class="breadcrumb-item"><a href="{{ route('action.suppliers') }}">Proveedores</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('suppliers.supplier_categories.index', [$supplier]) }}">Mapping Categorías</a></li>
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

                            <form method="post" action="{{ route('suppliers.supplier_categories.update', [$supplier, $supplierCategory]) }}">
                                @method('PATCH')
                                @csrf

                                <div class="form-group row">
                                    <label for="market_category_id" class="col-sm-2 col-form-label">Categoría MPS</label>
                                    <div class="col-sm-10">
                                        @include('forms.category', ['category_id' => $supplierCategory->category_id ?? old('category_id'), 'category_name' => $supplierCategory->category->name ?? old('category_name')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="code" class="col-sm-2 col-form-label">Categoría Proveedor</label>
                                    <div class="col-sm-2">
                                        @include('forms.custom', [
                                            'field_name' => 'supplierCategoryId',
                                            'placeholder' => 'Código',
                                            'value' => $supplierCategory->supplierCategoryId ?? old('supplierCategoryId')
                                        ])
                                    </div>
                                    <div class="col-sm-8">
                                        @include('forms.name', ['name' => $supplierCategory->name ?? old('name')])
                                    </div>
                                </div>

                                <br>
                                <div class="form-group row">
                                    <div class="col-sm-2"></div>
                                    <div class="col-sm-10">
                                        <a class="btn btn-danger" href="{{ route('suppliers.supplier_categories.index', [$supplier]) }}" role="button">Cancelar</a>
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
    @include('scripts.autocomplete-categories')
@endpush