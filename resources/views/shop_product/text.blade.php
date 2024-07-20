@extends('layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('plugins/summernote/summernote-bs4.css') }}">
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-8"><h1>EDITAR TEXTOS PARA EXPORTACIÓN: {{ $shop_product->product->name }}</h1></div>
                    <div class="col-sm-4">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('action.shops') }}">Tiendas</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('shops.shop_products.index', [$shop]) }}">Productos</a></li>
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

                            <form method="post" action="{{ route('shops.shop_products.update_text', [$shop, $shop_product]) }}">
                                @csrf

                                <div class="form-group row">
                                    <label for="name" class="col-sm-2 col-form-label">Nombre</label>
                                    <div class="col-sm-10">
                                        @include('forms.name', ['name' => $shop_product->name ?? old('name')])
                                    </div>
                                </div>
                                <br>

                                <div class="form-group row">
                                    <label for="longdesc" class="col-sm-2 col-form-label">Descripción</label>
                                    <div class="col-sm-10">
                                        <div id="disable-summernote" class="btn btn-warning">Desabilitar HTML</div>
                                        @include('forms.longdesc', ['longdesc' => $shop_product->longdesc ?? old('longdesc')])
                                    </div>
                                </div>

                                <br><div class="form-group row">
                                    <label for="attributes" class="col-sm-2 col-form-label">Atributos</label>
                                    <div class="col-sm-10">
                                        @include('forms.textarea', ['rows' => 40, 'field_name' => 'attributes', 'value' => $shop_product->attributes ?? old('attributes')])
                                    </div>
                                </div>
                                <br>

                                <div class="form-group row">
                                    <div class="col-sm-2"></div>
                                    <div class="col-sm-10">
                                        <a class="btn btn-danger" href="{{ route('shops.shop_products.index', $shop) }}" role="button">Cancelar</a>
                                        <button type="submit" class="btn btn-primary">Guardar texto</button>
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
    <script src="{{ asset('plugins/summernote/summernote-bs4.min.js') }}"></script>
    @include('scripts.product-textareas')
@endpush

