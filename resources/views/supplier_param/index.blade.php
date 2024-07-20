@extends('layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@push('menu')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('suppliers.supplier_params.sync', [$supplier]) }}"
                class="btn btn-danger">IMPORTANTE: SINCRONIZAR TABLAS DESPUÉS DE MODIFICAR</a></li>
@endpush

@section('content')
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-10"><h1>AÑADIR PARÁMETRO PARA EL PROVEEDOR: {{ $supplier->name }}</h1></div>
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

                            @if (isset($supplier_param))
                                <form method="post" action="{{ route('suppliers.supplier_params.update', [$supplier, $supplier_param]) }}">
                                    @method('PATCH')
                            @else
                                <form method="post" action="{{ route('suppliers.supplier_params.store', [$supplier]) }}">
                            @endif
                                @csrf

                                <p>Parámetros para el cálculo de los Precios Base de productos de este proveedor CONDICIONADOS
                                    por Producto, Marca o Categoría, por este orden de preferencia.</p>

                                <p class="text-success"><b>PARÁMETROS CONDICIONANTES</b></p>

                                <div class="form-group row">
                                    <label for="product_id" class="col-sm-2 col-form-label">Producto</label>
                                    <div class="col-sm-2">
                                        @include('forms.supplier_sku', ['supplierSku' => $supplier_param->supplierSku ?? old('supplierSku')])
                                    </div>
                                    <div class="col-sm-4">
                                        {{-- @include('forms.product', ['item_select' => 'name', 'product_id' => null, 'item_reference' => null]) --}}

                                        @include('forms.product', [
                                            'item_select' => (isset($supplier_param->pn) ? 'pn' :
                                                (isset($supplier_param->ean) ? 'ean' :
                                                (isset($supplier_param->upc) ? 'upc' :
                                                (isset($supplier_param->isbn) ? 'isbn' :
                                                (isset($supplier_param->gtin) ? 'gtin' : 'name'
                                            ))))),
                                            'product_id' => $supplier_param->product_id ?? old('product_id'),
                                            'item_reference' => $supplier_param->pn ??
                                                $supplier_param->ean ??
                                                $supplier_param->upc ??
                                                $supplier_param->isbn ??
                                                $supplier_param->gtin ??
                                                (isset($supplier_param->product_id) ? $supplier_param->product->name : old('item_reference')) ])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="brand_id" class="col-sm-2 col-form-label">Marca | Categoría</label>
                                    <div class="col-sm-2">
                                        @include('forms.brand', [
                                            'brand_id' => $supplier_param->brand_id ?? old('brand_id'),
                                            'brand_name' => $supplier_param->brand->name ?? old('brand_name')])
                                    </div>
                                    <div class="col-sm-4">
                                        @include('forms.category', [
                                            'category_id' => $supplier_param->category_id ?? old('category_id'),
                                            'category_name' => $supplier_param->category->name ?? old('category_name')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="cost_min" class="col-sm-2 col-form-label">Coste mínimo | Máximo</label>
                                    <div class="col-sm-4 form-inline">
                                        @include('forms.cost_filter', ['cost_min' => $supplier_param->cost_min ?? old('cost_min'), 'cost_max' => $supplier_param->cost_max ?? old('cost_max')])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="starts_at" class="col-sm-2 col-form-label">Fecha inicio | fin</label>
                                    <div class="col-sm-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                            </div>
                                        <input type="text" name="starts_at" id="starts_at" placeholder="yyyy-mm-dd"
                                            class="form-control mr-2"
                                            value="{{ isset($supplier_param->starts_at) ? $supplier_param->starts_at->format('Y-m-d') : old('starts_at') }}">
                                        </div>
                                        <small>Fechas en las que se aplicarán estos parámetros.</small>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                            </div>
                                            <input type="text" name="ends_at" id="ends_at"  placeholder="yyyy-mm-dd"
                                                class="form-control mr-2"
                                                value="{{ isset($supplier_param->ends_at) ? $supplier_param->ends_at->format('Y-m-d') : old('ends_at') }}">
                                        </div>
                                    </div>
                                </div>

                                <p class="text-success"><b>PARÁMETROS DE IMPORTACIÓN DEL PRODUCTO: CÁLCULO DEL PRECIO BASE O COSTE</b></p>

                                <div class="form-group row">
                                    <label for="rappel" class="col-sm-2 col-form-label">Rappel % | Ports €</label>
                                    {{-- <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'canon', 'value' => $supplier_param->canon ?? old('canon') ?? 0 ])
                                        <small>Canon de productos o añadido € al precio de coste.</small>
                                    </div> --}}
                                    <div class="col-sm-2">
                                        @include('forms.rappel', ['rappel' => $supplier_param->rappel ?? old('rappel') ?? 0 ])
                                        <small>Rappel % o descuento sobre el precio base (coste + canon).</small>
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'ports', 'value' => $supplier_param->ports ?? old('ports') ?? 0 ])
                                        <small>Transporte € SIN IVA o añadido al precio base.</small>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="fee" class="col-sm-2 col-form-label">€ Precio MP | Stock MP</label>
                                    <div class="col-sm-2">
                                        @include('forms.price', ['price' => $supplier_param->price ?? old('price')])
                                        <small>PVP € fijado para todas las Tiendas</small>
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.custom', ['field_name' => 'discount_price', 'value' => $supplier_param->discount_price ?? old('discount_price'), 'placeholder' => 'Precio final con descuento'])
                                        <small>PVP € de descuento fijado - Precio tachado</small>
                                    </div>
                                    <div class="col-sm-2">
                                        @include('forms.stock', ['stock' => $supplier_param->stock ?? old('stock')])
                                        <small>Stock fijado para todas las Tiendas</small>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <div class="col-sm-2"></div>
                                    <div class="col-sm-10">
                                        <button type="submit" class="btn btn-primary">Guardar parámetro a {{ $supplier->name }}</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">PARÁMETROS DEL PROVEEDOR {{ strtoupper($supplier->name) }}</div>
                        <div class="card-body">
                            <div class="panel panel-default">
                                <div class="panel-body">
                                    <p>Hay {{ count($supplier_params) }} parámetros</p>
                                    <br>
                                    <table class="table table-striped">
                                        <tr class="table-warning">
                                            <th>#</th>
                                            <th>Producto</th>
                                            <th>Categoría | Marca</th>

                                            <th>Coste Mín<br>Max</th>
                                            <th>Fecha inicio<br>Fin</th>
                                            <th>Canon<br>Rappel<br>Ports</th>
                                            <th>PVP<br>Descuento<br>Stock fijo</th>

                                            <th>Acciones</th>
                                        </tr>
                                        @foreach($supplier_params as $supplier_param)
                                            <tr supplier_param-id="{{ $supplier_param->id }}">
                                                <td>{{ $supplier_param->id }}</td>
                                                {{-- <td>{{ $supplier_param->supplierSku ?? '' }}</td> --}}

                                                <td>{{ isset($supplier_param->supplierSku) ? ('SKU: '.$supplier_param->supplierSku) :
                                                    (isset($supplier_param->product_id) ? substr($supplier_param->product->name ?? null, 0, 20) :
                                                    (isset($supplier_param->pn) ? ('PN: '.$supplier_param->pn) :
                                                    (isset($supplier_param->ean) ? ('EAN: '.$supplier_param->ean) :
                                                    (isset($supplier_param->upc) ? ('UPC: '.$supplier_param->upc) :
                                                    (isset($supplier_param->isbn) ? ('ISBN: '.$supplier_param->isbn) :
                                                    (isset($supplier_param->gtin) ? ('GTIN: '.$supplier_param->gtin) :
                                                    '' )))))) }}
                                                </td>


                                                <td>{{ $supplier_param->category_id ? '('.$supplier_param->category->code.') '.$supplier_param->category->name : '' }}
                                                    {{ $supplier_param->brand_id ? '| '.$supplier_param->brand->name : '' }}</td>


                                                <td>{{ $supplier_param->cost_min ? $supplier_param->cost_min.' €' : '' }}<br>
                                                    {{ $supplier_param->cost_max ? $supplier_param->cost_max.' €' : '' }}</td>
                                                <td>{{ isset($supplier_param->starts_at) ? $supplier_param->starts_at->format('Y-m-d') : '' }}<br>
                                                    {{ isset($supplier_param->ends_at) ? $supplier_param->ends_at->format('Y-m-d') : '' }}</td>
                                                <td>{{ $supplier_param->canon != 0 ? $supplier_param->canon.' €' : '' }}<br>
                                                    {{ $supplier_param->rappel != 0 ? $supplier_param->rappel.' %' : '' }}<br>
                                                    {{ $supplier_param->ports != 0 ? $supplier_param->ports.' €' : '' }}</td>

                                                <td>{{ $supplier_param->price != 0 ? $supplier_param->price.' €' : '' }}<br>
                                                    {{ $supplier_param->discount_price != 0 ? $supplier_param->discount_price.' €' : '' }}<br>
                                                    {{ $supplier_param->stock != 0 ? $supplier_param->stock : '' }}
                                                </td>

                                                <td class="row">
                                                    <a class="mr-2"
                                                        href="{{ route('suppliers.supplier_params.edit', [$supplier, $supplier_param]) }}" data-toggle="tooltip" title="Editar">
                                                        <i class="far fa-edit"></i></a>
                                                    <form class="delete" action="{{ route('suppliers.supplier_params.destroy', [$supplier, $supplier_param]) }}" method="post">
                                                        @method('delete')
                                                        @csrf
                                                        @include('forms.a_delete', ['title' => 'Quitar parámetro'])
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>
    </div>
@endsection
@push('scriptsEnd')
    @include('scripts.jquery-ui')
    @include('scripts.autocomplete-brands')
    @include('scripts.autocomplete-categories')
    @include('scripts.autocomplete-products', ['supplier_id' => $supplier->id])
    @include('scripts.submit-a-delete', ['question' => '¿Estás seguro de quitar este parámetro?'])
@endpush
