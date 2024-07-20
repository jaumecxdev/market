@extends('layouts.base')

@section('body')
    <body class="hold-transition sidebar-mini sidebar-collapse">
    <!-- Site wrapper -->
    <div class="wrapper">
        @yield('content')
        @yield('footer', View::make('partials.footer'))
    </div>
    <!-- ./wrapper -->
    </body>
@endsection
