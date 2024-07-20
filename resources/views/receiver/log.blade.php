@extends('layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@push('menu')
    @role('admin')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('receivers.index') }}" class="nav-link">Notificables</a></li>
    @endrole
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-10"><h1>Notificaciones</h1></div>
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

                            <form method="get" action="{{ route('log_notifications.index') }}" class="form-inline">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            @include('forms.supplier', ['suppliers' => $suppliers, 'supplier_id' => $params['supplier_id'] ?? old('supplier_id')])
                                            @include('forms.select', ['field_name' => 'class', 'placeholder' => 'Tipo', 'options' => $classes, 'option_selected' => $params['class'] ?? old('class')])
                                            @include('forms.name', ['name' => $params['name'] ?? old('name')])
                                            @include('forms.custom', ['field_name' => 'target', 'value' => $params['target'] ?? old('target')])
                                            @include('forms.custom', ['field_name' => 'type', 'value' => $params['type'] ?? old('type')])
                                            @include('forms.custom', ['field_name' => 'type_id', 'value' => $params['type_id'] ?? old('type_id')])
                                            @include('forms.custom', ['field_name' => 'item', 'value' => $params['item'] ?? old('item')])
                                            <a class="mr-2 mb-2" href="{{ route('log_notifications.index') }}">LIMPIAR</a>
                                            <button class="btn btn-success mb-2" type="submit" value="FILTRAR">FILTRAR</button>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <p>Hay {{ $log_notifications->total() }} notificaciones</p>
                            {!! $log_notifications->appends($params)->render() !!}
                            <table id="table_ordened" class="table table-striped">
                                <tr class="table-warning">
                                    <th>#</th>
                                    <th>@include('ordersby.log_notifications', ['order_by' => 'suppliers.name', 'title' => 'Proveedor'])</th>
                                    <th>@include('ordersby.log_notifications', ['order_by' => 'log_notifications.class', 'title' => 'Clase'])</th>
                                    <th>@include('ordersby.log_notifications', ['order_by' => 'log_notifications.name', 'title' => 'Nombre'])</th>
                                    <th>@include('ordersby.log_notifications', ['order_by' => 'log_notifications.target', 'title' => 'Target'])</th>
                                    <th>@include('ordersby.log_notifications', ['order_by' => 'log_notifications.type', 'title' => 'Tipo'])</th>
                                    <th>@include('ordersby.log_notifications', ['order_by' => 'log_notifications.type_id', 'title' => 'ID'])</th>
                                    <th>@include('ordersby.log_notifications', ['order_by' => 'log_notifications.item', 'title' => 'Item'])</th>
                                    <th>@include('ordersby.log_notifications', ['order_by' => 'log_notifications.created_at', 'title' => 'Enviado'])</th>
                                </tr>
                                @foreach($log_notifications as $log_notification)

                                    <tr $log_notification-id="{{ $log_notification->id }}">
                                        <td>{{ $log_notification->id }}</td>
                                        <td>{{ $log_notification->supplier_name }}</td>
                                        <td>{{ $log_notification->class }}</td>
                                        <td>{{ $log_notification->name }}</td>
                                        <td>{{ $log_notification->target }}</td>
                                        <td>{{ $log_notification->type }}</td>
                                        <td>{{ $log_notification->type_id }}</td>
                                        <td>{{ $log_notification->item }}</td>
                                        <td>{{ $log_notification->created_at->format('Y-m-d H:i') }}</td>
                                    </tr>

                                @endforeach
                            </table>
                            <br>
                            {!! $log_notifications->appends($params)->render() !!}

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
@push('scriptsEnd')
    @include('scripts.jquery-ui')
@endpush
