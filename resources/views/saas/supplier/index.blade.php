@extends('saas.layouts.app')

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-12"><h1>Proveedores</h1></div>
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
                                                {{-- <a class="mr-2" href="{{ route('saas.suppliers.edit', [$supplier]) }}"><i class="fas fa-cog nav-icon"></i> Configuración |</a>
                                                <a class="mr-2" href="{{ route('saas.suppliers.edit', [$supplier]) }}"><i class="fas fa-cog nav-icon"></i> Categorías |</a> --}}
                                                <a class="mr-2" href="{{ route('saas.suppliers.products.get', [$supplier]) }}"><i class="fas fa-cloud-download-alt"></i> Importar &nbsp;</a>
                                                <a class="mr-2" href="{{ route('saas.products', ['supplier_id' => $supplier->id]) }}"><i class="fas fa-laptop nav-icon"></i> Productos</a>
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
