@extends('layouts.app')
@section('content')
    <?php
    $thisTitle = "Платежи";
    $thisSysObjCode = 'paydocs';
    //dd($usrrights);
    $userid = Auth::user()->id;
    $userorgid = Auth::user()->curorgid;
    ?>

    <link rel="stylesheet" href="/css/subnav.css">
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
    <div class="container">

        <?php
        $breadcrumbs = [
            'Данные' => "/rqsts",
            'Взаиморасчеты' => "/rqsts?tab=nsi-pays",
            $thisTitle => null,
        ];
        //dd($breadcrumbs);
        ?>
        @includeIf('layouts.breadcrumbs')

        <div class="row">
            <div class="col-md-3">
                <h3>{{$thisTitle}}</h3>
            </div>

            <div class="col-md-9">
                <div class="subnav shift">
                    <ul>
                        <li><a href="{{route('reports.rep47')}}"
                               title="Должники">Должники</a>
                        </li>
                        @if(\App\usrsysright::isUserHasRightByCode_cached($userid,'mchn_raids.read'))
                            <li><a href="{{route('mchn_raids.index')}}"
                                   title="Перевозки">Перевозки</a></li>
                        @endif
                        @if(\App\usrsysright::isUserHasRightByCode_cached($userid,'orgs.read'))
                            <li><a href="{{route('orgs.index')}}"
                                   title="Контрагенты">Клиенты</a></li>
                        @endif
                        @if(\App\usrsysright::isUserHasRightByCode_cached($userid,$thisSysObjCode.'.set_lockdate'))
                            <li><a href="{{route('sysobj_lockdates.edit',520)}}"
                                   title="Установка даты блокировки данных"><i class="fa fa-lock"
                                                                               aria-hidden="true"></i></a>
                            </li>
                        @endif
                        @if($usrrights['finopers_refresh']??false)
                            <li><a href="{{route('paydocs.rfr_all_finopers')}}"
                                   title="Пересчет фин. транзакций для всех документов">
                                    <i class="fa fa-money fa-1" aria-hidden="true"></i>
                                    <i class="fa fa-refresh fa-1"
                                                                               aria-hidden="true"></i></a>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="mt-2">
                    @include('layouts.edit_msgs')
                    <form name="forIndex" id="forIndex" method="post" action="{{ route($thisSysObjCode.'.index') }}">
                        @csrf

                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <td>#</td>
                                <td>
                                    <a href="{{ route('set_sort',['field' => 'e.plnbegdt','retroute'=>$thisSysObjCode.'.index']) }}"
                                       class="btn btn-sm"
                                       title="Сортировать">Дата {!! sort_mark('e.plnbegdt',$sort_params) !!}</a>
                                </td>
                                <td>Контрагент</td>
                                <td>ГК</td>
                                <td>Тип</td>
                                <td>Приход</td>
                                <td>Расход</td>
                                <td style="text-align: center;">
                                    @if ($usrrights['create'])
                                        @if (isset($data->template_id))
                                            <a href="{{ route($thisSysObjCode.'.create')}}"
                                               class="btn btn-warning btn-sm d-print-none"
                                               title="Добавить запись с данными из шаблона"
                                               onclick="return confirm('Добавить новую запись с данными из шаблона?')">
                                                <i class="fa fa-plus"></i>
                                            </a>
                                            <a href="{{ route('user_templates.delete', $data->template_id)}}?returl={{Request::url()}}"
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Удалить текущий шаблон?')"
                                               title="Удалить текущий шаблон"
                                            >
                                                <i class="fa fa-minus-circle" aria-hidden="true"></i>
                                            </a>
                                        @else
                                            <a href="{{ route($thisSysObjCode.'.create')}}"
                                               class="btn btn-warning btn-sm d-print-none"
                                               title="Добавить запись">
                                                <i class="fa fa-plus"></i>
                                            </a>
                                        @endif
                                    @endif

                                </td>
                            </tr>

                            <tr class="d-print-none small text-center">
                                <td/>
                                <td>
                                    <div class="input-group">
                                        {!! Form::select('s_timestatuscode', $data->timestatuses??[]
                                            , $search_params['s_timestatuscode']??'',
                                                 [
                                                 'class' => 'form-control',
                                                 'placeholder' => '-все-',
                                                 'onchange' => 'form.submit()',
                                                 'id' => 's_timestatuscode',
                                                 ]) !!}
                                        <input type="date" class="form-control c" name="s_paydate"
                                               id="s_paydate"
                                               value="{{ $search_params['s_paydate'] ?? ''}}"
                                               placeholder="-название-"
                                               STYLE="display: none;"/>
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group">
                                        {!! Form::select('s_orgid', $data->orgs??[]
                                        , $search_params['s_orgid']??'',
                                             [
                                             'class' => 'form-control',
                                             'placeholder' => '-все-',
                                             'onchange' => 'form.submit()',
                                             ]) !!}
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group">
                                        {!! Form::select('s_ownorgid', $data->ownorgs??[]
                                        , $search_params['s_ownorgid']??'',
                                             [
                                             'class' => 'form-control',
                                             'placeholder' => '-все-',
                                             'onchange' => 'form.submit()',
                                             ]) !!}
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group">
                                        {!! Form::select('s_paytypeid', $data->paytypes??[]
                                        , $search_params['s_paytypeid']??'',
                                             [
                                             'class' => 'form-control',
                                             'placeholder' => '-все-',
                                             'onchange' => 'form.submit()',
                                             ]) !!}
                                    </div>
                                </td>
                                <td colspan="2" class="text-center">
                                    <div class="input-group">
                                        {!! Form::select('s_paydir', [1=>'приход',-1=>'расход']
                                        , $search_params['s_paydir']??'',
                                             [
                                             'class' => 'form-control',
                                             'placeholder' => '-все-',
                                             'onchange' => 'form.submit()',
                                             ]) !!}
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
                            $cur_paydate = -1;
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

                                @if($item->paydate<>$cur_paydate)
                                    <tr style="background-color: #e7f4f8">
                                        <td colspan="8" class="font-weight-bold text-left">
                                            {{date_create($item->paydate)->format('d.m.Y')}}
                                        </td>
                                    </tr>
                                    <?php
                                    $cur_paydate = $item->paydate;
                                    ?>
                                @endif
                                <tr style="background-color: {{$tr_bg_col}}">
                                    <td class="small text-right" bui>
                                        {{$loop->index + $rec0}} <a name="{{$item->id}}"></a>
                                    </td>
                                    <td class="text-left" style="background-color: {{$period_bg_col}}">

                                    </td>
                                    <td class="text-left">
                                        <a href="{{ route($thisSysObjCode.'.edit',$item->id)}}" style="">
                                            <b>{{$item->orgname}}</b>
                                        </a>
                                        <div class="mt-1 ml-3">
                                            {{$item->reason}}
                                        </div>
                                    </td>
                                    <td class="text-left">
                                        <a href="{{ route($thisSysObjCode.'.edit',$item->id)}}" style="">
                                            <b>{{$item->ownorgname}}</b>
                                        </a>
                                    </td>
                                    <td class="text-center">{{$item->paytype_name}}</td>
                                    @if($item->paydir>0)
                                        <td class="text-right text-success font-weight-bold">{{number_format($item->paysum,2)}}</td>
                                        <td></td>
                                    @else
                                        <td></td>
                                        <td class="text-right text-danger font-weight-bold">{{number_format($item->paysum,2)}}</td>
                                    @endif
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

        <script src="{{ asset('js/paydocs_index.js') }}" defer></script>

    </div>

@endsection
