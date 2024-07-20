@extends('saas.layouts.base')

@section('body')
    <body class="hold-transition">
    <div class="wrapper">
        @yield('navbar', View::make('saas.partials.navbar'))
        @yield('content')
        @yield('footer', View::make('saas.partials.footer'))
    </div>
    </body>
@endsection
