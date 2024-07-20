@extends('saas.layouts.app')

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-12"><h1>{{ isset($shop) ? 'EDITAR TIENDA: ('.$shop->market->name.') ' .$shop->name : 'AÑADIR TIENDA' }}</h1></div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">

                            @include('partials.status')
                            @include('partials.errors')

                            @if (isset($shop))
                                <form method="post" action="{{ route('saas.shops.update', $shop->id) }}">
                                    @method('PATCH')
                            @else
                                <form method="post" action="{{ route('saas.shops.store') }}">

                            @endif
                                @csrf

                                <div class="form-group row">
                                    <label for="ready" class="col-sm-2 col-form-label">Habilitada</label>
                                    <div class="col-sm-10">
                                        @include('forms.checkbox', ['field_name' => 'enabled', 'value' => $shop->enabled ?? old('enabled'), 'label' => 'Si se desabilita, no se actualizará la tienda.'])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="market_id" class="col-sm-2 col-form-label">Tienda ID | Nombre | Marketplace</label>
                                    <div class="col-sm-2">
                                        @include('forms.market_shop_id', ['marketShopId' => $shop->marketShopId ?? old('marketShopId')])
                                    </div>
                                    <div class="col-sm-4">
                                        @include('forms.name', ['name' => $shop->name ?? old('name')])
                                    </div>
                                    <div class="col-sm-4">
                                        @include('forms.market', ['markets' => $markets, 'market_id' => $shop->market_id ?? old('market_id')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="token" class="col-sm-2 col-form-label">Token</label>
                                    <div class="col-sm-10">
                                        @include('forms.token', ['token' => $shop->token ?? old('token')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="country" class="col-sm-2 col-form-label">Preparación | Logística | SKU</label>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'preparation', 'value' => $shop->preparation ?? old('preparation')])
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'shipping', 'value' => $shop->shipping ?? old('shipping')])
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.select', ['field_name' => 'sku_type', 'placeholder' => '', 'options' => $config['sku_types'], 'option_selected' => $config['sku_type'] ?? old('sku_type')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="locale" class="col-sm-2 col-form-label">Localización | Canales | % IVA</label>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'locale', 'value' => $shop->locale ?? old('locale')])
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'channel', 'value' => $shop->channel ?? old('channel')])
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'tax_rate', 'value' => $config['tax_rate'] ?? old('tax_rate')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="offer_desc" class="col-sm-2 col-form-label">Descripción oferta</label>
                                    <div class="col-sm-10">
                                        @include('forms.custom', ['field_name' => 'offer_desc', 'value' => $config['offer_desc'] ?? old('offer_desc')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="status" class="col-sm-2 col-form-label">Códigos de Estados</label>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'status_new', 'placeholder' => 'Nuevo', 'value' => $config['state_codes']->New ?? old('status_new')])
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'status_used', 'placeholder' => 'Usado', 'value' => $config['state_codes']->Used ?? old('status_used')])
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'status_refurbished', 'placeholder' => 'Remanufacturado', 'value' => $config['state_codes']->Refurbished ?? old('status_refurbished')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="reprice" class="col-sm-2 col-form-label">Reprice | Categorías simples</label>
                                    <div class="col-sm-2">
                                        @include('forms.checkbox', ['field_name' => 'reprice', 'value' => $config['reprice'] ?? old('reprice'), 'label' => 'Actualizar precios según competencia'])
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.checkbox', ['field_name' => 'all_categories_are_root', 'value' => $config['all_categories_are_root'] ?? old('all_categories_are_root'), 'label' => 'PCCompo: true, Otras: false'])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="only_stocks" class="col-sm-2 col-form-label">Actualizaciones | Tipo</label>
                                    <div class="col-sm-1">
                                        @include('forms.radio', ['field_name' => 'only_stocks', 'field_id' => 'only_stocks_1', 'value' => 'only_stocks', 'checked' => $config['only_stocks'] ?? old('only_stocks'), 'label' => 'Sólo Stocks'])
                                    </div>
                                    <div class="col-sm-1">
                                        @include('forms.radio', ['field_name' => 'only_stocks', 'field_id' => 'only_stocks_2', 'value' => 'stocks_prices', 'checked' => !$config['only_stocks'] ?? !old('only_stocks'), 'label' => 'Stocks y Precios'])
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.checkbox', ['field_name' => 'csv', 'value' => $config['csv'] ?? old('csv'), 'label' => 'Actualizaciones CSV'])
                                    </div>
                                </div>

                                <br>
                                <div class="form-group row">
                                    <div class="col-sm-2"></div>
                                    <div class="col-sm-10">
                                        <a class="btn btn-danger" href="{{ route('saas.shops') }}" role="button">Cancelar</a>
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
