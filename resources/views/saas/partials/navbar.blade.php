<nav class="navbar navbar-expand navbar-white border-bottom">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" href="{{ route('saas') }}"><i class="fas fa-home"></i></a>
        </li>
        @stack('menu')
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">

        <li class="nav-item">
            @php \Carbon\Carbon::setLocale('es') @endphp
            <span class="nav-link">{{ now()->format('h:i:s A') }}</span>
        </li>

        @guest
            <li class="nav-item">
                <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
            </li>
        @else
            @role('admin')
            <li class="nav-item">
                <a class="nav-link" href="{{ route('register') }}">Crear usuario</a>
            </li>
            @endrole

            <!-- User Dropdown Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="fas fa-user"></i>&nbsp;&nbsp;{{ Auth::user()->name }}
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="#"
                        onclick="event.preventDefault();
                        document.getElementById('logout-form').submit();">
                        <i class="fas fa-sign-out-alt"></i> {{ __('Logout') }}
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </div>
            </li>
        @endguest

    </ul>
</nav>
