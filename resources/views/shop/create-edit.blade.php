@extends('layouts.app')

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-10"><h1>{{ isset($shop) ? 'EDITAR TIENDA: ('.$shop->market->name.') ' .$shop->name : 'AÑADIR TIENDA' }}</h1></div>
                    <div class="col-sm-2">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('shops.index') }}">Tiendas</a></li>
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

                            @if (isset($shop))
                                <form method="post" action="{{ route('shops.update', $shop->id) }}">
                                    @method('PATCH')
                            @else
                                <form method="post" action="{{ route('shops.store') }}">

                            @endif
                                @csrf

                                <div class="form-group row">
                                    <label for="ready" class="col-sm-2 col-form-label">Habilitada</label>
                                    <div class="col-sm-10">
                                        @include('forms.checkbox', ['field_name' => 'enabled', 'value' => $shop->enabled ?? old('enabled'), 'label' => 'Si se desabilita, no se actualizará la tienda o lo hará con STOCK a 0.'])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="market_id" class="col-sm-2 col-form-label">Marketplace</label>
                                    <div class="col-sm-10">
                                        @include('forms.market', ['markets' => $markets, 'market_id' => $shop->market_id ?? old('market_id')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="code" class="col-sm-2 col-form-label">Code / Name</label>
                                    <div class="col-sm-3">
                                        @include('forms.code', ['code' => $shop->code ?? old('code')])
                                    </div>
                                    <div class="col-sm-3">
                                        @include('forms.name', ['name' => $shop->name ?? old('name')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="marketShopId" class="col-sm-2 col-form-label">marketShopId / marketSellerId</label>
                                    <div class="col-sm-3">
                                        @include('forms.market_shop_id', ['marketShopId' => $shop->marketShopId ?? old('marketShopId')])
                                    </div>
                                    <div class="col-sm-3">
                                        @include('forms.market_seller_id', ['marketSellerId' => $shop->marketSellerId ?? old('marketSellerId')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="country" class="col-sm-2 col-form-label">URLs</label>
                                    <div class="col-sm-5">
                                        @include('forms.endpoint', ['endpoint' => $shop->endpoint ?? old('endpoint')])
                                    </div>
                                    <div class="col-sm-5">
                                        @include('forms.custom', ['field_name' => 'redirect_url', 'value' => $shop->redirect_url ?? old('redirect_url')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="country" class="col-sm-2 col-form-label"></label>
                                    <div class="col-sm-5">
                                        @include('forms.custom', ['field_name' => 'store_url', 'value' => $shop->store_url ?? old('store_url')])
                                    </div>
                                    <div class="col-sm-5">
                                        @include('forms.custom', ['field_name' => 'header_url', 'value' => $shop->header_url ?? old('header_url')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="country" class="col-sm-2 col-form-label">Country | Site | Localización</label>
                                    <div class="col-sm-2">
                                        @include('forms.country', ['country' => $shop->country ?? old('country')])
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.site', ['site' => $shop->site ?? old('site')])
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'locale', 'value' => $shop->locale ?? old('locale')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="app_name" class="col-sm-2 col-form-label">App name / App version</label>
                                    <div class="col-sm-5">
                                        @include('forms.app_name', ['app_name' => $shop->app_name ?? old('app_name')])
                                    </div>
                                    <div class="col-sm-5">
                                        @include('forms.app_version', ['app_version' => $shop->app_version ?? old('app_version')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="client_id" class="col-sm-2 col-form-label">Client ID / Secret ID</label>
                                    <div class="col-sm-4">
                                        @include('forms.client_id', ['client_id' => $shop->client_id ?? old('client_id')])
                                    </div>
                                    <div class="col-sm-6">
                                        @include('forms.client_secret', ['client_secret' => $shop->client_secret ?? old('client_secret')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="dev_id" class="col-sm-2 col-form-label">Developer ID</label>
                                    <div class="col-sm-4">
                                        @include('forms.dev_id', ['dev_id' => $shop->dev_id ?? old('dev_id')])
                                    </div>
                                    <div class="col-sm-6">
                                        @include('forms.custom', ['field_name' => 'dev_secret',
                                            'value' => $shop->dev_secret ?? old('dev_id'),
                                            'placeholder' => 'Developer Secret'])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="token" class="col-sm-2 col-form-label">Token</label>
                                    <div class="col-sm-10">
                                        @include('forms.token', ['token' => $shop->token ?? old('token')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="token" class="col-sm-2 col-form-label">Refresh Token</label>
                                    <div class="col-sm-10">
                                        @include('forms.custom', ['field_name' => 'refresh', 'value' => $shop->refresh ?? old('refresh')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="country" class="col-sm-2 col-form-label">Templates</label>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'preparation', 'value' => $shop->preparation ?? old('preparation')])
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'shipping', 'value' => $shop->shipping ?? old('shipping')])
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'return', 'value' => $shop->return ?? old('return')])
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'payment', 'value' => $shop->payment ?? old('payment')])
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'channel', 'value' => $shop->channel ?? old('channel')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="config" class="col-sm-2 col-form-label">Configuración JSON</label>
                                    <div class="col-sm-10">
                                        @include('forms.textarea', ['field_name' => 'config', 'value' => $shop->config ?? old('config')])
                                    </div>
                                </div>

                                <br>
                                <div class="form-group row">
                                    <div class="col-sm-2"></div>
                                    <div class="col-sm-10">
                                        <a class="btn btn-danger" href="{{ route('shops.index') }}" role="button">Cancelar</a>
                                        <button type="submit" class="btn btn-primary">Guardar Tienda</button>
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
