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
                    <div class="col-sm-10"><h1>Importar fichero</h1></div>
                    <div class="col-sm-2">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item active">Importar fichero</li>
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

                            <h4>INFO DE IMPORTACIÓN</h4>
                            <p>{!! $import_text !!}</p>

                            <form method="POST" action="{{ route('utils.import.process') }}"
                                  class="uploader" accept-charset="utf-8" enctype="multipart/form-data">
                                @csrf

                                <input type="hidden" name="import" value="{{ $import }}"/>

                                <div class="form-group row">
                                    <div class="col-sm-3">
                                        @include('forms.select', [
                                                'placeholder' => 'Tipo de importación',
                                                'field_name' => 'function',
                                                'options' => $functions,
                                                'option_selected' => null
                                            ])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <div class="col-sm-12">
                                        <input type="file" id="file-input" name="fileinput[]" multiple />
                                        @error('fileinput')<br><br><div class="alert alert-danger">{{ $message }}</div>@enderror
                                    </div>
                                </div>

                                <br>
                                <div class="form-group row">
                                    <div class="col-sm-12">
                                        <a class="btn btn-danger" href="{{ route('utils') }}" role="button">Cancelar</a>
                                        <button type="submit" class="btn btn-primary">Importar fichero</button>
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
@push('scriptsEnd')
    <!-- jQuery UI 1.11.4 -->
    <script src="{{ asset('plugins/jquery-ui/jquery-ui.min.js') }}"></script>
@endpush
