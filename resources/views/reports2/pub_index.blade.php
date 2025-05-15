@extends('layouts.app')

@section('content')

    <?php
    $thisTitle = "Отчеты по данным ИС предприятия";
    $thisSysObjCode = 'reports';
    ?>

    <link rel="stylesheet" href="/css/tags.css">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: sans-serif;
        }

        h1 {
            font-size: 24px;
        }

        /* ---- button ---- */

        .button {
            display: inline-block;
            padding: 0.5em 1.0em;
            background: #EEE;
            border: none;
            border-radius: 7px;
            background-image: linear-gradient(to bottom, hsla(0, 0%, 0%, 0), hsla(0, 0%, 0%, 0.2));
            color: #222;
            font-family: sans-serif;
            font-size: 16px;
            text-shadow: 0 1px white;
            cursor: pointer;
        }

        .button:hover {
            background-color: #8CF;
            text-shadow: 0 1px hsla(0, 0%, 100%, 0.5);
            color: #222;
        }

        .button:active,
        .button.is-checked {
            background-color: #28F;
        }

        .button.is-checked {
            color: white;
            text-shadow: 0 -1px hsla(0, 0%, 0%, 0.8);
        }

        .button:active {
            box-shadow: inset 0 1px 10px hsla(0, 0%, 0%, 0.8);
        }

        /* ---- button-group ---- */

        .button-group {
            margin-bottom: 20px;
        }

        .button-group:after {
            content: '';
            display: block;
            clear: both;
        }

        .button-group .button {
            float: left;
            border-radius: 0;
            margin-left: 0;
            margin-right: 1px;
        }

        .button-group .button:first-child {
            border-radius: 0.5em 0 0 0.5em;
        }

        .button-group .button:last-child {
            border-radius: 0 0.5em 0.5em 0;
        }

        /* ---- isotope ---- */

        .grid {
            border: 1px solid #e0e0e0;
        }

        /* clear fix */
        .grid:after {
            content: '';
            display: block;
            clear: both;
        }

        /* ---- .element-item ---- */

        .element-item {
            position: relative;
            float: left;
            /*width: 267px;*/
            /*width: 359px;*/
            width: 512px;
            height: 160px;
            margin: 20px;
            padding: 10px;
            background: #888;
            color: #262524;
            /*border: 1px solid silver;*/
            box-shadow: rgb(149 157 165/20%) 0px 4px 4px;
        }

        .element-item > * {
            margin: 0;
            padding: 0;
        }

        .element-item .name {
            position: absolute;

            left: 10px;
            top: 10px;
            text-transform: none;
            letter-spacing: 0;
            font-size: 15px;
            font-weight: bold;
        }

        .element-item .name .descript {
            font-style: italic;
        }

        .element-item .tags {
            position: absolute;
            left: 10px;
            top: 100px;
        }

        .element-item .stat {
            position: absolute;
            right: 10px;
            bottom: 5px;
            font-size: 11px;
            color: gray;
        }

        .element-item .symbol {
            position: absolute;
            left: 10px;
            top: 0px;
            font-size: 42px;
            font-weight: bold;
            color: white;
        }

        .element-item .number {
            position: absolute;
            right: 8px;
            top: 5px;
        }

        .element-item .weight {
            position: absolute;
            left: 10px;
            top: 76px;
            font-size: 12px;
        }

        .element-item.rep {
            background: #ffffff;
            background: hsl(0, 0%, 100%);
        }

        .element-item.alkali {
            background: #F00;
            background: hsl(0, 100%, 50%);
        }

        .element-item.alkaline-earth {
            background: #F80;
            background: hsl(36, 100%, 50%);
        }

        .element-item.lanthanoid {
            background: #FF0;
            background: hsl(72, 100%, 50%);
        }

        .element-item.actinoid {
            background: #0F0;
            background: hsl(108, 100%, 50%);
        }

        .element-item.transition {
            background: #0F8;
            background: hsl(144, 100%, 50%);
        }

        .element-item.post-transition {
            background: #0FF;
            background: hsl(180, 100%, 50%);
        }

        .element-item.metalloid {
            background: #08F;
            background: hsl(216, 100%, 50%);
        }

        .element-item.diatomic {
            background: #00F;
            background: hsl(252, 100%, 50%);
        }

        .element-item.halogen {
            background: #F0F;
            background: hsl(288, 100%, 50%);
        }

        .element-item.noble-gas {
            background: #F08;
            background: hsl(324, 100%, 50%);
        }

    </style>
    <form name="forIndex" id="forIndex" method="post"
          action="{{ route($thisSysObjCode.'.pub_index') }}">
        @csrf

        <div class="container">

            <?php
            $breadcrumbs = [
                'Сервис' => "/admin?tab=nsi-dic#reports",
                $thisTitle => null,
            ];
            ?>
            @includeIf('layouts.breadcrumbs')

            @include('layouts.edit_msgs')

            <div class="row justify-content-center">
                <div class="col-md-12 col-sm-12 ">
                    <h1>{{$thisTitle}}</h1>

                    @if(1==1)
                        {{--формат с традиционным списком (таблица)--}}

                        <div class="tagcloud04 mt-2 ml-3">
                            <ul>
                                <?php
                                $s_tag = $search_params['s_tag'] ?? '*';
                                ?>
                                @foreach($data->usedtags as $key=>$tag)
                                    <?php
                                    $tclass = ($tag == $s_tag) ? 'bg-warning' : '';
                                    ?>
                                    <li>
                                        <a href="?s_tag={{rawurlencode($tag)}}" class="{{$tclass}}"
                                           title="{{$tag}}">{{$tag}}</a>
                                    </li>
                                @endforeach
                                <li>
                                    <a href="?s_tag=*" class="bg-success"
                                       title="Показать всё">всё</a>
                                </li>

                            </ul>
                        </div>

                        <?php
                        //var_dump($sort_params);
                        $statuses = [0 => 'черновик', 1 => 'доступен'];
                        $status_css = [0 => 'background-color:#f9cbcb !important;', 1 => ''];
                        ?>

                        <table class="table table-striped table-condensed">
                            <thead>
                            @php
                                $sort_params = session('sort_params');
                                if (isset($sort_params)) {
                                    $sort_by = $sort_params['field'];
                                    $sort_dir = $sort_params['dir'];
                                }
                            @endphp
                            <tr>
                                <td>Название, описание, теги</td>
                                <td>Статистика использования</td>
                            </tr>

                            <tr style="text-align: center;">
                                <td>
                                    <div class="input-group">
                                        <input type="text" class="form-control text-center"
                                               name="s_name"
                                               value="{{$search_params['s_name'] ?? ''}}"
                                               placeholder=""
                                        />
                                        <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                formaction="{{ route($thisSysObjCode.'.pub_index') }}"
                                                formmethod="post" title="Поиск">
                                            <i class="fa fa-search" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </td>
                                <td>
                                </td>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $curOwnOrgID = -1;
                            ?>
                            @if (count($recs)>0)
                                @foreach($recs as $rec)
                                    <?php
                                    $stage_css = ""
                                    ?>
                                    <tr style="{{$rec->OrdStyle}}">
                                        <td scope="row" class="text-left">
                                            <a href="{{route($thisSysObjCode.'.rep'.$rec->id)}}" target="_blank">
                                                <b>{{$rec->name}}</b>
                                            </a>
                                            <div class="ml-3 mt-1 font-italic small">
                                                {{$rec->descript}}
                                                @if($usrrights['edit_report']??false)
                                                <a href="{{route('reports.edit',$rec->id)}}" target="_blank"><i
                                                        class="fa fa-external-link text-info"
                                                        aria-hidden="true"></i></a>
                                                @endif
                                            </div>

                                            <div class="tagcloud01 mt-0 ml-3 tags float-right">
                                                <ul>
                                                    @foreach($rec->tags as $tag)
                                                        <li>
                                                            <a href="?s_tag={{rawurlencode($tag->tag)}}"
                                                               title="{{$tag->tag}}">{{$tag->tag}}</a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class=""> {{$rec->use_cnt}}</div>
                                            @if(isset($rec->lastuse_dt))
                                                <div
                                                    class="small"> {{date_create($rec->lastuse_dt)->format('d.m.Y H:i')}}
                                                    <br>{{$rec->lastuse_username}}</div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <?php
                                $msg = "Данные не найдены. \nПопробуйте изменить критерий поиска.";
                                ?>
                                <tr>
                                    <td colspan="6" class="c">{{$msg}}</td>
                                </tr>
                            @endif
                            </tbody>
                        </table>

                    @else
                        {{--формат с плашками--}}
                        <div class="list-group list-group-flush">

                            @if(isset($recs))
                                <div class="tagcloud04 mt-2 ml-3">
                                    <ul>
                                        <?php
                                        $s_tag = $search_params['s_tag'] ?? '*';
                                        ?>
                                        @foreach($data->usedtags as $key=>$tag)
                                            <?php
                                            $tclass = ($tag == $s_tag) ? 'bg-warning' : '';
                                            ?>
                                            <li>
                                                <a href="?s_tag={{rawurlencode($tag)}}" class="{{$tclass}}"
                                                   title="{{$tag}}">{{$tag}}</a>
                                            </li>
                                        @endforeach
                                        <li>
                                            <a href="?s_tag=*" class="bg-success"
                                               title="Показать всё">всё</a>
                                        </li>

                                    </ul>
                                </div>
                                <div class="grid">
                                    @foreach($recs as $rep)
                                        <div class="element-item rep metal inner-transition " data-category="rep">
                                            <div class="name">
                                                <a href="{{route('reports.rep'.$rep->id)}}"
                                                   class="list-group-item-action font-weight-bold">
                                                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                                                    {{$rep->name??'-'}}
                                                </a>
                                                <div class="descript small ml-3">{{$rep->descript}}
                                                    <a href="{{route('reports.edit',$rep->id)}}" target="_blank"><i
                                                            class="fa fa-external-link text-info"
                                                            aria-hidden="true"></i></a>
                                                </div>

                                            </div>

                                            {{--                                                                                <p class="symbol">Pu</p>--}}
                                            <div class="tagcloud01 mt-2 ml-3 tags">
                                                <ul>
                                                    @foreach($rep->tags as $tag)
                                                        <li>
                                                            <a href="?s_tag={{rawurlencode($tag->tag)}}"
                                                               title="{{$tag->tag}}">{{$tag->tag}}</a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                            <div class="stat">
                                                <i class="fa fa-eye" aria-hidden="true"></i>: {{$rep->use_cnt}}
                                                <i class="fa fa-user" aria-hidden="true"></i>
                                                {{$rep->lastuse_dt}} - {{$rep->lastuse_username}}
                                            </div>
                                        </div>

                                    @endforeach
                                </div>
                            @endif

                        </div>
                    @endif
                </div>
            </div>
        </div>
    </form>
@endsection

