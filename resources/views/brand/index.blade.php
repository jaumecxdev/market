@extends('layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@push('menu')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('brands.create') }}" class="nav-link">Añadir marca</a></li>
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-10"><h1>Marcas</h1></div>
                    <div class="col-sm-2">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item active">Marcas</li>
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

                            <p>Hay {{ count($brands) }} marcas</p>

                            <table class="table table-striped">
                                <tr class="table-warning">
                                    <th>#</th>
                                    <th>Nombre</th>
                                    <th>Acciones</th>
                                </tr>
                                @foreach($brands as $brand)
                                    <tr brand-id="{{ $brand->id }}">
                                        <td>{{ $brand->id }}</td>
                                        <td>{{ $brand->name }}</td>
                                        <td>
                                            <div class="row">
                                                <a class="mr-2" href="{{ route('brands.edit', [$brand]) }}" data-toggle="tooltip" title="Editar">
                                                    <i class="far fa-edit"></i></a>
                                                <form class="delete" action="{{ route('brands.destroy', [$brand]) }}" method="post">
                                                    @method('delete')
                                                    @csrf
                                                    @include('forms.button_delete', ['title' => 'Eliminar'])
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>

                            {!! $brands->render() !!}
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

    <script>
        $(document).ready(function() {
            $(".delete").on("submit", function () {
                return (confirm('¿Estás seguro de eliminar esta marca?'))
            });
        });
    </script>
@endpush
