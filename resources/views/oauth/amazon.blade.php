@extends('layouts.app')

@push('menu')
        <li class="nav-item d-none d-sm-inline-block"><a href="mailto:info@mpespecialist.com" class="nav-link">Support at: info@mpespecialist.com</a></li>
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-10"><h1>MPe App Authorization for Amazon | Autorización de App de MPe para Amazon</h1></div>
                    <div class="col-sm-2">
                        <ol class="breadcrumb float-sm-right">
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

                            {{-- @if (!isset($selling_partner_id))
                                <h3>Ha ocurrido un error, ponsage en contacto con Soporte Tecnico de MPe.</h3>
                            @else --}}

                                <form method="post" action="{{ route('oauth.amazon.build', ['selling_partner_id' => $selling_partner_id]) }}">
                                    @csrf

                                    <div class="form-group row">
                                        <label for="shop_type" class="col-sm-2 col-form-label">Store type | Tipo de tienda</label>
                                        <div class="col-sm-4">
                                            @include('forms.select', ['field_name' => 'shop_type', 'options' => $shop_types, 'option_selected' => 'Seller'])
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="country" class="col-sm-2 col-form-label">Store Country | País de la tienda</label>
                                        <div class="col-sm-4">
                                            @include('forms.select', ['field_name' => 'country', 'options' => $countries, 'option_selected' => 'Spain'])
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="selling_partner_id" class="col-sm-2 col-form-label">Store ID | ID de la tienda</label>
                                        <div class="col-sm-4">
                                            @include('forms.custom', ['field_name' => 'selling_partner_id', 'value' => $selling_partner_id, 'placeholder' => 'Ficha del vendedor'])
                                            <small>You will find the Store ID in your Seller Central | Account information | Seller file - Encontrarás el ID de Tienda, en tu Seller Central | Información de cuenta | Ficha de vendedor</small>
                                        </div>
                                    </div>

                                    @include('forms.hidden', ['field_name' => 'amazon_callback_uri', 'field_value' => $amazon_callback_uri])
                                    @include('forms.hidden', ['field_name' => 'amazon_state', 'field_value' => $amazon_state])

                                    <br>
                                    <div class="form-group row">
                                        <div class="col-sm-2"></div>
                                        <div class="col-sm-10">
                                            <button type="submit" class="btn btn-primary">Authorize MPe App | Autorizar App de MPe</button>
                                        </div>
                                    </div>

                                </form>

                           {{--  @endif --}}

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
