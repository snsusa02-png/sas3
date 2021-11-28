@extends('layouts.app')

@section('content')
    <?php

    //$userid = (\Auth::user()->active == 1) ? \Auth::user()->id : null;
    $userid = \Auth::user()->id ?? null;
    $userorgid = (\Auth::user()->active ?? 0 == 1) ? \Auth::user()->curorgid : null;

    ?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.13.0/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!--     Fonts and icons     -->
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700,200" rel="stylesheet"/>
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/latest/css/font-awesome.min.css" rel="stylesheet">
    <link href='https://fonts.googleapis.com/css?family=Muli:400,300' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v0.0.3/css/unicons.css">
    <link rel="stylesheet" href="/css/dashboard_test.css">
    <style>
        main {
            min-height: 94vh;
            background-color: rgb(244, 243, 239);
            {{--background: url({{env("WELCOME_BG_URI","/images/bgs/sl1.jpg")}}) center;--}}
  background: url({{env("WELCOME_BG_URI","/images/bgs/bg1.jpg")}}) no-repeat center center fixed;
            -webkit-background-size: cover;
            -moz-background-size: cover;
            -o-background-size: cover;
            background-size: cover;
        }

        .pad0 > tbody > tr > td {
            padding: 0px !important;
        }

        .app-title {
            margin: 20vh auto;
            padding: 2em;
        }
    </style>

    <div class="container">

        <span class="helptags" data="system_about"/>
        {{--		Dashboard begin		--}}

        @if(!isset($userorgid))

            <div class="app-title text-center" style="background-color: rgba(244, 243, 239,0.7);">
                <h2>Информационная система<br>ГК "САС ДВ"</h2>

                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link font-weight-bold" href="{{ route('login') }}">{{ __('Login') }}</a>
                    </li>
                    @if (Route::has('register'))
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                        </li>
                    @endif
                </ul>

            </div>
        @else
            {{--            Информация для зарегистрированных пользователей--}}

            {{--			@if (1==1 and isset($data->birthdays) and count($data->birthdays)>0)--}}
            @if (1==1)
                <style>
                    .news-list li {
                        list-style-type: none;
                    }
                </style>
                <div class="row">
                    @includeif('informers._adverts')
                    @includeif('informers._birthdays')
                </div>
            @endif


            @if (1==1 and isset($data->news) and count($data->news)>0)
                <style>
                    .news-list li {
                        list-style-type: none;
                    }

                    .news-list111 li:after {
                        content: "*"; /* Добавляем после текста абзаца */
                        color: red; /* Красный цвет текста */
                        font-style: italic;
                        border-bottom: 1px solid silver;
                    }
                </style>
                <div class="row">
                    <div class="offset-md-1 col-md-10 col-sm-12">
                        <div class="card">
                            <div class="card-header" style="background-color: #f9cc13">Новости ГК БАСКО</div>
                            <div class="card-body ">
                                <?php
                                $cur_bandid = -1;
                                ?>

                                @foreach($data->news as $itm)

                                @if($itm->bandid<>$cur_bandid)
                                @if($cur_bandid<>-1)
                                </ul>
                                @endif
                                <div class="font-weight-bold font-italic"><h3>{{$itm->bandname}}</h3></div>
                                <ul class="news-list">
                                    <?php
                                    $cur_bandid = $itm->bandid;
                                    ?>
                                    @endif
                                    <li>
                                        <div
                                            class="small mt-1">{{date_create($itm->updated_at)->format('d.m.Y H:i')}}</div>
                                        <div class="font-weight-bold"><h4>{{$itm->title}}</h4></div>

                                        @if(isset($itm->content))
                                            <div
                                                class="card-text ml-3 mt-2">{!!  \Illuminate\Support\Str::limit(strip_tags($itm->content), 120, '...') !!}
                                                <a href="/news/{{$itm->id}}" class=" ml-3">Подробнее →</a>
                                            </div>
                                        @endif
                                        <hr style="color: darkred">
                                    </li>
                                    @endforeach
                                </ul>

                            </div>
                            {{--
                            <div class="card-footer ">
                                 <hr>
                                 <div class="stats">
                                     <i class="fa fa-refresh">на сайте</i>
                                 </div>
                             </div>
                             --}}
                        </div>
                    </div>
                </div>
            @endif

        @endif




        {{--
        Dashboard end
        --}}
        {{--@endif--}}
    </div>
@endsection
