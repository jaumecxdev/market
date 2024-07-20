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
                    <div class="col-sm-10"><h1>Schedule Log</h1></div>
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


                            <p>Logs de proveedores</p>
                            <table id="table_ordened" class="table table-striped">
                                <tr class="table-warning">
                                    <th>@include('ordersby.log_schedules', ['order_by' => 'created_at', 'title' => 'Starts'])</th>
                                    <th>@include('ordersby.log_schedules', ['order_by' => 'ends_at', 'title' => 'Ends'])</th>
                                    <th>@include('ordersby.log_schedules', ['order_by' => 'name', 'title' => 'Schedule'])</th>
                                    <th>Info</th>
                                </tr>
                                @foreach(App\Http\Controllers\LogScheduleController::SUPPLIER_IDS as $supplier_id)
                                    <tr><td><strong>{{ strtoupper(App\Supplier::findOrFail($supplier_id)->name) }}</strong></td></tr>
                                    @foreach(App\Http\Controllers\LogScheduleController::SUPPLIER_LOG_TYPES as $supplier_log_type)
                                        @foreach(App\LogSchedule::whereType($supplier_log_type)->whereSupplierId($supplier_id)->orderBy('created_at', 'desc')->take(3)->get() as $type_log_schedule)

                                            <tr type_log_schedule-id="{{ $type_log_schedule->id }}">
                                                <td>{{ $type_log_schedule->created_at->format('d-m-Y H:i:s') }}</td>
                                                <td>{{ isset($type_log_schedule->ends_at) ? $type_log_schedule->ends_at->format('d-m-Y H:i:s') : null }}</td>
                                                <td><span class="badge {{ $type_log_schedule->ends_at ? 'bg-success' : 'bg-danger' }}">{{ $type_log_schedule->name }}</span></td>
                                                <td>{{ $type_log_schedule->info }}</td>
                                            </tr>

                                        @endforeach
                                    @endforeach
                                @endforeach
                            </table>
                            <br>


                            <p>Logs de tiendas</p>
                            <table id="table_ordened" class="table table-striped">
                                <tr class="table-warning">
                                    <th>@include('ordersby.log_schedules', ['order_by' => 'created_at', 'title' => 'Starts'])</th>
                                    <th>@include('ordersby.log_schedules', ['order_by' => 'ends_at', 'title' => 'Ends'])</th>
                                    <th>@include('ordersby.log_schedules', ['order_by' => 'name', 'title' => 'Schedule'])</th>
                                    <th>Info</th>
                                </tr>
                                @foreach(App\Shop::whereEnabled(1)->pluck('id')->toArray() as $shop_id)
                                    @php
                                        $shop = App\Shop::findOrFail($shop_id);
                                        $market = App\Market::findOrFail($shop->market_id);
                                        $res[] = $shop_id;
                                    @endphp
                                    <tr><td><strong>({{ strtoupper($market->name) }}) {{ strtoupper($shop->name)}}</strong></td></tr>
                                    @foreach(App\Http\Controllers\LogScheduleController::SHOPS_LOG_TYPES as $shop_log_type)

                                        @php
                                            $type_log_schedules = App\LogSchedule::whereType($shop_log_type)->whereShopId($shop_id)->orderBy('created_at', 'desc')->take(3)->get();
                                        @endphp
                                        @foreach(App\LogSchedule::whereType($shop_log_type)->whereShopId($shop_id)->orderBy('created_at', 'desc')->take(3)->get() as $type_log_schedule)
                                            <tr type_log_schedule-id="{{ $type_log_schedule->id }}">
                                                <td>{{ $type_log_schedule->created_at->format('d-m-Y H:i:s') }}</td>
                                                <td>{{ isset($type_log_schedule->ends_at) ? $type_log_schedule->ends_at->format('d-m-Y H:i:s') : null }}</td>
                                                <td><span class="badge {{ $type_log_schedule->ends_at ? 'bg-success' : 'bg-danger' }}">{{ $type_log_schedule->name }}</span></td>
                                                <td>{{ mb_substr($type_log_schedule->info, 0, 128) }}</td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                @endforeach
                            </table>
                            <br>


                            <p>Logs de notificaciones</p>
                            <table id="table_ordened" class="table table-striped">
                                <tr class="table-warning">
                                    <th>@include('ordersby.log_schedules', ['order_by' => 'created_at', 'title' => 'Starts'])</th>
                                    <th>@include('ordersby.log_schedules', ['order_by' => 'ends_at', 'title' => 'Ends'])</th>
                                    <th>@include('ordersby.log_schedules', ['order_by' => 'name', 'title' => 'Schedule'])</th>
                                    <th>Info</th>
                                </tr>
                                @php
                                    $type_log_schedules = App\LogSchedule::whereNull('supplier_id')->whereNull('shop_id')->orderBy('created_at', 'desc')
                                        ->whereType(App\Http\Controllers\LogScheduleController::NOTIFICATION_LOG_TYPE)
                                        ->where('info', '<>', 0)
                                        ->take(5)
                                        ->get();
                                @endphp
                                @foreach($type_log_schedules as $type_log_schedule)

                                    <tr type_log_schedule-id="{{ $type_log_schedule->id }}">
                                        <td>{{ $type_log_schedule->created_at->format('d-m-Y H:i:s') }}</td>
                                        <td>{{ isset($type_log_schedule->ends_at) ? $type_log_schedule->ends_at->format('d-m-Y H:i:s') : null }}</td>
                                        <td><span class="badge {{ $type_log_schedule->ends_at ? 'bg-success' : 'bg-danger' }}">{{ $type_log_schedule->name }}</span></td>
                                        <td>{{ $type_log_schedule->info }}</td>
                                    </tr>

                                @endforeach
                            </table>
                            <br>


                            <p>Otros Logs</p>
                            <table id="table_ordened" class="table table-striped">
                                <tr class="table-warning">
                                    <th>@include('ordersby.log_schedules', ['order_by' => 'created_at', 'title' => 'Starts'])</th>
                                    <th>@include('ordersby.log_schedules', ['order_by' => 'ends_at', 'title' => 'Ends'])</th>
                                    <th>@include('ordersby.log_schedules', ['order_by' => 'name', 'title' => 'Schedule'])</th>
                                    <th>Info</th>
                                </tr>

                                @foreach(App\Http\Controllers\LogScheduleController::OTHER_LOG_TYPES as $other_log_type)
                                    <tr><td><strong>{{ strtoupper($other_log_type) }}</strong></td></tr>
                                    @foreach(App\LogSchedule::whereType($other_log_type)->orderBy('created_at', 'desc')->take(3)->get() as $other_log_schedule)

                                        <tr other_log_schedule-id="{{ $other_log_schedule->id }}">
                                            <td>{{ $other_log_schedule->created_at->format('d-m-Y H:i:s') }}</td>
                                            <td>{{ isset($other_log_schedule->ends_at) ? $other_log_schedule->ends_at->format('d-m-Y H:i:s') : null }}</td>
                                            <td><span class="badge {{ $other_log_schedule->ends_at ? 'bg-success' : 'bg-danger' }}">{{ $other_log_schedule->name }}</span></td>
                                            <td>{{ $other_log_schedule->info }}</td>
                                        </tr>

                                    @endforeach
                                @endforeach
                            </table>
                            <br>




                            {{-- <p>Hay {{ $log_schedules->total() }} schedules</p>
                            {!! $log_schedules->appends($params)->render() !!}
                            <table id="table_ordened" class="table table-striped">
                                <tr class="table-warning">
                                    <th>@include('ordersby.log_schedules', ['order_by' => 'created_at', 'title' => 'Starts'])</th>
                                    <th>@include('ordersby.log_schedules', ['order_by' => 'ends_at', 'title' => 'Ends'])</th>
                                    <th>@include('ordersby.log_schedules', ['order_by' => 'name', 'title' => 'Schedule'])</th>
                                    <th>Info</th>
                                </tr>
                                @foreach($log_schedules as $log_schedule)

                                    <tr log_schedule-id="{{ $log_schedule->id }}">
                                        <td>{{ $log_schedule->created_at->format('d-m-Y H:i:s') }}</td>
                                        <td>{{ isset($log_schedule->ends_at) ? $log_schedule->ends_at->format('d-m-Y H:i:s') : null }}</td>
                                        <td><span class="badge {{ $log_schedule->ends_at ? 'bg-success' : 'bg-danger' }}">{{ $log_schedule->name }}</span></td>
                                        <td>{{ $log_schedule->info }}</td>
                                    </tr>

                                @endforeach
                            </table>
                            <br>
                            {!! $log_schedules->appends($params)->render() !!} --}}

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
