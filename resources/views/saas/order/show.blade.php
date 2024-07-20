@extends('saas.layouts.app')

@push('menu')
@endpush

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-12"><h1>PEDIDO: {{ $order->id }}</h1></div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">

                            @include('partials.status')

                            <div class="row">
                                <div class="col-sm-4">
                                    <h4>Pedido</h4>
                                    <div class="row">
                                        <div class="col-sm-12">({{ $order->market->name }}) {{ $order->shop->name }}</div>
                                        <div class="col-sm-12"><a href="{{ str_replace ('%marketOrderId', $order->marketOrderId, $order->market->order_url) }}">
                                                {{ $order->marketOrderId }}</a></div>
                                        @if (isset($order->buyer))
                                            <div class="col-sm-12">({{ $order->buyer->marketBuyerId }})
                                                <a href="{{ route('saas.buyers.show', $order->buyer) }}">{{ $order->buyer->name }}</a></div>
                                            <div class="col-sm-12">Tel: {{ $order->buyer->phone }}</div>
                                            <div class="col-sm-12">{{ $order->buyer->email }}</div>
                                        @endif
                                        <div class="col-sm-12">{{ $order->status->name }}</div>
                                        <div class="col-sm-12">Total: {{ $order->price }} {{ $order->currency->name ?? null }}</div>
                                    </div>
                                </div>
                                <div class="col-sm-4">
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
                                <div class="col-sm-4">
                                    <h4>Dirección de facturación</h4>
                                    @if ($order->billing_address_id)
                                        <div class="row">
                                            <div class="col-sm-12">{{ $order->billing_address->name }}</div>
                                            <div class="col-sm-12">{{ $order->billing_address->address1 }} {{ $order->billing_address->address2 }}</div>
                                            <div class="col-sm-12">{{ $order->billing_address->zipcode }} {{ $order->billing_address->city }}</div>
                                            <div class="col-sm-12">{{ $order->billing_address->state }}</div>
                                            <div class="col-sm-12">{{ $order->billing_address->country->name ?? '' }}</div>
                                            <div class="col-sm-12">{{ $order->billing_address->phone }}</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <br>

                            <table class="table table-striped">
                                <tr class="table-warning">
                                    <th></th>
                                    <th>IDs</th>
                                    <th>Proveedor</th>
                                    <th>Categoría<br>Marca</th>
                                    <th>Coste<br>Precio<br>Portes</th>
                                    <th>Cliente<br>MPe<br>MP</th>
                                    <th>Unidades pedido</th>
                                    <th>Stock Almacén</th>
                                    <th>Producto</th>
                                </tr>
                                @foreach($order->order_items as $order_item)
                                    <tr order_item-id="{{ $order_item->id }}">
                                        @if ($order_item->product)
                                            <td class="img-list">
                                                <img src="{{ $order_item->product->images()->count() ? $order_item->product->images()->first()->getFullUrl() : '' }}">
                                            </td>
                                            <td><span class="badge bg-success">{{ $order_item->product->id }}</span><br>
                                                <span class="font-weight-bold mp-sku {{ ($order_item->marketProductSku && $order_item->marketProductSku != 'ERROR') ? '' : 'text-danger' }}">SKU MP: </span>
                                                <a class="mp-sku" href="{{ str_replace('%marketProductSku', $order_item->marketProductSku, $order_item->order->market->product_url) }}">
                                                    {{ $order_item->marketProductSku }}</a><br>
                                                <small><span class="font-weight-bold">P/N:</span> {{ $order_item->product->pn }}</small><br>
                                                <small><span class="font-weight-bold">EAN:</span> {{ $order_item->product->ean }}</small>
                                            </td>
                                            <td>
                                                <small>{{ $order_item->product->supplier->name }}<br>
                                                    {{ $order_item->product->supplierSku }}
                                                </small>
                                            </td>
                                            <td>{{ $order_item->product->category->name ?? ''}}<br>
                                                {{ $order_item->product->brand->name ?? '' }}
                                            </td>
                                        @else
                                            <td></td>
                                            <td><span class="font-weight-bold">{{ $order_item->MpsSku }}</span></td>
                                            <td></td>
                                            <td></td>
                                        @endif

                                        <td style="width: 8%">{{ $order_item->cost }} {{ $order_item->currency->code ?? null }}<br>
                                            {{ $order_item->price }} {{ $order_item->currency->code ?? null }}<br>
                                            {{ $order_item->shipping_price }} {{ $order_item->currency->code ?? null }}</td>

                                        <td style="width: 8%">{{ $order_item->bfit }} {{ $order_item->currency->code ?? null }}<br>
                                            {{ $order_item->mps_bfit }} {{ $order_item->currency->code ?? null }}<br>
                                            {{ $order_item->mp_bfit }} {{ $order_item->currency->code ?? null }}</td>

                                        <td>{{ $order_item->quantity }}</td>

                                        @if ($order_item->product)
                                            <td>{{ $order_item->product->stock  ?? 0 }}</td>
                                            <td><a href="{{ route('saas.products.show', [$order_item->product]) }}">
                                                    {{ $order_item->product->name }}</a></td>
                                        @else
                                            <td></td>
                                            <td>{{ $order_item->name }}</td>
                                        @endif
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
