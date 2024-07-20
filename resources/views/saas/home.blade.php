@extends('saas.layouts.app')

@section('content')
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-12">
                    <h1 class="m-0 text-dark">Dashboard {{ Auth::user()->name }}</h1>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <div class="row">

                    <div class="col-12 col-lg-2 col-md-4 col-sm-6">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3>{{ $suppliers_count }}</h3>
                                <h4>Proveedores</h4>
                            </div>
                            <a href="{{ route('saas.suppliers') }}" class="small-box-footer">Más info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>

                    <div class="col-12 col-lg-2 col-md-4 col-sm-6">
                        <div class="small-box bg-primary">
                            <div class="inner">
                                <h3>{{ $products_count }}</h3>
                                <h4>Productos</h4>
                            </div>
                            <a href="{{ route('saas.products') }}" class="small-box-footer">Más info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>

                    <div class="col-12 col-lg-2 col-md-4 col-sm-6">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3>{{ $markets_count }}</h3>
                                <h4>Marketplaces</h4>
                            </div>
                            <a href="{{ route('saas.markets') }}" class="small-box-footer">Más info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>

                    <div class="col-12 col-lg-2 col-md-4 col-sm-6">
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <h3>{{ $shops_count }}</h3>
                                <h4>Tiendas</h4>
                            </div>
                            <a href="{{ route('saas.shops') }}" class="small-box-footer">Más info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>

                    <div class="col-12 col-lg-2 col-md-4 col-sm-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3>{{ $orders_count }}</h3>
                                <h4>Pedidos</h4>
                            </div>
                            <a href="{{ route('saas.orders') }}" class="small-box-footer">Más info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>

                </div>
            </div>
        </section>
    </div>
@endsection

@push('scriptsEnd')
    <script src="{{ asset('plugins/jquery-ui/jquery-ui.min.js') }}"></script>
    <script>
        $.widget.bridge('uibutton', $.ui.button)
    </script>
    <script src="{{ asset('plugins/chart.js/Chart.min.js') }}"></script>
    <script src="{{ asset('dist/js/pages/dashboard.js?').time() }}"></script>
@endpush
