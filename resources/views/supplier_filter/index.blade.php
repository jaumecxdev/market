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
                    <div class="col-sm-8"><h1>FILTROS: {{ $supplier->name }}</h1></div>
                    <div class="col-sm-4">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item active">Operaciones</li>
                            <li class="breadcrumb-item"><a href="{{ route('action.suppliers') }}">Proveedores</a></li>
                            <li class="breadcrumb-item active">Filtros</li>
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

                            @if (isset($supplier_filter))
                                <form method="post" action="{{ route('suppliers.supplier_filters.update', [$supplier, $supplier_filter]) }}" class="form-inline">
                                    @method('PATCH')
                            @else
                                <form method="post" action="{{ route('suppliers.supplier_filters.store', [$supplier]) }}" class="form-inline">
                            @endif
                                @csrf

                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            @include('forms.status_select', ['statuses' => $statuses, 'status_name' => $supplier_filter->status_name ?? null])
                                            @include('forms.brand', ['brand_id' => null, 'brand_name' => $supplier_filter->brand_name ?? null])
                                            @include('forms.category', ['category_id' => null, 'category_name' => $supplier_filter->category_name ?? null])
                                            @include('forms.supplier_sku', ['supplierSku' => $supplier_filter->supplierSku ?? null])
                                            @include('forms.product', [
                                                'item_select' => (isset($supplier_filter->name) ? 'name' :
                                                    (isset($supplier_filter->pn) ? 'pn' :
                                                    (isset($supplier_filter->ean) ? 'ean' :
                                                    (isset($supplier_filter->upc) ? 'upc' :
                                                    (isset($supplier_filter->isbn) ? 'isbn' :
                                                    (old('item_select') ?? 'name'
                                                )))))),
                                                'product_id' => null,
                                                'item_reference' => $supplier_filter->pn ??
                                                    $supplier_filter->ean ??
                                                    $supplier_filter->upc ??
                                                    $supplier_filter->isbn ??
                                                    $supplier_filter->name ?? old('item_reference') ?? null ])
                                            @include('forms.model', ['model' => $supplier_filter->model ?? null])
                                            @include('forms.cost_filter', [
                                                'cost_min' => $supplier_filter->cost_min ?? null,
                                                'cost_max' => $supplier_filter->cost_max ?? null])
                                            @include('forms.stock_filter', [
                                                'stock_min' => $supplier_filter->stock_min ?? null,
                                                'stock_max' => $supplier_filter->stock_max ?? null])

                                            @include('forms.custom', [
                                                'field_name' => 'field_name',
                                                'placeholder' => 'Nombre del campo',
                                                'value' => $supplier_filter->field_name ?? null])
                                            @include('forms.operator_select', ['field_operator' => $supplier_filter->field_operator ?? null])
                                            @include('forms.custom', [
                                                'field_name' => 'field_string',
                                                'placeholder' => 'Valor en Texto',
                                                'value' => $supplier_filter->field_string ?? null])
                                            @include('forms.custom', [
                                                'field_name' => 'field_integer',
                                                'placeholder' => 'Valor Entero',
                                                'value' => $supplier_filter->field_integer ?? null])
                                            @include('forms.custom', [
                                                'field_name' => 'field_float',
                                                'placeholder' => 'Valor Float',
                                                'value' => $supplier_filter->field_float ?? null])

                                            @include('forms.limit-products', ['limit_products' => $supplier_filter->limit_products ?? null])
                                            <button class="btn btn-success mb-2" type="submit">Guardar filtro</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">FILTROS</div>
                        <div class="card-body">
                            <p>Hay {{ count($supplier_filters) }} filtros</p>
                            <a href="{{ route('suppliers.products.get', [$supplier]) }}" class="btn btn-primary">Importar productos</a>
                            <br><br>

                            <table class="table table-striped">
                                <tr class="table-warning">
                                    <th>Estado</th>
                                    <th>Marca</th>
                                    <th>Categoría</th>
                                    <th>SKU prov.</th>
                                    <th>Nombre</th>
                                    <th>P/N</th>
                                    <th>EAN13</th>
                                    <th>UPC</th>
                                    <th>ISBN</th>
                                    <th>Modelo</th>
                                    <th>Coste mín</th>
                                    <th>Coste máx</th>
                                    <th>Stock mín</th>
                                    <th>Stock máx</th>
                                    <th>Campo</th>
                                    <th>Operador</th>
                                    <th>Texto</th>
                                    <th>Entero</th>
                                    <th>Float</th>
                                    <th>Limit</th>
                                    <th>Acciones</th>
                                </tr>

                                @foreach($supplier_filters as $supplier_filter)
                                    <tr>
                                        <td>{{ $supplier_filter->status_name }}</td>
                                        <td>{{ $supplier_filter->brand_name }}</td>
                                        <td>{{ $supplier_filter->category_name }}</td>
                                        <td>{{ $supplier_filter->supplierSku }}</td>
                                        <td>{{ $supplier_filter->name }}</td>
                                        <td>{{ $supplier_filter->pn }}</td>
                                        <td>{{ $supplier_filter->ean }}</td>
                                        <td>{{ $supplier_filter->upc }}</td>
                                        <td>{{ $supplier_filter->isbn }}</td>
                                        <td>{{ $supplier_filter->model }}</td>
                                        <td>{{ $supplier_filter->cost_min }}</td>
                                        <td>{{ $supplier_filter->cost_max }}</td>
                                        <td>{{ $supplier_filter->stock_min }}</td>
                                        <td>{{ $supplier_filter->stock_max }}</td>
                                        <td>{{ $supplier_filter->field_name }}</td>
                                        <td>{{ $supplier_filter->field_operator }}</td>
                                        <td>{{ $supplier_filter->field_string }}</td>
                                        <td>{{ $supplier_filter->field_integer }}</td>
                                        <td>{{ $supplier_filter->field_float }}</td>
                                        <td>{{ $supplier_filter->limit_products }}</td>
                                        <td class="form-inline">
                                            <a class="mr-2"
                                                href="{{ route('suppliers.supplier_filters.edit', [$supplier, $supplier_filter]) }}" data-toggle="tooltip" title="Editar">
                                                <i class="far fa-edit"></i></a>
                                            <form class="delete" action="{{ route('suppliers.supplier_filters.destroy', [$supplier, $supplier_filter]) }}" method="post">
                                                @method('delete')
                                                @csrf
                                                @include('forms.a_delete', ['title' => 'Eliminar'])
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
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
    @include('scripts.autocomplete-brands')
    @include('scripts.autocomplete-products')
    @include('scripts.submit-a-delete', ['question' => '¿Estás seguro de eliminar este filtro?'])
@endpush
