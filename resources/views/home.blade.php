@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Dashboard {{ Auth::user()->name }}</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="/">Home</a></li>
                            <li class="breadcrumb-item active">Dashboard</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <!-- /.content-header -->

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid dashboard-boxes">

                <div class="row">
                    <div class="col-lg-2 col-6">
                        <!-- small box -->
                        <div class="small-box bg-primary">
                            <div class="inner">
                                <h3>{{ $counts['suppliers'] }}</h3>

                                <p>Proveedores</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-android-download"></i>
                            </div>
                            <a href="{{ route('suppliers.index') }}" class="small-box-footer">Más info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <!-- ./col -->
                    <div class="col-lg-2 col-6">
                        <!-- small box -->
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3>{{ $counts['markets'] }}</h3>

                                <p>Marketplaces</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-upload"></i>
                            </div>
                            <a href="{{ route('markets.index') }}" class="small-box-footer">Más info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <!-- ./col -->
                    <div class="col-lg-2 col-6">
                        <!-- small box -->
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3>{{ $counts['shops'] }}</h3>

                                <p>Tiendas</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-home"></i>
                            </div>
                            <a href="{{ route('shops.index') }}" class="small-box-footer">Más info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <!-- ./col -->
                    <div class="col-lg-2 col-6">
                        <!-- small box -->
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3>{{ $counts['categories'] }}</h3>

                                <p>Categorías</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-android-list"></i>
                            </div>
                            <a href="{{ route('categories.index') }}" class="small-box-footer">Más info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <!-- ./col -->
                    <div class="col-lg-2 col-6">
                        <!-- small box -->
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3>{{ $counts['brands'] }}</h3>

                                <p>Marcas</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-social-apple"></i>
                            </div>
                            <a href="{{ route('brands.index') }}" class="small-box-footer">Más info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <!-- ./col -->
                </div>


                <div class="row">
                    <div class="col-lg-2 col-6">
                        <!-- small box -->
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3>{{ $counts['products'] }}</h3>

                                <p>Productos</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-laptop"></i>
                            </div>
                            <a href="{{ route('products.index') }}" class="small-box-footer">Más info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <!-- ./col -->
                    <div class="col-lg-2 col-6">
                        <!-- small box -->
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <h3>{{ number_format($counts['costs'], 0, ',', '.') }} €</h3>

                                <p>Stock</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-cash"></i>
                            </div>
                            <a href="{{ route('products.index') }}" class="small-box-footer">Más info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <!-- ./col -->
                    <div class="col-lg-2 col-6">
                        <!-- small box -->
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3>{{ $counts['orders'] }}</h3>

                                <p>Pedidos</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-stats-bars"></i>
                            </div>
                            <a href="{{ route('orders.index') }}" class="small-box-footer">Más info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <!-- ./col -->
                    <div class="col-lg-2 col-6">
                        <!-- small box -->
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3>{{ $counts['buyers'] }}</h3>

                                <p>Clientes</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-person"></i>
                            </div>
                            <a href="{{ route('buyers.index') }}" class="small-box-footer">Más info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <!-- ./col -->
                    <div class="col-lg-2 col-6">
                        <!-- small box -->
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <h3>{{ number_format($counts['orders_price'], 0, ',', '.') }} €</h3>

                                <p>Total ventas</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-cash"></i>

                            </div>
                            <a href="{{ route('orders.index') }}" class="small-box-footer">Más info <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <!-- ./col -->

                </div>


                <!-- Main row -->
                <div class="row">

                    <!-- DONUT - Ventas por Marketplace - .Left col -->
                    <section class="col-lg-6 connectedSortable">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-chart-pie mr-1"></i>Ventas por Marketplace</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                                    <button type="button" class="btn btn-tool" data-card-widget="remove"><i class="fas fa-times"></i></button>
                                </div>
                            </div><!-- /.card-header -->
                            <div class="card-body">
                                <canvas id="sales-chart-canvas" style="min-height: 300px; height: 300px; max-height: 300px; max-width: 100%;"></canvas>
                            </div>
                        </div>
                    </section>


                    <!-- GRAPH - Ventas por mes - right col (We are only adding the ID to make the widgets sortable)-->
                    <section class="col-lg-6 connectedSortable">

                        <!-- solid sales graph -->
                        <div class="card bg-gradient-info">
                            <div class="card-header border-0">
                                <h3 class="card-title"><i class="fas fa-th mr-1"></i>Ventas por mes</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn bg-info btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                                    <button type="button" class="btn bg-info btn-tool" data-card-widget="remove"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                            <div class="card-body">
                                <canvas class="chart" id="line-chart" style="min-height: 300px; height: 300px; max-height: 300px; max-width: 100%;"></canvas>
                            </div>
                            <!-- /.card-body -->

                        </div>
                        <!-- /.card -->

                    </section>
                    <!-- right col -->

                </div>
                <!-- /.row (main row) -->
            </div><!-- /.container-fluid -->

            <div id="qqqq"></div>
        </section>
        <!-- /.content -->
    </div>
@endsection

@push('scriptsEnd')
    <!-- jQuery UI 1.11.4 -->
    <script src="{{ asset('plugins/jquery-ui/jquery-ui.min.js') }}"></script>
    <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
    <script>
        $.widget.bridge('uibutton', $.ui.button)
    </script>
    <!-- ChartJS -->
    <script src="{{ asset('plugins/chart.js/Chart.min.js') }}"></script>
    <!-- AdminLTE dashboard demo (This is only for demo purposes) -->
    <script src="{{ asset('dist/js/pages/dashboard.js?').time() }}"></script>

@endpush
