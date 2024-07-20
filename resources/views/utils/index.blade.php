@extends('layouts.app')

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-10"><h1>Utilidades</h1></div>
                    <div class="col-sm-2">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item active">utilidades</li>
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

                            <div class="row">

                                <div class="col-md-6">
                                    <div class="card card-outline card-primary">
                                        <div class="card-header">
                                            <h3 class="card-title">Primary Outline</h3>
                                        </div>
                                        <div class="card-body">
                                            <a href="{{ route('utils.match.eans') }}" class="small-box-footer">Match EANS</a><br>
                                            <a href="{{ route('utils.match.images') }}" class="small-box-footer">Match Images</a><br>
                                            <br>

                                            <a href="{{ route('utils.product.backslash') }}" class="small-box-footer">Eliminar Backslashes de todos los productos</a><br>
                                            <br>
                                            <a href="{{ route('utils.update.vox') }}" class="small-box-footer">Update VOX66 API</a><br>
                                            <br>

                                            <h4>Scrape</h4>
                                            <a href="{{ route('utils.file.select') }}" class="small-box-footer">Generar fichero</a><br>
                                            <a href="{{ route('utils.file.get') }}" class="small-box-footer">Procesar fichero</a><br>
                                            <br>

                                            <h4>Mailing</h4>
                                            <a href="{{ route('utils.mailjet.sectors') }}" class="small-box-footer">Generar y Ver sectores de empresas</a><br>
                                            <a href="{{ route('utils.mailjet.blockeds') }}" class="small-box-footer">Eliminar correos bloqueados por Mailjet</a><br>
                                            <a href="{{ route('utils.mailjet.deleteds') }}" class="small-box-footer">Eliminar correos que no quieren Mailing</a><br>
                                            <br>

                                            <h4>Shop Filters</h4>
                                            <a href="{{ route('utils.own_suppliers.delete') }}" class="small-box-footer">Eliminar Proveedores de MPe de los Shop Filters</a><br>
                                            <br>

                                            <form method="POST" action="{{ route('utils.mailjet') }}">
                                                @csrf
                                                <div class="form-group row">
                                                    <div class="col-sm-6">
                                                        @include('forms.custom', [
                                                                'placeholder' => 'sectores separados por comas',
                                                                'field_name' => 'sectors'
                                                            ])
                                                    </div>
                                                    <div class="col-sm-4">
                                                        <button type="submit" class="btn btn-primary">Generar CSV para Mailjet</button>
                                                    </div>
                                                </div>
                                            </form>

                                            <form method="POST" action="{{ route('utils.import') }}">
                                                @csrf
                                                <div class="form-group row">
                                                    <div class="col-sm-3">
                                                        @include('forms.select', [
                                                                'placeholder' => 'Selecciona Importación',
                                                                'field_name' => 'import',
                                                                'options' => $imports,
                                                                'option_selected' => null
                                                            ])
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <button type="submit" class="btn btn-primary">Siguiente</button>
                                                    </div>
                                                </div>
                                            </form>


                                            <form method="POST" action="{{ route('utils.supplier_orders') }}">
                                                @csrf
                                                <div class="form-group row">
                                                    <div class="col-sm-6">
                                                        @include('forms.custom', [
                                                                'placeholder' => 'Marca',
                                                                'field_name' => 'brand_name'
                                                            ])
                                                    </div>
                                                    <div class="col-sm-4">
                                                        <button type="submit" class="btn btn-primary">Get Supplier Orders</button>
                                                    </div>
                                                </div>
                                            </form>


                                            <form method="POST" action="{{ route('utils.order_categories') }}">
                                                @csrf
                                                <div class="form-group row">
                                                    <div class="col-sm-6">
                                                        @include('forms.shop', ['shops' => $shops, 'shop_id' => null])
                                                    </div>
                                                    <div class="col-sm-4">
                                                        <button type="submit" class="btn btn-primary">Get Order Categories</button>
                                                    </div>
                                                </div>
                                            </form>


                                            <form method="POST" action="{{ route('utils.test') }}"
                                                class="uploader" accept-charset="utf-8" enctype="multipart/form-data">
                                                @csrf

                                                <div class="form-group row">
                                                    <div class="col-sm-2">
                                                        <button type="submit" class="btn btn-primary">Test File</button>
                                                    </div>
                                                    <div class="col-sm-4">
                                                        <input type="file" id="file-input" name="fileinput[]" multiple />
                                                        @error('fileinput')<br><br><div class="alert alert-danger">{{ $message }}</div>@enderror
                                                    </div>
                                                </div>
                                            </form>


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
