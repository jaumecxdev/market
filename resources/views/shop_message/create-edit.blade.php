@extends('layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-10"><h1>{{ isset($order) ? 'EDITAR PEDIDO: ' .$order->marketOrderId : 'AÑADIR PEDIDO' }}</h1></div>
                    <div class="col-sm-2">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('orders.index') }}">Pedidos</a></li>
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

                            @if (isset($order))
                                <form method="post" action="{{ route('orders.update', [$order]) }}">
                                    @method('PATCH')
                            @else
                                <form method="post" action="{{ route('orders.store') }}">
                            @endif
                                @csrf

                                <div class="form-group row">
                                    <label for="status_id" class="col-sm-2 col-form-label">Estado</label>
                                    <div class="col-sm-10">
                                        @include('forms.status_select', ['statuses' => $statuses, 'status_id' => $order->status_id ?? old('status_id')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="shop_id" class="col-sm-2 col-form-label">Tienda</label>
                                    <div class="col-sm-10">
                                        @include('forms.shop', ['shops' => $shops, 'shop_id' => $order->shop_id ?? old('shop_id')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="buyer_id" class="col-sm-2 col-form-label">Cliente comprador</label>
                                    <div class="col-sm-10">
                                        @include('forms.buyer', ['buyer_id' => $order->buyer_id ?? old('buyer_id'), 'buyer_name' => $order->buyer->name ?? old('buyer_name')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="currency_id" class="col-sm-2 col-form-label">Moneda</label>
                                    <div class="col-sm-10">
                                        @include('forms.currency_select', ['currencies' => $currencies, 'currency_id' => $order->currency_id ?? old('currency_id')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="total" class="col-sm-2 col-form-label">Precio total del pedido</label>
                                    <div class="col-sm-10">
                                        @include('forms.total', ['total' => $order->total ?? old('total')])
                                    </div>
                                </div>

                                <br>
                                <div class="form-group row">
                                    <div class="col-sm-2"></div>
                                    <div class="col-sm-10">
                                        <a class="btn btn-danger" href="{{ route('orders.index') }}" role="button">Cancelar</a>
                                        <button type="submit" class="btn btn-primary">Guardar pedido</button>
                                    </div>
                                </div>

                            @if (isset($product))</form>@else</form>@endif

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
@push('scriptsEnd')
    @include('scripts.jquery-ui')
    @include('scripts.autocomplete-buyers')
@endpush
