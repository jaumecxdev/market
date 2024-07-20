@extends('layouts.base')

@section('body')
    <body class="hold-transition sidebar-mini sidebar-collapse">
    <!-- Site wrapper -->
    <div class="wrapper">
        @yield('navbar', View::make('partials.navbar'))     {{--@include--}}
        @yield('sidebar', View::make('partials.sidebar'))  {{-- View::make( Auth::user()->getSidebar() )) --}}   {{--@include--}}
        @yield('content')
        @yield('footer', View::make('partials.footer'))     {{--@include--}}
    </div>
    <!-- ./wrapper -->
    </body>
@endsection
