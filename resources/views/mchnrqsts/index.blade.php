@extends('layouts.app')
@section('content')
    <?php
    $thisTitle = "Заявки на спецтехнику";
    $thisSysObjCode = 'mchnrqsts';
    ?>

    <form name="forIndex" id="forIndex" method="post" action="{{ route($thisSysObjCode.'.index') }}">
        @csrf
        <div class="container">

            <?php
            $breadcrumbs = [
                'Заявки' => "/rqsts?tab=nsi-rqsts",
                $thisTitle => null,
            ];
            //dd($breadcrumbs);
            ?>
            @includeIf('layouts.breadcrumbs')


            <div class="row justify-content-center">
                <div class="col-md-12">
                    <h3>{{$thisTitle}}</h3>
                    @if ($usrrights['create'])
                        <a href="{{ route('mchnrqsts.create')}}"
                           class="btn btn-warning btn-sm float-right mb-1"
                           title="Добавить заявку">
                            <i class="fa fa-plus"></i> Создать заявку
                        </a>
                    @endif


                    <div class="mt-2">

                        @include('layouts.edit_msgs')

                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <td style="width:64px;">
                                    <a href="{{ route('set_sort',['field' => 'mr.id','retroute'=>'mchnrqsts.index']) }}"
                                       class="btn btn-sm"
                                       title="Сортировать">заявка {!! sort_mark('mr.id',$sort_params) !!}</a>
                                </td>
                                <td>
                                    <a href="{{ route('set_sort',['field' => 'm.name','retroute'=>'mchnrqsts.index']) }}"
                                       class="btn btn-sm"
                                       title="Сортировать">Техника {!! sort_mark('m.name',$sort_params) !!}</a>
                                </td>

                                <td>
                                    <a href="{{ route('set_sort',['field' => 'mr.tgt_addr','retroute'=>'mchnrqsts.index']) }}"
                                       class="btn btn-sm"
                                       title="Сортировать">Тип заявки, объект, описание
                                        работ {!! sort_mark('mr.tgt_addr',$sort_params) !!}</a>
                                </td>
                                <td>
                                    <a href="{{ route('set_sort',['field' => 'mr.plnbegdt','retroute'=>'mchnrqsts.index']) }}"
                                       class="btn btn-sm"
                                       title="Сортировать">Когда
                                        требуется {!! sort_mark('mr.plnbegdt',$sort_params) !!}</a>
                                </td>

                                <td>
                                    <a href="{{ route('set_sort',['field' => 'o.name','retroute'=>'mchnrqsts.index']) }}"
                                       class="btn btn-sm"
                                       title="Сортировать">Заказчик,договор,
                                        инициатор {!! sort_mark('o.name',$sort_params) !!}</a>
                                </td>
                                <td>
                                    <a href="{{ route('set_sort',['field' => 'mr.statusid','retroute'=>'mchnrqsts.index']) }}"
                                       class="btn btn-sm"
                                       title="Сортировать">Статус {!! sort_mark('mr.statusid',$sort_params) !!}</a>
                                </td>

                                <td style="text-align: center;">
                                </td>
                            </tr>

                            <tr style="text-align: center;">
                                <td>
                                    <div class="input-group ">
                                        <input type="text" class="form-control small" name="s_rqstnum"
                                               value="{{ $search_params['s_rqstnum'] ?? ''}}"
                                               placeholder=""/>
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group ">
                                        <input type="text" class="form-control c" name="s_carname"
                                               value="{{ $search_params['s_carname'] ?? ''}}"
                                               placeholder="-название-"/>
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group ">
                                        {!! Form::select('s_rqsttypeid', $used_rqsttypes, $search_params['s_rqsttypeid']??'',
                                                        [
                                                        'class' => 'form-control small',
                                                        'placeholder' => '-все-',
                                                        'onChange' => 'this.form.submit()',
                                                        ])
                                                        !!}
                                        <input type="text" class="form-control c" name="s_name"
                                               value="{{ $search_params['s_name'] ?? ''}}"
                                               placeholder="-название-"/>
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group">
                                        {!! Form::select('s_timestatuscode', $timestatuses, $search_params['s_timestatuscode']??'',
                        [
                        'class' => 'form-control small',
                        'placeholder' => '-все-',
                        'id' => 's_timestatuscode',
                        ])
                        !!}
                                    </div>
                                    <input type="date" class="form-control c" name="s_plndate"
                                           id="s_plndate"
                                           value="{{ $search_params['s_plndate'] ?? ''}}"
                                           placeholder="-название-"
                                           STYLE="display: none;"/>
                                </td>
                                <td>
                                    <div class="input-group">
                                        {!! Form::select('s_orgid', $orgsinrqsts, $search_params['s_orgid']??'',
                                                [
                                                'class' => 'form-control small',
                                                'placeholder' => '-все-',
                                                'onChange' => 'this.form.submit()',
                                                ])
                                                !!}
                                        {!! Form::select('s_inituserid', $initusers, $search_params['s_inituserid']??'',
                                                [
                                                'class' => 'form-control small',
                                                'placeholder' => '-все-',
                                                'onChange' => 'this.form.submit()',
                                                ])
                                                !!}
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group">
                                        {!! Form::select('s_statuscode', $statuses, $search_params['s_statuscode']??'',
                                                        [
                                                        'class' => 'form-control small',
                                                        'placeholder' => '-все-',
                                                         'onChange' => 'this.form.submit()',
                                                        ])
                                                        !!}
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group-btn">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                formaction="{{ route('mchnrqsts.index') }}"
                                                formmethod="post" title="Поиск">
                                            <i class="fa fa-search" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            </thead>
                            @if(count($recs)>0)

                                <tbody>
                                <?php
                                $bgcols = array(
                                    '#FeFeFe', '#EfEfEf', '#FFBFBF', '#FFcccc', '#F9F5BD', '#FCFADC'
                                , '#BFF9B9', '#ccffcc', '#79D3FF', '#CCECF9', '#CC9999', '#E2C7C7'
                                , '#aaccaa', '#bbccbb');

                                $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;
                                $curOwnMark = "";
                                ?>

                                @foreach($recs as $item)
                                    <?php
                                    $colshift = (1 - $item->active) * 2;
                                    $colshift = 0;
                                    $tr_bg_col = $bgcols[$colshift + ($loop->index + $rec0) % 2];

                                    $placeinfo = $item->tgt_addr;
                                    if (isset($item->src_addr))
                                        $placeinfo = $item->src_addr . ' -> ' . $placeinfo;
                                    ?>
                                    <tr style="background-color: {{$tr_bg_col}}">
                                        <td class="text-center">
                                        <!-- {{$loop->index + $rec0}} -->
                                            № {{$item->id}}
                                            <div
                                                class="small">{{date_format(date_create($item->init_at),'d.m.Y')}}</div>
                                        </td>
                                        <td class="small text-left">
                                            {{$item->asgnmachinename??$item->mchntypename??'-не назначено-'}}
                                            <div class="small">
                                                {{$item->regnum}}
                                            </div>
                                        </td>
                                        <td>
                                            <a href="{{route('mchnrqsts.edit',$item->id)}}">
                                                <div style="font-variant: small-caps">{{$item->rqsttypename}}</div>
                                                <b>{{$placeinfo}}</b>
                                                <div class="small">
                                                    {{$item->descript}}
                                                </div>
                                            </a>
                                        </td>
                                        <td class="small text-center" nowrap>
                                            <?php
                                            $begdt = strtotime($item->plnbegdt);
                                            $enddt = strtotime($item->plnenddt);
                                            if (date("d.m.Y", $enddt) == date("d.m.Y", $begdt))
                                                $plnperiod = date("d.m.Y H:i", $begdt) . ' - ' . date("H:i", $enddt);
                                            else
                                                $plnperiod = date("d.m.Y H:i", $begdt) . ' - ' . date("d.m.Y H:i", $enddt);
                                            ?>
                                            {{$plnperiod}}
                                            <div class="small">продолжительность: <b>{{substr($item->duration,0,5)}}</b>
                                            </div>
                                        </td>
                                        <td class="text-left">
                                            {{$item->orgname}}
                                            <div class="ml-2 small">{{$item->doginfo}}</div>
                                            <div class="ml-2 small">{{$item->initusername}}</div>
                                        </td>
                                        <?php
                                        if ($item->statusid == 0) {
                                            $status_name = 'Черновик';
                                            $status_style = 'background-color: silver; color: black;';
                                        } elseif ($item->statusid == 1) {
                                            $status_name = 'Ожидает решения';
                                            $status_style = 'background-color: lightyellow; color: black;';
                                        } elseif ($item->statusid == 2) {
                                            $status_name = 'Согласована';
                                            $status_style = 'background-color: lightgreen; color: black;';
                                        } elseif ($item->statusid == 3) {
                                            $status_name = 'Отказано';
                                            $status_style = 'background-color: salmon; color: black;';
                                        } elseif ($item->statusid == 9) {
                                            $status_name = 'Завершена';
                                            $status_style = 'background-color: #597cb0; color: black;';
                                        } else {
                                            $status_name = '-';
                                            $status_style = 'background-color: silver; color: black;';
                                        }

                                        ?>
                                        <td class="text-center" style="{{$status_style}};">
                                            {{$status_name}}

                                            @if($usrrights['setoffbalance']??false)
                                                <div
                                                    class="small mt-2">{{($item->offbalance==1)?'внебалансовая':''}}</div>
                                            @endif
                                        </td>
                                        <td style="text-align: center;">
                                            <a href="{{ route('mchnrqsts.edit',$item->id)}}"
                                               class="btn btn-sm btn-primary"
                                               title="Просмотреть/Изменить запись">
                                                <i class="fa fa-pencil"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            @else
                                <tr>
                                    <td colspan="7" align="center">
                                        <h5 style="margin:0 auto">Заявки не найдены...</h5>
                                    </td>
                                </tr>
                            @endif
                        </table>

                        @includeIf('layouts.paginate_links')

                    </div>
                </div>
    </form>
    <script src="{{ asset('js/mchnrqst_index.js') }}" defer></script>
@endsection
