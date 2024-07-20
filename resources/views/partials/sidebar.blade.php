<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="/" class="brand-link">
        <img src="/dist/img/logoweb.png"
             alt="Marketplace e-Specialist Logo"
             class="brand-image"
             style="opacity: .8">
        <span class="brand-text font-weight-light">{{ config('app.name', 'Laravel') }}</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                @hasanyrole('user|owner|admin')
                <li class="nav-item">
                    <a href="{{ route('home') }}" class="nav-link">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>
                            Dashboard
                        </p>
                    </a>
                </li>
                @endhasanyrole

                @hasanyrole('seller|user|owner|admin')
                <li class="nav-header">PLATAFORMA</li>
                <li class="nav-item">
                    <a href="{{ route('products.index', ['stock_min' => 1]) }}" class="nav-link">
                        <i class="fas fa-laptop nav-icon"></i>
                        <p>Productos</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('orders.index') }}" class="nav-link">
                        <i class="far fa-smile nav-icon"></i>
                        <p>Pedidos</p>
                    </a>
                </li>
                @endhasanyrole

                @hasanyrole('user|owner|admin')
                <li class="nav-item">
                    <a href="{{ route('buyers.index') }}" class="nav-link">
                        <i class="fas fa-user-friends nav-icon"></i>
                        <p>Clientes</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('prices.price') }}" class="nav-link">
                        <i class="fas fa-euro-sign nav-icon"></i>
                        <p>Precios</p>
                    </a>
                </li>
                @endhasanyrole

                <li class="nav-header">OPERACIONES</li>
                @hasanyrole('user|owner|admin')
                <li class="nav-item">
                    <a href="{{ route('action.suppliers') }}" class="nav-link">
                        <i class="fas fa-cloud-download-alt nav-icon"></i>
                        <p>Proveedores</p>
                    </a>
                </li>
                @endhasanyrole

                @hasanyrole('seller|user|owner|admin')
                <li class="nav-item">
                    <a href="{{ route('action.markets') }}" class="nav-link">
                        <i class="fas fa-cloud-upload-alt nav-icon"></i>
                        <p>Marketplaces</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('action.shops') }}" class="nav-link">
                        <i class="fas fa-store nav-icon"></i>
                        <p>Tiendas</p>
                    </a>
                </li>
                @endhasanyrole

                @hasanyrole('user|owner|admin')
                <li class="nav-item">
                    <a href="{{ route('promos.index') }}" class="nav-link">
                        <i class="fas fa-store nav-icon"></i>
                        <p>Promos</p>
                    </a>
                </li>
                @endhasanyrole


                @role('admin')
                <li class="nav-header">TABLAS</li>
                <li class="nav-item">
                    <a href="{{ route('suppliers.index') }}" class="nav-link">
                        <i class="fas fa-cloud-download-alt nav-icon"></i>
                        <p>Proveedores</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('markets.index') }}" class="nav-link">
                        <i class="fas fa-cloud-upload-alt nav-icon"></i>
                        <p>Marketplaces</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('shops.index') }}" class="nav-link">
                        <i class="fas fa-store nav-icon"></i>
                        <p>Tiendas</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('categories.index') }}" class="nav-link">
                        <i class="fas fa-list nav-icon"></i>
                        <p>Categorías</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('brands.index') }}" class="nav-link">
                        <i class="far fa-copyright nav-icon"></i>
                        <p> Marcas</p>
                    </a>
                </li>
                @endrole


                @role('admin')
                <li class="nav-header">DT1P</li>
                <li class="nav-item">
                    <a href="{{ route('logs') }}" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Logs</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('log_schedules.index') }}" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Schedules</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('log_notifications.index') }}" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Notificaciones</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('utils') }}" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Utilidades</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('requests') }}" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Consultas</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('config') }}" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Configuración</p>
                    </a>
                </li>


                <li class="nav-item">
                    <a href="{{ route('telegram.index') }}" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Telegram</p>
                    </a>
                </li>
                {{-- <li class="nav-item">
                    <a href="{{ route('telescope') }}" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Telescope</p>
                    </a>
                </li> --}}
                @endrole
                <br><br>

            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>

