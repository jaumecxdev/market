@extends('layouts.app')

@push('menu')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('orders.comments.get', [$order]) }}" class="nav-link">Descargar comentarios del pedido</a></li>
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-10"><h1>ENVIAR COMENTARIO DEL PEDIDO: {{ $order->id }}</h1></div>
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

                                    <form method="post" action="{{ route('orders.comments.store', [$order]) }}">
                                        @csrf

                                        <div class="form-group row">
                                            <label for="comment" class="col-sm-2 col-form-label">Comentario</label>
                                            <div class="col-sm-4">
                                                @include('forms.custom', ['field_name' => 'comment', 'placeholder' => 'Comentario', 'value' => old('comment')])
                                            </div>
                                        </div>

                                        <br>
                                        <div class="form-group row">
                                            <div class="col-sm-2"></div>
                                            <div class="col-sm-10">
                                                <a class="btn btn-danger" href="{{ route('orders.show', [$order]) }}" role="button">Cancelar</a>
                                                <button type="submit" class="btn btn-primary">Enviar Comentario</button>
                                            </div>
                                        </div>

                                    </form>

                                </div>
                            </div>

                            <table class="table table-striped">
                                <tr class="table-warning">
                                    <th>Fecha</th>
                                    <th>Comentario</th>
                                </tr>
                                @foreach($order_comments as $order_comment)
                                    <tr order_comment-id="{{ $order_comment->id }}">
                                        <td>{{ $order_comment->created_at }}</td>
                                        <td>{{ $order_comment->comment }}</td>
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
