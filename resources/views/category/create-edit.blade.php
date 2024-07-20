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
                    <div class="col-sm-10"><h1>{{ isset($category) ? 'EDITAR CATEGORÍA: ' .$category->name : 'AÑADIR CATEGORÍA' }}</h1></div>
                    <div class="col-sm-2">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('categories.index') }}">Categorías</a></li>
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

                            @if (isset($category))
                                <form method="post" action="{{ route('categories.update', [$category]) }}">
                                    @method('PATCH')
                            @else
                                <form method="post" action="{{ route('categories.store') }}">
                            @endif
                                @csrf


                                <div class="form-group row">
                                    <label for="category_id" class="col-sm-2 col-form-label">Categoría padre</label>
                                    <div class="col-sm-8">
                                        @include('forms.category', ['category_id' => $category->parent_id ?? old('category_id'), 'category_name' => $category->parent->name ?? old('category_name')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="code" class="col-sm-2 col-form-label">Código | Nombre</label>
                                    <div class="col-sm-2">
                                        @include('forms.code', ['code' => $category->code ?? old('code')])
                                    </div>
                                    <div class="col-sm-6">
                                        @include('forms.name', ['name' => $category->name ?? old('name')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="seo_name" class="col-sm-2 col-form-label">SEO</label>
                                    <div class="col-sm-4">
                                        @include('forms.seo_name', ['seo_name' => $category->seo_name ?? old('seo_name')])
                                    </div>
                                </div>

                                <br>
                                <div class="form-group row">
                                    <div class="col-sm-2"></div>
                                    <div class="col-sm-10">
                                        <a class="btn btn-danger" href="{{ route('categories.index') }}" role="button">Cancelar</a>
                                        <button type="submit" class="btn btn-primary">Guardar categoria</button>
                                    </div>
                                </div>

                            @if (isset($category))</form>@else</form>@endif

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
