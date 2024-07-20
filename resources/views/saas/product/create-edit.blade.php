@extends('saas.layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('plugins/summernote/summernote-bs4.css') }}">
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-12"><h1>{{ isset($product) ? 'EDITAR PRODUCTO: ' .$product->name : 'AÑADIR PRODUCTO' }}</h1></div>
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

                            @if (isset($product))
                                <form method="post" action="{{ route('saas.products.update', $product->id) }}">
                                    @method('PATCH')
                            @else
                                <form method="post" action="{{ route('saas.products.store') }}">
                            @endif
                                @csrf

                                    <div class="form-group row">
                                        <label for="supplier" class="col-sm-2 col-form-label">Prov. / Cat. / Marca</label>
                                        <div class="col-sm-2">
                                            @include('forms.supplier', ['suppliers' => $suppliers, 'supplier_id' => $product->supplier_id ?? old('supplier_id')])
                                        </div>
                                        <div class="col-sm-4">
                                            @include('forms.supplier_category', ['supplier_category_id' => $product->supplier_category_id ?? old('supplier_category_id'), 'supplier_category_name' => $product->supplier_category->name ?? old('supplier_category_name')])
                                        </div>
                                        <div class="col-sm-4">
                                            @include('forms.supplier_brand', ['supplier_brand_id' => $product->supplier_brand_id ?? old('supplier_brand_id'), 'supplier_brand_name' => $product->supplier_brand->name ?? old('supplier_brand_name')])
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="name" class="col-sm-2 col-form-label">Título</label>
                                        <div class="col-sm-10">
                                            @include('forms.name', ['name' => $product->name ?? old('name')])
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="model" class="col-sm-2 col-form-label">Referencias Producto</label>
                                        <div class="col-sm-10 form-inline">
                                            @include('forms.product_ids', [
                                                'pn' => $product->pn ?? old('pn'),
                                                'ean' =>  $product->ean ?? old('ean'),
                                                'upc' => $product->upc ?? old('upc'),
                                                'isbn' => $product->isbn ?? old('isbn'),
                                                'gtin' => $product->gtin ?? old('gtin')])
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="weight" class="col-sm-2 col-form-label">Peso y dimensiones</label>
                                        <div class="col-sm-10 form-inline">
                                            @include('forms.weight', ['weight' => $product->weight ?? old('weight')])
                                            @include('forms.length', ['length' => $product->length ?? old('length')])
                                            @include('forms.width', ['width' => $product->width ?? old('width')])
                                            @include('forms.height', ['height' => $product->height ?? old('height')])
                                        </div>
                                    </div>


                                    <div class="form-group row">
                                        <label for="shortdesc" class="col-sm-2 col-form-label">Texto corto</label>
                                        <div class="col-sm-10">
                                            <div id="disable-summernote" class="btn btn-warning">Desabilitar HTML</div>
                                            @include('forms.shortdesc', ['shortdesc' => $product->shortdesc ?? old('shortdesc')])
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="longdesc" class="col-sm-2 col-form-label">Descripción</label>
                                        <div class="col-sm-10">
                                            @include('forms.longdesc', ['longdesc' => $product->longdesc ?? old('longdesc')])
                                        </div>
                                    </div>
                                    <br><br>

                                    <div class="form-group row">
                                        <label for="ready" class="col-sm-2 col-form-label">SKU Preparado</label>
                                        <div class="col-sm-10">
                                            @include('forms.ready_checkbox', ['ready' => $product->ready ?? old('ready')])
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="model" class="col-sm-2 col-form-label">Referencias SKU</label>
                                        <div class="col-sm-10 form-inline">
                                            @include('forms.supplier_sku', ['supplierSku' => $product->supplierSku ?? old('supplierSku')])
                                            @include('forms.model', ['model' => $product->model ?? old('model')])
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="status_id" class="col-sm-2 col-form-label">Estado / Coste / Stock</label>
                                        <div class="col-sm-10 form-inline">
                                            @include('forms.status_select', ['statuses' => $statuses, 'status_id' => $product->status_id ?? old('status_id')])
                                            @include('forms.currency_select', ['currencies' => $currencies, 'currency_id' => $product->currency_id ?? old('currency_id') ?? 1])
                                            @include('forms.cost', ['cost' => $product->cost ?? old('cost')])
                                            @include('forms.tax', ['tax' => $product->tax ?? old('tax') ?? 21])
                                            @include('forms.stock', ['stock' => $product->stock ?? old('stock')])
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="status_id" class="col-sm-2 col-form-label">Atributos SKU</label>
                                        <div class="col-sm-10 form-inline">
                                            @include('forms.size', ['size' => $product->size ?? old('size')])
                                            @include('forms.color', ['color' => $product->color ?? old('color')])
                                            @include('forms.material', ['material' => $product->material ?? old('material')])
                                            @include('forms.style', ['style' => $product->style ?? old('style')])
                                            @include('forms.gender', ['gender' => $product->gender ?? old('gender')])
                                        </div>
                                    </div>

                                    <br>
                                    <div class="form-group row">
                                        <div class="col-sm-2"></div>
                                        <div class="col-sm-10">
                                            <a class="btn btn-danger" href="{{ route('saas.products') }}" role="button">Cancelar</a>
                                            <button type="submit" class="btn btn-primary">Guardar producto</button>
                                        </div>
                                    </div>

                            @if (isset($product))</form>@else</form>@endif

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
@push('scriptsEnd')
    @include('scripts.jquery-ui')
    @include('scripts.autocomplete-supplierbrands', ['suppliers_id' => $suppliers->pluck('id')])
    @include('scripts.autocomplete-suppliercategories', ['suppliers_id' => $suppliers->pluck('id')])
    <script src="{{ asset('plugins/summernote/summernote-bs4.min.js') }}"></script>
    @include('scripts.product-textareas')
@endpush
