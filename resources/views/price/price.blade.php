@extends('layouts.app')

@push('styles')
    <link href="{{ asset('plugins/jquery-ui/jquery-ui.css') }}" rel="stylesheet">
@endpush

@push('menu')
    @role('admin')
    <li class="nav-item d-none d-sm-inline-block"><a href="{{ route('prices.index') }}" class="nav-link">Historial de precios</a></li>
    @endrole
@endpush

@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-10"><h1>Precios</h1></div>
                    <div class="col-sm-2">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item active">Precios</li>
                        </ol>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        @include('price.price_form')
        @include('price.by_fee')
        @include('price.by_bfit')
        @include('price.by_price')

    </div>
@endsection
@push('scriptsEnd')
    <!-- jQuery UI 1.11.4 -->
    <script src="{{ asset('plugins/jquery-ui/jquery-ui.min.js') }}"></script>

    <script>

        var cost = null;
        var canon = null;
        var rappel = null;
        var ports = null;

        var bfit_min = null;
        var mp_fee = null;
        var mp_fee_addon = null;
        var iva = null;

        var fee = null;
        var bfit = null;
        var price_mp = null;

        var total_cost = null;

        const formatter = new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });


        function getFormValues() {

            // parserFloat no work with , decimal en-US formatter -> use replace
            cost = parseFloat($(".cost").val().replace(',', ''));
            canon = parseFloat($(".canon").val().replace(',', ''));
            rappel = parseFloat($(".rappel").val().replace(',', ''));
            ports = parseFloat($(".ports").val().replace(',', ''));

            bfit_min = parseFloat($(".bfit_min").val().replace(',', ''));
            mp_fee = parseFloat($(".mp_fee").val().replace(',', ''));
            mp_fee_addon = parseFloat($(".mp_fee_addon").val().replace(',', ''));
            iva = parseFloat($(".iva").val().replace(',', ''));

            fee = parseFloat($(".fee").val().replace(',', ''));
            bfit = parseFloat($(".bfit").val().replace(',', ''));
            price_mp = parseFloat($(".price_mp").val().replace(',', ''));

            var cost_canon = cost + canon;
            total_cost = cost_canon - (cost_canon * rappel / 100) + (ports / (1 + iva/100));
            $("#total_cost").text(formatter.format(total_cost) + " €");
        }


        function getByFee()
        {
            var price = ((total_cost + mp_fee_addon) * (1 + iva/100)) / (1 - (fee/100) - (mp_fee/100) - (mp_fee/100) * (iva/100));
            var price_without_fee = price / (1 + iva/100);
            var bfit_final = (fee/100) * price_without_fee;
            if (bfit_final < bfit_min) {
                //price_without_fee = price_without_fee + (bfit_min - bfit_final);
                //price = price_without_fee * (1 + iva/100);
                //bfit_final = bfit_min;

                var cost_with_mps_benefit = total_cost + bfit_min;
                bfit_final = bfit_min;
                price = ((cost_with_mps_benefit + mp_fee_addon) * (1 + iva/100)) / (1 - (mp_fee/100) - (mp_fee/100) * (iva/100));
            }
            var mp_bfit_final = price * (mp_fee/100) + mp_fee_addon;

            $("#bfit_final").text(formatter.format(bfit_final) + " €");
            $("#mp_bfit_final").text(formatter.format(mp_bfit_final) + " €");
            $("#price_final").text(formatter.format(price) + " €");
        }


        function getByBfit()
        {
            var price = ((total_cost + bfit + mp_fee_addon) * (1 + iva/100)) / (1 - ((mp_fee/100) * (1 + iva/100)));
            var price_without_fee = price / (1 + iva/100);
            var fee_final = 100 * bfit / price_without_fee;
            var mp_bfit_final = price * (mp_fee/100) + mp_fee_addon;

            $("#fee_final2").text(formatter.format(fee_final) + " %");
            $("#mp_bfit_final2").text(formatter.format(mp_bfit_final) + " €");
            $("#price_final2").text(formatter.format(price) + " €");
        }


        function getByPrice()
        {
            var mp_bfit_final = price_mp * (mp_fee/100) + mp_fee_addon;
            var price_without_fee = price_mp / (1 + iva/100);
            var bfit_final = price_without_fee - mp_bfit_final - total_cost;
            var fee_final = 100 * (bfit_final / price_without_fee);

            $("#fee_final3").text(formatter.format(fee_final) + " %");
            $("#bfit_final3").text(formatter.format(bfit_final) + " €");
            $("#mp_bfit_final3").text(formatter.format(mp_bfit_final) + " €");
        }


        $(document).ready(function() {

            $(".cost").val(100);
            $(".canon").val(0);
            $(".rappel").val(0);
            $(".ports").val(0);

            $(".bfit_min").val(10);
            $(".mp_fee").val(6);
            $(".mp_fee_addon").val(0);
            $(".iva").val(21);

            $(".fee").val(10);
            $(".bfit").val(12.09);
            $(".price_mp").val(146.24);

            getFormValues();
            getByFee();
            getByBfit();
            getByPrice();

            $("input").change(function() {
                getFormValues();
                if (!isNaN(fee)) getByFee();
                if (!isNaN(bfit)) getByBfit();
                if (!isNaN(price_mp)) getByPrice();
            });

        });

    </script>
@endpush
