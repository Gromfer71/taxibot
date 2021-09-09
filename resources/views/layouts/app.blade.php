<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>TaxiBot</title>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet" type="text/css">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/dashboard.css') }}" rel="stylesheet">

     <link href="{{ asset('ulkit/css/uikit.min.css') }}" rel="stylesheet">
    <script src="{{ asset('ulkit/js/uikit.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/uikit@3.6.13/dist/js/uikit-icons.min.js"></script>

    <link href="https://cdn.fancygrid.com/fancy.min.css" rel="stylesheet">
    <script src="https://cdn.fancygrid.com/fancy.min.js"></script>
    <script>
        Fancy.MODULESDIR = "https://cdn.fancygrid.com/modules/";
    </script>


    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.2.1/chart.min.js"></script>


</head>
<body>
<div id="app">
    <nav class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">

        <a class="navbar-brand col-md-3 col-lg-2 mr-0 px-3" href="{{ url('/') }}">
            TaxiBot
        </a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-toggle="collapse"
                data-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false"
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <ul class="navbar-nav px-3">
            @auth
                <li class="nav-item text-nowrap">
                    <a class="nav-link" href="{{ route('logout') }}"
                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                        Выйти
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </li>
            @endauth
        </ul>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <!-- Left Side Of Navbar -->
            <ul class="navbar-nav mr-auto">

            </ul>

            <!-- Right Side Of Navbar -->
            {{--                <ul class="navbar-nav ml-auto">--}}
            {{--                    <!-- Authentication Links -->--}}
            {{--                    @guest--}}
            {{--                        <li class="nav-item">--}}
            {{--                            <a class="nav-link" href="{{ route('login') }}">Авторизация</a>--}}
            {{--                        </li>--}}
            {{--                        @if (Route::has('register'))--}}
            {{--                            <li class="nav-item">--}}
            {{--                                <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>--}}
            {{--                            </li>--}}
            {{--                        @endif--}}
            {{--                    @else--}}
            {{--                        <li class="nav-item dropdown">--}}
            {{--                            <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>--}}
            {{--                                {{ Auth::user()->phone }} <span class="caret"></span>--}}
            {{--                            </a>--}}

            {{--                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">--}}
            {{--                                <a class="dropdown-item" href="{{ route('logout') }}"--}}
            {{--                                   onclick="event.preventDefault();--}}
            {{--                                                     document.getElementById('logout-form').submit();">--}}
            {{--                                   Выйти--}}
            {{--                                </a>--}}

            {{--                                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">--}}
            {{--                                    @csrf--}}
            {{--                                </form>--}}
            {{--                            </div>--}}
            {{--                        </li>--}}
            {{--                    @endguest--}}
            {{--                </ul>--}}
        </div>

    </nav>

    <div class="container-fluid">
        <div class="row">
            @auth
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="sidebar-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link @if(request()->url() == route('bot_settings')) active @endif" href="{{ route('bot_settings') }}">

                                Настройки  <span class="sr-only">(current)</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link @if(request()->url() == route('users')) active @endif" href="{{ route('users') }}">

                                Пользователи
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link @if(request()->url() == route('edit_messages')) active @endif" href="{{ route('edit_messages') }}">

                                Сообщения
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link @if(request()->url() == route('edit_buttons')) active @endif" href="{{ route('edit_buttons') }}">

                                Кнопки
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <span data-feather="bar-chart-2"></span>
                                Статистика
                            </a>
                        </li>

                    </ul>

{{--                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">--}}
{{--                        <span>Saved reports</span>--}}
{{--                        <a class="d-flex align-items-center text-muted" href="#" aria-label="Add a new report">--}}
{{--                            <span data-feather="plus-circle"></span>--}}
{{--                        </a>--}}
{{--                    </h6>--}}
{{--                    <ul class="nav flex-column mb-2">--}}
{{--                        <li class="nav-item">--}}
{{--                            <a class="nav-link" href="#">--}}
{{--                                <span data-feather="file-text"></span>--}}
{{--                                Current month--}}
{{--                            </a>--}}
{{--                        </li>--}}
{{--                        <li class="nav-item">--}}
{{--                            <a class="nav-link" href="#">--}}
{{--                                <span data-feather="file-text"></span>--}}
{{--                                Last quarter--}}
{{--                            </a>--}}
{{--                        </li>--}}
{{--                        <li class="nav-item">--}}
{{--                            <a class="nav-link" href="#">--}}
{{--                                <span data-feather="file-text"></span>--}}
{{--                                Social engagement--}}
{{--                            </a>--}}
{{--                        </li>--}}
{{--                        <li class="nav-item">--}}
{{--                            <a class="nav-link" href="#">--}}
{{--                                <span data-feather="file-text"></span>--}}
{{--                                Year-end sale--}}
{{--                            </a>--}}
{{--                        </li>--}}
{{--                    </ul>--}}
                </div>
            </nav>
            @endauth

            <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">


                @if(session('ok'))
                    <script>
                        UIkit.notification({message: "{{ session('ok') }}", pos: 'top-center', status: 'success'})
                    </script>
                @endif
                @if(session('error'))
                    <script>
                        UIkit.notification({message: "{{ session('error') }}", pos: 'top-center', status: 'danger'})
                    </script>
                @endif
                @if($errors->any())
                    <script>
                        UIkit.notification({message: '{!! $errors->first() !!}', pos: 'top-center', status: 'danger'})
                    </script>
                @endif

                    @yield('content')

            </main>
        </div>
    </div>
</div>
@yield('scripts')
@stack('scripts')
</body>
</html>
