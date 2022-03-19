<?php
//extends('itmtypes_layout')
?>
@extends('layouts.app')
@section('content')
    <?php
    $thisTitle = "Задачи";
    $thisSysObjCode = 'tasks';
    //dd($usrrights);
    ?>


    <div class="container">

        <?php
        $breadcrumbs = [
            'Планирование' => route('planning'),
            'Задачи' => null,
        ];
        //dd($breadcrumbs);
        ?>
        @includeIf('layouts.breadcrumbs')

        <link rel="stylesheet" href="/css/tags.css">
        <style>

            .searchby {
                background-color: #dbfbce;
                font-weight: bold;
            }

            ul.no-bullets {
                list-style-type: none; /* Remove bullets */
                padding: 0; /* Remove padding */
                margin: 0; /* Remove margins */
            }

        </style>

        <div class="row justify-content-center">
            <div class="col-md-12">
                <h3>{{$thisTitle}}</h3>
                <div class="mt-2">
                    @include('layouts.edit_msgs')
                    <form name="forIndex" id="forIndex" method="post" action="{{ route($thisSysObjCode.'.index') }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-9">
                                <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm0">
                                    <div class="container">
                                        <button class="navbar-toggler" type="button" data-toggle="collapse"
                                                data-target="#navbarSupportedContent"
                                                aria-controls="navbarSupportedContent" aria-expanded="false"
                                                aria-label="{{ __('Toggle navigation') }}">
                                            <span class="navbar-toggler-icon"></span>
                                        </button>

                                        <div class="collapse navbar-collapse" id="navbarSupportedContent">
                                            <!-- Left Side Of Navbar -->
                                            <ul class="navbar-nav mr-auto">

                                                <?php
                                                $userid = Auth::user()->id;
                                                $userorgid = Auth::user()->curorgid;

                                                //dd($userorgid);
                                                ?>

                                                @if (isset($userorgid))
                                                    @if( 1==0)
                                                        <li>&nbsp;<a href="{{route('events.calendar')}}" class="ml-1">Календарь</a>
                                                        </li>
                                                    @endif
                                                @endif

                                            </ul>

                                            <!-- Right Side Of Navbar -->
                                            <ul class="navbar-nav ml-auto">

                                            </ul>
                                        </div>
                                    </div>
                                </nav>
                            </div>
                            <div class="form-group col-md-3">
                            </div>
                        </div>

                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <td>#</td>
                                <td>
                                    <a href="{{ route('set_sort',['field' => 'e.plnbegdt','retroute'=>$thisSysObjCode.'.index']) }}"
                                       class="btn btn-sm"
                                       title="Сортировать">Когда {!! sort_mark('e.plnbegdt',$sort_params) !!}</a>
                                </td>
                                <td>
                                    Что
                                </td>
                                <td>Где</td>
                                <td style="text-align: center;">
                                    @if ($usrrights['create'])
                                        <a href="{{ route($thisSysObjCode.'.create')}}"
                                           class="btn btn-warning btn-sm d-print-none"
                                           title="Добавить запись">
                                            <i class="fa fa-plus"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>

                            <tr class="d-print-none small text-center">
                                <td/>
                                <td>
                                    <div class="input-group">
                                        {!! Form::select('s_statusid', $data->statuses, $search_params['s_statusid'] ?? '',
                                                                                 [
                                                                                 'class' => 'form-control',
                                                                                 'placeholder' => '-все-',
                                                                                 ]) !!}
                                        <input type="date" class="form-control" name="s_docdate"
                                               value="{{$search_params['s_docdate'] ?? ''}}"
                                               placeholder="Дата"/>
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="s_name"
                                               value="{{ $search_params['s_name'] ?? ''}}"
                                               placeholder="-"/>
                                        {!! Form::select('s_tag', $data->usedtags, $search_params['s_tag'] ?? '',
                                         [
                                         'class' => 'form-control',
                                         'placeholder' => '-',
                                         ]) !!}
                                    </div>

                                </td>
                                <td>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="s_place"
                                               value="{{ $search_params['s_place'] ?? ''}}"
                                               placeholder=""/>
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group-btn">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                formaction="{{ route($thisSysObjCode.'.index') }}"
                                                formmethod="post" title="Поиск">
                                            <i class="fa fa-search" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $bgcols = array(
                                '#FeFeFe', '#EfEfEf', '#FFBFBF', '#FFcccc', '#F9F5BD', '#FCFADC'
                            , '#BFF9B9', '#ccffcc', '#79D3FF', '#CCECF9', '#CC9999', '#E2C7C7'
                            , '#aaccaa', '#bbccbb');

                            $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;
                            $curOwnMark = "";
                            $curbuildobjid = -1;
                            ?>
                            @foreach($recs as $item)
                                <?php
                                $colshift = (1 - $item->active) * 2;
                                $tr_bg_col = $bgcols[$colshift + ($loop->index + $rec0) % 2];
                                $colshift = (isset($item->enddt) and $item->enddt < now()) ? 2 : 0;
                                $period_bg_col = $bgcols[$colshift + ($loop->index + $rec0) % 2];

                                $tclass = ($item->categoryid == 1) ? 'badge-success'
                                    : (($item->categoryid == 2) ? 'badge-danger' : 'badge-warning');
                                ?>
                                <tr style="background-color: {{$tr_bg_col}}">
                                    <td class="small text-right" bui>
                                        {{$loop->index + $rec0}} <a name="{{$item->id}}"></a>
                                    </td>
                                    <td class="text-left" style="background-color: {{$period_bg_col}}">
                                        <?php
                                        $begDT = date_create($item->plnbegdt);
                                        $endDT = (isset($item->plnenddt)) ? date_create($item->plnenddt) : null;
                                        if (isset($endDT))
                                            if ($begDT->format('d.m.Y') == $endDT->format('d.m.Y'))
                                                $shwEndDT = ' - <span class="small">' . $endDT->format('H:i') . '</span>';
                                            else
                                                $shwEndDT = ' - ' . $endDT->format('d.m.Y')
                                                    . '  <span class="small">' . $endDT->format('H:i') . '</span>';
                                        else
                                            $shwEndDT = '';
                                        ?>
                                        {{$begDT->format('d.m.Y')}}<span
                                            class="small ml-1">{{$begDT->format('H:i')}}</span>
                                        {!!  $shwEndDT!!}
                                    </td>
                                    <td class="text-left">
                                        <div class="font-italic"><span
                                                class="">{{$item->inituser_name}}</span>:
                                        </div>
                                        <a href="{{ route($thisSysObjCode.'.edit',$item->id)}}" style="" class="ml-2">
                                            <b>{{$item->name}}</b>
                                            @if(1==0)
                                                <div class="mt-1 ml-3 small">
                                                    {{$item->descript}}
                                                </div>
                                            @endif
                                        </a>

                                        <div class="tagcloud01 mt-2 ml-3">
                                            <ul>
                                                @foreach($item->tags as $tag)
                                                    <li><a href="?s_tag={{$tag->tag}}">{{$tag->tag}}</a></li>
                                                    </a>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        {{$data->exe_statuses[$item->statusid]??$item->statusid}}
                                        @if($item->progress>0)
                                            <div class="small">
                                                {{$item->progress}}%
                                            </div>
                                        @endif
                                    </td>
                                    <td style="text-align: center;">
                                        <a href="{{ route($thisSysObjCode.'.edit',$item->id)}}"
                                           class="btn btn-sm btn-primary no-print d-print-none"
                                           title="Просмотреть/Изменить запись">
                                            <i class="fa fa-pencil"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </form>

                    <div>
                        {{$recs->links()}}
                    </div>
                </div>
            </div>
        </div>

        <script src="{{ asset('js/contract_index.js') }}" defer></script>

    </div>

@endsection
