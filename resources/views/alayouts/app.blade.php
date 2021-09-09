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
    <link href="{{ asset('css/auch.css') }}" rel="stylesheet">
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
<body class="text-center">
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

@yield('scripts')
@stack('scripts')
</body>
</html>
