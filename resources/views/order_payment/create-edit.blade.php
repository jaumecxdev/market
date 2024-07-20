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
                <div class="row">
                    <div class="col-sm-10"><h1>EDITAR COBRO: {{ $order_payment->order->marketOrderId .' ('.$order_payment->order_item->marketItemId.')' }}</h1></div>
                    <div class="col-sm-2">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('orders.index') }}">Pedidos</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('order_payments.index') }}">Cobros</a></li>
                            <li class="breadcrumb-item active">Editar</li>
                        </ol>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-10"><h5>{{ '('.$order_payment->order->market->name .') '.$order_payment->order->shop->name }}</h5></div>
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


                            <form method="post" action="{{ route('order_payments.update', [$order_payment]) }}">
                                @method('PATCH')
                                @csrf

                                <div class="form-group row">
                                    <label for="fixed" class="col-sm-2 col-form-label">Datos fijados manualmente</label>
                                    <div class="col-sm-10">
                                        @include('forms.checkbox', ['field_name' => 'fixed',
                                            'value' => $order_payment->fixed ?? old('fixed'),
                                            'label' => 'Si se habilita, estos datos NO se actualizarán automáticamente.'])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="fee" class="col-sm-2 col-form-label">€ Coste | € Precio | € Portes</label>
                                    <div class="col-sm-2">
                                        @include('forms.cost', ['cost' => $order_payment->cost ?? old('cost') ?? 0])
                                        <small>Coste sin IVA del producto</small>
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.price', ['price' => $order_payment->price ?? old('price') ?? 0])
                                        <small>PVP del producto pagado</small>
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'shipping_price', 'placeholder' => null, 'value' => $order_payment->shipping_price ?? old('shipping_price') ?? 0])
                                        <small>Portes del envío pagados</small>
                                    </div>
                                   {{--  <div class="col-sm-2">
                                        @include('forms.currency_select', ['currencies' => $currencies, 'currency_id' => $order->currency_id ?? old('currency_id'),
                                            'currency_id' => $order_payment->currency_id ?? old('currency_id')])
                                    </div> --}}
                                </div>

                                <div class="form-group row">
                                    <label for="bfit" class="col-sm-2 col-form-label">€ Cliente | € Mpe | € MP</label>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'bfit', 'placeholder' => null, 'value' => $order_payment->bfit ?? old('bfit') ?? 0])
                                        <small>€ Beneficio del cliente</small>
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'mps_bfit', 'placeholder' => null, 'value' => $order_payment->mps_bfit ?? old('mps_bfit') ?? 0])
                                        <small>€ Beneficio de MPe</small>
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'mp_bfit', 'placeholder' => null, 'value' => $order_payment->mp_bfit ?? old('mp_bfit') ?? 0])
                                        <small>€ Beneficio del Marketplace</small>
                                    </div>
                                </div>

                                {{-- 'invoice_mpe'               => 'nullable|max:64',
                                'invoice_mpe_price'         => 'nullable|numeric|gte:0',
                                'invoice_mpe_created_at'    => 'nullable|date_format:Y-m-d',
 --}}
                                <div class="form-group row">
                                    <label for="invoice" class="col-sm-2 col-form-label">Fecha | Factura MPe | Total €</label>
                                    <div class="col-sm-2">
                                        <div class="input-group mr-2 mb-2">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                            </div>
                                            <input type="text" name="invoice_mpe_created_at" id="invoice_mpe_created_at"
                                                placeholder="yyyy-mm-dd" class="form-control" class="form-control mr-2 mb-2"
                                                value = "{{ $order_payment->invoice_mpe_created_at ? $order_payment->invoice_mpe_created_at->format('Y-m-d') : old('invoice_mpe_created_at') }}">
                                        </div>
                                        <small>Fecha de la factura de MPe YYYY-MM-DD</small>
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'invoice_mpe',
                                            'placeholder' => null,
                                            'value' => $order_payment->invoice_mpe ?? old('invoice_mpe')])
                                        <small>Número de factura de MPe</small>
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'invoice_mpe_price',
                                            'placeholder' => null,
                                            'value' => $order_payment->invoice_mpe_price ?? old('invoice_mpe_price') ?? 0])
                                        <small>€ Total Factura IVA Incluido</small>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="charget" class="col-sm-2 col-form-label">Cobrado del Marketplace?</label>
                                    <div class="col-sm-2">
                                        @include('forms.checkbox', ['field_name' => 'charget', 'value' => $order_payment->charget ?? old('charget'),
                                                'label' => 'Cobrado ???'])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="invoice" class="col-sm-2 col-form-label">Fecha | Factura Marketplace</label>
                                    <div class="col-sm-2">
                                        <div class="input-group mr-2 mb-2">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                            </div>
                                            <input type="text" name="payment_at" id="payment_at"
                                                placeholder="yyyy-mm-dd" class="form-control" class="form-control mr-2 mb-2"
                                                value = "{{ $order_payment->payment_at ? $order_payment->payment_at->format('Y-m-d') : old('payment_at') }}">
                                        </div>
                                        <small>Fecha del cobro YYYY-MM-DD</small>
                                    </div>
                                    <div class="col-sm-4">
                                        @include('forms.custom', ['field_name' => 'invoice', 'placeholder' => null, 'value' => $order_payment->invoice ?? old('invoice')])
                                        <small>Número de factura del Marketplace</small>
                                    </div>
                                </div>

                                <br>
                                <div class="form-group row">
                                    <div class="col-sm-2"></div>
                                    <div class="col-sm-10">
                                        <a class="btn btn-danger" href="{{ route('order_payments.index') }}" role="button">Cancelar</a>
                                        <button type="submit" class="btn btn-primary">Guardar cobro</button>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.15/jquery.mask.min.js"></script>
    <script>
        $(function() {
            $('#payment_at').mask('0000-00-00');
        });
    </script>
@endpush
