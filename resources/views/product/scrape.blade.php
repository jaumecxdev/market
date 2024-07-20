@extends('layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@push('menu')
@endpush

@section('content')
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-10"><h1>SCRAPEAR PRODUCTO</h1></div>
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

                            <a href="{{ route('products.scrapeby', [$product, 'Vox66Scrape']) }}">Scrape By Vox66</a><br>
                            <a href="{{ route('products.scrapeby', [$product, 'KinyoScrape']) }}">Scrape By Kinyo</a><br>
                            <a href="{{ route('products.scrapeby', [$product, 'ICECatScrape']) }}">Scrape By ICECat IS COMING...</a><br>

                        </div>
                    </div>

                </div>
            </div>
        </section>
    </div>
@endsection
@push('scriptsEnd')
@endpush
