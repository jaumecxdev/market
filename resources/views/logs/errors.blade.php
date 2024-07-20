@extends('layouts.app')

@push('menu')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('logs') }}" class="nav-link">Logs</a></li>
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-10"><h1>Logs</h1></div>
                    <div class="col-sm-2">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item active">Logs</li>
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


                                <div class="form-group row">

                                    <div class="col-sm-4">
                                        <h3>System Errors</h3>
                                        @if ($logs_list !== null)
                                            @foreach ($logs_list as $file)
                                                <a href="{{ $file['href'] }}" target="_blank">{{ $file['name'] }}</a><br>
                                            @endforeach
                                        @endif
                                    </div>

                                    <div class="col-sm-4">
                                        <h3>Marketplace Errors</h3>
                                        @foreach ($mp_list as $mp => $files)
                                            @if ($files !== null)
                                                <h5>{{ $mp }}</h5>
                                                @foreach ($files as $file)
                                                    <a href="{{ $file['href'] }}" target="_blank">{{ $file['name'] }}</a><br>
                                                @endforeach
                                            @endif
                                        @endforeach
                                    </div>

                                    <div class="col-sm-4">
                                        <h3>Supplier Errors</h3>
                                        @foreach ($supplier_list as $supplier => $files)
                                            @if ($files !== null)
                                                <h5>{{ $supplier }}</h5>
                                                @foreach ($files as $file)
                                                    <a href="{{ $file['href'] }}" target="_blank">{{ $file['name'] }}</a><br>
                                                @endforeach
                                            @endif
                                        @endforeach
                                    </div>
                                </div>


                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
