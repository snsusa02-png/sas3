@extends('layouts.report')
@section('content')
    <?php
    $thisTitle = "График производства работ " . $prodplan->name
        . " " . date_create($prodplan->mindate)->format('d.m.Y')
        . " - " . date_create($prodplan->maxdate)->format('d.m.Y');
    //    $thisSysObjCode = 'invoices';
    //    $thisSysObjId = 855;    //reports
    //    $thisObjId = 25;
    $retURL = route('prodplans.edit', $prodplan->id);
    ?>


    <link rel="stylesheet" href="/css/tags.css">
    <style>
        @media print {
            @page {
                size: landscape;
                /*size: portrait;*/
            }

            .pagebreak0 {
                page-break-after: always;
            }

            .pagebreak {
                page-break-inside: avoid;
                page-break-before: always;
            }

            /*Печать фона*/
            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact;
            }
        }

        body {
            position: relative;
        }

        .table-wrapper {
            overflow-x: scroll;
            overflow-y: visible;
            width: 100%;
            margin-left: 70px;
        }

        td, th {
            /*padding: 5px 20px;*/
            /*width: 182px;*/
            /*height: 48px;*/
        }

        tbody tr {
        }

        th:first-child {
            position: absolute;
            left: 5px
        }

        .table-data {
            background-color: white;
            font-size: 10px;
            /*border: 1px solid gray;*/
            /*width: 100%;*/
        }

        .table-data tr {
            vertical-align: top;
        }

        .table-data .title {
            height: 1.5em;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .table-data .ym {
            text-align: center;
        }

        .table-data .cell {
            min-width: 2.0em;
            max-width: 2.0em;
            text-align: center;
        }

        .table-data .work {
            background-color: #fa755e;
        }
    </style>

    <div class="container-fluid" onload="window.print();">
        <div class="mt-3 p-3" style="background-color: snow">
            {{--            <span class="small ml-5"> по состоянию на {{now()}}</span>--}}
            <a class="btn btn-warning btn-sm print-window my-2 d-print-none"
               onclick="window.print();"
               title="печать">
                <i class="fa fa-print" aria-hidden="true"></i>
            </a>

            <a class="btn btn-close btn-light btn-sm float-right d-print-none"
               style="float:right;"
               href="{{ route('prodplans.edit',$prodplan->id) }}"
               title="Вернуться">
                <i class="fa fa-times" aria-hidden="true"></i>
            </a>

            <div class="table-wrapper1">
                <b>{{$thisTitle}}</b>
                <table class="table-data" border="1">
                    <tr>
                        <td rowspan="2">Наименование работ</td>
                        <td rowspan="2" class="text-center">Объем, ЕИ</td>
                        <td rowspan="2" class="text-center">Сумма, &#8381;</td>
                        <?php
                        $mindate = date_create($prodplan->mindate);
                        $maxdate = date_create($prodplan->maxdate);
                        $date = clone $mindate;
                        //dd($date, $mindate, $maxdate);
                        $tdate = $date->format('Y-m-d');
                        $totSum = 0;
                        $cur_parid = -1;
                        ?>
                        @while ($date <= $maxdate)
                            <?php
                            //последний день текущего месяца
                            $ldm = date("Y-m-t", strtotime($date->format('Y-m-d')));
                            //dd($ldm, $maxdate);
                            if (date_create($ldm) > $maxdate) {
                                $ldm = $maxdate->format('Y-m-d');
                            }

                            $month_days = (strtotime($ldm) - strtotime($date->format('Y-m-d'))) / 3600 / 24 + 1;
                            //dd($ldm,$month_days);
                            ?>
                            <td class="ym"
                                colspan="{{$month_days}}">{{$prodplan->monthes[$date->format('n')]??$date->format('m')}} {{$date->format('Y')}}</td>
                            <?php
                            $date = date_create($ldm)->modify('+1 day');
                            $tdate = $date->format('Y-m-d');
                            ?>
                        @endwhile
                    </tr>
                    <tr>

                        <?php
                        $date = clone $mindate;
                        ?>
                        @while ($date<=$maxdate)
                            <td class="cell">{{$date->format('d')}}</td>
                            <?php
                            $date->modify('+1 day');
                            ?>
                        @endwhile

                    </tr>

                    @foreach($items as $itm)
                        @if($itm->parid<>$cur_parid)
                            <tr>
                                <td colspan="{{$prodplan->days+3}}"
                                    class="text-center font-weight-bold font-italic">{{$itm->par_name}}</td>
                            </tr>
                            <?php
                            $cur_parid = $itm->parid;
                            ?>
                        @endif

                        <?php
                        //дней перед началом работ
                        $days_before = (strtotime($itm->begdate) - strtotime($prodplan->mindate)) / 3600 / 24;
                        //$days_before = ($days_before < 0) ? 0 : $days_before;
                        //dd($days_before, $itm->begdate, $prodplan->mindate);

                        //дней на работу
                        $days_work = (strtotime($itm->enddate) - strtotime($itm->begdate)) / 3600 / 24 + 1;

                        //дней после окончания работ
                        $days_after = (strtotime($prodplan->maxdate) - strtotime($itm->enddate)) / 3600 / 24;
                        //$days_after = ($days_after < 0) ? 0 : $days_after;

                        //dd($itm->begdate, $itm->enddate, $prodplan->mindate, $days_before, $days_work, $days_after);
                        ?>
                        <tr>
                            <td class="title1">
                                <div class="title" title="{{$itm->name}}"> {{$itm->name}}</div>
                            </td>
                            <td class="text-right">
                                {{(float)$itm->plnqty}}<span class="ml-1 small text-secondary">{{$itm->unit}}</span>
                            </td>
                            <td class="text-right">
                                {{number_format($itm->plncost,2)}}
                            </td>
                            @for ($i = 0; $i < $days_before; $i++)
                                <td class="cell"></td>
                            @endfor
                            @for ($i = 0; $i < $days_work; $i++)
                                <td class="cell work"></td>
                            @endfor
                            @for ($i = 0; $i < $days_after; $i++)
                                <td class="cell"></td>
                            @endfor
                        </tr>
                        <?php
                        $totSum += $itm->plncost;
                        ?>
                    @endforeach
                    <tr>
                        <td colspan="2" class="text-right">Всего:</td>
                        <td class="text-right font-weight-bold">{{number_format($totSum,2)}}</td>
                        <td colspan="{{$prodplan->days}}"></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>




    <script src="{{ asset('js/rep23.js') }}" defer></script>

    </div>

@endsection
