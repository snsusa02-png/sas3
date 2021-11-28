<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
   <!-- CSRF Token -->
   <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Scripts     -->
    <script src="{{ asset('js/app.js') }}" defer></script>
    <!-- Icons -->
    <link rel="stylesheet" href="/css/font-awesome-4.7.0/css/font-awesome.css">

  <link href="{{ asset('css/app.css') }}" rel="stylesheet" type="text/css" />
</head>
<body style="background-color: dimgray">

{{--  <div class="container">--}}
{{--        <div class="row">--}}
            @yield('content')
{{--        </div class="row">--}}
{{--  </div>--}}
  @stack('scripts')
</body>
</html>
