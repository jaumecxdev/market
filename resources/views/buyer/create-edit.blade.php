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
                    <div class="col-sm-10"><h1>{{ isset($buyer) ? 'EDITAR COMPRADOR: ' .$buyer->name : 'AÑADIR COMPRADOR' }}</h1></div>
                    <div class="col-sm-2">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('buyers.index') }}">Clientes</a></li>
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

                            @if (isset($buyer))
                                <form method="post" action="{{ route('buyers.update', [$buyer]) }}">
                                    @method('PATCH')
                            @else
                                <form method="post" action="{{ route('buyers.store') }}">
                            @endif
                                @csrf

                                <div class="form-group row">
                                    <label for="name" class="col-sm-2 col-form-label">Nombre del cliente</label>
                                    <div class="col-sm-4">
                                        @include('forms.name', ['name' => $buyer->name ?? old('name')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="email" class="col-sm-2 col-form-label">Email del cliente</label>
                                    <div class="col-sm-4">
                                        @include('forms.email', ['email' => $buyer->email ?? old('email')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="phone" class="col-sm-2 col-form-label">Teléfono</label>
                                    <div class="col-sm-4">
                                        @include('forms.phone', ['phone' => $buyer->phone ?? old('phone')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="company_name" class="col-sm-2 col-form-label">Empresa</label>
                                    <div class="col-sm-4">
                                        @include('forms.company_name', ['company_name' => $buyer->company_name ?? old('company_name')])
                                    </div>
                                </div>

                                <br>
                                <div class="row">

                                    <div class="col-sm-6">
                                        <div class="card">
                                            <div class="card-header">DIRECCIÓN DE ENVÍO</div>
                                            <div class="card-body">

                                                <div class="panel panel-default">
                                                    <div class="panel-body">

                                                        <div class="form-group row">
                                                            <label for="shipping_address_name" class="col-sm-2 col-form-label">Nombre del cliente</label>
                                                            <div class="col-sm-6">
                                                                @include('forms.address_name', ['address_name' => $buyer->shipping_address->name ?? old('shipping_address_name'), 'type' => 'shipping'])
                                                            </div>
                                                        </div>

                                                        <div class="form-group row">
                                                            <label for="shipping_address_address1" class="col-sm-2 col-form-label">Dirección</label>
                                                            <div class="col-sm-10">
                                                                @include('forms.address_address1', ['address_address1' => $buyer->shipping_address->address1 ?? old('shipping_address_address1'), 'type' => 'shipping'])
                                                                @include('forms.address_address2', ['address_address2' => $buyer->shipping_address->address2 ?? old('shipping_address_address2'), 'type' => 'shipping'])
                                                                @include('forms.address_address3', ['address_address3' => $buyer->shipping_address->address3 ?? old('shipping_address_address3'), 'type' => 'shipping'])
                                                            </div>
                                                        </div>

                                                        <div class="form-group row">
                                                            <label for="shipping_address_city" class="col-sm-2 col-form-label">Ciudad</label>
                                                            <div class="col-sm-5">
                                                                @include('forms.address_city', ['address_city' => $buyer->shipping_address->city ?? old('shipping_address_city'), 'type' => 'shipping'])
                                                            </div>
                                                            <div class="col-sm-5">
                                                                @include('forms.address_municipality', ['address_municipality' => $buyer->shipping_address->municipality ?? old('shipping_address_municipality'), 'type' => 'shipping'])
                                                            </div>
                                                        </div>

                                                        <div class="form-group row">
                                                            <label for="shipping_address_state" class="col-sm-2 col-form-label">Província</label>
                                                            <div class="col-sm-2">
                                                                @include('forms.address_zipcode', ['address_zipcode' => $buyer->shipping_address->zipcode ?? old('shipping_address_zipcode'), 'type' => 'shipping'])
                                                            </div>
                                                            <div class="col-sm-4">
                                                                @include('forms.address_state', ['address_state' => $buyer->shipping_address->state ?? old('shipping_address_state'), 'type' => 'shipping'])
                                                            </div>
                                                            <div class="col-sm-4">
                                                                @include('forms.address_district', ['address_district' => $buyer->shipping_address->district ?? old('shipping_address_district'), 'type' => 'shipping'])
                                                            </div>
                                                        </div>

                                                        <div class="form-group row">
                                                            <label for="shipping_address_country_id" class="col-sm-2 col-form-label">País</label>
                                                            <div class="col-sm-5">
                                                                @include('forms.address_country', ['countries' => $countries, 'country_id' => $buyer->shipping_address->country_id ?? old('shipping_address_country_id'), 'type' => 'shipping'])
                                                            </div>
                                                        </div>

                                                        <div class="form-group row">
                                                            <label for="shipping_address_phone" class="col-sm-2 col-form-label">Teléfono</label>
                                                            <div class="col-sm-5">
                                                                @include('forms.address_phone', ['address_phone' => $buyer->shipping_address->phone ?? old('shipping_address_phone'), 'type' => 'shipping'])
                                                            </div>
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-sm-6">
                                        <div class="card">
                                            <div class="card-header">DIRECCIÓN DE FACTURACIÓN</div>
                                            <div class="card-body">

                                                <div class="panel panel-default">
                                                    <div class="panel-body">

                                                        <div class="form-group row">
                                                            <label for="billing_address_name" class="col-sm-2 col-form-label">Nombre del cliente</label>
                                                            <div class="col-sm-6">
                                                                @include('forms.address_name', ['address_name' => $buyer->billing_address->name ?? old('billing_address_name'), 'type' => 'billing'])
                                                            </div>
                                                        </div>

                                                        <div class="form-group row">
                                                            <label for="billing_address_address1" class="col-sm-2 col-form-label">Dirección</label>
                                                            <div class="col-sm-10">
                                                                @include('forms.address_address1', ['address_address1' => $buyer->billing_address->address1 ?? old('billing_address_address1'), 'type' => 'billing'])
                                                                @include('forms.address_address2', ['address_address2' => $buyer->billing_address->address2 ?? old('billing_address_address2'), 'type' => 'billing'])
                                                                @include('forms.address_address3', ['address_address3' => $buyer->billing_address->address3 ?? old('billing_address_address3'), 'type' => 'billing'])
                                                            </div>
                                                        </div>

                                                        <div class="form-group row">
                                                            <label for="billing_address_city" class="col-sm-2 col-form-label">Ciudad</label>
                                                            <div class="col-sm-5">
                                                                @include('forms.address_city', ['address_city' => $buyer->billing_address->city ?? old('billing_address_city'), 'type' => 'billing'])
                                                            </div>
                                                            <div class="col-sm-5">
                                                                @include('forms.address_municipality', ['address_municipality' => $buyer->billing_address->municipality ?? old('billing_address_municipality'), 'type' => 'billing'])
                                                            </div>
                                                        </div>

                                                        <div class="form-group row">
                                                            <label for="billing_address_state" class="col-sm-2 col-form-label">Província</label>
                                                            <div class="col-sm-2">
                                                                @include('forms.address_zipcode', ['address_zipcode' => $buyer->billing_address->zipcode ?? old('billing_address_zipcode'), 'type' => 'billing'])
                                                            </div>
                                                            <div class="col-sm-4">
                                                                @include('forms.address_state', ['address_state' => $buyer->billing_address->state ?? old('billing_address_state'), 'type' => 'billing'])
                                                            </div>
                                                            <div class="col-sm-4">
                                                                @include('forms.address_district', ['address_district' => $buyer->billing_address->district ?? old('billing_address_district'), 'type' => 'billing'])
                                                            </div>
                                                        </div>

                                                        <div class="form-group row">
                                                            <label for="billing_address_country_id" class="col-sm-2 col-form-label">País</label>
                                                            <div class="col-sm-5">
                                                                @include('forms.address_country', ['countries' => $countries, 'country_id' => $buyer->billing_address->country_id ?? old('billing_address_country_id'), 'type' => 'billing'])
                                                            </div>
                                                        </div>

                                                        <div class="form-group row">
                                                            <label for="billing_address_phone" class="col-sm-2 col-form-label">Teléfono</label>
                                                            <div class="col-sm-5">
                                                                @include('forms.address_phone', ['address_phone' => $buyer->billing_address->phone ?? old('billing_address_phone'), 'type' => 'billing'])
                                                            </div>
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <br>
                                <div class="form-group row">
                                    <div class="col-sm-12">
                                        <a class="btn btn-danger" href="{{ route('buyers.index') }}" role="button">Cancelar</a>
                                        <button type="submit" class="btn btn-primary">Guardar cliente</button>
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
