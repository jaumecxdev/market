@extends('layouts.app')

@push('menu')
    @role('admin')
        <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('products.attributes.create', [$product]) }}" class="nav-link">Añadir Atributo</a></li>
    @endrole
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-10"><h1>ATRIBUTOS: {{ $product->name }}</h1></div>
                    <div class="col-sm-2">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('products.show', [$product]) }}">Producto</a></li>
                            <li class="breadcrumb-item active">Atributos</li>
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

                            @include('partials.errors')
                            @include('partials.status')

                            <p>Hay {{ count($provider_product_attributes) }} atributos</p>
                            <table class="table table-striped">
                                <tr class="table-warning">
                                    <th>Etiqueta</th>
                                    <th>Valor</th>
                                    <th>Acciones</th>
                                </tr>

                                {{-- provider_product_attribute', 'provider_attribute', 'provider_attributes_values --}}

                                {{-- provider_id" => 3, "product_id" => 264527, provider_attribute, "provider_attribute_value_id" --}}

                                @foreach($provider_product_attributes as $provider_product_attribute)
                                    <tr provider_product_attribute-id="{{ $provider_product_attribute->id }}">
                                        <td>{{ $provider_product_attribute->id }} ({{ $provider_product_attribute->provider_attribute_id}}) {{ $provider_product_attribute->provider_attribute->name ?? null }}</td>
                                        <td>{{ $provider_product_attribute->provider_attribute_value->name }}</td>
                                        <td>
                                            <div class="row">
                                                <a class="mr-2"
                                                    href="{{ route('products.attributes.edit', [$product, $provider_product_attribute]) }}" data-toggle="tooltip" title="Editar">
                                                    <i class="far fa-edit"></i></a>
                                                <form class="delete" action="{{ route('products.attributes.destroy', [$product, $provider_product_attribute]) }}" method="post">
                                                    @method('delete')
                                                    @csrf
                                                    @include('forms.button_delete', ['title' => 'Eliminar'])
                                                </form>
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
    <script>
        $(document).ready(function() {

            $(".delete").on("submit", function () {
                return (confirm('¿Estás seguro de eliminar este atributo de producto?'))
            });

        });
    </script>
@endpush
