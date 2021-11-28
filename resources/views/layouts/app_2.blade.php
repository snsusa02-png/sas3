<?php
$bForCatalog = env("APP_CATALOG_ENABLED") ?? false;

$userid = \Auth::user()->id ?? null;
//$userid = (\Auth::user()->active == 1) ? \Auth::user()->id : null;
$userorgid = (\Auth::user()->active ?? 0 == 1) ? \Auth::user()->curorgid : null;
?>
    <!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title','') / {{config('app.name')}}</title>

    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-174477303-1"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }

        gtag('js', new Date());

        gtag('config', 'UA-174477303-1');
    </script>


    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="/css/font-awesome-4.7.0/css/font-awesome.css">

    {{--	<link href="/favicon.ico" rel="shortcut icon" type="image/vnd.microsoft.icon"/>--}}

    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="theme-color" content="#ffffff">

    <!-- Styles -->
    @notifyCss
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    <style>
        /*.nav-item > dropdown > a {*/
        /*	padding-top: 15px;*/
        /*	padding-bottom: 15px;*/
        /*}*/

        /*.main-header .navbar .nav>li>a>.label {*/


        .navbar-nav > li > a > .label {
            position: absolute;
            top: 3px;
            right: 3px;
            text-align: center;
            font-size: 9px;
            padding: 3px 4px;
            line-height: .9;
            color: snow;
            font-weight: bold;
        }

        .label-warning, .modal-warning .modal-body {
            /*background-color: #f39c12 !important;*/
            /*background-color: rgba(243, 156, 18, 0.9) !important;*/
            background-color: rgba(255, 16, 16, 0.9) !important;
            color: white;
            border-radius: 50%;
        }

        .label-no_warning {
            background-color: silver !important;
            color: snow !important;
        }

        li .disable {
            color: silver;
        }

        .navbar-brand {
            transition: color 0.5s ease;
            color: green;
        }

        .navbar-brand:hover {
            color: #3c22ff;
        }


    </style>

</head>
<body>
<div id="app">
    <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm sticky-top1">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/home') }}">
                {{ config('app.name', 'СтройПлан') }}
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
                        @if (isset($userid) and isset($userorgid))

                            @if( 1==1)
                                <li class="ml-1">&nbsp;<a href="{{route('rqsts')}}"
                                                          title="Спецтехника, перевозки, материалы, обордуование...">Заявки</a>
                                </li>
                            @endif

                            @if (1==1)

                                @if( 1==1)
                                    <li>&nbsp;<a href="{{route('office')}}" class="ml-1">Офис</a></li>
                                    {{--                                    <li>&nbsp;<a href="{{route('orgcontacts.index')}}" class="ml-1">Контакты</a></li>--}}
                                @endif

                                {{--                                @if( 1==1 and \App\usrsysright::isUserHasRightByCode_cached($userid,'buildobjs.read'))--}}
                                @if( 1==1 )
                                    <li>&nbsp;<a href="{{route('planning')}}" class="ml-1">Планирование</a></li>
                                @else
                                    <li>&nbsp;<span class="ml-1 disable">Планирование</span></li>
                                @endif

                                @if( 1==1 or \App\usrsysright::isUserHasRightByCode_cached($userid,'invoices.read'))
                                    <li>&nbsp;<a href="{{route('finance')}}" class="ml-1">ФинМонитор</a></li>
                                @else
                                    <li>&nbsp;<span class="ml-1 disable">ФинМонитор</span></li>
                                @endif
                                {{--                                @if( 1==1 || \App\usrsysright::isUserHasRightByCode_cached($userid,'posts.read'))--}}
                                @if( 1==1 )
                                    <li>&nbsp;<a href="{{route('posts.public_index')}}" class="ml-1">Библиотека</a></li>
                                @endif
                                @if( 1==1 and \App\usrsysright::isUserHasRightByCode_cached($userid,'qchecks.read')
                                    or \App\usrsysright::isUserHasRightByCode($userid,'docs101.read'))
                                    <li>&nbsp;<a href="{{route('qcheck')}}" class="ml-1">СтройКонтроль</a></li>
                                @else
                                    <li>&nbsp;<span class="ml-1 disable">СтройКонтроль</span></li>
                                @endif

                                {{--todo: Изменить --}}
                                {{--								@if(1==1 and Module::collections()->has('Analitics'))--}}
                                @if (1==0 and \App\usrsysright::isUserHasRightByCode_cached($userid,'analitics.reports'))
                                    <li>&nbsp;<a href="{{route('analitics.reports')}}">Аналитика</a></li>
                                @endif
                                @if(1==0)
                                    <li>&nbsp;<a href="{{route('reports')}}" class="ml-1">Отчеты</a></li>
                                @endif
                                @if(1==1 or \App\usrsysright::isUserHasRightByCode_cached($userid, 'admin'))
                                    <li>&nbsp;<a href="{{route('admin')}}" class="ml-1">Сервис</a></li>
                                @endif
                            @endif

                        @endif
                    @endguest
                </ul>

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
                        $userInfo = Auth::user()->name;

                        $userdata = \App\User::getUserInfo($userid);
                        //dd($userdata);

                        //$userorgid = $userdata->curorgid;
                        //dd($userorgid, $userdata->curorgid);
                        if (isset($userdata->userorgs)) {
                            $userorgs = $userdata->userorgs->toArray();
                        }

                        $userInfo = "";
                        $mngrInfo = "";
                        if (is_object($userdata)) {
                            $userInfo = $userdata->userName;
                            if ($userdata->orgname != "") {
                                //$userInfo = $userInfo . '&nbsp; "' . $userdata->orgname . '"';
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
                        {{-- Notification --}}
                        @if (isset($userorgid))
                            <?php
                            $notice_cnt = \App\user_notice::where('to_userid', Auth::user()->id)
                                ->whereNull('readed_at')
                                ->count();

                            $notice_class = ($notice_cnt > 0) ? "label-warning" : "label-no_warning";
                            ?>
                            {{--						<li class="dropdown notifications-menu">--}}
                            <li class="nav-item dropdown notifications-menu" style="padding-top: 10px;
    padding-bottom: 8px;">
                                <a data-toggle="dropdown" class="dropdown-toggle" href="#" aria-expanded="false">
                                    <i class="fa fa-bell-o font-weight-bold" style="color: black"></i>
                                    <span class="label {{$notice_class}}">{{ $notice_cnt }}</span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-right"
                                    style="padding: 8px 16px 8px 16px;min-width: 36rem">
                                    <li class="header">Уведомлений для Вас: <b>{{ $notice_cnt }}</b>
                                    </li>
                                    <li>
                                        <!-- inner menu: contains the actual data -->
                                        <ul class="menu">
                                            <?php
                                            $ret_URL = Request::path();
                                            $prms = Request::fullUrl();
                                            if (strpos($prms, '?') > -1)
                                                $ret_URL .= mb_substr($prms, strpos($prms, '?'));
                                            ?>

                                            @foreach(\App\user_notice::where('to_userid',Auth::user()->id)
                                        ->whereNull('readed_at')->limit(7)
                                        ->orderBy('created_at', 'DESC')->get() as $historic)

                                                @if ($loop->index==6)
                                                    <span class="font-italic font-weight-bold">... показаны только последние 7 уведомлений! </span>
                                                @else
                                                    <li>
                                                        <i class="fa fa-user text-info"></i>
                                                        {{ucfirst(\App\User::find($historic->created_by)->short_fio??null) }}
                                                        :
                                                        <b>{{ $historic['subj'] }}</b>
                                                        <div class="ml-2 ">
                                                            {{$historic['msg']}}
                                                            @if(isset($historic['ref_url']))
                                                                <a href="{{$historic['ref_url']}}"
                                                                   class=" small mt-1">подробнее >>></a>
                                                            @endif
                                                            {{--                                                @dd((Route::current()->getName())??'/home', Route::currentRouteAction(), Route::current()->getName())--}}
                                                            <a href="{{route('user_notices.delete',['id'=>$historic->id, 'route'=>$ret_URL])}}"
                                                               class="btn btn-sm btn-light small"><i
                                                                    class="fa fa-bell-slash"
                                                                    aria-hidden="true"></i></a>
                                                        </div>

                                                        @if($historic['project_id'] != '')<em
                                                            style="display: block;"> {{strtoupper(Project::find($historic['project_id'])['libelle'])}} </em> @endif
                                                        <hr>
                                                    </li>
                                                @endif
                                            @endforeach

                                        </ul>
                                    </li>
                                    {{--								<li class="footer"><a href="#">Посмотреть все</a></li>--}}
                                </ul>
                            </li>
                        @endif

                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="nav-link dropdown-toggle"
                               href="#" role="button" style="display:inline;"
                               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                @if (auth()->user()->image)
                                    <img src="{{ asset(auth()->user()->image) }}"
                                         style="width: 40px; height: 40px; border-radius: 50%;">
                                @endif
                                {!!$userInfo!!}
                                <span class="caret"></span>
                            </a>
                            @if(1==1 and isset($userorgs) and sizeof($userorgs)>0)
                                {!! Form::select('userorgid',
                                 $userorgs,
                                 $userorgid,
                                 ['id'=>'userorgid',
                                 'class'=>'userorgs input-control',
                                 'style'=>'max-width:196px',
                                 'placeholder'=>'']) !!}
                            @endif

                            <span class="mngrinfo">{!! $mngrInfo!!}</span>

                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                                @if (\App\usrsysright::isUserHasRightByCode_cached($userid,'users.update'))
                                    <a class="dropdown-item" href="{{route('users.edit',$userid)}}">Мои данные</a>
                                @endif
                                <a class="dropdown-item" href="{{ route('profile') }}">Профиль</a>
                                <a class="dropdown-item" href="{{ route('changePassword') }}">Сменить пароль</a>

                                <a class="dropdown-item" href="{{ route('logout') }}"
                                   onclick="event.preventDefault();document.getElementById('logout-form').submit();">
                                    {{ __('Logout') }}
                                </a>

                                <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                      style="display: none;">
                                    @csrf
                                </form>
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                        </li>

                    @endguest
                </ul>
            </div>
        </div>
    </nav>

    <main class="py-4">
        @yield('content')
    </main>
</div>
<div id='div_session_write'></div>
<script type="text/javascript" defer>
    window.onload = function () {

        $("#userorgid").change(function () {
            var userorgid = $('#userorgid').val();
            //console.log(userorgid);
            $('#div_session_write').load('/setuserorg/' + userorgid);

            //Такой способ позволяет действительно перезагрузить страницу (не из кэша)
            setTimeout(function () {
                //window.location.reload(true);
                window.location.assign(window.location.href);
            }, 300);
        });
    }
</script>
</body>

<script src="{{ asset('js/HelpfulArticle.js') }}" defer></script>
@yield('page-js-files')
@yield('page-js-script')

@include('notify::messages')
@notifyJs

</html>
