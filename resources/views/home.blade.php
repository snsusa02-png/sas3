@extends('layouts.app')

@section('content')
    <?php
    $userorgid = \Auth::user()->curorgid;
    //$userid = (\Auth::user()->active == 1) ? \Auth::user()->id : null;
    $userid = \Auth::user()->id ?? null;
    $userorgid = (\Auth::user()->active ?? 0 == 1) ? \Auth::user()->curorgid : null;
    ?>
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v0.0.3/css/unicons.css">
    {{--    <link rel="stylesheet" href="/css/dashboard_test.css">--}}



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

        P {
            text-indent: 1.5em; /* Отступ первой строки */
            text-align: justify; /* Выравнивание по ширине */
        }

        .card-category {
            font-size: 18px;
            font-weight: bold;
        }

        .text-info {
            font-size: 16px;
            font-weight: bold;
        }

    </style>
    <link rel="stylesheet" href="/js/news-ticker-controls-acme/assets/css/style.css"/>
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>

    {{-- для charts --}}
    <script src="/js/ajax/libs/moment.js/2.27.0/moment.min.js"></script>
    <script src="/js/ajax/libs/moment.js/2.27.0/locale/ru.min.js"></script>

    @if(isset($userorgid))
        <script src="/js/smooth-eocjs-news-ticker/eocjs-newsticker.js" defer></script>

        <link rel="stylesheet" href="/js/smooth-eocjs-news-ticker/eocjs-newsticker.css"/>

        <script defer>

            if (1 == 0) {
                document.addEventListener('DOMContentLoaded', function () {

                    $("#news-ticker").eocjsNewsticker({
                        type: 'ajax',	// 'static' or 'ajax'
                        //source: '/js/smooth-eocjs-news-ticker/data.json',
                        source: '/news_feed',
                        dataType: 'json',// or 'jsonp'
                        // used for jsonp
                        //callback: 'callback',
                        // polling interval of the ajax source (seconds)
                        interval: 600,

                        // animation speed
                        speed: 20,

                        // time to wait before starting
                        timeout: 1,

                        // divider between news
                        divider: '&nbsp;&nbsp;&nbsp; . . . &nbsp;&nbsp;&nbsp;',

                    });

                    $("#msgs-ticker").eocjsNewsticker({
                        type: 'ajax',	// 'static' or 'ajax'
                        source: '/msgs_feed',
                        dataType: 'json',// or 'jsonp'
                        // used for jsonp
                        //callback: 'callback',
                        // polling interval of the ajax source (seconds)
                        interval: 120,

                        // animation speed
                        speed: 16,

                        // time to wait before starting
                        timeout: 1,

                        // divider between news
                        divider: '&nbsp;&nbsp;&nbsp; . . . &nbsp;&nbsp;&nbsp;',

                    });
                });
            }
        </script>
    @endif


    {{--    <div class="container" style="background-color: rgba(244, 243, 239,0.7);">--}}
    <div class="container">
        <span class="helptags" data="system_about"/>

        {{--		Dashboard begin		--}}

        @if(!isset($userorgid))
            <div class="p-5" style="background-color: rgba(255,255,255,0.8)">
                <h3>Вы подали заявку на регистрацию в Информационной Системе ГК "{{env('APP_NAME','XYZ')}}"</h3>
                <p>Доступ к системе будет предоставлен после подтверждения администратором.</p>
            </div>
        @endif

        @includeIf('layouts.edit_msgs')

        @if(1==0 and isset($userorgid))
            <div class="acme-news-ticker mb-3" style="border-color: silver">
                <div class="acme-news-ticker-label">&nbsp;&nbsp;&nbsp;<a
                        href="{{route('news.public_index')}}">Новости</a>&nbsp;&nbsp;&nbsp;
                </div>
                <div class="acme-news-ticker-box">
                    <div id="news-ticker">
                    </div>
                </div>
                {{--			<div class="acme-news-ticker-controls acme-news-ticker-horizontal-controls">--}}
                {{--				<span class="acme-news-ticker-arrow acme-news-ticker-prev"></span>--}}
                {{--				<span class="acme-news-ticker-pause"></span>--}}
                {{--				<span class="acme-news-ticker-arrow acme-news-ticker-next"></span>--}}
                {{--			</div>--}}
            </div>

            <div class="acme-news-ticker mb-3" style="border-color: silver;">
                <div class="acme-news-ticker-label" style="background-color: #eed428;">Сообщения</div>
                <div class="acme-news-ticker-box">
                    <div id="msgs-ticker">
                    </div>
                </div>
            </div>


            <div class="row">
                @includeif('informers._birthdays')
                @includeif('informers._adverts')
            </div>

            <div class="row justify-content-center">
                @includeif("informers._last_viewpoints_photos")
            </div>

        @endif


        <div class="row justify-content-center">

            @if(isset($userorgid))

                @includeif("informers._long_wait_bills")
                @includeif("informers._nofile_invoices")
                @include("informers._users_today")
                @include("informers._calendar_raids")
                @includeif("informers._ownorg_saldos")
                @includeif("informers._ownorg_saldo_details")
                @include("informers._opertypes_sums")

            @endif

            @if( 1==0 and $usrrights['mchnrqsts.approve']??false)

                <div class="col-lg-3 col-md-6 col-sm-6">
                    <div class="card card-stats mb-1">
                        <div class="card-body "
                             style="cursor: pointer;"
                             onclick="javascript:document.location='{{ route('mchnrqsts.index') }}';">
                            <div class="row">
                                <div class="col-5 col-md-4">
                                    <div class="icon-big text-center icon-warning">
                                        <i class='uil uil-truck text-primary'></i>
                                    </div>
                                </div>
                                <div class="col-7 col-md-8">
                                    <div class="numbers">
                                        <p class="card-category">Заявки на согласование</p>
                                        <p class="card-title"
                                           id="cntOrgActiveOrders">{{UserAct::cntActiveMchnRqsts()}} </p>
                                    </div>
                                    <script>
                                        setInterval(function () {
                                            $('#cntOrgActiveOrders').load('/cnt/cntActiveMchnRqsts');
                                        }, 120000) /* time in milliseconds (ie 2 seconds)*/
                                    </script>
                                </div>
                            </div>
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
            @endif

            @if($usrrights['admin-global']??false)

                @php($cnt=null)
                @php($cnt=UserAct::cntNewUsers())
                @if(1==0 and !is_Null($cnt))
                    <div class="col-lg-3 col-md-6 col-sm-6">
                        <div class="card card-stats mt-3">
                            <div class="card-body "
                                 style="cursor: pointer;"
                                 onclick="javascript:document.location='{{ route('users.index') }}';">
                                <div class="row">
                                    <div class="col-5 col-md-4">
                                        <div class="icon-big text-center icon-warning" style="font-size: 32px">
                                            <i class='uil uil-user text-warning'></i>
                                        </div>
                                    </div>
                                    <div class="col-7 col-md-8">
                                        <div class="numbers">
                                            <p class="card-category">Новые пользователи</p>
                                            <p class="card-title"
                                               style="font-size: 24px">{{UserAct::cntNewUsers()}} </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif



            @if(isset($userorgid))

                {{--                @includeif("informers._eri_ri_stat")--}}
                @includeif("informers._contract_statistics")
                @includeif("informers._document_statistics")

                @includeif("informers._orgacntsums")

                @includeif("informers._suporg_est_diff_sums")
                @includeif("informers._orgextsrvcsums")

                @includeif("informers._staff_wo_di")
                @includeif("informers._cwp_estimates")
                @includeif("informers._popular_reports")
                @includeif("informers._new_contracts")
                @includeif("informers._new_docs")
                @includeif("informers._new_upds")

                {{--                @includeif("informers._smet_ord_stat")--}}

                @includeif("informers._newmsgs")

                {{--@includeif("informers._birthdays")--}}
                @includeif("informers._birthdays_now")

                @includeif("informers._meetings")
                @includeif("informers._equiprqsts")
                @includeif("informers._plnpays4approve")
                @includeif("informers._plnpays4regpay")

                {{--                @includeif("informers._lateworks")--}}

                @includeif("charts.pie2")

                @includeif("charts.pie_er_stat")

                @if(1==0)
                    <div class="col-md-6 mt-3">
                        <div class="card card-stats" style="border-radius: 8px;">
                            <div class="card-body">
                                @includeif("charts.day30qchecks")
                            </div>
                        </div>
                    </div>
                @endif

                @if(isset($data) and isset($data->newqchkphotos) and count($data->newqchkphotos)>0)
                    <style>
                        .carousel-indicators li {
                            background-color: darkorange;
                        }

                        .carousel-inner {
                            border-radius: 1em;
                        }
                    </style>


                    <div class="col-lg-6 col-md-6 col-sm-6 ">
                        <div class="card mt-3">
                            <div class="card-header">

                                <h5 style="margin: 6px 0 0 0;">Фото-факт</h5>
                            </div>

                            <div class="card-body">
                                <div id="carouselNewItemsIndicators"
                                     class="carousel slideinfo"
                                     data-ride="carousel"
                                     style="
								 /*background-color:lightgoldenrodyellow;*/
                                 margin-bottom: 0.3em;
                                 border-radius: 0.6em;
								 border: 1px solid #eaeaea;"
                                >
                                    <ol class="carousel-indicators ">
                                        @foreach($data->newqchkphotos as $itm)
                                            <li data-target="#carouselNewItemsIndicators"
                                                data-slide-to="{{$loop->iteration-1}}"></li>
                                        @endforeach
                                    </ol>
                                    <div class="carousel-inner" style="box-shadow: none;">
                                        @php ($active = "active")
                                        @foreach($data->newqchkphotos as $itm)
                                            <div class="carousel-item p-3 {{$active}}">
                                                <div class="row">
                                                    <div class="col-md-5">
                                                        @if ($itm->photourl != "")
                                                            <a href="{{route("qcheck_items.edit",$itm->id)}}">
                                                                <img class="d-block w-33 h-33"
                                                                     src="/storage/{{$itm->photourl}}"
                                                                     style="max-height:160px; width:100%"></a>
                                                        @endif
                                                    </div>
                                                    <div class="col-md-7">
                                                        <div style="">
                                                            {{$itm->chkdate}}
                                                            <b>{{$itm->category??''}}</b>
                                                            <div class="float-right"> {{$itm->buildobjname}}</div>
                                                            <div class="font-italic"> {{$itm->itmtypename}}:</div>
                                                        </div>
                                                        <div class="small">{{$itm->chkreport}}</div>
                                                    </div>
                                                </div>
                                                <br><br>
                                            </div>
                                            @php ($active = "")
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                @endif

                {{--                @includeif("informers._day30acntrest")--}}


            @endif
        </div>
    </div>
@endsection
