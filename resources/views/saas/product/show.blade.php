@extends('saas.layouts.app')

@push('menu')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('saas.products.edit', [$product]) }}" class="nav-link">Editar</a></li>
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('saas.products.images', [$product]) }}" class="nav-link">Imágenes</a></li>
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-12"><h1>{{ $product->name }}</h1></div>
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

                            <div class="row">
                                <div class="col-12 col-sm-6">
                                    <h3 class="d-inline-block d-sm-none">{{ strtoupper($product->name) }}</h3>
                                    <div class="col-12">
                                        <img src="{{ $product->images()->count() ? $product->images->first()->getFullUrl() : '' }}" class="product-image" alt="Product Image">
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <h3 class="my-3">{{ $product->name }}</h3>
                                    <a href="{{ route('saas.products', [
                                            'supplier_category_id' => $product->supplier_category_id,
                                            'supplier_category_name' => $product->supplier_category->name
                                        ]) }}">
                                        {{ $product->supplier_category->name }}</a><br>
                                    <a href="{{ route('saas.products', [
                                            'supplier_brand_id' => $product->supplier_brand_id,
                                            'supplier_brand_name' => $product->supplier_brand->name
                                        ]) }}">
                                        {{ $product->supplier_brand->name }}</a><br><br>
                                    <p>{{ $product->shortdesc }}</p>

                                    {{-- size, color, material, style, gender, weight, length, width, height --}}
                                    <hr>
                                    <div class="row">
                                        @if ($product->color)
                                            <div class="col-3">
                                            <h4>Color</h4>
                                            <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                                <label class="btn btn-default text-center active">
                                                    {{ $product->color }}
                                                    <br>
                                                </label>
                                            </div>
                                            </div>
                                        @endif
                                        @if ($product->size)
                                            <div class="col-3">
                                            <h4>Talla</h4>
                                            <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                                <label class="btn btn-default text-center active">
                                                    {{ $product->size }}
                                                    <br>
                                                </label>
                                            </div>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="bg-gray py-2 px-3 mt-4">
                                        <h2 class="mb-0">
                                            {{ $product->cost .' '. $product->currency->code }}
                                        </h2>
                                        <h4 class="mt-0">
                                        <small>{{ 'IVA '. $product->tax .'%: '. round($product->cost*$product->tax/100, 2) .' '. $product->currency->code }}</small>
                                        </h4>
                                    </div>

                                    <div class="mt-4">
                                        <div class="btn btn-default btn-lg btn-flat">
                                            Stock: {{ $product->stock }} unidades
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <div class="btn btn-default btn-lg btn-flat" style="text-align: left;">
                                            <strong>ID:</strong>  <span class="badge bg-success">{{ $product->id }}</span><br>
                                            <strong>SKU:</strong>  {{ $product->supplierSku }}<br>
                                            <strong>Part Number:</strong>  {{ $product->pn }}<br>
                                            <strong>EAN:</strong>  {{ $product->pn }}
                                        </div>
                                    </div>

                                    <div class="row mt-4">
                                        <nav class="w-100">
                                            <div class="nav nav-tabs" id="product-tab" role="tablist">
                                                <a class="nav-item nav-link active" id="product-desc-tab" data-toggle="tab" href="#product-desc" role="tab" aria-controls="product-desc" aria-selected="true">Descripción</a>
                                            </div>
                                        </nav>
                                        <div class="tab-content p-3" id="nav-tabContent">
                                            <div class="tab-pane fade show active" id="product-desc" role="tabpanel" aria-labelledby="product-desc-tab">{{ $product->longdesc}}</div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
