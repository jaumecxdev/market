@extends('layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@push('menu')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('shops.shop_groups.get', [$shop]) }}" class="nav-link">Descargar Grupos</a></li>
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('shops.shop_groups.post', [$shop]) }}" class="nav-link">Subir Grupos</a></li>
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-8"><h1>GRUPOS DE CATEGORÍAS: ({{ $shop->market->name }}) {{ $shop->name }}</h1></div>
                    <div class="col-sm-4">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item active">Operaciones</li>
                            <li class="breadcrumb-item"><a href="{{ route('action.shops') }}">Tiendas</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('shops.shop_products.index', [$shop]) }}">Productos</a></li>
                            <li class="breadcrumb-item active">Grupos</li>
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

                            <form method="post" action="{{ route('shops.shop_groups.store', [$shop]) }}">
                                @csrf

                                <div class="form-group row">
                                    <label for="group_id" class="col-sm-2 col-form-label">Grupo</label>
                                    <div class="col-sm-3">
                                        @include('forms.group', ['groups' => $groups, 'group_id' => old('group_id')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="market_category_id" class="col-sm-2 col-form-label">Categoría del MP</label>
                                    <div class="col-sm-3">
                                        @include('forms.market_category', ['market_category_id' => old('market_category_id'), 'market_category_name' => old('market_category_name')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <div class="col-sm-2"></div>
                                    <div class="col-sm-10">
                                        <button type="submit" class="btn btn-primary">Añadir categoría al grupo</button>
                                    </div>
                                </div>
                            </form>

                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">GRUPOS</div>
                        <div class="card-body">
                            <p>Hay {{ $shop_groups_count }} categorías en grupos</p>
                            <br>
                            <table class="table table-striped">
                                <tr class="table-warning">
                                    <th>#</th>
                                    <th>Grupo del MP</th>
                                    <th>Categoría del MP</th>
                                    <th>Camino</th>
                                    <th>Acciones</th>
                                </tr>

                                @foreach($shop_groups as $shop_group)
                                    <tr shop_group-id="{{ $shop_group->id }}">
                                        <td>{{ $shop_group->id }}</td>
                                        <td>{{ $shop_group->group->name }}</td>
                                        <td>{{ $shop_group->market_category->name }}</td>
                                        <td>{{ $shop_group->market_category->path }}</td>
                                        <td>
                                            <form class="delete" action="{{ route('shops.shop_groups.destroy', [$shop, $shop_group]) }}" method="post">
                                                @method('delete')
                                                @csrf
                                                @include('forms.button_delete', ['title' => 'Eliminar'])
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
    @include('scripts.autocomplete-marketcategories', ['market_id' => $shop->market_id])
    @include('scripts.submit-delete', ['question' => '¿Estás seguro de eliminar esta categoría del grupo?'])
@endpush
