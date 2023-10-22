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

            <div class="container-fluid">

                @include('layouts.edit_msgs')

                <div class="row ">

                    <div class="col-md-12">
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
                                        <div class="form-group col-md-4 driver_info" style="">
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
                                            <label for="name" class="required">Дата:</label>
                                            @if ($usrrights['edit'])
                                                <input type="date" class="form-control text-center font-weight-bold"
                                                       name="docdate" id="docdate"
                                                       min="{{$rec->wrkdate_min}}"
                                                       max="{{today()->format('Y-m-d')}}"
                                                       value="{{old('docdate',$rec->docdate)}}"/>
                                            @else
                                                <div
                                                    class="font-weight-bold text-center">
                                                    {{date_create($rec->docdate)->format('d.m.Y')}}
                                                    {{ Form::hidden('docdate', $rec->docdate,['id'=>'docdate']) }}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="form-group offset-md-0 col-md-1">
                                            <label for="charge_sum" class="required">Ставка, &#8381;/час:</label>
                                            @if ($usrrights['edit'])
                                                <input type="number"
                                                       class="charge_sum form-control font-weight-bold text-right"
                                                       name="charge_sum"
                                                       value="{{ old('day_hr_cost',$rec->day_hr_cost) }}"/>
                                            @else
                                                <div
                                                    class="font-weight-bold text-right">
                                                    {{number_format($rec->day_hr_cost,2)}}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="form-group offset-md-0 col-md-5">
                                            <label for="name" class="">Примечание:</label>
                                            <input type="text" class="form-control" name="notes" maxlength="160"
                                                   value="{{ old('notes',$rec->notes) }}"/>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <?php
                                            $date = new DateTime($rec->begdate);
                                            //$date = new DateTime("2023-10-01");

                                            $date = empty($date) ? Illuminate\Support\Carbon::now() : Illuminate\Support\Carbon::createFromDate($date);
                                            $startOfCalendar = $date->copy()->firstOfMonth()->startOfWeek(Illuminate\Support\Carbon::MONDAY);
                                            $endOfCalendar = $date->copy()->lastOfMonth()->endOfWeek(Illuminate\Support\Carbon::SUNDAY);

                                            $html = '<div class="calendar">';

                                            $html .= '<div class="month-year">';
                                            $html .= '<span class="month">' . $date->format('M') . '</span>';
                                            $html .= '<span class="year">' . $date->format('Y') . '</span>';
                                            $html .= '</div>';

                                            $html .= '<div class="days">';

                                            $dayLabels = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
                                            foreach ($dayLabels as $dayLabel) {
                                                $html .= '<span class="day-label">' . $dayLabel . '</span>';
                                            }

                                            while ($startOfCalendar <= $endOfCalendar) {
                                                $extraClass = $startOfCalendar->format('m') != $date->format('m') ? 'dull' : '';
                                                $extraClass .= $startOfCalendar->isToday() ? ' today' : '';

                                                $html .= '<span class="day ' . $extraClass . '"><span class="content">' . $startOfCalendar->format('j')
//                                                    . '<input type="text">'
                                                    . '</span></span>';
                                                $startOfCalendar->addDay();
                                            }
                                            $html .= '</div></div>';
                                            ?>
                                            {!! $html !!}

                                            <table class="table-sm table-bordered table-striped">
                                                <?php
                                                //$d = date("Y-m-d", strtotime($rec->begdate));
                                                //$d = strtotime($rec->begdate);
                                                $d = strtotime("2023-10-08");
                                                $dw = date("w", $d);
                                                //dd($rec->begdate, $d, $dw);
                                                //Добавление дней к дате

                                                //                                                $date = new DateTime('2009-09-30 20:24:00');
                                                $date = new DateTime($rec->begdate);
                                                echo 'date before day adding: ' . $date->format('Y-m-d H:i:s - w');
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
                                                        $d =  $startOfCalendar->format('d');
                                                        $html .= '<td class=""><div class="small">' . $startOfCalendar->format('j') . '</div>'
                                                            . '<div class="float-right"><input type="text" style="width:48px" class="day_hr form-control font-weight-bold text-right"
                                                                   name="day_hr[]" min="0" max="24"  value="' .old('day_hr',$rec->dhr[$d-1]??0) .'"></div>'.'</td>';
                                                    }
                                                    $startOfCalendar->addDay();

                                                    if($startOfCalendar->format('w')==1){
                                                        $html = '<tr>' . $html . '</tr>';
                                                    }

                                                }
                                                $html = '<tr>' . $html . '</tr>';

                                                ?>
                                                    {!! $html !!}
                                            </table>

{{--                                            <table class="table-sm">--}}
{{--                                                <tr>--}}
{{--                                                    @for ($d = 1; $d <= $rec->endday; $d++)--}}
{{--                                                        <td class="text-center">{{$d}}</td>--}}
{{--                                                    @endfor--}}
{{--                                                </tr>--}}
{{--                                                <tr>--}}
{{--                                                    <?php--}}

{{--                                                    ?>--}}
{{--                                                    @for ($d = 1; $d <= $rec->endday; $d++)--}}
{{--                                                        <td><input type="text"--}}
{{--                                                                   class="day_hr form-control font-weight-bold text-right"--}}
{{--                                                                   name="day_hr[]" min="0" max="24"--}}
{{--                                                                   value="{{ old('day_hr',$rec->dhr[$d-1]??0) }}"/></td>--}}
{{--                                                    @endfor--}}
{{--                                                </tr>--}}
{{--                                            </table>--}}
                                        </div>

                                    </div>

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
            <script src="{{ asset('js/stf_charge_calc_edit.js') }}" defer></script>

        @endif
    @endguest
@endsection
