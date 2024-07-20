@extends('saas.layouts.app')

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
                    <div class="col-sm-12"><h1>IMÁGEN: {{ $product->name }}</h1></div>
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

                            <form method="POST" action="{{ route('saas.products.storeimages', [$product]) }}"
                                  class="uploader" accept-charset="utf-8" enctype="multipart/form-data">
                                @csrf

                                <div class="form-group row">
                                    <label for="name" class="col-sm-2 col-form-label">Añadir Imágen</label>
                                    <div class="col-sm-10">
                                        <input type="file" id="file-input" name="image" />
                                        @error('image')<br><br><div class="alert alert-danger">{{ $message }}</div>@enderror
                                        <div id="thumb-output"></div>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-2 col-form-label">#</label>
                                    <div class="col-sm-10">
                                        <div class="row">
                                            <div class="col-sm-3">
                                                <img class="w-100" src="{{ $image->getFullUrl().'?'.time() }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <br>
                                <div class="form-group row">
                                    <div class="col-sm-2"></div>
                                    <div class="col-sm-10">
                                        <a class="btn btn-danger" href="{{ route('saas.products') }}" role="button">Cancelar</a>
                                        <button type="submit" class="btn btn-primary">Guardar imágenes</button>
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

    <script>
        $(document).ready(function(){

            $('#delete-all').change(function() {
                if ( $('#delete-all').prop('checked') ) {
                    $('input:checkbox').prop('checked', true);
                } else {
                    $('input:checkbox').prop('checked', false);
                }
            });

            $('#file-input').on('change', function(){                                                   //on file input change
                if (window.File && window.FileReader && window.FileList && window.Blob)                 //check File API supported browser
                {
                    var data = $(this)[0].files;                                                        //this file data

                    $.each(data, function(index, file){                                                 //loop though each file
                        if(/(\.|\/)(gif|jpe?g|png)$/i.test(file.type)){                                 //check supported file type
                            var fRead = new FileReader();                                               //new filereader
                            fRead.onload = (function(file){                                             //trigger function on successful read
                            return function(e) {
                                var img = $('<img/>').addClass('w-25').attr('src', e.target.result);   //create image element
                                $('#thumb-output').append(img);                                         //append image to output element
                            };
                            })(file);
                            fRead.readAsDataURL(file);                                                  //URL representing the file's data.
                        }
                    });

                }
                else
                {
                    alert("Your browser doesn't support File API!");                                    //if File API is absent
                }
            });
        });
    </script>
@endpush
