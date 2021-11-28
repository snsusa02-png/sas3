<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<!-- CSRF Token -->
	<meta name="csrf-token" content="{{ csrf_token() }}">

	<title>{{ config('app.name', 'Laravel') }}</title>

	<!-- Scripts     -->
	<script src="{{ asset('js/app.js') }}" defer></script>

	<!-- Fonts -->
	<link rel="dns-prefetch" href="//fonts.gstatic.com">
	<link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet" type="text/css">

	<!-- Icons -->
	<link rel="stylesheet" href="/css/font-awesome-4.7.0/css/font-awesome.css">
	<link href="/favicon.ico" rel="shortcut icon" type="image/vnd.microsoft.icon"/>
	<!-- Styles -->
	<link href="{{ asset('css/app.css') }}" rel="stylesheet">

	<script src="{{ asset('js/LocalSession.js') }}" defer></script>
	<style>
		.navbar-nav li {
			margin-left: 8px;
		}
	</style>
</head>
<body>
<div id="app">

	<nav class="navbar navbar-expand-md navbar-light navbar-laravel">
		<div class="container">
			<a class="navbar-brand" href="{{ route('home') }}">
				{{ config('app.name', 'Laravel') }}
			</a>
			<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
					aria-controls="navbarSupportedContent" aria-expanded="false"
					aria-label="{{ __('Toggle navigation') }}">
				<span class="navbar-toggler-icon"></span>
			</button>

			<div class="collapse navbar-collapse" id="navbarSupportedContent">
				<!-- Left Side Of Navbar -->
				<ul class="navbar-nav mr-auto">
					@guest
					@else
                        <?php
                        $staffid = Auth::user()->StaffID;
                        $userid = Auth::user()->id;
                        $orgid = \App\User::getOrgID($userid);
                        $isOurStaff = $orgid == 1;
                        ?>

						<li>&nbsp;<a href="{{route('catalog')}}">Каталог</a></li>
						@if ($staffid !="")
							@if (!$isOurStaff)
								<li>&nbsp;<a href="{{route('client.orders.index')}}">Заказы</a></li>
								<li>&nbsp;<a href="/basket">Корзина</a> <span id="basketinfo"></span></li>
							@else
								<li style="color:silver;">&nbsp;Аналитика&nbsp;</li>
								<li>&nbsp;<a href="{{route('nsi')}}">Данные</a></li>
							<!--
	                            <li style="margin-left:6px;">&nbsp;<a href="{{route('lawyer')}}" title="Юридический сервис">Юр-сервис</a>
	                            </li>
	                            -->
							@endif
						@endif
					@endguest
				</ul>
				@if (isset($staffid))
					<input type="hidden" id="staffid" value="{{$staffid}}"/>
				@endif

				@if (isset($userid))
					<input type="hidden" id="userid" value="{{$userid}}"/>
					<input type="hidden" id="orgid" value="{{$orgid}}"/>
			@endif

			<!-- Right Side Of Navbar -->
				<ul class="navbar-nav ml-auto">
					<!-- Authentication Links -->
					@guest
						<li class="nav-item">
							<a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
						</li>
						@if (Route::has('register'))
							<li class="nav-item">
								<a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
							</li>
						@endif
					@else
                        <?php
                        $userid = Auth::user()->id;
                        $userInfo = Auth::user()->name;

                        $userdata = \App\user::getUserInfo($userid);
                        //dd($userdata);

                        $userInfo = "";
                        $mngrInfo = "";
                        if (is_object($userdata)) {
                            $userInfo = $userdata->userName;
                            if ($userdata->orgname != "") {
                                $userInfo = $userInfo . '&nbsp; "' . $userdata->orgname . '"';
                            }

                            $mngrInfo = "";
                            if ($userdata->curatorstaffid != "") {
//                                $mngrInfo = $mngrInfo . "<br>Ваш менеджер: <b>" . $userdata->curatorName . "</b>";
                                $mngrInfo = $mngrInfo . "Ваш менеджер: <b>" . $userdata->curatorName . "</b>";
                                if ($userdata->phone != "") {
                                    $mngrInfo = $mngrInfo . ", <a href='tel:" . $userdata->phone . "'>" . $userdata->phone . "</a>";
                                }
                            }
                        }
                        //dd($userInfo);
                        ?>
						<li class="nav-item dropdown">
							<a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button"
							   data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
								{!!$userInfo!!} <span class="caret"></span>
							</a>
							{!! $mngrInfo!!}

							<div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
								<a class="dropdown-item" href="{{ route('logout') }}"
								   onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
									{{ __('Logout') }}
								</a>

								<form id="logout-form" action="{{ route('logout') }}" method="POST"
									  style="display: none;">
									@csrf
								</form>
							</div>

						</li>
					@endguest
				</ul>
			</div>
		</div>
	</nav>

	<div class="container">
		<div><hr></div>
		<div class="row">
			<div class="col-md-2">
				@yield('left_column')
			</div>

			<div class="col-md-10">
				<main class="py-4">
					@yield('content')
				</main>
			</div>
		</div>
	</div>
</div>

</div>
</body>
</html>
