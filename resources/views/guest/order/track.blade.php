@extends('layouts.guest')

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-12"><h1>ENVIAR TRACKING DEL PEDIDO: {{ $order->marketOrderId }}</h1></div>
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

                            <div class="row">
                                <div class="col-sm-12">

                                    <form method="post" action="{{ route('guest_service.order.track.store', [$order, 'token' => $token]) }}">
                                        @csrf

                                        <h5>{{ $order->market->name }} {{ $order->shop->name }}</h5>
                                        <br>

                                        <div class="form-group row">
                                            <label for="full" class="col-sm-2 col-form-label">Envío completo</label>
                                            <div class="col-sm-10">
                                                @include('forms.checkbox', ['field_name' => 'full', 'label' => 'Envío completo', 'value' => 1])
                                                <div class="form-group row">
                                                    <div class="col-sm-12">
                                                        <label for="order_item_id" class="col-form-label">Producto</label>
                                                        @include('forms.select_id', ['field_name' => 'order_item_id', 'placeholder' => 'Línea de pedido',
                                                            'options' => $order_items, 'option_id' => $order_items->first()->id])
                                                    </div>
                                                </div>
                                                <div class="form-group row">
                                                    <label for="quantity" class="col-sm-2 col-form-label">Unidades enviadas</label>
                                                    <div class="col-sm-2">
                                                        @include('forms.custom', ['field_name' => 'quantity', 'placeholder' => null, 'value' => $order_items->first()->quantity])
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="market_carrier_id" class="col-sm-2 col-form-label">Transporte</label>
                                            <div class="col-sm-4">
                                                @include('forms.select_id', ['field_name' => 'market_carrier_id', 'placeholder' => 'Empresa de transporte',
                                                    'options' => $market_carriers, 'option_id' => null])
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="tracking" class="col-sm-2 col-form-label">Tracking</label>
                                            <div class="col-sm-4">
                                                @include('forms.custom', ['field_name' => 'tracking', 'placeholder' => 'Número de Tracking', 'value' => ''])
                                            </div>
                                        </div>

                                        <br>
                                        <div class="form-group row">
                                            <div class="col-sm-2"></div>
                                            <div class="col-sm-10">
                                                <button type="submit" class="btn btn-primary">TRAMITAR</button>
                                            </div>
                                        </div>

                                    </form>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
