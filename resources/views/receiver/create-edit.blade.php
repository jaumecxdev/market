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
                    <div class="col-sm-10"><h1>{{ isset($receiver) ? 'EDITAR NOTIFICABLE: ' .$receiver->name : 'AÑADIR NOTIFICABLE' }}</h1></div>
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

                            @if (isset($receiver))
                                <form method="post" action="{{ route('receivers.update', $receiver->id) }}">
                                    @method('PATCH')
                            @else
                                <form method="post" action="{{ route('receivers.store') }}">
                            @endif
                                @csrf

                                    <div class="form-group row">
                                        <label for="supplier" class="col-sm-2 col-form-label">Proveedor</label>
                                        <div class="col-sm-2">
                                            @include('forms.supplier', ['suppliers' => $suppliers, 'supplier_id' => $receiver->supplier_id ?? old('supplier_id')])
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="class" class="col-sm-2 col-form-label">Tipo</label>
                                        <div class="col-sm-2">
                                            @include('forms.select', ['field_name' => 'class', 'options' => $classes, 'option_selected' => $receiver->class?? old('class')])
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="name" class="col-sm-2 col-form-label">Nombre</label>
                                        <div class="col-sm-4">
                                            @include('forms.name', ['name' => $receiver->name ?? old('name')])
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="email" class="col-sm-2 col-form-label">Correo</label>
                                        <div class="col-sm-4">
                                            @include('forms.email', ['email' => $receiver->email ?? old('email')])
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="telegram_invite_code" class="col-sm-2 col-form-label">Telegram</label>
                                        <div class="col-sm-3">
                                            @include('forms.custom', ['field_name' => 'telegram_invite_code', 'value' => $receiver->telegram->invite_code ?? old('telegram_invite_code')])
                                        </div>
                                        <div class="col-sm-3">
                                            @include('forms.custom', ['field_name' => 'telegram_user_id', 'value' => $receiver->telegram->user_id ?? old('telegram_user_id')])
                                        </div>
                                        <div class="col-sm-3">
                                            @include('forms.custom', ['field_name' => 'telegram_chat_id', 'value' => $receiver->telegram->chat_id ?? old('telegram_chat_id')])
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="twitter_user_id" class="col-sm-2 col-form-label">Usuario Twitter</label>
                                        <div class="col-sm-4">
                                            @include('forms.custom', ['field_name' => 'twitter_user_id', 'value' => $receiver->twitter->user_id ?? old('twitter_user_id')])
                                        </div>
                                    </div>

                                    <br>
                                    <div class="form-group row">
                                        <div class="col-sm-2"></div>
                                        <div class="col-sm-10">
                                            <a class="btn btn-danger" href="{{ route('receivers.index') }}" role="button">Cancelar</a>
                                            <button type="submit" class="btn btn-primary">Guardar notificable</button>
                                        </div>
                                    </div>

                            @if (isset($receiver))</form>@else</form>@endif

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
