@extends('layouts.app')

@push('menu')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('orders.carriers.get', [$order]) }}" class="nav-link">Descargar lista de Transportes</a></li>
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-10"><h1>ENVIAR TRACKING DEL PEDIDO: {{ $order->id }}</h1></div>
                    <div class="col-sm-2">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('orders.index') }}">Pedidos</a></li>
                            <li class="breadcrumb-item active">Ver</li>
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

                            <div class="row">
                                <div class="col-sm-4">
                                    <h4>Pedido</h4>
                                    <div class="row">
                                        <div class="col-sm-12">({{ $order->market->name }}) {{ $order->shop->name }}</div>
                                        <div class="col-sm-12"><a href="{{ str_replace ('%marketOrderId', $order->marketOrderId, $order->market->order_url) }}">
                                                {{ $order->marketOrderId }}</a></div>
                                        <div class="col-sm-12">({{ $order->buyer->marketBuyerId }})
                                            <a href="{{ route('buyers.show', $order->buyer) }}">{{ $order->buyer->name }}</a></div>
                                        <div class="col-sm-12">Tel: {{ $order->buyer->phone }}</div>
                                        <div class="col-sm-12">{{ $order->buyer->email }}</div>
                                        <div class="col-sm-12">{{ $order->status->name }}</div>
                                        <div class="col-sm-12">Total: {{ $order->price }} {{ $order->currency->name }}</div>
                                    </div>
                                    <br>
                                    <h4>Dirección de envío</h4>
                                    @if ($order->shipping_address_id)
                                        <div class="row">
                                            <div class="col-sm-12">{{ $order->shipping_address->name }}</div>
                                            <div class="col-sm-12">{{ $order->shipping_address->address1 }} {{ $order->shipping_address->address2 }}</div>
                                            <div class="col-sm-12">{{ $order->shipping_address->zipcode }} {{ $order->shipping_address->city }}</div>
                                            <div class="col-sm-12">{{ $order->shipping_address->state }}</div>
                                            <div class="col-sm-12">{{ $order->shipping_address->country->name ?? '' }}</div>
                                            <div class="col-sm-12">{{ $order->shipping_address->phone }}</div>
                                        </div>
                                    @endif
                                </div>

                                <div class="col-sm-8">

                                    @if (isset($order_shipment))
                                        <form method="post" action="{{ route('orders.shipments.update', [$order, $order_shipment]) }}">
                                            @method('PATCH')
                                    @else
                                        <form method="post" action="{{ route('orders.shipments.store', [$order]) }}">
                                    @endif
                                        @csrf

                                        <div class="form-group row">
                                            <label for="full" class="col-sm-2 col-form-label">Envío completo</label>
                                            <div class="col-sm-10">
                                                @include('forms.checkbox', ['field_name' => 'full', 'label' => 'Envío completo (NO seleccionar para Rechazar pedido)',
                                                    'value' => $order_shipment->full ?? old('full') ?? 1 ])
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="market_carrier_id" class="col-sm-2 col-form-label">Transporte</label>
                                            <div class="col-sm-4">
                                                @include('forms.select_id', ['field_name' => 'market_carrier_id', 'placeholder' => 'Empresa de transporte',
                                                    'options' => $market_carriers, 'option_id' => $order_shipment->market_carrier_id ?? old('market_carrier_id')])
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="tracking" class="col-sm-2 col-form-label">Tracking</label>
                                            <div class="col-sm-4">
                                                @include('forms.custom', ['field_name' => 'tracking', 'placeholder' => 'Número de Tracking', 'value' => $order_shipment->tracking ?? old('tracking')])
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="desc" class="col-sm-2 col-form-label">Anotaciones</label>
                                            <div class="col-sm-4">
                                                @include('forms.custom', ['field_name' => 'desc', 'placeholder' => 'Anotaciones del envío', 'value' => $order_shipment->desc ?? old('desc')])
                                            </div>
                                        </div>

                                        <p>En caso de no ser un envío completo, rellenar los siguientes campos</p>

                                        <div class="form-group row">
                                            <label for="order_item_id" class="col-sm-2 col-form-label">Producto</label>
                                            <div class="col-sm-10">
                                                @include('forms.select_id', ['field_name' => 'order_item_id', 'placeholder' => 'Línea de pedido',
                                                    'options' => $order_items, 'option_id' => $order_shipment->order_item_id ?? old('order_item_id')])
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="quantity" class="col-sm-2 col-form-label">Unidades enviadas</label>
                                            <div class="col-sm-4">
                                                @include('forms.custom', ['field_name' => 'quantity', 'placeholder' => 'Unidades', 'value' => $order_shipment->quantity ?? old('quantity')])
                                            </div>
                                        </div>

                                        <br>
                                        <div class="form-group row">
                                            <div class="col-sm-2"></div>
                                            <div class="col-sm-10">
                                                <a class="btn btn-danger" href="{{ route('orders.show', [$order]) }}" role="button">Cancelar</a>
                                                <button type="submit" class="btn btn-primary">Enviar Tracking</button>
                                            </div>
                                        </div>

                                    </form>

                                </div>
                            </div>

                            {{-- market_carrier_id, order_id, full, order_item_id, quantity, tracking, created_at, updated_at --}}

                            <table class="table table-striped">
                                <tr class="table-warning">
                                    <th>Completo</th>
                                    <th>Transporte</th>
                                    <th>Tracking</th>
                                    <th>Producto</th>
                                    <th>Unidades</th>
                                    <th>Acciones</th>
                                </tr>
                                @foreach($order_shipments as $order_shipment)
                                    <tr order_shipment-id="{{ $order_shipment->id }}">
                                        <td>{{ $order_shipment->full ? 'Sí' : 'No' }}</td>
                                        <td>{{ $order_shipment->market_carrier->name ?? '' }}</td>
                                        <td>{{ $order_shipment->tracking }}</td>
                                        <td>{{ $order_shipment->order_item->name ?? '' }}</td>
                                        <td>{{ $order_shipment->quantity ?? '' }}</td>
                                        <td><a class="mr-2" href="{{ route('orders.shipments.edit', [$order, $order_shipment]) }}" data-toggle="tooltip" title="Editar">
                                            <i class="far fa-edit"></i></a></td>
                                    </tr>
                                @endforeach
                            </table>

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
