@extends('layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@push('menu')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('receivers.create') }}" class="nav-link">Añadir notificable</a></li>
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-10"><h1>Notificables</h1></div>
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

                            <form method="get" action="{{ route('receivers.index') }}" class="form-inline">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            @include('forms.supplier', ['suppliers' => $suppliers, 'supplier_id' => $params['supplier_id'] ?? old('supplier_id')])
                                            @include('forms.select', ['field_name' => 'class', 'placeholder' => 'Tipo', 'options' => $classes, 'option_selected' => $params['class'] ?? old('class')])
                                            @include('forms.name', ['name' => $params['name'] ?? old('name')])
                                            @include('forms.email', ['email' => $params['email'] ?? old('email')])
                                            @include('forms.custom', ['field_name' => 'telegram_user_id', 'value' => $params['telegram_user_id'] ?? old('telegram_user_id')])
                                            @include('forms.custom', ['field_name' => 'telegram_chat_id', 'value' => $params['telegram_chat_id'] ?? old('telegram_chat_id')])
                                            @include('forms.custom', ['field_name' => 'twitter_user_id', 'value' => $params['twitter_user_id'] ?? old('twitter_user_id')])
                                            <a class="mr-2 mb-2" href="{{ route('receivers.index') }}">LIMPIAR</a>
                                            <button class="btn btn-success mb-2" type="submit" value="FILTRAR">FILTRAR</button>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <p>Hay {{ $receivers->total() }} notificables</p>
                            {!! $receivers->appends($params)->render() !!}
                            <table id="table_ordened" class="table table-striped">
                                <tr class="table-warning">
                                    <th></th>
                                    <th>@include('ordersby.receivers', ['order_by' => 'suppliers.name', 'title' => 'Proveedor'])</th>
                                    <th>@include('ordersby.receivers', ['order_by' => 'receivers.class', 'title' => 'Tipo'])</th>
                                    <th>@include('ordersby.receivers', ['order_by' => 'receivers.name', 'title' => 'Nombre'])</th>
                                    <th>@include('ordersby.receivers', ['order_by' => 'receivers.email', 'title' => 'Correo'])</th>
                                    <th>@include('ordersby.receivers', ['order_by' => 'telegrams.user_id', 'title' => 'Telegram'])</th>
                                    <th>@include('ordersby.receivers', ['order_by' => 'twitters.user_id', 'title' => 'Twitter'])</th>
                                    <th>Acciones</th>
                                </tr>
                                @foreach($receivers as $receiver)

                                    <tr $receiver-id="{{ $receiver->id }}">
                                        <td>{{ $receiver->id }}</td>
                                        <td>{{ $receiver->supplier_name }}</td>
                                        <td>{{ $receiver->class }}</td>
                                        <td>{{ $receiver->name }}</td>
                                        <td>{{ $receiver->email }}</td>
                                        <td>{{ $receiver->telegram->user_id ?? '' }} {{ isset($receiver->telegram_id) ? '|' : '' }} {{ $receiver->telegram->chat_id ?? '' }}</td>
                                        <td>{{ $receiver->twitter->user_id ?? '' }}</td>
                                        <td>
                                            <div class="row">
                                                <a class="mr-2" href="{{ route('receivers.edit', [$receiver]) }}" data-toggle="tooltip" title="Editar">
                                                    <i class="far fa-edit"></i></a>
                                                <form class="delete" action="{{ route('receivers.destroy', [$receiver]) }}" method="post">
                                                    @method('delete')
                                                    @csrf
                                                    @include('forms.button_delete', ['title' => 'Eliminar'])
                                                </form>
                                            </div>
                                        </td>
                                    </tr>

                                @endforeach
                            </table>
                            <br>
                            {!! $receivers->appends($params)->render() !!}

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
@push('scriptsEnd')
    @include('scripts.jquery-ui')
    @include('scripts.submit-delete', ['question' => '¿Estás seguro de eliminar este notificable?'])
@endpush
