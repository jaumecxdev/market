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
                    <div class="col-sm-10"><h1>{{ isset($brand) ? 'EDITAR MARCA: ' .$brand->name : 'AÑADIR MARCA' }}</h1></div>
                    <div class="col-sm-2">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('brands.index') }}">Marcas</a></li>
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

                            @if (isset($brand))
                                <form method="post" action="{{ route('brands.update', $brand->id) }}">
                                    @method('PATCH')
                            @else
                                <form method="post" action="{{ route('brands.store') }}">
                            @endif
                                @csrf

                                <div class="form-group row">
                                    <label for="name" class="col-sm-2 col-form-label">Nombre</label>
                                    <div class="col-sm-10">
                                        @include('forms.name', ['name' => $brand->name ?? old('name')])
                                    </div>
                                </div>

                                <br>
                                <div class="form-group row">
                                    <div class="col-sm-2"></div>
                                    <div class="col-sm-10">
                                        <a class="btn btn-danger" href="{{ route('brands.index') }}" role="button">Cancelar</a>
                                        <button type="submit" class="btn btn-primary">Guardar marca</button>
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
