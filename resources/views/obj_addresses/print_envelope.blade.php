@extends('layouts.report')

@if (!isset( $rec))
    <?php
    redirect()->route('budgets.index');
    header("Location:" . route('meetings.index'));
    die();
    ?>
@else
    <?php
    $sysobjid = 1923;
    $objcode = 'obj_addresses';
    $ThisTitle = "Этикетка на конверт";

    $route_index = route($objcode . '.edit', $rec->id) . '#' . 'printform1';
    ?>

@section('content')

    <style>
        @media print {
            @page {
                /*size: 110mm 220mm;*/
                size: 220mm 110mm;
                size: landscape;
                /*size: portrait;*/
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
            /*width: 960px;*/
            width: 220mm;
            /*line-height: 2.3em;*/
        }

    </style>

    <div class="container">

        <div class="sheet">

            <div class="pagebreak000">

                <table class="" border="0" cellpadding="3" style="width: 100%; margin-top: 0mm">
                    <thead>
                    <tr class="text-center align-top small">
                        <td style="width:50%;"
                        >
                            <table class="" style="margin-left: 25mm; margin-top: 4mm; line-height: 1.2;">
                                <tr>
                                    <td>ООО "Новостроев"</td>
                                </tr>
                                <tr>
                                    <td>
                                        <div style="min-height: 13mm;">г. Владивосток, ул. Русская, д.99, офис 12</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>&nbsp;123 456 789</td>
                                </tr>
                                <tr>
                                    <td style="font-size: 1.2em">690105</td>
                                </tr>
                            </table>
                        </td>
                        <td></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
                            <table class="table-borderless" cellpadding="2" style="margin-top: 9mm; margin-left: 26mm">
                                <tr>
                                    <td>{{$rec->obj->name}}</td>
                                </tr>
                                <tr>
                                    <td>
                                        <div style="min-height: 22mm;">{{$rec->region}} {{$rec->city}} {{$rec->street_adr}}</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>&nbsp;555 12 55</td>
                                </tr>
                                <tr>
                                    <td style="font-size: 1.2em">{{$rec->zip}}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    </thead>
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

    //window.document.onload = window.print();

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
