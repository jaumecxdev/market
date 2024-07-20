@extends('layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@push('menu')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('markets.create') }}" class="nav-link">Añadir Marketplace</a></li>
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-10"><h1>Marketplaces</h1></div>
                    <div class="col-sm-2">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item active">Marketplaces</li>
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

                            <p>Hay {{ count($markets) }} marketplaces</p>

                            <table class="table table-striped">
                                <tr class="table-warning">
                                    <th>#</th>
                                    <th>Code</th>
                                    <th>Nombre</th>
                                    <th>WS</th>

                                    <th>PN</th>
                                    <th>EAN13</th>
                                    <th>Título</th>
                                    <th>M.Category</th>
                                    <th>Images</th>
                                    <th>Attributes</th>

                                    <th>Acciones</th>
                                </tr>
                                @foreach($markets as $market)
                                    <tr market-id="{{ $market->id }}">
                                        <td>{{ $market->id }}</td>
                                        <td>{{ $market->code }}</td>
                                        <td>{{ $market->name }}</td>
                                        <td>{{ $market->ws }}</td>
                                        <td>{{ $market->pn_required ? 'Sí' : 'No' }}</td>
                                        <td>{{ $market->ean_required ? 'Sí' : 'No' }}</td>
                                        <td>{{ $market->name_required ? 'Sí' : 'No' }}</td>
                                        <td>{{ $market->market_category_required ? 'Sí' : 'No' }}</td>
                                        <td>{{ $market->images_required ? 'Sí' : 'No' }}</td>
                                        <td>{{ $market->attributes_required ? 'Sí' : 'No' }}</td>
                                        <td>
                                            <div class="row">
                                                <a class="mr-2" href="{{ route('markets.edit', [$market]) }}"
                                                   data-toggle="tooltip" title="Editar">
                                                    <i class="far fa-edit"></i></a>
                                                <form class="delete" action="{{ route('markets.destroy', [$market]) }}" method="post">
                                                    @method('delete')
                                                    @csrf
                                                    @include('forms.button_delete', ['title' => 'Eliminar'])
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>

                            {!! $markets->render() !!}

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
                return (confirm('¿Estás seguro de eliminar este marketplace?'))
            });
        });
    </script>
@endpush
