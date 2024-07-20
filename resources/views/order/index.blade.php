@extends('layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@push('menu')
    @role('admin')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('order_payments.index') }}" class="nav-link">Control de cobros</a></li>
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('receivers.index') }}" class="nav-link">Notificables</a></li>
    @endrole
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-10"><h1>Pedidos</h1></div>
                    <div class="col-sm-2">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item active">Pedidos</li>
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

                            <form method="get" action="{{ route('orders.index') }}" class="form-inline">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            @include('forms.shop', ['shops' => $shops, 'shop_id' => $params['shop_id'] ?? old('shop_id')])
                                            @include('forms.status_select', ['statuses' => $statuses, 'status_id' => $params['status_id'] ?? old('status_id')])
                                            @include('forms.market_order_id', ['marketOrderId' => $params['marketOrderId'] ?? old('marketOrderId')])
                                            @include('forms.custom', ['field_name' => 'buyer_name', 'placeholder' => 'Cliente', 'value' => $params['buyer_name'] ?? old('buyer_name')])
                                            <a class="mr-2 mb-2" href="{{ route('orders.index') }}">LIMPIAR</a>
                                            <button class="btn btn-success mb-2" type="submit" value="FILTRAR">FILTRAR</button>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <p>Hay {{ $orders->total() }} pedidos</p>
                            <table class="table table-striped">
                                <tr class="table-warning">
                                    <th>#</th>
                                    <th>@include('ordersby.orders', ['order_by' => 'marketOrderId', 'title' => 'ID del MP'])</th>
                                    <th>@include('ordersby.orders', ['order_by' => 'shop_name', 'title' => 'Tienda'])</th>
                                    <th>@include('ordersby.orders', ['order_by' => 'status_name', 'title' => 'Estado'])</th>
                                    <th>@include('ordersby.orders', ['order_by' => 'price', 'title' => 'Total'])</th>
                                    <th>@include('ordersby.orders', ['order_by' => 'buyer_name', 'title' => 'Cliente'])</th>
                                    <th>@include('ordersby.orders', ['order_by' => 'updated_at', 'title' => 'Actual.'])</th>
                                    <th>@include('ordersby.orders', ['order_by' => 'created_at', 'title' => 'Creado'])</th>
                                    <th>Acciones</th>
                                </tr>
                                @foreach($orders as $order)
                                    <tr order-id="{{ $order->id }}">
                                        <td>{{ $order->id }}</td>
                                        <td><a href="{{ str_replace ('%marketOrderId', $order->marketOrderId, $order->market_order_url) }}">
                                                {{ $order->marketOrderId }}</a></td>
                                        <td>{{ $order->market_shop_name }}</td>
                                        <td>{{ $order->status_name ?? $order->status->marketStatusName }}</td>
                                        <td>{{ $order->price }} {{ $order->currency_code }}</td>
                                        <td><a href="{{ route('buyers.show', [$order->buyer ?? 0]) }}">{{ $order->buyer_name ?? null }}</a></td>
                                        <td>{{ $order->updated_at->format('Y-m-d H:i:s') }}</td>
                                        <td>{{ $order->created_at->format('Y-m-d H:i:s') }}</td>
                                        <td>
                                            <div class="row">
                                                <a class="mr-2" href="{{ route('orders.show', [$order]) }}"
                                                   data-toggle="tooltip" title="Ver"><i class="far fa-eye"></i></a>

                                                <a class="mr-2" href="{{ route('orders.shipments', [$order]) }}"
                                                   data-toggle="tooltip" title="Trackings"><i class="fas fa-shipping-fast"></i></a>

                                                <a class="mr-2" href="{{ route('orders.comments', [$order]) }}"
                                                   data-toggle="tooltip" title="Comentarios del pedido"><i class="fas fa-comments"></i></a>

                                                <a class="mr-2 {{ $order->notified ? 'text-danger' : '' }}" href="{{ route('orders.send', [$order]) }}"
                                                   data-toggle="tooltip" title="Enviar notificaciones"><i class="far fa-paper-plane"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>

                            {!! $orders->render() !!}

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
