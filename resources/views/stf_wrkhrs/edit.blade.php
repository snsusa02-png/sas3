@extends('layouts.edit')
@section('content')

    <style>
        label {
            color: gray;
            margin-bottom: 0px;
        }

        .calendar {
            display: flex;
            position: relative;
            padding: 16px;
            margin: 0 auto;
            max-width: 320px;
            background: white;
            border-radius: 4px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .month-year {
            position: absolute;
            bottom: 62px;
            right: -27px;
            font-size: 2rem;
            line-height: 1;
            font-weight: 300;
            color: #94A3B8;
            transform: rotate(90deg);
            -webkit-transform: rotate(90deg);
            -moz-transform: rotate(90deg);
            -ms-transform: rotate(90deg);
        }

        .year {
            margin-left: 4px;
            color: #CBD5E1;
        }

        .days {
            display: flex;
            flex-wrap: wrap;
            flex-grow: 1;
            margin-right: 46px;
        }

        .day-label {
            position: relative;
            flex-basis: calc(14.286% - 2px);
            margin: 1px 1px 12px 1px;
            font-weight: 700;
            font-size: 0.65rem;
            text-transform: uppercase;
            color: #1E293B;
            text-align: center;
        }

        .day {
            position: relative;
            flex-basis: calc(14.286% - 2px);
            margin: 1px;
            border-radius: 999px;
            cursor: pointer;
            font-weight: 300;
        }

        .day.dull {
            color: #94A3B8;
        }

        .day.today {
            color: #0EA5E9;
            font-weight: 600;
        }

        .day::before {
            content: '';
            display: block;
            padding-top: 100%;
        }

        .day:hover {
            background: #E0F2FE;
        }

        .day .content {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

    </style>
    @guest
        <?php
        redirect()->route('login');
        //Почемуто не страбатывает в Firefox (иногда потому добавим переходов)
        header("Location:" . route('login'));
        die();
        ?>
    @else
        @if (!isset( $rec))
            <?php
            redirect()->route('home');
            header("Location:" . route('home'));
            die();
            ?>
        @else
            <?php
            $thisSysObjId = 1212;
            $sysobjid = $thisSysObjId;
            $sysobjcode = 'stf_wrkhrs';
            $thisTitle = "Регистрация рабочего времени сотрудника";

            $retRoute = $rec->retURL;

            //для блокировки текстовых полей пользователям, не имеющим право на редактирование
            $inputReadOnly = "readonly";
            if ($usrrights['save']) $inputReadOnly = "";
            ?>

            <div class="container">

                @include('layouts.edit_msgs')

                <div class="row ">

                    <div class="col-md-9">
                        <div class="card mt-3">
                            <div class="card-header">
                                {{$thisTitle}}
                                <a class="btn btn-close btn-light btn-sm"
                                   style="float:right;"
                                   href="{{ $retRoute }}"
                                   title="Вернуться">
                                    <i class="fa fa-times" aria-hidden="true"></i>
                                </a>
                            </div>
                            <div class="card-body">

                                @include('layouts.err_msgs')

                                <form name="forEdit" id="forEdit" method="post"
                                      action="{{ route($sysobjcode.'.update', $rec->id) }}">
                                    @method('PUT')
                                    @csrf
                                    {{ Form::hidden('id', $rec->id, ['id'=>'id']) }}
                                    {{ Form::hidden('retURL', $rec->retURL, ['id'=>'retURL']) }}

                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-3">
                                            <label for="name" class="required">Учетный период:</label>
                                            @if ($usrrights['edit_date'])
                                                <input type="date" class="form-control text-center font-weight-bold"
                                                       name="begdate" id="begdate" title="Дата периода учета"
                                                       min="{{$rec->wrkdate_min}}"
                                                       max="{{today()->format('Y-m-d')}}"
                                                       value="{{old('begdate',$rec->begdate)}}"/>
                                            @else
                                                <div
                                                    class="font-weight-bold text-center">
                                                    {{date_create($rec->begdate)->format('d.m.Y')}}
                                                    {{ Form::hidden('begdate', $rec->begdate,['id'=>'begdate']) }}
                                                </div>
                                            @endif
                                        </div>

                                        <div class="form-group col-md-7 driver_info" style="">
                                            <label for="name" class="required">Сотрудник: </label>
                                            @if ($usrrights['edit'])
                                                <div class="input-group mb-3 ">
                                                    <input type="text" name="staff_name" id="staff_name"
                                                           class="staff_name form-control ac_name font-weight-bold"
                                                           value="{{old('staff_name',$rec->_obj_info)}}">
                                                    <input type="text" class="form-control text-center small ac_status"
                                                           style="display: none; border: #d7f3e3; max-width: 30px"
                                                           readonly>
                                                    <input type="hidden" name="staffid" class="ac_id" id="staffid"
                                                           value="{{old('staffid', $rec->staffid)}}">
                                                </div>
                                            @else
                                                <div class="font-weight-bold">{{$rec->_obj_info}}</div>
                                                <input type="hidden" name="staffid" id="staffid"
                                                       value="{{$rec->staffid}}">
                                            @endif
                                            <input type="hidden" name="orgid" id="orgid"
                                                   value="{{$rec->orgstaff->orgid}}">
                                        </div>
                                        <div class="form-group offset-md-0 col-md-2">
                                            <label for="day_hr_cost" class="required">Ставка, &#8381;/час:</label>
                                            @if ($usrrights['edit'])
                                                <input type="number"
                                                       class="charge_sum form-control font-weight-bold text-right"
                                                       name="day_hr_cost"
                                                       id="day_hr_cost"
                                                       value="{{ old('day_hr_cost',$rec->day_hr_cost) }}"/>
                                            @else
                                                <div
                                                    class="font-weight-bold text-right">
                                                    {{number_format($rec->day_hr_cost,2)}}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-6">
                                            <label for="name" class="">Тип деятельности:</label>
                                            {!! Form::select('opertypeid', $data->opertypes??[]
                                            , $rec->opertypeid,
                                                 [
                                                 'class' => 'form-control',
                                                 'placeholder' => '',
                                                 ]) !!}
                                        </div>
                                        <div class="form-group offset-md-0 col-md-6">
                                            <label for="name" class="">Примечание:</label>
                                            <input type="text" class="form-control" name="notes" maxlength="160"
                                                   value="{{ old('notes',$rec->notes) }}"/>
                                        </div>
                                    </div>

                                    @if( !$usrrights['edit_date'])
                                        <div class="row">
                                            <div class="col-md-12">
                                                @if(1==0)
                                                    <?php
                                                    //$date = new DateTime($rec->begdate);
                                                    $begdate = strtotime($rec->begdate);
                                                    //echo date("d", $begdate);
                                                    $d = date("d", $begdate) + 0;
                                                    $d_max = date("t", $begdate) + 0;
                                                    //echo $rec->begdate, ' ', $d_max;

                                                    $html = '';
                                                    while ($d <= 15) {
                                                        $html .= '<td class=""><div class="small">' . $d . '</div>'
                                                            . '<div class="float-right"><input type="text" style="width:48px" class="day_hr form-control font-weight-bold text-right"
                                                                   name="day_hr[]" min="0" max="24" value="' . old('day_hr', $rec->dhr[$d - 1] ?? 0) . '"></div>' . '</td>';

                                                        $d++;
                                                    }
                                                    $html = '<tr>' . $html . '</tr>';

                                                    while ($d <= $d_max) {
                                                        $html .= '<td class=""><div class="small">' . $d . '</div>'
                                                            . '<div class="float-right"><input type="text" style="width:48px" class="day_hr form-control font-weight-bold text-right"
                                                                   name="day_hr[]" min="0" max="24"  value="' . old('day_hr', $rec->dhr[$d - 1] ?? 0) . '"></div>' . '</td>';
                                                        $d++;
                                                    }
                                                    $html = '<tr>' . $html . '</tr>';
                                                    ?>
                                                    <h5>Укажите часы работы по дням месяца</h5>
                                                    <table class="table-sm table-bordered table-striped">
                                                        {!! $html !!}
                                                    </table>
                                                @endif

                                                <h5 class="text-center">Укажите часы работы по дням месяца</h5>
                                                <table class="table-sm table-bordered table-striped" align="center">
                                                    <?php
                                                    $d = strtotime("2023-10-08");
                                                    $dw = date("w", $d);
                                                    //dd($rec->begdate, $d, $dw);
                                                    //Добавление дней к дате

                                                    //                                                $date = new DateTime('2009-09-30 20:24:00');
                                                    $date = new DateTime($rec->begdate);
                                                    //echo 'date before day adding: ' . $date->format('Y-m-d H:i:s - w');
                                                    //  $date->modify('+1 day');

                                                    $date = empty($date) ? Illuminate\Support\Carbon::now() : Illuminate\Support\Carbon::createFromDate($date);
                                                    $startOfCalendar = $date->copy()->firstOfMonth()->startOfWeek(Illuminate\Support\Carbon::MONDAY);
                                                    $endOfCalendar = $date->copy()->lastOfMonth()->endOfWeek(Illuminate\Support\Carbon::SUNDAY);

                                                    $dayLabels = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
                                                    $html = '<tr>';
                                                    foreach ($dayLabels as $dayLabel) {
                                                        $html .= '<td class="day-label">' . $dayLabel . '</td>';
                                                    }
                                                    $html .= '</tr>';

                                                    while ($startOfCalendar <= $endOfCalendar) {
                                                        $extraClass = $startOfCalendar->format('m') != $date->format('m') ? 'dull' : '';

                                                        if ($startOfCalendar->format('m') != $date->format('m')) {
                                                            $html .= '<td class=""></td>';
                                                        } else {
                                                            $d = $startOfCalendar->format('d');//
                                                            $html .= '<td class=""><div class="small">' . $startOfCalendar->format('j') . '</div>'
                                                                . '<div class="float-right">'
                                                                . '<input type="number" style="width:70px" class="day_hr form-control font-weight-bold text-right"
                                                                   name="day_hr[]" min="0" max="24" step="0.5" value="' . old('day_hr', $rec->dhr[$d - 1] ?? 0) . '">'
//                                                                . '<input type="text" style="width:48px" class="night_hr form-control font-weight-bold text-right"
//                                                                   name="night_hr[]" min="0" max="24"  value="' . old('night_hr', $rec->nhr[$d - 1] ?? 0) . '">'
                                                                . '</div></td>';
                                                        }
                                                        $startOfCalendar->addDay();

                                                        if ($startOfCalendar->format('w') == 1) {
                                                            $html = '<tr>' . $html . '</tr>';
                                                        }

                                                    }
                                                    $html = '<tr>' . $html . '</tr>';

                                                    ?>
                                                    {!! $html !!}
                                                    <tr>
                                                        <td colspan="3" class="text-right">
                                                            <div class="form-group float-right">
                                                                <label for="day_hr_cost" class="">Всего,
                                                                    дней:</label>
                                                                <input type="text" style="width:96px"
                                                                       class=" form-control font-weight-bold text-right"
                                                                       name="days_tot_cnt" id="days_tot_cnt" readonly
                                                                       value="{{$rec->days_tot_cnt ?? 0}}">
                                                            </div>
                                                        </td><td colspan="2" class="text-right">
                                                            <div class="form-group float-right">
                                                                <label for="day_hr_cost" class="">Всего,
                                                                    часов:</label>
                                                                <input type="text" style="width:96px"
                                                                       class=" form-control font-weight-bold text-right"
                                                                       name="day_tot_hrs" id="day_tot_hrs" readonly
                                                                       value="{{$rec->day_tot_hrs ?? 0}}">
                                                            </div>
                                                        </td>
                                                        <td colspan="2">
                                                            <div class="form-group float-right">
                                                                <label for="day_hr_cost" class="">Всего,
                                                                    руб:</label>
                                                                <input type="text" style="width:124px"
                                                                       class=" form-control font-weight-bold text-right"
                                                                       name="day_tot_sum" id="day_tot_sum" readonly
                                                                       value="{{$rec->day_tot_sum ?? 0}}">
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    @endif

                                    <div>
                                        <hr>
                                        @if ($usrrights['save'])
                                            <button type="submit" class="btn btn-success">
                                                <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                                Сохранить
                                            </button>
                                        @endif
                                        &nbsp;
                                        <a class="btn btn-close btn-info" href="{{ $retRoute }}">
                                            <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                            Закрыть
                                        </a>
                                        &nbsp;
                                        @if ($usrrights['delete'])
                                            <a class="btn btn-danger btn-sm"
                                               style="margin-left:24px"
                                               href="{{ route($sysobjcode.'.delete', $rec->id)}}"

                                               onclick="return confirm('Вы действительно хотите удалить запись?')"
                                               title="Удалить запись"
                                            >
                                                <i class="fa fa-trash-o" aria-hidden="true"></i>
                                            </a>
                                        @endif
                                    </div>
                                </form>

                                @include('layouts._who_when')
                            </div>

                        </div>
                    </div>

                    @if($rec->id<>-1)
                        <div class="col-md-5">
                        </div>
                    @endif

                </div>
            </div>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js"
                    integrity="sha256-4iQZ6BVL4qNKlQ27TExEhBN1HFPvAvAMbFavKKosSWQ=" crossorigin="anonymous"></script>
            <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
            <script src="{{ asset('js/jquery-ui.js') }}" defer></script>
            <script src="{{ asset('js/stf_wrkhrs_edit.js') }}" defer></script>

        @endif
    @endguest
@endsection
