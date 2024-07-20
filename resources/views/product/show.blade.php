@extends('layouts.app')

@push('menu')

    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('products.edit', [$product]) }}" class="nav-link">Editar</a></li>
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('products.images', [$product]) }}" class="nav-link">Imágenes</a></li>
    @role('admin')
        <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('products.shops', [$product]) }}" class="nav-link">Tiendas</a></li>
    @endrole
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('products.attributes', [$product]) }}" class="nav-link">Atributos</a></li>
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('products.relateds', [$product]) }}" class="nav-link">Relacionados</a></li>
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-10"><h1>{{ $product->name }}</h1></div>
                    <div class="col-sm-2">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item active">Product</li>
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

                            <table>
                                @foreach($product->toArray() as $key => $value)
                                    @if ($key == 'name')
                                        <tr><td>{{ $key }}</td><td>{{ $product->buildTitle() }}</td></tr>
                                    @elseif ($key == 'shortdesc')
                                        <tr><td>&nbsp;&nbsp;</td><td>&nbsp;&nbsp;</td></tr>
                                        <tr><td style="vertical-align: top">{{ $key }}</td><td>{!! stripslashes($value) !!}</td></tr>
                                    @elseif ($key == 'longdesc')
                                        <tr><td>&nbsp;&nbsp;</td><td>&nbsp;&nbsp;</td></tr>
                                        <tr><td style="vertical-align: top">{{ $key }}</td>
                                            <td>{!! $product->buildDescription4Html() !!}</td></tr>
                                    @else
                                        <tr><td>{{ $key }}</td><td>{{ stripslashes($value) }}</td></tr>
                                    @endif
                                @endforeach
                            </table><br>

                            <h2>ATTRIBUTOS</h2><table>
                            @foreach($product->product_attributes as $product_attribute)
                                    <tr><td>{{ $product_attribute->attribute->name }}</td><td>{{ $product_attribute->value }}</td></tr>
                            @endforeach
                            </table><br>

                            <h2>IMÁGENES</h2>
                            @foreach($product->images as $image)
                                <img class="w-25" src="{{ $image->getFullUrl() }}">
                            @endforeach
                            <br>

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
