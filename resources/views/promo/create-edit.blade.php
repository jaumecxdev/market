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
                    <div class="col-sm-10"><h1>{{ isset($promo) ? 'EDITAR PROMOCIÓN: ' .$promo->name : 'AÑADIR PROMOCIÓN' }}</h1></div>
                    <div class="col-sm-2">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('promos.index') }}">Promociones</a></li>
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

                            @if (isset($promo))
                                <form method="post" action="{{ route('promos.update', $promo->id) }}">
                                    @method('PATCH')
                            @else
                                <form method="post" action="{{ route('promos.store') }}">
                            @endif
                                @csrf

                                <div class="form-group row">
                                    <label for="name" class="col-sm-2 col-form-label">Título Promoción</label>
                                    <div class="col-sm-4">
                                        @include('forms.name', ['name' => $promo->name ?? old('name', isset($copy) ? $copy->name : '' )])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="supplier" class="col-sm-2 col-form-label">Tienda / Proveedor</label>
                                    <div class="col-sm-2">
                                        @include('forms.shop', ['shops' => $shops, 'shop_id' => $promo->shop_id ??
                                        old('shop_id', isset($copy) ? $copy->shop_id : null)])
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.supplier', ['suppliers' => $suppliers, 'supplier_id' => $promo->supplier_id ??
                                        old('supplier_id', isset($copy) ? $copy->supplier_id : null)])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="product_id" class="col-sm-2 col-form-label">Producto</label>
                                    <div class="col-sm-6">
                                        @include('forms.product', ['item_select' => $params['item_select'] ?? old('item_select'),
                                                'product_id' => $promo->product_id ?? old('product_id', isset($copy) ? $copy->product_id : null),
                                                'item_reference' => (isset($promo) && $promo->product_id) ? $promo->product->name :
                                                old('item_reference', isset($copy) ? $copy->product->name : '')])
                                    </div>
                                    <div class="col-sm-4"></div>
                                    <div class="col-sm-2"></div>
                                    <div class="col-sm-10 form-inline">
                                        @include('forms.supplier_sku', ['supplierSku' => old('supplierSku')])
                                        @include('forms.mps_sku', ['MPSSku' => old('MPSSku')])
                                        @include('forms.market_product_sku', ['marketProductSku' => old('marketProductSku')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="price" class="col-sm-2 col-form-label">Precio oferta</label>
                                    <div class="col-sm-2">
                                        @include('forms.price', ['price' => $promo->price ?? old('price', isset($copy) ? $copy->price : '' )])
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.discount', ['discount' => $promo->discount ?? old('discount', isset($copy) ? $copy->discount : '' )])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="begin_at" class="col-sm-2 col-form-label">Fechas</label>
                                    <div class="col-sm-2">
                                        <div class="form-group">
                                            <div class="input-group date" id="begins_at" data-target-input="nearest">
                                                <input type="text" name="begins_at" class="form-control datetimepicker-input" data-target="#begins_at"
                                                       value="{{ (isset($promo) && $promo->begins_at) ?  $promo->begins_at->format('Y-m-d H:i') :
                                                       old('begins_at', isset($copy) ? $copy->begins_at : now()->format('Y-m-d'). '00:00') }}" />
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
                                                       value="{{ (isset($promo) && $promo->ends_at) ?  $promo->ends_at->format('Y-m-d H:i') :
                                                       old('begins_at', isset($copy) ? $copy->ends_at : now()->addDays(7)->format('Y-m-d'). '00:00') }}" />
                                                <div class="input-group-append" data-target="#ends_at" data-toggle="datetimepicker">
                                                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>



                                <br>
                                <div class="form-group row">
                                    <div class="col-sm-2"></div>
                                    <div class="col-sm-10">
                                        <a class="btn btn-danger" href="{{ route('promos.index') }}" role="button">Cancelar</a>
                                        <button type="submit" class="btn btn-primary">Guardar promoción</button>
                                    </div>
                                </div>

                            @if (isset($promo))</form>@else</form>@endif

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
