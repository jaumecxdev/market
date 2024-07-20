@extends('layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@push('menu')
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-8"><h1>MAPPING DE CATEGORÍAS DEL PROVEEDOR: {{ $supplier->name ?? '' }}</h1></div>
                    <div class="col-sm-4">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item active">Operaciones</li>
                            <li class="breadcrumb-item"><a href="{{ route('action.suppliers') }}">Proveedores</a></li>
                            <li class="breadcrumb-item active">Mapping de categorías</li>
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

                            <form method="get" action="{{ route('suppliers.supplier_categories.index', [$supplier]) }}" class="form-inline">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">

                                            @include('forms.supplier_category', ['supplier_category_id' => $params['supplier_category_id'] ?? old('supplier_category_id'),
                                                'supplier_category_name' => $params['supplier_category_name'] ?? old('supplier_category_name')])
                                            @include('forms.supplier_category_id', ['supplierCategoryId' => $params['supplierCategoryId'] ?? old('supplierCategoryId')])
                                            @include('forms.category', ['category_id' => $params['category_id'] ?? old('category_id'), 'category_name' => $params['category_name'] ?? old('category_name')])

                                            <a class="mr-2 mb-2" href="{{ route('suppliers.supplier_categories.index', [$supplier]) }}">LIMPIAR</a>
                                            <button class="btn btn-success mb-2" type="submit" value="FILTRAR">FILTRAR</button>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <p>Hay {{ $supplier_categories->total() }} categorías de proveedor</p>
                            {!! $supplier_categories->appends($params)->render() !!}
                            <table class="table table-striped">
                                <tr class="table-warning">
                                    <th>@include('ordersby.supplier_categories', ['order_by' => 'supplier_categories.name', 'title' => 'Categoría Proveedor'])</th>
                                    <th>Unidades</th>
                                    <th>@include('ordersby.supplier_categories', ['order_by' => 'categories.name', 'title' => 'Categoría MPS'])</th>
                                    <th>Acciones</th>
                                </tr>
                                @foreach($supplier_categories as $supplier_category)
                                    <tr supplier_category-id="{{ $supplier_category->id }}">
                                        <td>{{ $supplier_category->id }} (<b>{{ $supplier_category->supplierCategoryId }}</b>) {{ $supplier_category->name }}</td>
                                        <td>{{ $supplier_category->products_count }}</td>
                                        <td>
                                            @isset($supplier_category->category_id)
                                                {{ $supplier_category->category->id }} ({{ $supplier_category->category->code }}) {{ $supplier_category->category->path }} / {{ $supplier_category->category->name }}
                                            @endisset
                                        </td>
                                        <td>
                                            <div class="row">
                                                <a class="mr-2" href="{{ route('suppliers.supplier_categories.edit', [$supplier, $supplier_category]) }}"
                                                   data-toggle="tooltip" title="Editar Mapping">
                                                    <i class="far fa-edit"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                            {{ $supplier_categories->appends($params)->render() }}

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
@push('scriptsEnd')
    @include('scripts.jquery-ui')
    @include('scripts.autocomplete-suppliercategories', ['suppliers_id' => '['.$supplier->id.']'])
    @include('scripts.autocomplete-categories')
@endpush
