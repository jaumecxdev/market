@extends('layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css') }}">
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-10"><h1>{!! 'EXPORTAR PRODUCTOS PARA PROMOCIÓN EN: <span class="text-primary">(' .$shop->market->name. ') ' .$shop->name .'</span>' !!}</h1></div>
                    <div class="col-sm-2">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('shops.shop_products.index', [$shop]) }}">Productos de la Tienda</a></li>
                            <li class="breadcrumb-item active">Exportar a Promo</li>
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

                            <form method="post" action="{{ route('shops.shop_products.export_promo', [$shop]).'?'.http_build_query($params) }}">
                                @csrf

                                <div class="form-group row">
                                    <label for="name" class="col-sm-2 col-form-label">Título Promoción</label>
                                    <div class="col-sm-4">
                                        @include('forms.name', ['name' => '' ])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="price" class="col-sm-2 col-form-label">Promo Descuentos en %</label>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'discount', 'placeholder' => 'Web y Móvil'])
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'mobile', 'placeholder' => 'App'])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="price" class="col-sm-2 col-form-label">Descuento adicional %</label>
                                    <div class="col-sm-2">
                                        @include('forms.select', ['field_name' => 'target', 'placeholder' => 'Objetivo',
                                            'options' => ['store_fans', 'fresh_member'], 'option_selected' => null])
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'extra', 'placeholder' => 'Descuento extra'])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="price" class="col-sm-2 col-form-label">Límite por comprador</label>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'limit', 'placeholder' => 'Límite'])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="begin_at" class="col-sm-2 col-form-label">Fechas</label>
                                    <div class="col-sm-2">
                                        <div class="form-group">
                                            <div class="input-group date" id="begins_at" data-target-input="nearest">
                                                <input type="text" name="begins_at" class="form-control datetimepicker-input" data-target="#begins_at"
                                                       value="{{ now()->format('Y-m-d'). '00:00' }}" />
                                                <div class="input-group-append" data-target="#begins_at" data-toggle="datetimepicker">
                                                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="form-group">
                                            <div class="input-group date" id="ends_at" data-target-input="nearest">
                                                <input type="text" name="ends_at" class="form-control datetimepicker-input" data-target="#ends_at"
                                                       value="{{ now()->addDays(7)->format('Y-m-d'). '23:30' }}" />
                                                <div class="input-group-append" data-target="#ends_at" data-toggle="datetimepicker">
                                                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="ready" class="col-sm-2 col-form-label">¿Importar a Promos?</label>
                                    <div class="col-sm-10">
                                        @include('forms.import_checkbox', ['import' => false])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="ready" class="col-sm-2 col-form-label">¿Importar a Promos?</label>
                                    <div class="col-sm-10">
                                        @include('forms.update_checkbox', ['update' => false])
                                    </div>
                                </div>

                                <br>
                                <div class="form-group row">
                                    <div class="col-sm-2"></div>
                                    <div class="col-sm-10">
                                        <a class="btn btn-danger" href="{{ route('shops.shop_products.index', [$shop]) }}" role="button">Cancelar</a>
                                        <button type="submit" class="btn btn-primary">Exportar Productos a Promo XLS</button>
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
    @include('scripts.autocomplete-products')
    <script src="{{ asset('plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') }}"></script>
    <script>
        $(function () {
            $('#begins_at').datetimepicker({locale: 'es', format: 'YYYY-MM-DD HH:mm'});
            $('#ends_at').datetimepicker({locale: 'es', format: 'YYYY-MM-DD HH:mm'});
        })
    </script>
@endpush
