@extends('layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@push('menu')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('categories.index') }}" class="nav-link">Categorías</a></li>
    {{-- <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('categories.canons.sync') }}"
        class="btn btn-danger">IMPORTANTE: SINCRONIZAR TABLAS DESPUÉS DE MODIFICAR</a></li> --}}
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-10"><h1>Cánones Digitales</h1></div>
                    <div class="col-sm-2">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item active">Categorías</li>
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

                            <p>Hay {{ $category_canons->count() }} cánones</p>

                            <table class="table table-striped">
                                <tr class="table-warning">
                                    <th>Localización</th>
                                    <th>Categoría</th>
                                    <th>Canon</th>
                                </tr>
                                @foreach($category_canons as $category_canon)
                                    <tr category_canon-id="{{ $category_canon->id }}">
                                        <td>{{ $category_canon->locale }}</td>
                                        <td>{{ $category_canon->category_id }} ({{ $category_canon->category_code }}) {{ $category_canon->category_name }}</td>
                                        <td>{{ $category_canon->canon }} €</td>
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
