@extends('layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@push('menu')
    @role('admin')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('order_payments.get') }}" class="nav-link">Importar nuevos pedidos</a></li>
    @endrole
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-8"><h1>Control de pagos de los Marketplaces</h1></div>
                    <div class="col-sm-4">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('orders.index') }}">Pedidos</a></li>
                            <li class="breadcrumb-item active">Control de cobros de pedidos</li>
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

                            <form method="get" action="{{ route('order_payments.index') }}" class="form-inline">

                                <div class="form-group row">
                                    <div class="col-sm-12">
                                        @include('forms.market_order_id', ['marketOrderId' => $params['marketOrderId'] ?? old('marketOrderId')])
                                        @include('forms.custom', ['field_name' => 'invoice_mpe',
                                            'value' => $params['invoice_mpe'] ?? old('invoice_mpe'),
                                            'placeholder' => 'Factura MPe'])
                                        @include('forms.custom', ['field_name' => 'invoice',
                                            'value' => $params['invoice'] ?? old('invoice'),
                                            'placeholder' => 'Factura o Transfer del Marketplace'])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <div class="col-sm-12">
                                        @include('forms.checkbox', ['field_name' => 'charget',
                                            'value' => $params['charget'] ?? old('charget'),
                                            'label' => 'Solo Cobradas de los Marketplaces.'])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <div class="col-sm-12">
                                        @include('forms.shop', ['shops' => $shops,
                                            'shop_id' => $params['shop_id'] ?? old('shop_id')])
                                        @include('forms.buyer', ['buyer_id' => $params['buyer_id'] ?? old('buyer_id'),
                                            'buyer_name' =>$params['buyer_name']  ?? old('buyer_name')])
                                        @include('forms.status_select', ['statuses' => $statuses,
                                            'status_id' => $params['status_id'] ?? old('status_id')])
                                    </div>
                                </div>

                                <div class="form-group row">

                                    <div class="col-sm-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                            </div>
                                            <input type="text" name="order_created_at_min" id="order_created_at_min"
                                                value="{{ $params['order_created_at_min'] ?? old('order_created_at_min') }}"
                                                placeholder="yyyy-mm-dd" class="form-control">

                                        </div>
                                        <small>Fecha del Pedido >=</small>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                            </div>
                                            <input type="text" name="order_created_at_max" id="order_created_at_max"
                                                value="{{ $params['order_created_at_max'] ?? old('order_created_at_max') }}"
                                                placeholder="yyyy-mm-dd" class="form-control mr-2">
                                        </div>
                                        <small>Fecha del Pedido <=</small>
                                    </div>

                                    <div class="col-sm-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                            </div>
                                            <input type="text" name="invoice_mpe_created_at_min" id="invoice_mpe_created_at_min"
                                                value="{{ $params['invoice_mpe_created_at_min'] ?? old('invoice_mpe_created_at_min') }}"
                                                placeholder="yyyy-mm-dd" class="form-control">

                                        </div>
                                        <small>Fecha de la Factura MPe >=</small>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                            </div>
                                            <input type="text" name="invoice_mpe_created_at_max" id="invoice_mpe_created_at_max"
                                                value="{{ $params['invoice_mpe_created_at_max'] ?? old('invoice_mpe_created_at_max') }}"
                                                placeholder="yyyy-mm-dd" class="form-control mr-2">
                                        </div>
                                        <small>Fecha de la Factura MPe <=</small>
                                    </div>

                                    <div class="col-sm-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                            </div>
                                            <input type="text" name="payment_at_min" id="payment_at_min"
                                                value="{{ $params['payment_at_min'] ?? old('payment_at_min') }}"
                                                placeholder="yyyy-mm-dd" class="form-control">

                                        </div>
                                        <small>Fecha del Cobro del Marketplace >=</small>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                            </div>
                                            <input type="text" name="payment_at_max" id="payment_at_max"
                                                value="{{ $params['payment_at_max'] ?? old('payment_at_max') }}"
                                                placeholder="yyyy-mm-dd" class="form-control mr-2">
                                        </div>
                                        <small>Fecha del Cobro del Marketplace <=</small>
                                    </div>

                                </div>

                                <div class="form-group row">
                                    <div class="col-sm-12">
                                        <button class="btn btn-success mb-2" type="submit" value="FILTRAR">FILTRAR</button>
                                        <a class="mr-2 mb-2" href="{{ route('order_payments.index') }}">LIMPIAR</a>
                                        <button class="btn btn-warning mb-2 mr-2" type="" name="action" value="export">EXPORTAR</button>
                                    </div>
                                </div>

                            </form>
                            <br>

                            <div class="row">

                                <div class="col-sm-2 col-2">
                                    <div class="small-box bg-info">
                                        <div class="inner">
                                            <h5>{{ $total['cost'] }} €</h5>
                                            <p>Total Costes MPe</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-2 col-2">
                                    <div class="small-box bg-info">
                                        <div class="inner">
                                            <h5>{{ $total['invoice_mpe_price'] }} €</h5>
                                            <p>Total Facturas MPe</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-2 col-2">
                                    <div class="small-box bg-warning">
                                        <div class="inner">
                                            <h5>{{ $total['mp_bfit'] }} €</h5>
                                            <p>Total Comisiones Marketplaces</p>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <p>Hay {{ $order_payments->total() }} cobros de pedidos</p>
                            <table class="table table-striped">
                                <tr class="table-warning">
                                    <th>#</th>
                                    <th>
                                        @include('ordersby.order_payments', ['order_by' => 'marketOrderId', 'title' => 'Pedido MP'])<br>
                                        @include('ordersby.order_payments', ['order_by' => 'marketItemId', 'title' => 'Línea MP'])<br>
                                        @include('ordersby.order_payments', ['order_by' => 'order_status_name', 'title' => 'Estado'])
                                    </th>
                                    <th>
                                        @include('ordersby.order_payments', ['order_by' => 'market_shop_name', 'title' => 'Tienda'])<br>
                                        @include('ordersby.order_payments', ['order_by' => 'orders.created_at', 'title' => 'Fecha del pedido'])<br>
                                        @include('ordersby.order_payments', ['order_by' => 'buyer_name', 'title' => 'Cliente'])
                                    </th>
                                    <th>
                                        @include('ordersby.order_payments', ['order_by' => 'invoice_mpe', 'title' => 'Factura MPe'])<br>
                                        @include('ordersby.order_payments', ['order_by' => 'invoice_mpe_price', 'title' => 'Total F.'])<br>
                                        @include('ordersby.order_payments', ['order_by' => 'invoice_mpe_created_at', 'title' => 'Fecha F.'])
                                    </th>
                                    <th>
                                        @include('ordersby.order_payments', ['order_by' => 'cost', 'title' => 'Coste'])<br>
                                        @include('ordersby.order_payments', ['order_by' => 'price', 'title' => 'Precio PVP'])<br>
                                        @include('ordersby.order_payments', ['order_by' => 'shipping_price', 'title' => 'Portes'])
                                    </th>
                                    <th>
                                        @include('ordersby.order_payments', ['order_by' => 'bfit', 'title' => 'Bcio Cliente'])<br>
                                        @include('ordersby.order_payments', ['order_by' => 'mps_bfit', 'title' => 'Comi MPe'])<br>
                                        @include('ordersby.order_payments', ['order_by' => 'mp_bfit', 'title' => 'Comi MP'])
                                    </th>
                                    <th>
                                        @include('ordersby.order_payments', ['order_by' => 'charget', 'title' => 'Cobrado MP'])<br>
                                        @include('ordersby.order_payments', ['order_by' => 'invoice', 'title' => 'Factura MP'])<br>
                                        @include('ordersby.order_payments', ['order_by' => 'payment_at', 'title' => 'Fecha cobro'])
                                    </th>
                                    <th>
                                        <span>Bcio</span>
                                    </th>

                                    <th>Acciones</th>
                                </tr>
                                @foreach($order_payments as $order_payment)
                                    <tr order_payment-id="{{ $order_payment->id }}">
                                        <td>{{ $order_payment->id }}</td>
                                        <td>
                                            <a href="{{ str_replace ('%marketOrderId', $order_payment->marketOrderId, $order_payment->market_order_url) }}">
                                            {{ $order_payment->marketOrderId }}</a><br>
                                            <small>{{ $order_payment->marketItemId }}</small><br>
                                            {{ $order_payment->order_status_name }}
                                        </td>
                                        <td>
                                            {{ $order_payment->market_shop_name }}<br>
                                            {{ $order_payment->order->created_at->format('Y-m-d') }}<br>
                                            {{ $order_payment->buyer_name }}
                                        </td>
                                        <td>
                                            {{ $order_payment->invoice_mpe }}<br>
                                            {{ $order_payment->invoice_mpe_price }} {{ $order_payment->currency_code }}<br>
                                            {{ $order_payment->invoice_mpe_created_at ? $order_payment->invoice_mpe_created_at->format('Y-m-d') : null }}<br>
                                        </td>
                                        <td style="width: 8%" class="{{ $order_payment->fixed ? 'text-success' : 'text-danger' }}">
                                            {{ $order_payment->cost }} {{ $order_payment->currency_code }}<br>
                                            {{ $order_payment->price }} {{ $order_payment->currency_code }}<br>
                                            {{ $order_payment->shipping_price }} {{ $order_payment->currency_code }}
                                        </td>
                                        <td style="width: 8%" class="{{ $order_payment->fixed ? 'text-success' : 'text-danger' }}">
                                            {{ $order_payment->bfit }} {{ $order_payment->currency_code }}<br>
                                            {{ $order_payment->mps_bfit }} {{ $order_payment->currency_code }}<br>
                                            {{ $order_payment->mp_bfit }} {{ $order_payment->currency_code }}
                                        </td>
                                        <td>
                                            {{ $order_payment->charget ? 'Sí' : 'No'}}<br>
                                            {{ $order_payment->invoice }}<br>
                                            {{ $order_payment->payment_at ? $order_payment->payment_at->format('Y-m-d') : '' }}
                                        </td>

                                        @php
                                            $bfit = round($order_payment->invoice_mpe_price - $order_payment->cost*1.21 - $order_payment->mp_bfit, 2);
                                        @endphp
                                        <td class="{{ $bfit < 0 ? 'text-danger' : 'text-success' }}">
                                            {{ $bfit }} {{ $order_payment->currency_code }}
                                        </td>
                                        <td>
                                            <div class="row">
                                                <a class="mr-2" href="{{ route('order_payments.edit', [$order_payment]) }}"
                                                   data-toggle="tooltip" title="Editar"><i class="far fa-edit"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>

                            {!! $order_payments->appends($params)->render() !!}

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
    @include('scripts.autocomplete-buyers')
    <script>
        $(function() {
            $('#order_created_at').mask('0000-00-00');
        });
    </script>
@endpush
