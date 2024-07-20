@extends('layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@push('menu')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('categories.create') }}" class="nav-link">Añadir categoría</a></li>
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('categories.import') }}" class="nav-link">Importar categorías de Google</a></li>
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('categories.canons') }}" class="nav-link">Cánones</a></li>
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-10"><h1>Categorías</h1></div>
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

                            <form method="get" action="{{ route('categories.index') }}" class="form-inline">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">

                                            @include('forms.category', ['category_id' => $params['category_id'] ?? old('category_id'), 'category_name' => $params['category_name'] ?? old('category_name')])

                                            <a class="mr-2 mb-2" href="{{ route('categories.index') }}">LIMPIAR</a>
                                            <button class="btn btn-success mb-2" type="submit" value="FILTRAR">FILTRAR</button>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <p>Hay {{ $categories->total() }} categorias</p>
                            {!! $categories->appends($params)->render() !!}
                            <table class="table table-striped">
                                <tr class="table-warning">
                                    <th>#</th>
                                    <th>Padre</th>
                                    <th>Nombre</th>
                                    <th>Árbol</th>
                                    <th>SEO</th>
                                    <th>Acciones</th>
                                </tr>
                                @foreach($categories as $category)
                                    <tr category-id="{{ $category->id }}">
                                        <td>{{ $category->id }}</td>
                                        <td>{{ isset($category->parent) ? '('.$category->parent_code.') ' .$category->parent->name : '' }}</td>
                                        <td>({{ $category->code }}) {{ $category->name }}</td>
                                        <td>{{ $category->path }}</td>
                                        <td>{{ $category->seo_name }}</td>
                                        <td>
                                            <div class="row">
                                                <a class="mr-2" href="{{ route('categories.edit', [$category]) }}" data-toggle="tooltip" title="Editar SEO">
                                                    <i class="far fa-edit"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>

                            {!! $categories->appends($params)->render() !!}
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
