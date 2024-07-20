@extends('layouts.app')

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-8"><h1>Proveedores</h1></div>
                    <div class="col-sm-4">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item active">Operaciones</li>
                            <li class="breadcrumb-item active">Proveedores</li>
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

                            <table class="table table-striped">
                                <tr class="table-warning">
                                    <th>Proveedor</th>
                                    <th>Acciones</th>
                                </tr>
                                @foreach($suppliers as $supplier)
                                    <tr supplier-id="{{ $supplier->id }}">
                                        <td>{{ $supplier->id }} {{ $supplier->name }}</td>
                                        <td>
                                            <div class="row">
                                                <a class="mr-2" href="{{ route('suppliers.supplier_params.index', [$supplier]) }}"><i class="fas fa-euro-sign nav-icon"></i> Parámetros Importación del Coste |</a>
                                                <a class="mr-2" href="{{ route('suppliers.supplier_categories.index', [$supplier]) }}"><i class="fas fa-list nav-icon"></i> Mapping Categorías |</a>
                                                <a class="mr-2" href="{{ route('suppliers.supplier_filters.index', [$supplier]) }}"><i class="fas fa-filter"></i> Filtros |</a>
                                                <a class="mr-2" href="{{ route('suppliers.products.get', [$supplier]) }}"><i class="fas fa-cloud-download-alt"></i> Importar productos |</a>
                                                <a class="mr-2" href="{{ route('suppliers.products.getpricesstocks', [$supplier]) }}"><i class="fas fa-cloud-download-alt"></i> Actualizar Precios y Stocks |</a>
                                                <a class="mr-2" href="{{ route('products.index', ['supplier_id' => $supplier->id]) }}"><i class="fas fa-laptop nav-icon"></i> Productos</a>
                                            </div>
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
    <!-- jQuery UI 1.11.4 -->
    <script src="{{ asset('plugins/jquery-ui/jquery-ui.min.js') }}"></script>


@endpush
