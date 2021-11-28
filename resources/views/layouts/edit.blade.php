<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title','Данные') / {{config('app.name')}}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{--    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.27.0/moment.min.js"></script>--}}
    <script src="/js/ajax/libs/moment.js/2.27.0/moment.min.js"></script>
    {{--    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.27.0/locale/ru.min.js"></script>--}}
    <script src="/js/ajax/libs/moment.js/2.27.0/locale/ru.min.js"></script>

    <script src="{{ asset('js/app.js') }}" defer></script>

    <!-- Icons -->
    <link rel="stylesheet" href="/css/font-awesome-4.7.0/css/font-awesome.css">
    <link href="/favicon.ico" rel="shortcut icon" type="image/vnd.microsoft.icon"/>

    @notifyCss
    <link href="{{ asset('css/app.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('css/aux_styles.css') }}" rel="stylesheet" type="text/css"/>

    {{-- 	<script src="{{ asset('js/app.js') }}" defer></script>--}}

    <style>
        body {
            background-color: dimgray;
        }

        .modal-body {
            max-height: calc(100vh - 210px);
            overflow-y: auto;
        }
    </style>

    @yield('page-style-files')

</head>
<body>
    <button onclick="getArticleText()" class="btn-success btn-sm"
        style="position:fixed;left:6px;bottom:32px;display: none;" id="btnGetHelp"><i class="fa fa-question-circle" aria-hidden="true"></i>
    </button>
<div class="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="background-color: #fffded;">
            <div class="modal-header font-weight-bold" id="ArticleTitle">
                Полезные материалы
            </div>
            <div class="modal-body" id="ArticleText"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm"
                        data-dismiss="modal"
                        onclick="$('.modal').hide()"
                >Закрыть
                </button>
            </div>
        </div>
    </div>
</div>

@yield('content')

@stack('scripts')



@includeIf('obj_msgs._side_msgs')

</body>

{{--<script src="{{ asset('js/app.js') }}" type="text/js"></script>--}}
<script src="{{ asset('js/checkUnsave.js') }}" defer></script>

@include('notify::messages')
@notifyJs

<script src="{{ asset('js/HelpfulArticle.js') }}" defer></script>

@yield('page-js-files')
@yield('page-js-script')

</html>
