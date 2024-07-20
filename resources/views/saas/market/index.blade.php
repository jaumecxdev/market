@extends('saas.layouts.app')

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-12"><h1>Marketplaces</h1></div>
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

                            <table class="table table-striped">
                                <tr class="table-warning">
                                    <th>Market place</th>
                                    <th>Acciones</th>
                                </tr>
                                @foreach($markets as $market)
                                    <tr market-id="{{ $market->id }}">
                                        <td>{{ $market->id }} {{ $market->name }}</td>
                                        <td>
                                            <div class="row">
                                                <a class="mr-2" href="{{ route('saas.markets.market_categories', [$market]) }}"><i class="fas fa-list nav-icon"></i> Categorías | </a>
                                                <a class="mr-2" href="{{ route('saas.markets.supplier_categories', [$market]) }}"><i class="fas fa-list nav-icon"></i> Mapping</a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
