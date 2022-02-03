@extends('layouts.report')
@section('content')
    <?php
    $thisTitle = "Реестр договоров";
    $thisSysObjCode = 'contracts';
    $thisSysObjId = 855;    //reports
    $thisObjId = 23;
    $action_url = route('reports.rep' . $thisObjId);
    $retURL = route('contracts.index');
    ?>


    <div class="container">

        <div class="row mb-3">
            <div class="col-md-12">

                <div class="params no-print card mt-3 d-print-none">
                    <div class="card-header font-weight-bold">
                        Параметры отчета "{{$thisTitle}}"
                    </div>
                    <div class="card-body">

                        <form name="forRep01" id="forRep01" method="post"
                              action="{{ $action_url }}">
                            @csrf

                            @if(1==1)
                                <div class="row">

                                    <div class="form-group col-md-2">
                                        <label for="s_begdate">От холдинга:</label>
                                        {!! Form::select('s_ownorgid', $usedownorgs, $search_params['s_ownorgid'] ?? '',
                                             [
                                             'class' => 'form-control small',
                                             'placeholder' => '-любой-',
                                             ]) !!}
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="s_begdate">Категория:</label>
                                        {!! Form::select('s_categoryid', $usedcategories, $search_params['s_categoryid'] ?? '',
										 [
										 'class' => 'form-control',
										 'placeholder' => '-любая-',
                                         'title' => 'Выберите нужную категорию договора',
                                         'data-toggle' => 'tooltip',
										 ]) !!}

                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="s_begdate">Начальный рег. №:</label>
                                        <input type="number" class="form-control small" name="s_min_regnum"
                                               value="{{$search_params['s_min_regnum'] ?? ''}}"
                                               min="1"
                                               placeholder="-"
                                               title="Укажите только номер. Например '75'"
                                               data-toggle="tooltip"
                                        />
                                    </div>

                                    <div class="form-group col-md-2">
                                        <label for="s_begdate">Конечн. рег. №:</label>
                                        <input type="number" class="form-control small" name="s_max_regnum"
                                               value="{{$search_params['s_max_regnum'] ?? ''}}"
                                               placeholder="-"
                                               title="Укажите только номер. Например '101'"
                                               data-toggle="tooltip"
                                        />
                                    </div>

                                    @if(1==0)
                                        <div class="form-group col-md-3">
                                            <label for="s_begdate">Начало периода:</label>
                                            <input type="date" class="form-control text-center"
                                                   name="s_begdate"
                                                   value="{{$search_params['s_begdate']??''}}"
                                            />
                                        </div>

                                        <div class="form-group col-md-3">
                                            <label for="s_begdate">Окончание периода:</label>
                                            <input type="date" class="form-control text-center"
                                                   name="s_enddate"
                                                   value="{{$search_params['s_enddate']??''}}"
                                            />
                                        </div>
                                    @endif

                                </div>
                            @endif

                            <div style="border-top:1px solid silver;" class="mt-1 p-1">
                                <button type="submit" class="btn btn-sm btn-success"
                                        {{--								formaction="{{ route('mchnrqsts.index') }}"--}}
                                        formmethod="post">
                                    <i class="fa fa-refresh" aria-hidden="true"></i>
                                    Сформировать
                                </button>
                                <a class="btn btn-close btn-info btn-sm"
                                   href="{{ $retURL  }}">
                                    <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                    Закрыть
                                </a>
                                @if(1==1)
                                    <span class="small float-right" ml-2>
									 <a href="{{route('objevntlog',['sysobjid'=>$thisSysObjId, 'objid'=>$thisObjId,'route'=>Route::current()->getName()])}}">журнал</a>
								</span>
                                @endif


                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if (isset($recs))
            {{--            @if ($recs->count()==0)--}}
            @if (count($recs)==0)

                <div class="page p-3 d-print-none" align="center" style="background-color: white">
                    нет операций для заданных значений
                </div>

            @else
                <?php
                $s_buildopertypeid = $search_params['s_buildopertypeid'] ?? '';
                $s_min_regnum = $search_params['s_min_regnum'] ?? '';
                $s_max_regnum = $search_params['s_max_regnum'] ?? '';
                $s_begdate = $search_params['s_begdate'] ?? '';
                $s_enddate = $search_params['s_enddate'] ?? '';

                ?>


                <div class="page p-2 " style="background-color: white">

                    <a class="btn btn-warning btn-sm print-window d-print-none float-right"
                       onclick="window.print();"
                       title="печать">
                        <i class="fa fa-print" aria-hidden="true"></i>
                    </a>

                    <div class="mt-2" align="center"
                         style="font-size: 18px;">
                        <h3>{{$thisTitle}}</h3>
                        @if(isset($s_buildopertypeid) and $s_buildopertypeid<>'')
                            <br>вид работ: <b>{{$data->buildopertype->name}}</b>
                        @endif
                        @if($s_min_regnum<>'' or $s_max_regnum<>'')
                            рег. №:
                            @if(isset($s_min_regnum) and $s_min_regnum<>'')
                                с {{$s_min_regnum}}
                            @endif
                            @if(isset($s_max_regnum) and $s_max_regnum<>'')
                                по {{$s_max_regnum}}
                            @endif
                        @endif
                        @if(isset($s_begdate) and $s_begdate<>'')
                            с {{date_format(date_create($s_begdate),'d.m.Y')}}
                        @endif
                        @if(isset($s_enddate) and $s_enddate<>'')
                            по {{date_format(date_create($s_enddate),'d.m.Y')}}
                        @endif
                        <span class="small ml-3 d-print-none"><br>по состоянию на {{now()}}</span>
                    </div>


                    <style>
                        tr:nth-child(even) .aux {
                            background: snow
                        }

                        tr:nth-child(odd) .aux {
                            background: #dfdfdf
                        }
                    </style>
                    <table class="table table-sm table-striped rep-data mt-3"
                           style="background-color: snow; font-size:16px; width:960px"
                           align=center>

                        <thead>
                        <tr>
                            <td>#</td>
                            <td>Холдинг
                                Рег. №
                            </td>
                            <td>Контрагент</td>
                            <td> №, Дата, Предмет договора</td>
                            <td> Период действия</td>
                            <td> Категория, тип</td>
                            <Td>Сумма договора, руб</Td>

                            <td>Статус</td>

                        </tr>

                        </thead>

                        <tbody>
                        <?php
                        $bgcols = array(
                            '#FeFeFe', '#EfEfEf', '#FFBFBF', '#FFcccc', '#F9F5BD', '#FCFADC'
                        , '#BFF9B9', '#ccffcc', '#79D3FF', '#CCECF9', '#CC9999', '#E2C7C7'
                        , '#aaccaa', '#bbccbb');

                        $rec0 = 1;
                        $curOwnMark = "";
                        $curOwnOrgID = -1;
                        ?>
                        @foreach($recs as $item)
                            <?php
                            $colshift = (1 - $item->active) * 2;
                            $tr_bg_col = $bgcols[$colshift + ($loop->index + $rec0) % 2];
                            $colshift = (isset($item->enddate) and $item->enddate < now()) ? 2 : 0;
                            $period_bg_col = $bgcols[$colshift + ($loop->index + $rec0) % 2];

                            $tclass = ($item->categoryid == 1) ? 'badge-success'
                                : (($item->categoryid == 2) ? 'badge-danger' : 'badge-warning');
                            ?>
                            @if($item->ownorgid<>$curOwnOrgID)
                                <tr>
                                    <td colspan="10" class="font-italic font-weight-bold"
                                        style="background-color: #fdffd1">
                                        {{$item->ownorgname}}
                                    </td>
                                </tr>
                                <?php
                                $curOwnOrgID = $item->ownorgid;
                                ?>
                            @endif
                            <tr style="background-color: {{$tr_bg_col}}">
                                <td class="small text-right" bui>
                                    {{$loop->index + $rec0}} <a name="{{$item->id}}"></a>
                                </td>
                                <td>
                                    <span style="color: #29bfa1"
                                    <a href="{{ route($thisSysObjCode.'.edit',$item->id)}}" style="">
											<span class="badge badge-pill {{$tclass}}"
                                                  style="font-size: 16px;">{{$item->regnum}}</span>
                                    </a>
                                </td>
                                {{--                                    <td></td>--}}
                                <td>
                                    @if(isset($item->controrg_lst))
                                        <?php
                                        $controrgs = explode(';', $item->controrg_lst);
                                        ?>
                                        <ul style="padding-left: 8px;" class="small">
                                            @foreach($controrgs as $controrg)
                                                <li>{{$controrg}}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        {{$item->orgname}}
                                    @endif
                                </td>
                                <td class="small text-left">
                                    {{$item->name}}
                                    №<b>{{$item->docnum}}</b> от {{date_create($item->docdate)->format('d.m.Y')}}
                                    <div class="mt-1 ml-3">
                                        <a href="{{ route($thisSysObjCode.'.edit',$item->id)}}" style="">
                                            {{$item->descript}}
                                        </a>
                                    </div>
                                    {{--										tags--}}
                                    <div class="tagcloud01 mt-2 ml-3">
                                        <ul>
                                            @foreach($item->tags as $tag)
                                                <li><a href="?s_tag={{$tag->tag}}">{{$tag->tag}}</a></li>
                                                </a>
                                            @endforeach
                                        </ul>
                                    </div>
                                    @if(isset($item->lstImageDocs))
                                        <div class="float-right">
                                            <?php
                                            $imgdoctypes = explode(';', $item->lstImageDocs);
                                            ?>
                                            <i class="fa fa-file-text-o" aria-hidden="true"></i>
                                            <ul class=" no-bullets">
                                                @foreach($imgdoctypes as $imgdoctype)
                                                    <li>- {{$imgdoctype}}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </td>
                                <td class="small text-center" style="background-color: {{$period_bg_col}}">
                                    <?php
                                    $shBegDate = (isset($item->begdate)) ? date_create($item->begdate)->format('d.m.Y') : '...';
                                    $shEndDate = (isset($item->enddate)) ? date_create($item->enddate)->format('d.m.Y') : '...';
                                    ?>
                                    {{$shBegDate}} - {{$shEndDate}}
                                </td>
                                <td class="small text-left">
                                    {{$item->categoryname}} / {{$item->contracttypename}}
                                </td>
                                <td class="small text-right">
                                    {{number_format($item->docsum,2)}}
                                </td>
                                <td>{{$statuses[$item->statusid]??'-'}}
                                    <div class="small ml-3">{{$item->status_notes}}</div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                    </table>
                    <script type="text/javascript">

                        // window.document.onload = window.print();

                        window.onafterprint = function () {
                            setTimeout(function () {
                                window.close();
                            }, 500);
                        }

                        window.onfocus = function () {
                            setTimeout(function () {
                                window.close();
                            }, 500);
                        }
                    </script>


                </div>
            @endif
        @endif


        <script src="{{ asset('js/rep23.js') }}" defer></script>

    </div>

@endsection
