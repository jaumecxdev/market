@extends('layouts.app')

@push('styles')
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

                            <a href="{{ route('shops.shop_products.export_product', [$shop]).'?'.http_build_query($params) }}">Exportar productos a Excel</a><br>
                            <a href="{{ route('shops.shop_products.export_json', [$shop, 'ean']).'?'.http_build_query($params) }}">Exportar EANs a JSON</a><br>
                            <a href="{{ route('shops.shop_products.export_json', [$shop, 'pn']).'?'.http_build_query($params) }}">Exportar PNs a JSON</a><br>
                            <a href="{{ route('shops.shop_products.export_json', [$shop, 'name']).'?'.http_build_query($params) }}">Exportar Títulos a JSON</a><br>
                            <a href="{{ route('shops.shop_products.promo', [$shop]).'?'.http_build_query($params) }}">Exportar Promo</a><br>
                            <br>
                            <form method="POST" action="{{ route('shops.shop_products.repricing', [$shop]) }}"
                                  class="uploader" accept-charset="utf-8" enctype="multipart/form-data">
                                @csrf
                                <div class="form-group row">
                                    <div class="col-sm-12">
                                        <input type="file" id="file-input" name="fileinput[]" multiple />
                                        @error('fileinput')<br><br><div class="alert alert-danger">{{ $message }}</div>@enderror
                                        <button type="submit" class="btn btn-primary">RePricing</button>
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
@endpush
