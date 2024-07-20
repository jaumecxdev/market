@extends('layouts.app')

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-8"><h1>TELEGRAM</h1></div>
                    <div class="col-sm-4">
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

                            <form method="get" action="{{ route('telegram.webhook') }}" class="">

                                <div class="form-group row">
                                    <label for="bot" class="col-sm-2 col-form-label">Bot username</label>
                                    <div class="col-sm-4">
                                        @include('forms.custom', ['field_name' => 'bot', 'value' => 'mpspecialistbot'])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="invite_code" class="col-sm-2 col-form-label">invite_code</label>
                                    <div class="col-sm-4">
                                        @include('forms.custom', ['field_name' => 'invite_code', 'value' => '9E3D4B623CBF2'])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="chat_id" class="col-sm-2 col-form-label">chat_id</label>
                                    <div class="col-sm-4">
                                        @include('forms.custom', ['field_name' => 'chat_id', 'value' => '639306860'])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="text" class="col-sm-2 col-form-label">Text</label>
                                    <div class="col-sm-4">
                                        @include('forms.custom', ['field_name' => 'text', 'value' => 'Hola'])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <div class="col-sm-2"></div>
                                    <div class="col-sm-8">
                                        <button class="btn btn-success mr-2 mb-2" type="submit" name="action" value="set">SET WEBHOOK</button>
                                        <button class="btn btn-success mr-2 mb-2" type="submit" name="action" value="get">GET WEBHOOK</button>
                                        <button class="btn btn-success mr-2 mb-2" type="submit" name="action" value="delete">DELETE WEBHOOK</button>
                                        <br>
                                        <button class="btn btn-primary mr-2 mb-2" type="submit" name="action" value="invite">INVITAR AL BOT</button>
                                        <br>
                                        <button class="btn btn-primary mr-2 mb-2" type="submit" name="action" value="getme">GET_ME</button>
                                        <button class="btn btn-primary mr-2 mb-2" type="submit" name="action" value="sendmessage">SEND MESSAGE</button>
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
