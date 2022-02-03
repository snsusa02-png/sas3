@extends('layouts.report')

@if (!isset( $rec))
    <?php
    redirect()->route('budgets.index');
    header("Location:" . route('meetings.index'));
    die();
    ?>
@else
    <?php
    $sysobjid = 151;
    $objcode = 'contracts';
    $ThisTitle = "Исполнение договора";

    $route_index = route($objcode . '.edit', $rec->id) . '#' . 'printform1';
    ?>

@section('content')

    <style>
        @media print {
            @page {
                /*size: landscape*/
                size: portrait;
            }

            .pagebreak {
                page-break-inside: avoid;
                page-break-before: always;
            }
        }

        .sheet {
            font-family: serif;
            font-size: 18px;
            margin-top: 10px;
            background-color: white;
            padding-left: 1em;
            padding-right: 1em;
            width: 960px;
            /*line-height: 2.3em;*/
        }

        h2 {
            margin: 0 auto 1em;
            text-align: center;
        }

        .sup {
            vertical-align: super;
            font-size: xx-small;
            text-align: center;
        }

        .bb1 {
            border-bottom: 1px solid black;
        }

        .grafa::before {
            content: "\00A0";
        }

        .grafa::after {
            content: "\00A0";
        }

        .grafa {
            text-decoration: underline;
            font-weight: bold;
            padding: 0.5em;
            white-space: normal;
        }

        .this_rqst {
            background-color: #e1f0c6;
        }

        .column {
            -webkit-column-width: 300px;
            -moz-column-width: 300px;
            column-width: 300px;
            -webkit-column-count: 2;
            -moz-column-count: 2;
            column-count: 2;
            -webkit-column-gap: 30px;
            -moz-column-gap: 30px;
            column-gap: 30px;
            -webkit-column-rule: 1px solid #ccc;
            -moz-column-rule: 1px solid #ccc;
            column-rule: 1px solid #ccc;
        }

        .column3 {
            -webkit-column-width: 200px;
            -moz-column-width: 200px;
            column-width: 200px;
            -webkit-column-count: 2;
            -moz-column-count: 2;
            column-count: 2;
            -webkit-column-gap: 30px;
            -moz-column-gap: 30px;
            column-gap: 30px;
            /*-webkit-column-rule: 1px solid #ccc;*/
            /*-moz-column-rule: 1px solid #ccc;*/
            /*column-rule: 1px solid #ccc;*/
        }
    </style>

    <div class="container">

        <div class="sheet">

            <div class="pagebreak000">
                <div class="mt-3">

                    <div class="text-center" style="font-family: Arial, Helvetica, sans-serif">
                        <span style="font-size: 1.1em;">Исполнение по документам <br>"{{$rec->info}}"</span>
                        <div class="text-right" style="font-size: 0.8em">по данным
                            на {{now()->format('d.m.Y H:i')}}</div>
                    </div>

                </div>

                <table class="table-bordered p-1" style="width: 100%;">
                    <thead>
                    <tr class="text-center align-top small">
                        <td>#</td>
                        <td class="text-center">Дата док-та</td>
                        <td class="text-left">Документ (Основание)</td>
                        <td class="text-right">Сумма исполн., руб</td>
                        <td class="text-right">Сумма поставок, руб</td>
                        <td class="text-right">Сумма давал., руб</td>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;

                    $totDocSum = 0;
                    $totDoc1Sum = 0;
                    $totDoc2Sum = 0;
                    $totDoc3Sum = 0;

                    $botDocSum = 0;
                    $botDoc1Sum = 0;
                    $botDoc2Sum = 0;
                    $botDoc3Sum = 0;

                    $curBuildOperTypeid = -1;
                    $doctypes = \App\contract_exe::doctypes();
                    ?>
                    @foreach($rec->contract_exes as $itm)
                        @if($itm->buildopertypeid<>$curBuildOperTypeid)

                            @if($curBuildOperTypeid<>-1)
                                <tr class="small">
                                    <td colspan="2" class="text-right">Итого по виду работ:</td>
                                    <td class="font-weight-bold text-right">{{number_format($botDocSum,2)}}</td>
                                    <td class="font-weight-bold text-right">{{number_format($botDoc1Sum,2)}}</td>
                                    <td class="font-weight-bold text-right">{{number_format($botDoc3Sum,2)}}</td>
                                    <td class="font-weight-bold text-right">{{number_format($botDoc2Sum,2)}}</td>
                                    <td></td>
                                </tr>
                            @endif
                            <tr>
                                <td colspan="5" class="font-weight-bold font-italic">
                                    {{$itm->buildopertypename}}
                                </td>
                            </tr>
                            <?php
                            $curBuildOperTypeid = $itm->buildopertypeid;
                            $botDocSum = 0;
                            $botDoc1Sum = 0;
                            $botDoc2Sum = 0;
                            $botDoc3Sum = 0;
                            ?>
                        @endif

                        <?php
                        $npp++;
                        $botDocSum += $itm->docsum;
                        $totDocSum += $itm->docsum;

                        $doc1Sum = '-';
                        $doc2Sum = '-';
                        $doc3Sum = '-';
                        if ($itm->doctypeid == 1) {
                            $doc1Sum = number_format($itm->docsum, 2);
                            $botDoc1Sum += $itm->docsum;
                            $totDoc1Sum += $itm->docsum;
                        } elseif ($itm->doctypeid == 2) {
                            $doc2Sum = number_format($itm->docsum, 2);;
                            $botDoc2Sum += $itm->docsum;
                            $totDoc2Sum += $itm->docsum;
                        } elseif ($itm->doctypeid == 3) {
                            $doc3Sum = number_format($itm->docsum, 2);;
                            $botDoc3Sum += $itm->docsum;
                            $totDoc3Sum += $itm->docsum;
                        }
                        ?>

                        <tr class="align-top ">
                            <td class="small text-right">{{$loop->iteration}}</td>
                            <td class="text-center small" style="">
                                {{date_create($itm->docdate)->format('d.m.Y')}}
                            </td>
                            <td class="text-left " style="">
                                <span class="small">{{$doctypes[$itm->doctypeid]??'n/a'}}</span> / {{$itm->docinfo}}
                            </td>
                            <td class="text-right small" style="">
                                {{$doc1Sum}}
                            </td>
                            <td class="text-right small" style="">
                                {{$doc3Sum}}
                            </td>
                            <td class="text-right small" style="">
                                {{$doc2Sum}}
                            </td>
                        </tr>
                    @endforeach
                    @if($curBuildOperTypeid<>-1)
                        <tr class="small">
                            <td colspan="2" class="text-right">Итого по виду работ:</td>
                            <td class="font-weight-bold text-right">{{number_format($botDocSum,2)}}</td>
                            <td class="font-weight-bold text-right">{{number_format($botDoc1Sum,2)}}</td>
                            <td class="font-weight-bold text-right">{{number_format($botDoc3Sum,2)}}</td>
                            <td class="font-weight-bold text-right">{{number_format($botDoc2Sum,2)}}</td>
                        </tr>
                    @endif
                    <tr>
                        <td colspan="2" class="text-right">Всего:</td>
                        <td class="font-weight-bold text-right ">{{number_format($totDocSum,2)}}</td>
                        <td class="font-weight-bold text-right pl-1">{{number_format($totDoc1Sum,2)}}</td>
                        <td class="font-weight-bold text-right pl-1">{{number_format($totDoc3Sum,2)}}</td>
                        <td class="font-weight-bold text-right pl-1">{{number_format($totDoc2Sum,2)}}</td>
                    </tr>
                    </tbody>
                </table>


            </div>

            <a class="btn btn-warning btn-sm print-window mt-2 d-print-none"
               onclick="window.print();"
               title="печать">
                <i class="fa fa-print" aria-hidden="true"></i>
            </a>

        </div>
    </div>
@endsection
<script type="text/javascript">
    //$(document).ready(function() { window.print(); });

    window.document.onload = window.print();

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
@endif
