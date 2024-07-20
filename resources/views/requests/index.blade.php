@extends('layouts.app')

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-10"><h1>Consultas</h1></div>
                    <div class="col-sm-2">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item active">Consultas</li>
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

                            <form method="post" action="{{ route('requests.get') }}" class="form-inline">
                                @csrf

                                <div class="container">
                                    <div class="form-group row">
                                        <div class="col-sm-12">

                                            <select class="form-control mr-2 mb-2" name="request_name">
                                                <option value="">Consulta</option>
                                                @foreach ($requests_name as $key => $value)
                                                    <option value="{{ $key }}">{{ $value }}</option>
                                                @endforeach
                                            </select>
                                            <br>

                                            <select class="form-control mr-2 mb-2" name="shop_id">
                                                <option value="">Tienda</option>
                                                @foreach ($shops as $shop)
                                                    <option value="{{ $shop->id }}">({{ $shop->market->name }}) {{ $shop->name }}</option>
                                                @endforeach
                                            </select>
                                            <br>

                                            <input type="text" class="form-control border-primary" name="param" id="param"
                                                   placeholder="Parámetro" value="{{ old('param') }}">
                                            <input type="text" class="form-control border-primary" name="param2" id="param2"
                                                   placeholder="Parámetro 2" value="{{ old('param2') }}">
                                            <input type="text" class="form-control border-primary" name="param3" id="param3"
                                                   placeholder="Parámetro 3" value="{{ old('param3') }}">
                                            <br><br>

                                            <button class="btn btn-success mb-2" type="submit" value="Consultar">Consultar</button>
                                        </div>
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
