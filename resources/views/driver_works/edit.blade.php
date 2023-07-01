@extends('layouts.edit')

@section('content')
    @if (!isset( $rec))
        <?php
        redirect()->route('driver_works.index');
        header("Location:" . route('driver_works.index'));
        die();
        ?>
    @else
        <script src="{{ asset('js/collapse.js') }}" defer></script>

        <?php
        $sysobjid = 1108;
        $thisSysObjId = $sysobjid;
        $sysobjcode = 'driver_works';
        $ThisTitle = "Учет работы водителей";

        $route_index = route('driver_works.index') . '#item_' . $rec->id;

        $inputReadOnly = 'readonly';
        //$usrrights['edit']=false;
        ?>
        <style>
            label {
                color: gray;
                margin-bottom: 0px;
            }

            input.has-value {
                color: #00028e;
                font-weight: bold;
            }

            input[type="number"].hrs {
                width: 80px;
            }

            input.aux_hr_sum {
                background-color: aliceblue !important;
            }
        </style>
        <div class="container">

            @includeIf('layouts.edit_msgs')

            <div class="row ">
                <div class="col-md-9">
                    <div class="card p-2 my-2 my-md-3" style="background-color: #f8f8f8">
                        <div class="card-header">
                            <?php
                            $chkdate = date_format(date_create($rec->wrkdate), 'd.m.Y');
                            ?>
                            {{$ThisTitle}} "{{$chkdate}}"
                            <a class="btn btn-close btn-light btn-sm"
                               style="float:right;"
                               href="{{ $route_index }}"
                               title="Вернуться в список">
                                <i class="fa fa-times" aria-hidden="true"></i>
                            </a>
                        </div>
                        <div class="card-body">

                            @include('layouts.err_msgs')

                            <form name="forEdit" id="forEdit" method="post"
                                  action="{{ route($sysobjcode.'.update', $rec->id) }}">
                                @method('PUT')
                                @csrf
                                {{ Form::hidden('ttt', 0) }}


                                <div class="row">
                                    {{--                                    <div class="form-group offset-md-0 col-md-4">--}}
                                    {{--                                        <label for="name">Дата:</label>--}}
                                    {{--                                        @if ($usrrights['edit'])--}}
                                    {{--                                            <input type="date" class="form-control text-center font-weight-bold"--}}
                                    {{--                                                   name="wrkdate" id="wrkdate"--}}
                                    {{--                                                   min="{{$rec->wrkdate_min}}"--}}
                                    {{--                                                   max="{{today()->format('Y-m-d')}}"--}}
                                    {{--                                                   value="{{old('wrkdate',$rec->wrkdate)}}"/>--}}
                                    {{--                                        @else--}}
                                    {{--                                            <div--}}
                                    {{--                                                class="font-weight-bold">{{date_create($rec->wrkdate)->format('d.m.Y')}}--}}
                                    {{--                                                {{ Form::hidden('wrkdate', $rec->wrkdate) }}--}}
                                    {{--                                            </div>--}}
                                    {{--                                        @endif--}}
                                    {{--                                    </div>--}}
                                    <div class="form-group offset-md-0 col-md-7">
                                        <label for="name" class="required">Водитель:</label>
                                        @if ($usrrights['save'] and $usrrights['edit'])
                                            <div class="input-group mb-3 ">
                                                <input type="text" name="staff_name"
                                                       class="stfname form-control font-weight-bold"
                                                       value="{{old('staff_name',$rec->orgstaff->name)}}">
                                                <input type="text" class="form-control text-center small ac_status"
                                                       style="display: none; border: #d7f3e3; max-width: 30px" readonly>
                                                <input type="hidden" name="staffid" class="ac_id" id="staffid"
                                                       value="{{old('staffid',$rec->staffid)}}">
                                                <a class="btn btn-light id_lnk" id="driverid_lnk"
                                                   data-id="staffid" data-obj="orgstaff" target="_blank">
                                                    <i class="fa fa-info text-info" aria-hidden="true"></i>
                                                </a>
                                            </div>
                                        @else
                                            <div class="font-weight-bold">{{$rec->orgstaff->name}}</div>
                                        @endif
                                    </div>

                                    <div class="form-group offset-md-0 col-md-5">
                                        <label for="name" class="required">Тип работы:</label>
                                        @if ($usrrights['save'])
                                            {!! Form::select('wrktypeid', $rec->main_wrktypes??[], old('wrktypeid',$rec->wrktypeid),
                                             [
                                                 'id' => 'wrktypeid',
                                             'class' => 'form-control',
                                             'placeholder' => '-выбор-',
                                             'required' => 'required',
                                             ]) !!}
                                        @else
                                            <div class="font-weight-bold">{{$rec->wrktype->name}}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group offset-md-0 col-md-9">
                                        <label for="name" class="required">Спецтехника/Автомобиль:</label>
                                        @if ($usrrights['save'] and $usrrights['edit'])
                                            <div class="input-group mb-3 ">
                                                <input type="text" name="machine_name"
                                                       class="machine_name form-control font-weight-bold"
                                                       value="{{old('machine_name',$rec->machine->info)}}">
                                                <input type="text" class="form-control text-center small ac_status"
                                                       style="display: none; border: #d7f3e3; max-width: 30px" readonly>
                                                <input type="hidden" name="machineid" class="machineid" id="machineid"
                                                       value="{{old('machineid',$rec->machineid)}}">
                                                {{--                                                <input type="hidden" name="orgid" class="orgid" id="orgid"--}}
                                                {{--                                                       value="{{old('orgid',$rec->orgid)}}">--}}
                                            </div>
                                        @else
                                            <div class="font-weight-bold">{{$rec->machine->name}}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row">

                                    <div class="form-group offset-md-0 col-md-5">
                                        <label for="name" class="required">Начало:</label>
                                        @if(isset($rec->dw_id))
                                            <a href="{{route('driver_works.edit',$rec->dw_id)}}" class="float-right"
                                               style="{{$in_gk_hide}}">Отчет:
                                                >>></a>
                                        @endif
                                        @if ($usrrights['save'] and $usrrights['edit'])
                                            <div class="input-group ">
                                                <input type="date" class="form-control text-center font-weight-bold"
                                                       name="wrkdate" id="wrkdate" required
                                                       min="{{$rec->wrkdate_min}}"
                                                       max="{{today()->format('Y-m-d')}}"
                                                       value="{{old('wrkdate',$rec->wrkdate)}}"/>
                                                <div class="input-group-append">
                                                    <input type="time" name="begtime" id="begtime" required
                                                           class="form-control text-center font-weight-bold"
                                                           {{--                                                   max="{{$rec->maxtime}}"--}}
                                                           step0="300"
                                                           value="{{old('begtime',$rec->begtime)}}">
                                                </div>
                                            </div>
                                        @else
                                            <div
                                                class="font-weight-bold text-center">{{date_create($rec->wrkdate)->format('d.m.Y')}}
                                                {{$rec->begtime}}
                                                {{ Form::hidden('wrkdate', $rec->wrkdate,['id'=>'wrkdate']) }}
                                            </div>
                                        @endif
                                    </div>

                                    @if(1==1)
                                        <div class="form-group offset-md-0 col-md-5">
                                            <label for="name" class="required">Окончание:</label>
                                            @if ($usrrights['save'] and $usrrights['edit'])
                                                <div class="input-group ">
                                                    <input type="date" class="form-control text-center font-weight-bold"
                                                           name="wrkenddate" id="wrkenddate" required
                                                           {{--                                                           min="{{$rec->wrkdate_min}}"--}}
                                                           max="{{today()->format('Y-m-d')}}"
                                                           value="{{old('wrkenddate',$rec->wrkenddate)}}"/>
                                                    <div class="input-group-append">
                                                        <input type="time" name="endtime" id="endtime" required
                                                               class="form-control text-center font-weight-bold"
                                                               step0="300"
                                                               value="{{old('endtime',$rec->endtime)}}">
                                                    </div>
                                                </div>
                                            @else
                                                <div
                                                    class="font-weight-bold text-center">{{date_create($rec->wrkenddate)->format('d.m.Y')}}
                                                    {{$rec->endtime}}
                                                    {{ Form::hidden('wrkenddate', $rec->wrkenddate,['id'=>'wrkenddate']) }}
                                                </div>
                                            @endif
                                        </div>

                                        <div class="form-group col-md-2">
                                            <label>Всего, ч </label>
                                            @if ($usrrights['save'])
                                                <input type="text" name="stfwrkhrs" id="stfwrkhrs"
                                                       class="form-control text-center"
                                                       readonly value="{{$rec->stfwrkhrs}}">
                                            @else
                                                <div class="font-weight-bold text-center">{{$rec->stfwrkhrs}}</div>
                                            @endif
                                        </div>
                                    @endif

                                </div>

                                <div class="row">
                                    <div class="form-group offset-md-2 col-md-3">
                                        <label for="name" class="">Дневная смена, ч:</label>
                                        @if ($usrrights['save'] )
                                            <input type="text" name="day_hrs" id="day_hrs"
                                                   class="form-control text-center"
                                                   readonly value="{{$rec->day_hrs}}">
                                        @else
                                            <div
                                                class="font-weight-bold text-center">{{$rec->day_wrkhrs + $rec->day_brkhrs}}</div>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label>простой, ч </label>
                                        @if ($usrrights['save'])
                                            <input type="text" name="day_brkhrs" id="day_brkhrs"
                                                   readonly
                                                   class="form-control text-center"
                                                   value="{{$rec->day_brkhrs}}">
                                        @else
                                            <div class="font-weight-bold text-center">{{$rec->day_brkhrs}}</div>
                                        @endif
                                    </div>
                                    <div class="form-group offset-md-0 col-md-2">
                                        <label for="name" class="">Работа, ч:</label>
                                        @if ($usrrights['save'] )
                                            <input type="text" name="day_wrkhrs" id="day_wrkhrs"
                                                   class="form-control text-center"
                                                   readonly value="{{$rec->day_wrkhrs}}">
                                        @else
                                            <div class="font-weight-bold text-center">{{$rec->day_wrkhrs}}</div>
                                        @endif
                                    </div>
                                    <div class="form-group offset-md-0 col-md-2">
                                        <label for="name" class="">Ставка, &#8381;/ч:</label>
                                        @if ($usrrights['save'])
                                            <input type="text" name="day_hr_rate" id="day_hr_rate"
                                                   class="form-control text-center"
                                                   readonly value="{{$rec->day_hr_rate}}">
                                        @else
                                            <div class="font-weight-bold text-center">{{$rec->day_hr_rate}}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group offset-md-2 col-md-3">
                                        <label for="name" class="">Ночная смена, ч:</label>
                                        @if ($usrrights['save'])
                                            <input type="text" name="night_hrs" id="night_hrs"
                                                   class="form-control text-center"
                                                   readonly value="{{$rec->night_hrs}}">
                                        @else
                                            <div
                                                class="font-weight-bold text-center">{{$rec->night_wrkhrs + $rec->night_brkhrs}}</div>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label>простой, ч </label>
                                        @if ($usrrights['save'])
                                            <input type="text" name="night_brkhrs" id="night_brkhrs"
                                                   readonly
                                                   class="form-control text-center"
                                                   value="{{$rec->night_brkhrs}}">
                                        @else
                                            <div class="font-weight-bold text-center">{{$rec->night_brkhrs}}</div>
                                        @endif
                                    </div>
                                    <div class="form-group offset-md-0 col-md-2">
                                        <label for="name" class="">Работа, ч:</label>
                                        @if ($usrrights['save'])
                                            <input type="text" name="night_wrkhrs" id="night_wrkhrs"
                                                   class="form-control text-center"
                                                   readonly value="{{$rec->night_wrkhrs}}">
                                        @else
                                            <div class="font-weight-bold text-center">{{$rec->night_wrkhrs}}</div>
                                        @endif
                                    </div>
                                    <div class="form-group offset-md-0 col-md-2">
                                        <label for="name" class="">Ставка, &#8381;/ч:</label>
                                        @if ($usrrights['save'])
                                            <input type="text" name="night_hr_rate" id="night_hr_rate"
                                                   class="form-control text-center"
                                                   readonly value="{{$rec->night_hr_rate}}">
                                        @else
                                            <div class="font-weight-bold text-center">{{$rec->night_hr_rate}}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group offset-md-9 col-md-3">
                                        <label for="name" class="">Итого работа, &#8381;:</label>
                                        @if ($usrrights['save'])
                                            <input type="text" name="hrs_salary" id="hrs_salary"
                                                   class="form-control text-right"
                                                   readonly value="{{$rec->hrs_salary}}">
                                        @else
                                            <div class="font-weight-bold text-right">{{$rec->hrs_salary}}</div>
                                        @endif
                                    </div>
                                </div>
                                {{--                                <hr>--}}

                                В том числе простои:
                                <table class="table table-striped table-sm">
                                    <tr>
                                        <th>Вид</th>
                                        <th>Днем, ч&nbsp;&nbsp;</th>
                                        <th>Ставка, &#8381;/ч</th>
                                        <th>Ночью, ч</th>
                                        <th>Ставка, &#8381;/ч</th>
                                        <th>Сумма по часам, &#8381;</th>
                                        <th>Сумма, &#8381;</th>
                                    </tr>
                                    @foreach($rec->aux_wrk_rates as $itm)
                                        @if ($usrrights['save'] or $itm->day_hrs>0 or $itm->night_hrs>0 or $itm->aux_sum>0)
                                            <tr>
                                                <td colspan="6"><b>{{$itm->wrktype_name}}</b>
                                                    <input type="hidden" name="aux_dwi_id[]" value="{{$itm->dwi_id}}">
                                                    <input type="hidden" name="aux_wrktypeid[]"
                                                           value="{{$itm->wrktypeid}}">
                                                </td>
                                            </tr>
                                            <tr class="break_item">
                                                <td></td>
                                                <td align="center">
                                                    @if ($usrrights['save'])
                                                        <input type="number" name="aux_day_hrs[]"
                                                               class="form-control text-center hrs aux_day_hrs"
                                                               value="{{$itm->day_hrs}}"
                                                               title="Количество часов днем"
                                                               min="0" max="99" step="0.25">
                                                    @else
                                                        {{$itm->day_hrs}}
                                                    @endif
                                                </td>
                                                <td align="right">
                                                    @if ($usrrights['save'])
                                                        <input type="text" name="aux_hr_day_rate[]"
                                                               id="hr_day_rate_wt{{$itm->wrktypeid}}"
                                                               class="form-control text-center aux_hr_day_rate"
                                                               title="Ставка днем, &#8381;/ч"
                                                               readonly value="{{$itm->hr_day_rate}}">
                                                    @else
                                                        {{$itm->hr_day_rate}}
                                                    @endif
                                                </td>
                                                <td align="center">
                                                    @if ($usrrights['save'])
                                                        <input type="number" name="aux_night_hrs[]"
                                                               class="form-control text-center hrs aux_night_hrs"
                                                               value="{{$itm->night_hrs}}"
                                                               title="Количество часов ночью"
                                                               min="0" max="99" step="0.25">
                                                    @else
                                                        {{$itm->night_hrs}}
                                                    @endif
                                                </td>
                                                <td align="right">
                                                    @if ($usrrights['save'])
                                                        <input type="text" name="aux_hr_night_rate[]"
                                                               id="hr_night_rate_wt{{$itm->wrktypeid}}"
                                                               class="form-control text-center aux_hr_night_rate"
                                                               title="Ставка ночью, &#8381;/ч"
                                                               readonly value="{{$itm->hr_night_rate}}">
                                                    @else
                                                        {{$itm->hr_night_rate}}
                                                    @endif
                                                </td>
                                                <td align="right">
                                                    @if ($usrrights['save'])
                                                        <input type="text" name="aux_hr_sum"
                                                               class="form-control text-center aux_hr_sum"
                                                               title="Сумма ЗП из расчета по-часам, &#8381;"
                                                               value="{{$itm->day_hrs*$itm->hr_day_rate + $itm->night_hrs*$itm->hr_night_rate}}"
                                                               readonly>
                                                    @else
                                                        {{$itm->day_hrs*$itm->hr_day_rate + $itm->night_hrs*$itm->hr_night_rate}}
                                                    @endif
                                                </td>
                                                <td align="right">
                                                    @if ($usrrights['save'])
                                                        <input type="number" name="aux_aux_sum[]"
                                                               class="form-control text-center aux_aux_sum"
                                                               value="{{$itm->aux_sum}}"
                                                               title="Дополнительная сумма ЗП, &#8381;"
                                                               min="0">
                                                    @else
                                                        {{$itm->aux_sum}}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </table>
                                <div class="row">
                                    <div class="form-group offset-md-9 col-md-3">
                                        <label>Итого простои, &#8381;</label>
                                        @if ($usrrights['save'])
                                            <input type="text" name="breaks_sum" id="breaks_sum"
                                                   class="form-control text-right "
                                                   readonly value="{{$rec->breaks_sum}}">
                                        @else
                                            <div class="font-weight-bold text-right">{{$rec->breaks_sum}}</div>
                                        @endif
                                    </div>
                                </div>


                                <div class="row">
                                    <div class="form-group offset-md-9 col-md-3">
                                        <label>ЗП Всего, &#8381;</label>
                                        @if ($usrrights['save'])
                                            <input type="text" name="salary_sum" id="salary_sum"
                                                   class="form-control text-right font-weight-bold"
                                                   readonly value="{{$rec->salary_sum}}">
                                        @else
                                            <div class="font-weight-bold text-right">{{$rec->salary_sum}}</div>
                                        @endif
                                    </div>
                                </div>


                                <div id="accordionAux">

                                    <div class="p-2" style="background-color: #fdf2d4;">
                                        <button type="button" class="btn btn-link"
                                                style="background-color: #fffcf5;"
                                                data-toggle="collapse"
                                                data-target="#collapse_meter">
                                            <b>Показания спидометра</b>:
                                        </button>
                                        <div class="row" id="collapse_meter" class="collapse "
                                             aria-labelledby="heading_dimensions"
                                             data-parent="#accordionAux">
                                            <div class="offset-md-4 col-md-3">
                                                <div class="form-group">
                                                    <label for="category" class="">на начало, км:</label>
                                                    @if ( $usrrights['save'] and $usrrights['edit'] )
                                                        <input type="number"
                                                               class="form-control rounded-0 text-right font-weight-bold"
                                                               name="meter_begqty"
                                                               id="meter_begqty"
                                                               min=0

                                                               value="{{old('meter_begqty',$rec->meter_begqty)}}">
                                                    @else
                                                        <div
                                                            class="font-weight-bold text-center"> {{$rec->meter_begqty??'-'}}</div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="offset-md-0 col-md-3">
                                                <div class="form-group">
                                                    <label class="">по окончанию, км:</label>
                                                    @if ($usrrights['save'] and $usrrights['edit'] )
                                                        <input type="number"
                                                               class="form-control rounded-0 text-right font-weight-bold"
                                                               name="meter_endqty" id="meter_endqty"
                                                               min=0
                                                               value="{{old('meter_endqty',$rec->meter_endqty)}}">
                                                    @else
                                                        <div
                                                            class="font-weight-bold text-center">{{$rec->meter_endqty??'-'}}</div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="offset-md-0 col-md-2">
                                                <div class="form-group">
                                                    <label for="category">Пробег, км:</label>
                                                    @if ($usrrights['save'] )
                                                        <input type="number"
                                                               class="form-control rounded-0 text-right font-weight-bold"
                                                               id="meter_qty"
                                                               readonly
                                                               value="{{old('meter_qty',$rec->meter_qty)}}">
                                                    @else
                                                        <div class="font-weight-bold text-center">
                                                            {{$rec->meter_endqty??'-'}}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="p-2" style="background-color: #d8f6c3;">
                                        <button type="button" class="btn btn-link"
                                                style="background-color: #e2fdcc;"
                                                data-toggle="collapse"
                                                data-target="#collapse_fuel">
                                            <b>Топливо</b>:
                                        </button>
                                        <div class="row" id="collapse_fuel" class="collapse "
                                             aria-labelledby="heading_dimensions"
                                             data-parent="#accordionAux">
                                            <div class="offset-md-1 col-md-3">
                                                <div class="form-group">
                                                    <label for="fuel_begqty" class="">на начало, л:</label>
                                                    @if ($usrrights['save'] )
                                                        <input type="number"
                                                               class="form-control rounded-0 text-right font-weight-bold"
                                                               name="fuel_begqty"
                                                               id="fuel_begqty"
                                                               min=0

                                                               value="{{old('fuel_begqty',$rec->fuel_begqty)}}">
                                                    @else
                                                        <div class="font-weight-bold text-center">
                                                            {{$rec->fuel_begqty??'-'}}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="offset-md-0 col-md-3">
                                                <div class="form-group">
                                                    <label>получено, л:</label>
                                                    @if ($usrrights['save'] )
                                                        <input type="number"
                                                               class="form-control rounded-0 text-right font-weight-bold"
                                                               name="fuel_inpqty"
                                                               id="fuel_inpqty"
                                                               min=0
                                                               value="{{old('fuel_inpqty',$rec->fuel_inpqty)}}">
                                                    @else
                                                        <div class="font-weight-bold text-center">
                                                            {{$rec->fuel_inpqty??'0'}}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="offset-md-0 col-md-3">
                                                <div class="form-group">
                                                    <label class="">по окончанию, л:</label>
                                                    @if ($usrrights['save'] )
                                                        <input type="number"
                                                               class="form-control rounded-0 text-right font-weight-bold"
                                                               name="fuel_endqty"
                                                               id="fuel_endqty"
                                                               min=0
                                                               value="{{old('fuel_endqty',$rec->fuel_endqty)}}">
                                                    @else
                                                        <div class="font-weight-bold text-center">
                                                            {{$rec->fuel_endqty??'-'}}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="offset-md-0 col-md-2">
                                                <div class="form-group">
                                                    <label for="category">Расход, л:</label>
                                                    @if ($usrrights['save'] )
                                                        <input type="text"
                                                               class="form-control rounded-0 text-right font-weight-bold"
                                                               id="fuel_spentqty"
                                                               readonly
                                                               value="{{old('fuel_spentqty',$rec->fuel_spentqty)}}">
                                                    @else
                                                        <div class="font-weight-bold text-center">
                                                            {{$rec->fuel_spentqty??'-'}}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                {{--                                <div class="p-2" style="background-color: #d6f6f6;">--}}
                                {{--                                    <div class="row">--}}
                                {{--                                        <div class="form-group offset-md-0 col-md-12">--}}
                                {{--                                            <label for="wrk_descript">Описание работ:</label>--}}
                                {{--                                            @if ($usrrights['edit'])--}}
                                {{--                                                <textarea name="wrk_descript" class="form-control"--}}
                                {{--                                                          maxlength="360">{{old('wrk_descript',$rec->wrk_descript)}}</textarea>--}}
                                {{--                                            @else--}}
                                {{--                                                <div class="font-weight-bold">{{$rec->wrk_descript}}</div>--}}
                                {{--                                            @endif--}}
                                {{--                                        </div>--}}
                                {{--                                    </div>--}}

                                {{--                                </div>--}}


                                <div class="row">
                                    @if(1==0)
                                        <div class="form-group offset-md-0 col-md-4">
                                            <label for="address">Статус:</label>
                                            @if ($usrrights['change_status'])
                                                {!! Form::select('statusid', $rec->statuses??[], $rec->statusid,
                                                 [
                                                 'class' => 'form-control',
                                                 ]) !!}
                                            @else
                                                <div
                                                    class="font-weight-bold">{{$rec->statuses[$rec->statusid]??'-?-'}}</div>
                                            @endif
                                        </div>
                                    @endif

                                    <div class="offset-md-4 col-md-8">
                                        <div class="form-group">
                                            <label for="decision">Примечание:</label>
                                            @if ($usrrights['save'] or $usrrights['change_status'])
                                                <textarea class="form-control rounded-0"
                                                          name="notes" id="notes"
                                                          rows="1">{{old('notes',$rec->notes)}}</textarea>
                                            @else
                                                @if(!empty($rec->notes))
                                                    <div class="font-weight-bold">
                                                        <div class="font-weight-bold">{{$rec->notes??'-'}}</div>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <hr>
                                @if ($usrrights['save'] or $usrrights['change_status'])
                                    <button type="submit" class="btn btn-success" title="Сохранить изменения"
                                            name="update">
                                        <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                        Сохранить
                                    </button>
                                @endif
                                &nbsp;
                                <a class="btn btn-close btn-info" href="{{ $route_index }}"
                                   title="Вернуться в список">
                                    <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                    Закрыть
                                </a>
                                @if ($rec->id != -1 and $usrrights['delete'])
                                    <a class="btn btn-danger btn-sm"
                                       style="margin-left:24px"
                                       href="{{ route($sysobjcode.'.delete', $rec->id)}}"

                                       onclick="return confirm('Вы действительно хотите удалить запись?')"
                                       title="Удалить запись"
                                    >
                                        <i class="fa fa-trash-o" aria-hidden="true"></i>
                                    </a>
                                @endif
                                @if ($rec->id != -1 and $usrrights['make_template']??true)
                                    <button type="submit"
                                            class="btn btn-info btn-sm"
                                            style="margin-left:24px"
                                            formaction="{{ route($sysobjcode . '.make_template', $rec->id)}}"
                                            formmethod="get"
                                            onclick="return confirm('Создать шаблон для новых записей на основе данных текущей записи?')"
                                            title="Создать шаблон на основе данных записи"
                                    >
                                        <i class="fa fa-clone" aria-hidden="true"></i>
                                    </button>

                                    @if(isset($rec->template_id))
                                        <a href="{{ route('user_templates.delete', $rec->template_id)}}?returl={{Request::url()}}"
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Удалить текущий шаблон?')"
                                           title="Удалить текущий шаблон"
                                        >
                                            <i class="fa fa-minus-circle" aria-hidden="true"></i>
                                        </a>
                                    @endif
                                @endif
                                @include('layouts._who_when')
                            </form>
                        </div>
                    </div>
                </div>
                @if($rec->id<>-1)
                    <div class="col-md-3">
                        @include('driver_works._raids')
                        {{--                        @include('driver_works._breaks')--}}
                        {{--                        @include('objfiles.obj_files')--}}
                        {{--                        @include('obj_readers._readers')--}}
                    </div>
                @endif
            </div>
        </div>

        <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
        <script src="{{ asset('js/jquery-ui.js') }}" defer></script>
        <script src="{{ asset('js/driver_works_edit.js') }}" defer></script>

    @endif
@endsection
