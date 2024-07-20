@extends('layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@push('menu')
@endpush

@section('content')
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-10"><h1>AÑADIR PRODUCTO A LOS FILTROS DE LA TIENDA</h1></div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="row">
                <div class="col-12">

                    <div class="card">
                        <div class="card-body">

                            @include('partials.status')
                            @include('partials.errors')

                            <form method="post" action="{{ route('products.addtoshopupdate', [$product]) }}">
                                @csrf

                                <p class="text-success"><b>PRODUCTO</b></p>

                                <span class="font-weight-bold">{{ $product->getMPSSku() }}</span><br>
                                <span class="">{{ $product->name }}</span><br><br>

                                <p class="text-success"><b>TIENDA</b></p>

                                <div class="form-group row">
                                    <label for="shop_id" class="col-sm-2 col-form-label">Tienda</label>
                                    <div class="col-sm-4">
                                        @include('forms.shop', ['shops' => $shops, 'shop_id' => old('shop_id')])
                                    </div>
                                </div>

                                <p class="text-success"><b>OPCIONAL: PARÁMETROS DE EXPORTACIÓN A MARKETPLACES: CÁLCULO DEL PRECIO FINAL Y STOCKS</b></p>

                                <div class="form-group row">
                                    <label for="fee" class="col-sm-2 col-form-label">% Bcio | € Bcio Mín | Mpe</label>
                                    <div class="col-sm-2">
                                        @include('forms.fee', ['fee' => old('fee')])
                                        <small>Margen de beneficio %.</small>
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.bfit_min', ['bfit_min' => old('bfit_min')])
                                        <small>Beneficio € mínimo por producto.</small>
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.mps_fee', ['mps_fee' => old('mps_fee')])
                                        <small>Intermediación Mpe %.</small>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="fee" class="col-sm-2 col-form-label">€ Precio MP | Stock MP</label>
                                    <div class="col-sm-2">
                                        @include('forms.price', ['price' => old('price')])
                                        <small>PVP € fijado para esta Tienda</small>
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.stock', ['stock' => old('stock')])
                                        <small>Stock fijado para esta Tienda</small>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="stock_min" class="col-sm-2 col-form-label">Stock mínimo y Máximo</label>
                                    <div class="col-sm-4 form-inline">
                                        @include('forms.stock_filter', ['stock_min' => old('stock_min'), 'stock_max' => old('stock_max')])
                                        <small>No se publicará en la Tienda si tiene menos de STOCK MÍNIMO.<br>Si Stock > STOCK MÁXIMO luego Stock Tienda = STOCK MÁXIMO.</small>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="starts_at" class="col-sm-2 col-form-label">Fecha inicio | fin</label>
                                    <div class="col-sm-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                            </div>
                                            <input type="text" name="starts_at" id="starts_at"  placeholder="yyyy-mm-dd" class="form-control" class="form-control mr-2 mb-2">
                                        </div>
                                        <small>Fechas en las que se aplicarán estos parámetros.</small>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                            </div>
                                            <input type="text" name="ends_at" id="ends_at"  placeholder="yyyy-mm-dd" class="form-control" class="form-control mr-2 mb-2">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <div class="col-sm-2"></div>
                                    <div class="col-sm-10">
                                        <button type="submit" class="btn btn-primary">Añadir producto a la tienda</button>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.15/jquery.mask.min.js"></script>
    <script>
        $(function() {
            $('#starts_at').mask('0000-00-00');
            $('#ends_at').mask('0000-00-00');
        });
    </script>
@endpush
