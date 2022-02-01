@extends('layouts.edit')
@section('content')

    <?php
    $repid = 45;

    $thisTitle = $data->report->title ?? $data->report->name;

    $showGrpSum = ($search_params['showGrpSum'] == 1);
    $ordbyItmSumDesc = ($search_params['ordbyItmSumDesc'] == 1);
    $ordbyDocQtyDesc = ($search_params['ordbyDocQtyDesc'] == 1);

    $retRoute = ($data->retURL)
        ? ($data->retURL . '#rep45')
        : route('mchn_raids.index') . '#rep45';
    ?>

    <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
    <script src="{{ asset('js/jquery-ui.js') }}" defer></script>

    <script src="{{ asset('js/callListOrgs.js') }}" defer></script>
    <script0 src="{{ asset('js/ri_ac_userorgs.js') }}" defer></script0>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.6.0/Chart.bundle.js" charset="utf-8"></script>

    <script>
        function getRandomColor() {
            var letters = '0123456789ABCDEF'.split('');
            var color = '#';
            for (var i = 0; i < 6; i++) {
                // color += letters[Math.floor(Math.random() * 16)];
                color += letters[Math.floor(7 + Math.random() * 9)];
            }
            return color;
        }
    </script>
    <style>
        .rep-data td {
            padding: 5px;
            border-collapse: collapse;
            border: 1px solid #e2e2e2;
        }

        .grpTotal {
            background-color: lightgoldenrodyellow;
            font-weight: bold;
        }

        .grandTotal {
            background-color: #ffff80;
            font-weight: bold;
        }

        .brg {
            border-right: 1px solid gray !important;
        }

        .fnt12 {
            font-size: 12px;
        }

        .subLbl {
            background-color: #e4e4e4;
            display: inline-block;
            font-size: 12px;
            padding: 2px;
        }

    </style>

    <div class="container">

        <div class="row">
            <div class="col-md-12">

                <div class="params no-print card mt-3" style="min-width:400px;">
                    <div class="card-header font-weight-bold">
                        Параметры отчета "{{$thisTitle}}"
                    </div>
                    <div class="card-body">

                        <form name="forRep" id="forRep" method="post" action="{{ route('reports.rep45set') }}">
                            @csrf

                            <div class="row">
                                <div class="form-group col-md-8">

                                    <div class="row">
                                        <div class="col-md-8">
                                            <table class="">
                                                <tr align="center">
                                                    <td style="min-width:248px;" ><label class="required">Основной период</label>
                                                        <?php
                                                        $TimeSelTypes = [1 => 'год/месяц', 2 => 'дата'];
                                                        ?>
                                                        {!! Form::select('vTimeSelType', $TimeSelTypes, $search_params['vTimeSelType'],
                                                                        [
                                                                        'class000' => 'form-control',
                                                                        'required' => 'required',
                                                                        'onChange' => 'vTimeSelType_change(this);',
                                                                        'style' => 'font-size: 11px; float:right;',
                                                                        ])
                                                                        !!}

                                                    </td>
                                                    <td style="min-width:248px;">Период для сравнения</td>
                                                </tr>

                                                <?php
                                                $vTimeSelType = $search_params['vTimeSelType'];

                                                $vYr1 = $search_params['vYr1'] ?? 0;
                                                $vMn1 = $search_params['vMn1'] ?? 0;
                                                $vDc1 = $search_params['vDc1'] ?? 0;
                                                $vWk1 = $search_params['vWk1'] ?? 0;

                                                $vYr2 = $search_params['vYr2'] ?? 0;
                                                $vMn2 = $search_params['vMn2'] ?? 0;
                                                $vDc2 = $search_params['vDc2'] ?? 0;
                                                $vWk2 = $search_params['vWk2'] ?? 0;

                                                $vBegDate1 = $search_params['vBegDate1'] ?? '';
                                                $vEndDate1 = $search_params['vEndDate1'] ?? '';

                                                $vBegDate2 = $search_params['vBegDate2'] ?? '';
                                                $vEndDate2 = $search_params['vEndDate2'] ?? '';

                                                $decades = [];
                                                for ($i = 1; $i < 4; $i++) $decades[$i] = $i;

                                                $weeks = [];
                                                for ($i = 1; $i < 54; $i++) $weeks[$i] = $i;

                                                $TimeSelType_1_style = ($vTimeSelType == 1) ? "" : "display:none;";
                                                $TimeSelType_2_style = ($vTimeSelType == 1) ? "display:none;" : "";

                                                if ($vYr1 == 0) {
                                                    $tMnStyle = "display:none;";
                                                    $tDcStyle = "display:none;";
                                                    $tWkStyle = "display:none;";
                                                } else {
                                                    if ($vMn1 == 0 and $vWk1 == 0) {
                                                        $tMnStyle = "display:inline-block;";
                                                        $tDcStyle = "display:none;";
                                                        $tWkStyle = "display:inline-block;";
                                                    } else {
                                                        if ($vMn1 <> 0) {
                                                            $tMnStyle = "display:inline-block;";
                                                            //пока не отображаем декады
//                                                            $tDcStyle = "display:inline-block;";
                                                            $tDcStyle = "display:none;";
                                                            $tWkStyle = "display:none;";
                                                        } else {
                                                            $tMnStyle = "display:none;";
                                                            $tDcStyle = "display:none;";
                                                            $tWkStyle = "display:inline-block;";
                                                        }
                                                    }
                                                }

                                                ?>

                                                <tr id="bTimeSelType_1" align="center" valign="TOP"
                                                    style="{{$TimeSelType_1_style}}">
                                                    <td>

                                                        <div id="bYr1" class="subLbl">&nbsp;год:<br>
                                                            {!! Form::select('vYr1', $data->years, $search_params['vYr1']
                                                                        ,['placeholder' => ' - год - ',
                                                                        'class' => 'text-center',
                                                                        'onChange' => 'vYr1_change()',
                                                                        'id' => 'vYr1',
                                                                        ]) !!}
                                                        </div>

                                                        <div id="bMn1" class="subLbl" style="{{$tMnStyle}}">&nbsp;месяц:<br>
                                                            {!! Form::select('vMn1', $data->monthes, $search_params['vMn1']
                                                                        ,['placeholder' => ' - все - ',
                                                                        'class' => 'text-center',
                                                                        'onChange' => 'vMn1_change();',
                                                                        'id' => 'vMn1',
                                                                        ]) !!}
                                                        </div>

                                                        <div id="bDc1" class="subLbl" style="{{$tDcStyle}}">&nbsp;декада:<br>
                                                            {!! Form::select('vDc1', $decades, $search_params['vDc1']
                                                                        ,['placeholder' => ' - дек. - ',
                                                                        'class' => 'text-center',
                                                                        'id' => 'vDc1',
                                                                        ]) !!}
                                                        </div>

                                                        <div id="bWk1" class="subLbl" style="{{$tWkStyle}}">неделя
                                                            года:<br>
                                                            {!! Form::select('vWk1', $weeks, $search_params['vWk1']
                                        ,['placeholder' => ' - нед. - ',
                                        'class' => 'text-center',
                                        'id' => 'vWk1',
                                        'onChange' => 'vWk1_change();',
                                        ]) !!}
                                                        </div>
                                                    </td>

                                                    <td>
                                                        <?php
                                                        if ($vYr2 == 0) {
                                                            $tMnStyle = "display:none;";
                                                            $tDcStyle = "display:none;";
                                                            $tWkStyle = "display:none;";

                                                        } else {
                                                            if ($vMn2 == 0 and $vWk2 == 0) {
                                                                $tMnStyle = "display:inline-block;";
                                                                $tDcStyle = "display:none;";
                                                                $tWkStyle = "display:inline-block;";
                                                            } else {
                                                                if ($vMn2 <> 0) {
                                                                    $tMnStyle = "display:inline-block;";
//                                                                    $tDcStyle = "display:inline-block;";
                                                                    $tDcStyle = "display:none;";
                                                                    $tWkStyle = "display:none;";
                                                                } else {
                                                                    $tMnStyle = "display:none;";
                                                                    $tDcStyle = "display:none;";
                                                                    $tWkStyle = "display:inline-block;";
                                                                }
                                                            }
                                                        }
                                                        ?>

                                                        <div id="bYr2" class="subLbl">&nbsp;год:<br>
                                                            {!! Form::select('vYr2', $data->years, $search_params['vYr2']
                                                                        ,['placeholder' => ' - нет - ',
                                                                        'class' => 'text-center',
                                                                        'onChange' => 'vYr2_change()',
                                                                        'id' => 'vYr2',
                                                                        ]) !!}
                                                        </div>

                                                        <div id="bMn2" class="subLbl" style="{{$tMnStyle}}">&nbsp;месяц:<br>
                                                            {!! Form::select('vMn2', $data->monthes, $search_params['vMn2']
                                                                        ,['placeholder' => ' - все - ',
                                                                        'class' => 'text-center',
                                                                        'onChange' => 'vMn2_change()',
                                                                        'id' => 'vMn2',
                                                                        ]) !!}
                                                        </div>

                                                        <div id="bDc2" class="subLbl" style="{{$tDcStyle}}">&nbsp;декада:<br>
                                                            {!! Form::select('vDc2', $decades, $search_params['vDc2']
                                                                        ,['placeholder' => ' - дек. - ',
                                                                        'class' => 'text-center',
                                                                        'id' => 'vDc2',
                                                                        ]) !!}
                                                        </div>

                                                        <div id="bWk2" class="subLbl" style="{{$tWkStyle}}">&nbsp;неделя
                                                            года:<br>
                                                            {!! Form::select('vWk2', $weeks, $search_params['vWk2']
                                        ,['placeholder' => ' - нед. - ',
                                        'class' => 'text-center',
                                        'id' => 'vWk2',
                                        'onChange' => 'vWk2_change();',
                                        ]) !!}
                                                        </div>
                                                    </td>
                                                </tr>

                                                <tr id="bTimeSelType_2" align="center" valign="TOP"
                                                    style="{{$TimeSelType_2_style}}">
                                                    <td>
                                                        <table style="font-size: 12px;">
                                                            <tr>
                                                                <td align="right">с &nbsp;</td>
                                                                <td><input type="date" name="vBegDate1" id="vBegDate1"
                                                                           size="8"
                                                                           class="text-center" value="{{$vBegDate1}}">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td align="right">по &nbsp;</td>
                                                                <td><input type="date" name="vEndDate1" id="vEndDate1"
                                                                           size="8"
                                                                           class="text-center" value="{{$vEndDate1}}">
                                                            </tr>
                                                        </table>
                                                    </td>
                                                    <td>
                                                        <table style="font-size: 12px;">
                                                            <tr>
                                                                <td align="right">с &nbsp;</td>
                                                                <td><input type="date" name="vBegDate2" id="vBegDate2"
                                                                           size="8"
                                                                           class="text-center" value="{{$vBegDate2}}">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td align="right">по &nbsp;</td>
                                                                <td><input type="date" name="vEndDate2" id="vEndDate2"
                                                                           size="8"
                                                                           class="text-center" value="{{$vEndDate2}}">
                                                                    <button onClick="clrPeriod2();"
                                                                            title="Очистить период для сравнения"
                                                                            style="font-size:10px;">
                                                                        <i class="fa fa-times" aria-hidden="true"></i>
                                                                    </button>
                                                            </tr>
                                                        </table>

                                                    </td>
                                                </tr>

                                            </table>
                                        </div>

                                        <div class="form-group col-md-4">
                                            <label for="s_sale_dirs" class="required">Тип операций:</label>
                                            {!! Form::select('s_sale_dir', $data->sale_dirs??[], $search_params['s_sale_dir']??'',
                                                            [
                                                            'class' => 'form-control font-weight-bold',
                                                            'placeholder' => '-',
                                                            'required' => 'required'
                                                            ])
                                                            !!}
                                        </div>

                                        @if (1==1 and count($data->suporgs)>1)
                                            <div class="form-group col-md-4">
                                                <label for="s_suporgs">Исполнитель:</label>
                                                {!! Form::select('s_suporgid', $data->suporgs, $search_params['s_suporgid'],
                                                                [
                                                                'class' => 'form-control',
                                                                'placeholder' => '-любой-',
                                                                ])
                                                                !!}
                                            </div>
                                        @endif

                                        @if (1==1 and count($data->orgs)>1)
                                            <div class="form-group col-md-4">
                                                <label for="s_orgid">Заказчик:</label>
                                                {!! Form::select('s_orgid', $data->orgs, $search_params['s_orgid'],
                                                                [
                                                                'class' => 'form-control',
                                                                'placeholder' => '-любой-',
                                                                ])
                                                                !!}
                                            </div>
                                        @endif
                                        @if (1==0 and count($data->contracts)>0)
                                            <div class="form-group col-md-6">
                                                <label for="s_contractid">Договор с подрядчиком:</label>
                                                {!! Form::select('s_contractid', $data->contracts, $search_params['s_contractid'],
                                                                [
                                                                'class' => 'form-control',
                                                                'placeholder' => '-',
                                                                ])
                                                                !!}
                                            </div>
                                        @endif

                                        @if (count($data->dispatchers)>1)
                                            <div class="form-group col-md-3">
                                                <label for="s_suporgid">Диспетчер:</label>
                                                {!! Form::select('s_mngrid', $data->dispatchers, $search_params['s_mngrid'],
                                                                [
                                                                'class' => 'form-control',
                                                                'placeholder' => '',
                                                                ])
                                                                !!}
                                            </div>
                                        @endif


                                        @if (1==0 and count($data->buildobjs)>1)
                                            <div class="form-group col-md-6">
                                                <label for="s_buildobjid">Объект:</label>
                                                {!! Form::select('s_buildobjid', $data->buildobjs, $search_params['s_buildobjid'],
                                                                [
                                                                'class' => 'form-control',
                                                                 'placeholder' => '-',
                                                                ])
                                                                !!}
                                            </div>
                                        @endif

                                    </div>

                                    <div class="row">
                                        @if (1==0 and count($data->orggroups)>0)
                                            <div class="form-group offset-md-0 col-md-3">
                                                <label for="s_orggrpid">Группа клиентов:</label>
                                                {!! Form::select('s_orggrpid', $data->orggroups, $search_params['s_orggrpid'],
                                                                [
                                                                'class' => 'form-control',
                                                                ])
                                                                !!}
                                            </div>
                                        @endif

                                        @if(1==0)
                                            <?php
                                            $s_orgname = $search_params['s_orgname'] ?? '';
                                            $s_orgid = $search_params['s_orgid'] ?? '';
                                            ?>
                                            <div class="form-group col-md-6">
                                                <label for="name">Заказчик:</label>
                                                <div class="input-group mb-3 ">
                                                    <input type="text" class="form-control" name="orgname"
                                                           id="orgname"
                                                           value="{{$s_orgname}}"
                                                        {{--											{{$inputMode}}--}}
                                                    />
                                                    <input type="text" class="form-control text-center small"
                                                           style="display: none; border: #d7f3e3;" id="ac_orgid"
                                                           readonly>
                                                    <input type="hidden" name="s_orgid" id="orgid"
                                                           value="{{$s_orgid}}">


                                                    <div class="input-group-append">
                                                        <a onclick="callListOrgs({{$s_orgid}})" title="Поиск"
                                                           class="btn btn-sm btn-light form-control">
                                                            <i class="fa fa-search" aria-hidden="true"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif


                                        @if (1==0 )
                                            <div class="form-group col-md-3">
                                                <label for="name">Min "Сумма":</label>
                                                <input type="number" name="s_minitmsum" min=0 step="10000"
                                                       class="form-control text-right"
                                                       value="{{$search_params['s_minitmsum']}}">
                                            </div>

                                            <div class="form-group col-md-3">
                                                <label for="name">Min "Средний заказ":</label>
                                                <input type="number" name="s_mindocsum" min=0 step="1000"
                                                       class="form-control text-right"
                                                       value="{{$search_params['s_mindocsum']}}">
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="form-group col-md-4">
                                    <table border="0" bgcolor="#EEEEEE" class="fnt12 small" id="np" align="center"
                                           cellpadding="2">
                                        <tr bgcolor="#C0C0C0">
                                            <td colspan="3">&nbsp;<b>Группировать по колонке:</b></td>
                                        </tr>
                                        <tr align="center">
                                            <td>кандидаты</td>
                                            <td></td>
                                            <td><b>выбор</b></td>
                                        </tr>

                                        <TR valign="top">
                                            <TD>
                                                @php($tSize = max(6, count($aCnds), count($aGrps)))
                                                {!! Form::select('list1', $aCnds, null
                                                            ,[
                                                            'id' => 'list1',
                                                            'class' => 'form-control text-center  fnt12',
                                                            'style' => "min-width:130px;",
                                                            'MULTIPLE' => 'MULTIPLE',
                                                            'size' => $tSize,
                                                            ]) !!}
                                            </TD>
                                            <TD VALIGN=MIDDLE ALIGN=CENTER>
                                                <INPUT TYPE="button" Name="right" VALUE="&gt;" id="add"
                                                       class="fnt9" style="width: 38px;"><br>
                                                <!--
                                                <INPUT TYPE="button" Name="right" VALUE="&gt;&gt;" ONCLICK="opt.transferAllRight()" class="fnt9" style="width: 38px;">
                                                -->
                                                <br><br>
                                                <INPUT TYPE="button" Name="left" VALUE="&lt;" id="remove"
                                                       class="fnt9" style="width: 38px;">
                                                <br>
                                                <INPUT TYPE="button" Name="left" VALUE="&lt;&lt;" id="allremove"
                                                       class="fnt9" style="width: 38px;">
                                            </TD>
                                            <TD>
                                                {!! Form::select('list2[]', $aGrps, null
                                                            ,[
                                                            'id' => 'list2',
                                                            'class' => 'form-control text-center fnt12',
                                                            'MULTIPLE' => 'MULTIPLE',
                                                            'size' => $tSize,
                                                            'style' => "min-width:120px;",
                                                            ]) !!}
                                            </TD>
                                        </TR>
                                        <tr>
                                            <td colspan="3">
                                                <input type="hidden" name="GrpLst" id="GrpLst" value="{{$GrpLst}}">

                                                <label for="showGrpSum" class="mt-1 ml-1" id="bShowGrpSum"
                                                       style="display:none;">Выводить
                                                    итоги по группам:
                                                    {!! Form::checkbox('showGrpSum', 1, $showGrpSum, ['class=""']) !!}
                                                </label>
                                                <br><label for="ordbyItmSumDesc" id="bOrdbyItmSum" class="mt-1 ml-1"
                                                           style="display:none;">Сортировать по
                                                    убыванию
                                                    суммы:
                                                    {!! Form::checkbox('ordbyItmSumDesc', 1, $ordbyItmSumDesc, ['class=""']) !!}
                                                </label>
                                                <br><label for="ordbyDocQtyDesc" id="bOrdbyDocQty" class="mt-1 ml-1"
                                                           style="display:none;">Сортировать по убыванию количества
                                                    рейсов:
                                                    {!! Form::checkbox('ordbyDocQtyDesc', 1, $ordbyDocQtyDesc, ['class=""']) !!}
                                                </label>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <div class="form-group col-md-12">


                            </div>

                            <div style="border-top:1px solid silver;" class="mt-1 p-1">
                                <button type="submit" class="btn btn-success"
                                >
                                    <i class="fa fa-refresh" aria-hidden="true"></i>
                                    Сформировать
                                </button>
                                <a class="btn btn-close btn-info btn-sm ml-2"
                                   href="{{$retRoute}}">
                                    <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                    Закрыть
                                </a>
                                <span class="small ml-2 float-right">
{{--									<a href="{{route('objevntlog',['sysobjid'=>15, 'objid'=>83,'route'=>Route::current()->getName()])}}">журнал</a>--}}
									<a href="{{route('objevntlog',['sysobjid'=>855, 'objid'=>45,'route'=>Route::current()->getName()])}}">журнал</a>
								</span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if(isset($recs))
            <div class="row">
                <div class="col-md-12">

                    <table class="rep-data small my-3" style="background-color: snow;">
                        <thead>
                        <tr>
                            <td colspan="15">
                                <span class="font-weight-bold ml-2" style="font-size: 18px;"> {{$thisTitle}}</span>
                                <span class="small ml-3">по данным на {{now()}}</span>
                                <div class="text-center">
                                    {!! $conditions !!}
                                </div>
                            </td>
                        </tr>
                        <tr class="text-center">
                            <td class="small" rowspan="2">#</td>
                            @foreach($grps as $grp)
                                <td rowspan="2">{{$grp['title']}}</td>
                            @endforeach

                            <?php
                            for($ds = 1;$ds <= $datasetCnt;$ds++){
                            ?>
                            {{--                            <td colspan="4" class="font-weight-bold brg">--}}
                            <td colspan="3" class="font-weight-bold brg">
                                {{$dataset[$ds][0]}} - {{$dataset[$ds][1]}}
                            </td>
                            <?php
                            }?>
                            @if( $datasetCnt > 1)
                                {{--                                <td colspan="3">Прирост, %</td>--}}
                                <td colspan="2">Прирост, %</td>
                            @endif
                        </tr>
                        <tr class="text-center">
                            <?php
                            for($ds = 1;$ds <= $datasetCnt; $ds++){
                            ?>
                            {{--                            <td>Часов</td>--}}
                            <td>Сумма, руб</td>
                            <td>Кол-во рейсов</td>
                            <td class="brg">В среднем за рейс, руб</td>
                            <?php
                            }?>
                            @if($datasetCnt>1)
                                {{--                                <td>Часы</td>--}}
                                <td>Сумма</td>
                                <td>Кол-во рейсов</td>
                            @endif

                        </tr>
                        </thead>

                        <tbody>
                        <tr>
                        <?php

                        $totItmQty = 0;
                        $totItmSum = 0;
                        $totDocQty = 0;

                        $grpCnt = count($grps);

                        //$grpCurVal = array_fill(0, $grpCnt, null);
                        $grpCurVal = array_fill(0, $grpCnt, ' ***');

                        $grpBgCol = ['#dff5ff', 'lightgoldenrodyellow', 'lightyellow', 'thistle'];

                        $grpRedCol = ['#ffcbb7', '#fbd4c6', '#fbd4c6', '#fbd4c6', '#fbd4c6', '#fbd4c6'];
                        $grpGrnCol = ['#b9f3b9', '#b9f3b9', '#b9f3b9', '#b9f3b9', '#b9f3b9', '#b9f3b9'];

                        $newLine = true;

                        $lineData = [];

                        $grpItmQty = [];
                        $grpItmSum = [];
                        $grpDocQty = [];

                        $grandTotal = [];

                        $chart_labels = '';
                        $chart_dataset = [];
                        $chart_data = [];

                        for ($ds = 1; $ds <= $datasetCnt; $ds++) {

                            $lineData[$ds] = array_fill(0, 4, 0.00);

                            if ($grpCnt > 0) {
                                $grpItmQty[$ds] = array_fill(0, $grpCnt - 1, 0);
                                $grpItmSum[$ds] = array_fill(0, $grpCnt - 1, 0.00);
                                $grpDocQty[$ds] = array_fill(0, $grpCnt - 1, 0);
                            }

                            $grandTotal[$ds] = array_fill(0, 4, 0.00);
                        }

                        $npp = 0;
                        $isFirstCycle = true;

                        foreach ($recs as $itm) {


                            if (1 == 0)
                                echo('<tr>'
                                    . '<td colspan="14"> данные: '
                                    . $itm->dataset
                                    . ' / ' . $itm->mn
                                    . ' / ' . $itm->saleuserid
                                    . ' / ' . $itm->orgid
                                    . ' / ' . $itm->itmqty
                                    . '</td>'
                                    . '</tr>');


                            if ($isFirstCycle) {
                                //первый вход - просто заполним массивы
                                $isFirstCycle = false;

                                for ($g = 0; $g < $grpCnt; $g++) {
                                    $lbl = $grps[$g]['lbl'];
                                    $v = $itm->$lbl;
                                    $grpCurVal[$g] = $v;
                                }

                                // суммы
                                if (isset($itm->itmsum)) {
                                    $lineData[$itm->dataset] = [
                                        $itm->itmqty,
                                        $itm->itmsum,
                                        $itm->raid_qty,
                                        ($itm->raid_qty > 0) ? round($itm->itmsum / $itm->raid_qty, 2) : 0,
                                    ];

                                    $ds = $itm->dataset;

                                    //подитоги групп
                                    if ($grpCnt > 0) {
                                        for ($g = 0; $g < $grpCnt - 1; $g++) {
                                            $grpItmQty[$ds][$g] += $lineData[$ds][0];
                                            $grpItmSum[$ds][$g] += $lineData[$ds][1];
                                            $grpDocQty[$ds][$g] += $lineData[$ds][2];
                                        }
                                    }

                                    //GrandTotal
                                    for ($i = 0; $i < 4; $i++)
                                        $grandTotal[$ds][$i] += $lineData[$ds][$i];
                                }

                            } else {

                                //Не первая строка - проверим - совместимы ли ее значения с предыдущими
                                // Если нет - нужно будет вывести предыдущее и начать накопление опять

                                $newline = false;
                                for ($g = 0; $g < $grpCnt; $g++) {

                                    $lbl = $grps[$g]['lbl'];
                                    $v = $itm->$lbl;

                                    if ($v <> $grpCurVal[$g]) {
                                        $newline = true;
                                        if (1 == 0)
                                            echo('<tr>'
                                                . '<td colspan="14"> вышли за пределы группы: "'
                                                . $g
                                                . '"" : ' . $v
                                                . ' <> ' . $grpCurVal[$g]
                                                . '</td>'
                                                . '</tr>');
                                        break;
                                    }
                                }//проверка на нужность новой строки

                                if ($newline) {

                                    //выведем все, что накопили ---------------------------------------------------------------
                                    echo('<tr class="text-center"><td class="small text-right">' . ++$npp . '</td>');

                                    for ($g = 0; $g < $grpCnt; $g++) {
                                        $c = "";
                                        if ($g == $grpCnt - 1) $c = "brg";
                                        echo('<td class="' . $c . '">' . $grpCurVal[$g] . '</td>');
                                    }

                                    for ($ds = 1; $ds <= $datasetCnt; $ds++) {
                                        //echo('<td class="text-right">' . $lineData[$ds][0] . '</td>');
                                        echo('<td class="text-right">' . number_format($lineData[$ds][1], 2) . '</td>');
                                        echo('<td class="text-right">' . $lineData[$ds][2] . '</td>');
                                        echo('<td class="text-right brg">' . number_format($lineData[$ds][3], 2) . '</td>');
                                    }

                                    //Прирост ------------------------------------------------------------------------
                                    if ($datasetCnt > 1) {

                                        //for ($i = 0; $i < 3; $i++) {
                                        for ($i = 1; $i < 3; $i++) {
                                            if ($lineData[1][$i] == 0) {
                                                if ($lineData[2][$i] == 0) {
                                                    $dif = "";
                                                    $bg = "yellow";
                                                } else {
                                                    $dif = "100.0";
                                                    //$bg = "#dbf3db";
                                                    $bg = "#daffda";
                                                }
                                            } else {
                                                $dif = number_format(($lineData[2][$i] - $lineData[1][$i]) / $lineData[1][$i] * 100, 1);
                                                $bg = ($dif <= 0) ? "#ffeee8" : "#daffda";
                                            }
                                            echo('<td class="text-right" style="background-color:' . $bg . '">' . $dif . '</td>');
                                        }
                                    }
                                    //----------------------------------------------------------------------------------

                                    echo('</tr>');
                                    //--------------------------------------------------------------------
                                    if (1 == 1) {
                                        $chart_data[] = $lineData[1][0];
                                    }

                                    // обнулим ---------------------------------------------------------
                                    for ($ds = 1; $ds <= $datasetCnt; $ds++)
                                        $lineData[$ds] = array_fill(0, 4, 0.00);
                                }


                                //==========================================================================================
                                //Нужно ли вывести подитоги по группе?
                                if ($showGrpSum) {
                                    for ($g = $grpCnt - 2; $g >= 0; $g--) {

                                        $lbl = $grps[$g]['lbl'];
                                        $v = $itm->$lbl;

                                        if ($v <> $grpCurVal[$g]) {

                                            //Отобразим подитоги по группе -----------------------------------------------------
                                            echo('<tr class="text-right grpTotal" style="background-color:' . $grpBgCol[$g] . '">');
                                            echo('<td class="brg" colspan="' . ($grpCnt + 1) . '">Итого по ' . $grps[$g]['title']
                                                . '= <b>' . $grpCurVal[$g] . '</b>:</td>');

                                            for ($ds = 1; $ds <= $datasetCnt; $ds++) {

                                                //echo('<td>' . number_format($grpItmQty[$ds][$g], 0) . '</td>');
                                                echo('<td>' . number_format($grpItmSum[$ds][$g], 2) . '</td>');
                                                echo('<td>' . number_format($grpDocQty[$ds][$g], 0) . '</td>');
                                                if ($grpDocQty[$ds][$g] == 0)
                                                    $avgDealSum = "-";
                                                else
                                                    $avgDealSum = number_format($grpItmSum[$ds][$g] / $grpDocQty[$ds][$g], 2);
                                                echo('<td class="text-right brg">' . $avgDealSum . '</td>');

                                            }
                                            //Новое текущее значение группы
                                            $grpCurVal[$g] = $v;

                                            //Прирост ------------------------------------------------------------------------
                                            if ($datasetCnt > 1) {
                                                if ($grpItmQty[1][$g] == 0) {
                                                    if ($grpItmQty[2][$g] == 0) {
                                                        $dif = "";
                                                        $bg = "yellow";
                                                    } else {
                                                        $dif = "100.0";
                                                        $bg = $grpGrnCol[$g];
                                                    }
                                                } else {
                                                    $dif = number_format(($grpItmQty[2][$g] - $grpItmQty[1][$g]) / $grpItmQty[1][$g] * 100, 1);
                                                    $bg = ($dif <= 0) ? $grpRedCol[$g] : $grpGrnCol[$g];
                                                }
                                                echo('<td class="text-right " style="background-color:' . $bg . '">' . $dif . '</td>');

                                                if ($grpItmSum[1][$g] == 0) {
                                                    if ($grpItmSum[2][$g] == 0) {
                                                        $dif = "";
                                                        $bg = "yellow";
                                                    } else {
                                                        $dif = "100.0";
                                                        $bg = $grpGrnCol[$g];
                                                    }
                                                } else {
                                                    $dif = number_format(($grpItmSum[2][$g] - $grpItmSum[1][$g]) / $grpItmSum[1][$g] * 100, 1);
                                                    $bg = ($dif <= 0) ? $grpRedCol[$g] : $grpGrnCol[$g];
                                                }
                                                echo('<td class="text-right" style="background-color:' . $bg . '">' . $dif . '</td>');

                                                if ($grpDocQty[1][$g] == 0) {
                                                    if ($grpDocQty[2][$g] == 0) {
                                                        $dif = "";
                                                        $bg = "yellow";
                                                    } else {
                                                        $dif = "100.0";
                                                        $bg = $grpGrnCol[$g];
                                                    }
                                                } else {
                                                    $dif = number_format(($grpDocQty[2][$g] - $grpDocQty[1][$g]) / $grpDocQty[1][$g] * 100, 1);
                                                    $bg = ($dif <= 0) ? $grpRedCol[$g] : $grpGrnCol[$g];
                                                }
                                                echo('<td class="text-right" style="background-color:' . $bg . '">' . $dif . '</td>');
                                            }

                                            if (1 == 1) {
                                                //echo(' ' . $g);
                                                if ($g == 0) {
//                                                    $chart_labels .= "'".$v."',";
//                                                    $chart_labels .= '"' . $v . '",';
                                                    $chart_labels .= $v . ',';

                                                    $chart_dataset[] = $chart_data;
                                                    $chart_data = [];
                                                }
                                            }
                                            //----------------------------------------------------------------------------------

                                            //Обнулим
                                            for ($ds = 1; $ds <= $datasetCnt; $ds++) {
                                                $grpItmQty[$ds][$g] = 0;
                                                $grpItmSum[$ds][$g] = 0;
                                                $grpDocQty[$ds][$g] = 0;

                                            }
                                        }
                                    }
                                }
                                //===========================================================================================


                                //заполним новыми значениями ------------------------------------------------------------
                                for ($g = 0; $g < $grpCnt; $g++) {
                                    $lbl = $grps[$g]['lbl'];
                                    $v = $itm->$lbl;
                                    $grpCurVal[$g] = $v;
                                }
                                $lineData[$itm->dataset] = [
                                    $itm->itmqty,
                                    $itm->itmsum,
                                    $itm->raid_qty,
                                    ($itm->raid_qty > 0) ? round($itm->itmsum / $itm->raid_qty, 2) : 0,
                                ];
                                //подитоги групп
                                $ds = $itm->dataset;
                                for ($g = 0; $g < $grpCnt - 1; $g++) {
                                    $grpItmQty[$ds][$g] += $lineData[$ds][0];
                                    $grpItmSum[$ds][$g] += $lineData[$ds][1];
                                    $grpDocQty[$ds][$g] += $lineData[$ds][2];
                                }
                                //GrandTotal
                                for ($i = 0; $i < 4; $i++)
                                    $grandTotal[$ds][$i] += $lineData[$ds][$i];

                            }
                        } //foreach ========================================================================================



                        //выведем все, что накопили ---------------------------------------------------------------
                        if ($isFirstCycle) {
                            echo('<tr class="text-center"><td class="font-weight-bold" style="background-color:#ffff80;"
								colspan="' . (1 + $grpCnt + 4 * $datasetCnt + 3 * ($datasetCnt - 1)) . '">Нет данных</td></tr>');
                        } else {
                            echo('<tr class="text-center"><td class="small text-right">' . ++$npp . '</td>');

                            for ($g = 0; $g < $grpCnt; $g++) {
                                $c = "";
                                if ($g == $grpCnt - 1) $c = "brg";
                                echo('<td class="' . $c . '">' . $grpCurVal[$g] . '</td>');
                            }

                            for ($ds = 1; $ds <= $datasetCnt; $ds++) {
                                //echo('<td class="text-right">' . $lineData[$ds][0] . '</td>');
                                echo('<td class="text-right">' . number_format($lineData[$ds][1], 2) . '</td>');
                                echo('<td class="text-right">' . $lineData[$ds][2] . '</td>');
                                echo('<td class="text-right brg">' . number_format($lineData[$ds][3], 2) . '</td>');
                            }
                            //--------------------------------------------------------------------

                            //Прирост ------------------------------------------------------------------------
                            if ($datasetCnt > 1) {
//                                for ($i = 0; $i < 3; $i++) {
                                for ($i = 1; $i < 3; $i++) {
                                    if ($lineData[1][$i] == 0) {
                                        if ($lineData[2][$i] == 0) {
                                            $dif = "";
                                            $bg = "yellow";
                                        } else {
                                            $dif = "100.0";
                                            //$bg = "#dbf3db";
                                            $bg = "#daffda";
                                        }
                                    } else {
                                        $dif = number_format(($lineData[2][$i] - $lineData[1][$i]) / $lineData[1][$i] * 100, 1);
                                        $bg = ($dif <= 0) ? "#ffeee8" : "#daffda";
                                    }
                                    echo('<td class="text-right" style="background-color:' . $bg . '">' . $dif . '</td>');
                                }
                            }
                            //----------------------------------------------------------------------------------
                            echo('</tr>');
                        }

                        // Подитоги по группам -------------------------------------------------------------------------
                        if ($showGrpSum) {
                            for ($g = $grpCnt - 2; $g >= 0; $g--) {

                                //Отобразим подитоги по группе -----------------------------------------------------
                                echo('<tr class="text-right grpTotal" style="background-color:' . $grpBgCol[$g] . '">');
                                echo('<td class="brg" colspan="' . ($grpCnt + 1) . '">Итого по ' . $grps[$g]['title']
                                    . '= <b>' . $grpCurVal[$g] . '</b>:</td>');

                                for ($ds = 1; $ds <= $datasetCnt; $ds++) {

                                    //echo('<td>' . number_format($grpItmQty[$ds][$g], 0) . '</td>');
                                    echo('<td>' . number_format($grpItmSum[$ds][$g], 2) . '</td>');
                                    echo('<td>' . number_format($grpDocQty[$ds][$g], 0) . '</td>');
                                    if ($grpDocQty[$ds][$g] == 0)
                                        $avgDealSum = "-";
                                    else
                                        $avgDealSum = number_format($grpItmSum[$ds][$g] / $grpDocQty[$ds][$g], 2);
                                    echo('<td class="text-right brg">' . $avgDealSum . '</td>');

                                }

                                //Прирост ------------------------------------------------------------------------
                                if ($datasetCnt > 1) {

                                    if ($grpItmQty[1][$g] == 0) {
                                        if ($grpItmQty[2][$g] == 0) {
                                            $dif = "";
                                            $bg = "yellow";
                                        } else {
                                            $dif = "100.0";
                                            $bg = $grpGrnCol[$g];
                                        }
                                    } else {
                                        $dif = number_format(($grpItmQty[2][$g] - $grpItmQty[1][$g]) / $grpItmQty[1][$g] * 100, 1);
                                        $bg = ($dif <= 0) ? $grpRedCol[$g] : $grpGrnCol[$g];
                                    }
                                    echo('<td class="text-right " style="background-color:' . $bg . '">' . $dif . '</td>');

                                    if ($grpItmSum[1][$g] == 0) {
                                        if ($grpItmSum[2][$g] == 0) {
                                            $dif = "";
                                            $bg = "yellow";
                                        } else {
                                            $dif = "100.0";
                                            $bg = $grpGrnCol[$g];
                                        }
                                    } else {
                                        $dif = number_format(($grpItmSum[2][$g] - $grpItmSum[1][$g]) / $grpItmSum[1][$g] * 100, 1);
                                        $bg = ($dif <= 0) ? $grpRedCol[$g] : $grpGrnCol[$g];
                                    }
                                    echo('<td class="text-right" style="background-color:' . $bg . '">' . $dif . '</td>');

                                    if ($grpDocQty[1][$g] == 0) {
                                        if ($grpDocQty[2][$g] == 0) {
                                            $dif = "";
                                            $bg = "yellow";
                                        } else {
                                            $dif = "100.0";
                                            $bg = $grpGrnCol[$g];
                                        }
                                    } else {
                                        $dif = number_format(($grpDocQty[2][$g] - $grpDocQty[1][$g]) / $grpDocQty[1][$g] * 100, 1);
                                        $bg = ($dif <= 0) ? $grpRedCol[$g] : $grpGrnCol[$g];
                                    }
                                    echo('<td class="text-right" style="background-color:' . $bg . '">' . $dif . '</td>');
                                }
                                //----------------------------------------------------------------------------------
                            }
                        }
                        //--------------------------------------------------------------------------------------------------



                        //GrandTotal
                        if (!$isFirstCycle) {
                            echo('<tr class="text-right grandTotal">');
                            echo('<td class="" colspan="' . ($grpCnt + 1) . '">Всего:</td>');

                            for ($ds = 1; $ds <= $datasetCnt; $ds++) {

                                //echo('<td>' . number_format($grandTotal[$ds][0], 1) . '</td>');
                                echo('<td>' . number_format($grandTotal[$ds][1], 2) . '</td>');
                                echo('<td>' . number_format($grandTotal[$ds][2], 0) . '</td>');
                                if ($grandTotal[$ds][2] == 0)
                                    $avgDealSum = "-";
                                else
                                    $avgDealSum = number_format($grandTotal[$ds][1] / $grandTotal[$ds][2], 2);
                                echo('<td class="text-right brg">' . $avgDealSum . '</td>');
                            }
                            //Прирост ------------------------------------------------------------------------
                            if ($datasetCnt > 1) {

                                if ($grandTotal[1][0] == 0) {
                                    $dif = "";
                                    $bg = "yellow";
                                } else {
                                    $dif = number_format(($grandTotal[2][0] - $grandTotal[1][0]) / $grandTotal[1][0] * 100, 1);
                                    $bg = ($dif <= 0) ? "#ff9871" : "limegreen";
                                }
                                //не выводим колонку с % прироста кол-ва
                                //echo('<td class="text-right " style="background-color:' . $bg . '">' . $dif . '</td>');

                                if ($grandTotal[1][1] == 0) {
                                    $dif = "";
                                    $bg = "yellow";
                                } else {
                                    $dif = number_format(($grandTotal[2][1] - $grandTotal[1][1]) / $grandTotal[1][1] * 100, 1);
                                    $bg = ($dif <= 0) ? "#ff9871" : "limegreen";
                                }
                                echo('<td class="text-right" style="background-color:' . $bg . '">' . $dif . '</td>');

                                if ($grandTotal[1][2] == 0) {
                                    $dif = "";
                                    $bg = "yellow";
                                } else {
                                    $dif = number_format(($grandTotal[2][2] - $grandTotal[1][2]) / $grandTotal[1][2] * 100, 1);
                                    $bg = ($dif <= 0) ? "#ff9871" : "limegreen";
                                }
                                echo('<td class="text-right" style="background-color:' . $bg . '">' . $dif . '</td>');
                            }
                            //----------------------------------------------------------------------------------
                            echo('</tr>');
                        }

                        if ($npp > 1) {
                            echo('<tr class="text-right grandAvg">');
                            echo('<td class="" colspan="' . ($grpCnt + 1) . '">Среднее:</td>');

                            for ($ds = 1; $ds <= $datasetCnt; $ds++) {

                                //echo('<td>' . number_format($grandTotal[$ds][0] / $npp, 0) . '</td>');
                                echo('<td>' . number_format($grandTotal[$ds][1] / $npp, 2) . '</td>');
                                echo('<td>' . number_format($grandTotal[$ds][2] / $npp, 0) . '</td>');
                                if ($grandTotal[$ds][2] == 0)
                                    $avgDealSum = "-";
                                else
                                    $avgDealSum = number_format($grandTotal[$ds][1] / $grandTotal[$ds][2], 2);
                                echo('<td class="text-right brg">' . $avgDealSum . '</td>');
                            }
                            //Прирост ------------------------------------------------------------------------
                            if (1 == 0 and $datasetCnt > 1) {

                                if ($grandTotal[1][0] == 0) {
                                    $dif = "";
                                    $bg = "yellow";
                                } else {
                                    $dif = number_format(($grandTotal[2][0] - $grandTotal[1][0]) / $grandTotal[1][0] * 100, 1);
                                    $bg = ($dif <= 0) ? "#ff9871" : "limegreen";
                                }
                                echo('<td class="text-right " style="background-color:' . $bg . '">' . $dif . '</td>');

                                if ($grandTotal[1][1] == 0) {
                                    $dif = "";
                                    $bg = "yellow";
                                } else {
                                    $dif = number_format(($grandTotal[2][1] - $grandTotal[1][1]) / $grandTotal[1][1] * 100, 1);
                                    $bg = ($dif <= 0) ? "#ff9871" : "limegreen";
                                }
                                echo('<td class="text-right" style="background-color:' . $bg . '">' . $dif . '</td>');

                                if ($grandTotal[1][2] == 0) {
                                    $dif = "";
                                    $bg = "yellow";
                                } else {
                                    $dif = number_format(($grandTotal[2][2] - $grandTotal[1][2]) / $grandTotal[1][2] * 100, 1);
                                    $bg = ($dif <= 0) ? "#ff9871" : "limegreen";
                                }
                                echo('<td class="text-right" style="background-color:' . $bg . '">' . $dif . '</td>');
                            }
                            //----------------------------------------------------------------------------------
                            echo('</tr>');
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>

            @if(1==1 and $datasetCnt==1 and count($grps)==1)
                <?php

                $chart_lbls = [];
                $chart_data = [];

                $lblfld = $grps[0]['lbl'];
                $fldtitle = ($grps[0]['title']);
                //dd($recs->count());
                $timescale = $grps[0]['timescale'] ?? 0;

                $dataCnt = $recs->count();

                if ($timescale == 1) {
                    $chartType = 'horizontalBar';
                    $divClass = "col-md-12";
                } else {
                    $recs = $recs->sortbyDesc('itmsum');
                    if ($dataCnt > 12) {
                        $recs = $recs->take(45);
                        $chartType = 'horizontalBar';
                        $divClass = "col-md-12";
                    } else {
                        $chartType = 'doughnut';
                        $divClass = "col-md-6";
                    }
                }
                //                $recs = $recs->sortbyDesc('itmsum')->take(12);

                foreach ($recs as $rec) {
                    $lbl = $rec->$lblfld;
                    $val = $rec->itmsum;
                    //dd($lbl, $val);
                    $chart_lbls[] = $lbl;
                    $chart_data[] = $val;
                }
                //                var_dump($chart_lbls, $chart_data);
                $chart_lbls = implode('|', $chart_lbls);
                $chart_data = implode(',', $chart_data);
                //var_dump($chart_lbls, $chart_data);
                ?>
                <div class="{{$divClass}} mt-2" style="background-color: white; ">
                    <canvas id="myChart"></canvas>
                </div>
                <script>
                    //console.log(getRandomColor());

                    var canvas = document.getElementById("myChart");
                    var ctx = canvas.getContext('2d');

                    var DataCnt = {{$recs->count()}};
                    var chartType = '{{$chartType}}';

                    // Global Options:
                    Chart.defaults.global.defaultFontColor = 'black';
                    Chart.defaults.global.defaultFontSize = 12;

                    var labels = '{{$chart_lbls}}'.split("|");
                    var colors = ['red'];
                    for (var i = 1; i < DataCnt; i++)
                        colors[i] = getRandomColor();

                    var data = {
                        labels: labels,
                        datasets: [
                            {
                                fill: true,
                                backgroundColor: colors,
                                data: [{{$chart_data}}],
                                // Notice the borderColor
                                //borderColor: ['black', 'black'],
                                borderWidth: [1, 1, 1, 1, 1, 1]
                            },

                        ]
                    };


                    if (chartType == 'horizontalBar')
                        var options = {
                            title: {
                                display: true,
                                text: 'Сумма затрат в разрезе "{{$fldtitle}}"',
                                position: 'top'
                            },
                            legend: {
                                display: false
                            },
                            scales: {
                                xAxes: [{
                                    ticks: {
                                        beginAtZero: true
                                    }
                                }]
                            },
                        };
                    else
                        var options = {
                            title: {
                                display: true,
                                text: 'Сумма затрат в разрезе "{{$fldtitle}}"',
                                position: 'top'
                            },
                            legend: {
                                display: true,
                            },
                            // Notice the rotation from the documentation.
                            rotation: -1.0 * Math.PI
                        };


                    // Chart declaration:
                    var myBarChart = new Chart(ctx, {
                        // type: 'doughnut',
                        type: chartType,
                        data: data,
                        options: options
                    });

                </script>
            @endif

            @if(1==1 and $datasetCnt==1 and count($grps)==2)
                <?php
                $chart_lbls = [];
                $chart_data = [];

                $lblfld0 = $grps[0]['lbl'];
                $fldtitle = ($grps[0]['title']);

                $lblfld1 = $grps[1]['lbl'];
                $fldtitle1 = ($grps[1]['title']);
                //dd($lblfld1);

                $datasets = [];

                $recs1 = $recs->unique($lblfld0);
                $ylabels = [];
                foreach ($recs1 as $r) {
                    $ylabels[] = $r->$lblfld0;
                }
                $chart_lbls = implode('|', $ylabels);
                $yLblCnt = count($ylabels);

                $recs1 = $recs->groupby($lblfld1);
                foreach ($recs1 as $r) {

                    $chart_data = [];
                    foreach ($ylabels as $l) {
                        $chart_data[$l] = 0;
                    }

                    foreach ($r as $rec) {

                        $lbl = $rec->$lblfld1;
                        $val = $rec->itmsum;
//                        $val = $rec->docqty;
                        $chart_data[$rec->$lblfld0] = $val;
                    }
                    $datasets[] = [$lbl, $chart_data];
                }

                $chart_data = implode(',', $chart_data);

                $auxStyle = "";
                $maintainAspectRatio = "true";
                if ($yLblCnt > 20 or count($datasets) > 20) {
                    $auxStyle = "height:" . ($yLblCnt * 18 + count($datasets) * 5) . 'px !important;';
                    $maintainAspectRatio = "false";
                }
                ?>

                <div class="col-md-12 mt-2" style="background-color: white; {{$auxStyle}}">
                    <canvas id="myChart"></canvas>
                </div>
                <script>
                    var canvas = document.getElementById("myChart");
                    var ctx = canvas.getContext('2d');

                    // Global Options:
                    Chart.defaults.global.defaultFontColor = 'black';
                    Chart.defaults.global.defaultFontSize = 12;

                    var labels = '{{$chart_lbls}}'.split("|");

                    var data = {
                        // labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
                        labels: labels,
                        datasets: [@foreach ($datasets as $ds){
                            label: '{{$ds[0]}}',
                            backgroundColor: getRandomColor(),
                            data: [{{implode(',',$ds[1])}}]
                        },@endforeach ]

                    };

                    var options = {
                        responsive: true,
                        maintainAspectRatio: {{$maintainAspectRatio}},
                        title: {
                            display: true,
                            text: 'Сумма затрат в разрезе "{{$fldtitle}}" / "{{$fldtitle1}}"',
                            position: 'top'
                        },
                        legend: {
                            display: false,
                        },
                        scales: {
                            xAxes: [{
                                stacked: true,
                            }],
                            yAxes: [{
                                stacked: true,
                            }]
                        }

                    };


                    // Chart declaration:
                    var myBarChart = new Chart(ctx, {
                        type: 'horizontalBar',
                        data: data,
                        options: options
                    });

                </script>
            @endif

        @endif

    </div>
@endsection

@section('page-js-files')
    <script LANGUAGE="JavaScript" SRC="{{ asset('js/rep32.js') }}" defer></script>
@endsection

@section('page-js-script')
@endsection
