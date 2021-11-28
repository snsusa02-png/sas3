@extends('layouts.edit')

<?php
$sysobjid = 1202;
$objcode = 'stforders';
$ThisTitle = "Приказ";

?>

@section('content')
    @if (!isset( $rec))
        <?php
        redirect()->route($objcode . '.index');
        header("Location:" . route($objcode . '.index'));
        die();
        ?>
    @else
        <?php
        $route_index = route($objcode . '.edit', $rec->id) . '#' . 'printform1';
        ?>


        <style>
            @media print {
                @page {
                    /*size: landscape*/
                    size: portrait;
                }

                .pagebreak0 {
                    page-break-after: always;
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

            .fnt12 {
                font-size: 12px
            }

            .fnt12b {
                font-size: 12px;
                font-weight: bold
            }

            .fnt9 {
                font-size: 9px
            }

            sup {
                top: 0.3em;
            }

        </style>
        <style type="text/css">
            .brd1blck_ltrb_bold {
                border-left: 2px solid Black;
                border-Top: 2px solid Black;
                border-right: 2px solid Black;
                border-Bottom: 2px solid Black;
            }

            .brd1blck_b {
                border-bottom: 1px solid Black;
            }

            .t0l0r0b11 {
                border-color: black black #000000;
                border-style: solid;
                border-top-width: 0px;
                border-right-width: 0px;
                border-bottom-width: 1px;
                border-left-width: 0px
            }

            .t0l1r0b11 {
                border: #000000;
                border-style: solid;
                border-top-width: 0px;
                border-right-width: 0px;
                border-bottom-width: 1px;
                border-left-width: 1px
            }

            .t0l1r1b11 {
                border: #000000;
                border-style: solid;
                border-top-width: 0px;
                border-right-width: 1px;
                border-bottom-width: 1px;
                border-left-width: 1px
            }

            .t1l1r0b11 {
                border: #000000;
                border-style: solid;
                border-top-width: 1px;
                border-right-width: 0px;
                border-bottom-width: 1px;
                border-left-width: 1px
            }

            .t1l1r1b11 {
                border: #000000;
                border-style: solid;
                border-top-width: 1px;
                border-right-width: 1px;
                border-bottom-width: 1px;
                border-left-width: 1px
            }
        </style>

        <div class="container" onload="window.print();">

            <div class="sheet">

                <table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
                    <tr>
                        <td style="width: 1cm;"></td>
                        <td>

                            <table width="100%" border="0" cellspacing="0" cellpadding="0" align="center" class="fnt12">
                                <tr class="fnt9">
                                    <td align="right">Унифицированная форма #Т-1
                                        <br>Утверждена Постановлением Госкомстата
                                        <br>России от 06.04.2001 №26
                                    </td>
                                </tr>
                                <tr>
                                    <td height="6"></td>
                                </tr>
                                <tr>
                                    <td>
                                        <table width="100%" cellspacing="0" cellpadding="1" border="0" class="fnt9">
                                            <tr>
                                                <td rowspan="3" width="*">
                                                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                                        <tr class="fnt12">
                                                            <td class="brd1blck_b">
                                                                <b>{{$rec->staff->org->fullname}}</b>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td align="center" class="fnt8"><sup>наименование
                                                                    организации</sup></td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td align="right" width="120"></td>
                                                <td align="center" width="128" class="brd1blck_ltr">Код</td>
                                            </tr>
                                            <tr>
                                                <td align="right">&nbsp;Форма по ОКУД</td>
                                                <td align="center" class="brd1blck_ltr">0301001</td>
                                            </tr>
                                            <tr>
                                                <td align="right">&nbsp;по ОКПО</td>
                                                <td align="center" class="brd1blck_ltrb">42072462</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td height="6"></td>
                                </tr>
                                <tr>
                                    <td>
                                        <table width="100%" border="0" cellspacing="0" cellpadding="2">
                                            <tr align="center" class="fnt9">
                                                <td width="279" rowspan="2"></td>
                                                <th width="94" rowspan="2" valign="bottom" class="fnt16">ПРИКАЗ&nbsp;
                                                </th>
                                                <td rowspan="2" width="24"></td>
                                                <td width="118" class="brd1blck_lt">Номер</td>
                                                <td width="127" class="brd1blck_ltr">Дата</td>
                                            </tr>
                                            <tr align="center" class="font-weight-bold">
                                                <td class="brd1blck_ltb">{{$rec->ordnum}}</td>
                                                <td class="brd1blck_ltrb">{{$rec->orddate}}</td>
                                            </tr>
                                            <tr align="center">
                                                <td class="fnt12b" colspan="5">{{$rec->ordTypeName}}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td height="6"></td>
                                </tr>

                                @if($rec->ordtype->code=="jobbeg")
                                    <tr>
                                        <td>
                                            <table width="100%" border="0" cellspacing="0" cellpadding="2">
                                                <tr align="center" class="fnt9">
                                                    <td></td>
                                                    <td></td>
                                                    <td class="brd1blck_ltr">Дата</td>
                                                </tr>
                                                <tr align="center" class="fnt12">
                                                    <td align="right"><b>Принять на работу</b>&nbsp;</td>
                                                    <td width="100" class="brd1blck_lt">с</td>
                                                    <td width="128" class="brd1blck_ltr"><b><%=BegDate%></b></td>
                                                </tr>
                                                <tr align="center" class="fnt12">
                                                    <td></td>
                                                    <td class="brd1blck_ltb">по</td>
                                                    <td class="brd1blck_ltrb"><b><%=EndDate%></b></td>
                                                </tr>
                                            </table>

                                        </td>
                                    </tr>

                                @elseif($rec->ordtype->code=="BUSNTRIP")
                                    <tr>
                                        <td>
                                            <table width="100%" border="0" cellspacing="0" cellpadding="2">
                                                <tr align="center" class="fnt12">
                                                    <td align="left">Направить в командировку:&nbsp;</td>
                                                </tr>
                                            </table>

                                        </td>
                                    </tr>

                                @elseif($rec->ordtype->code=="BONUSTRIP")

                                    <?php

                                    if ($rec->SubRsnType == 1) {
                                        //'по закону №4520-1 от 19.02.1993
                                        $OrdSubject = "В целях компенсации расходов по оплате стоимости проезда к месту
                                        использования отпуска и обратно согласно Закона РФ №4520-1 от 19.02.1993г.
                                        предоставить
                                        проезд";
                                    } elseif ($rec->SubRsnType == 2) {
                                        $OrdSubject = "Предоставить льготный проезд";
                                    } else {
                                        $OrdSubject = "Предоставить льготный проезд";
                                    }
                                    ?>
                                    ?>

                                    <tr>
                                        <td>
                                            <table width="100%" border="0" cellspacing="0" cellpadding="2">
                                                <tr align="center" class="fnt12">
                                                    <td align="left"><b>{{$OrdSubject}}</b>:&nbsp;</td>
                                                </tr>
                                            </table>

                                        </td>
                                    </tr>
                                @endif

                                <tr>
                                    <td height="6"></td>
                                </tr>
                                <tr>
                                    <td>
                                        <table width="100%" border="0" cellspacing="0" cellpadding="2">
                                            <tr align="center">
                                                <td rowspan="2" valign="bottom" class="brd1blck_b">
                                                    <b>{{$rec->staff->name}}</b>
                                                </td>
                                                <td width="128" class="brd1blck_ltr"><font class="fnt9">Табельный
                                                        номер</font></td>
                                            </tr>
                                            <tr>
                                                <td align="center" class="brd1blck_ltrb"><font
                                                        class="fnt10">&nbsp;{{$rec->staff->tabnum??'-'}}&nbsp;</font>
                                                </td>
                                            </tr>
                                            <tr align="center">
                                                <td class="fnt8"><sup>Фамилия Имя Отчество</sup></td>
                                                <td></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                @if($rec->ordtype->code=="jobbeg")
                                    <tr>
                                        <td height="6"></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <table width="100%" border="0" cellspacing="0" cellpadding="2">
                                                <tr align="center" class="fnt12">
                                                    <td width="24">в</td>
                                                    <td valign="bottom" class="brd1blck_b"><b>{{$rec->orgdep->name}}</b>
                                                    </td>
                                                </tr>
                                                <tr align="center">
                                                    <td></td>
                                                    <td class="fnt8"><sup>Наименование структурного подразделения</sup>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td height="6"></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <table width="100%" border="0" cellspacing="0" cellpadding="2">
                                                <tr align="center" class="fnt12">
                                                    <td valign="bottom" class="brd1blck_b"><b>{{$rec->post->name}}</b>
                                                    </td>
                                                </tr>
                                                <tr align="center">
                                                    <td class="fnt8"><sup>должность (специальность, профессия), разряд,
                                                            класс (категория) квалификации</sup></td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td height="6"></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <table width="100%" border="0" cellspacing="0" cellpadding="2">
                                                <tr align="center" class="fnt12">
                                                    <td valign="bottom" class="brd1blck_b">
                                                        &nbsp;<b>{{$rec->aux->jobtype}}</b>&nbsp;
                                                    </td>
                                                </tr>
                                                <tr align="center">
                                                    <td class="fnt8"><sup>условия приема на работу, характер
                                                            работы</sup>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td height="6"></td>
                                    </tr>

                                    <tr>
                                        <td>
                                            <table border="0" cellspacing="0" cellpadding="2" align="center"
                                                   class="fnt12">
                                                <tr>
                                                    <td height="24" align="right">с окладом (тарифной ставкой)&nbsp;
                                                    </td>
                                                    <td align="center" class="brd1blck_b">
                                                        &nbsp;<b>{{number_format($rec->aux->salary,2)}}
                                                            рублей</b>&nbsp;
                                                    </td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td height="24" align="right">надбавкой&nbsp;</td>
                                                    <td align="center" class="brd1blck_b">
                                                        &nbsp;<b>{{$rec->aux->bonus}}</b>&nbsp;
                                                    </td>
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <td height="24" align="right">с испытанием на срок&nbsp;</td>
                                                    <td align="center" class="brd1blck_b">
                                                        &nbsp;<b>{{$rec->aux->testterm}}</b>&nbsp;
                                                    </td>
                                                    <td class="fnt10">месяца (ев)</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                @endif

                                @if($rec->ordtype->code=="BUSNTRIP")
                                    <tr>
                                        <td height="6"></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <table width="100%" border="0" cellspacing="0" cellpadding="2">
                                                <tr align="center" class="fnt12">
                                                    <td valign="bottom" class="brd1blck_b"><b><%=PostName%></b></td>
                                                </tr>
                                                <tr align="center">
                                                    <td class="fnt8"><sup>должность (специальность, профессия), разряд,
                                                            класс (категория) квалификации</sup></td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td height="6"></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <table width="100%" border="0" cellspacing="0" cellpadding="2">
                                                <tr align="center" class="fnt12">
                                                    <td valign="bottom" class="brd1blck_b"><b><%=DepName%></b></td>
                                                </tr>
                                                <tr align="center">
                                                    <td class="fnt8"><sup>Наименование структурного подразделения</sup>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td height="6"></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <table width="100%" border="0" cellspacing="0" cellpadding="2">
                                                <tr align="center" class="fnt12">
                                                    <td valign="bottom" class="brd1blck_b">&nbsp;<b><%=Destination%></b>&nbsp;
                                                    </td>
                                                </tr>
                                                <tr align="center">
                                                    <td class="fnt8"><sup>место назначения (страна, город,
                                                            организация)</sup></td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td height="15" colspan="2"></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <table width="100%" border="0" cellspacing="0" cellpadding="2" align="left"
                                                   class="fnt12">
                                                <tr>
                                                    <td width="80" height="35" align="right">сроком на&nbsp;</td>
                                                    <td align="center" class="brd1blck_ltrb_bold">
                                                        &nbsp;<b><%=TripDays%></b>&nbsp;
                                                    </td>
                                                    <td width="75%">&nbsp;календарных дней</td>
                                                </tr>
                                                <tr>
                                                    <td height="30" colspan="3"> &nbsp;с
                                                        <b><%=formatDateTime(BegDate,1)%></b> по <b><%=formatDateTime(EndDate,1)%></b>&nbsp;
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td height="15" colspan="2"></td>
                                    </tr>
                                    <tr>
                                        <td height="24">
                                            <table width="100%" border="0" cellspacing="0" cellpadding="2"
                                                   class="fnt12">
                                                <tr>
                                                    <td width="56" height="35" class="fnt12">с целью&nbsp;</td>
                                                    <td align="center" valign="bottom" class="brd1blck_b">
                                                        &nbsp;<b><%=Goal%></b>&nbsp;
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td height="6" colspan="2"></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <table width="100%" border="0" cellspacing="0" cellpadding="2"
                                                   class="fnt12">
                                                <tr>
                                                    <td width="186" height="35" class="fnt12">Командировка за счет
                                                        средств&nbsp;
                                                    </td>
                                                    <td align="center" valign="bottom" class="brd1blck_b">&nbsp;<b><%=FinSrc%></b>&nbsp;
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                @endif

                                @if($rec->ordtype->code=="BONUSTRIP")
                                    <tr>
                                        <td height="6"></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <table width="100%" border="0" cellspacing="0" cellpadding="2">
                                                <tr align="center" class="fnt12">
                                                    <td valign="bottom" class="brd1blck_b"><b><%=PostName%></b></td>
                                                </tr>
                                                <tr align="center">
                                                    <td class="fnt8"><sup>должность (специальность, профессия), разряд,
                                                            класс (категория) квалификации</sup></td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td height="6"></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <table width="100%" border="0" cellspacing="0" cellpadding="2">
                                                <tr align="center" class="fnt12">
                                                    <td valign="bottom" class="brd1blck_b"><b><%=DepName%></b></td>
                                                </tr>
                                                <tr align="center">
                                                    <td class="fnt8"><sup>Наименование структурного подразделения</sup>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>

                                    @if($rec->aux->ExtraPerson<>"")
                                        <tr>
                                            <td height="6"></td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <table width="100%" border="0" cellspacing="0" cellpadding="2">
                                                    <tr align="center" class="fnt12">
                                                        <td valign="bottom" class="brd1blck_b">&nbsp;дополнительно
                                                            следует:
                                                            <b><%=ExtraPerson%></b>&nbsp;
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td height="15" colspan="2"></td>
                                        </tr>
                                    @else
                                        <tr>
                                            <td height="6"></td>
                                        </tr>
                                    @endif

                                    <tr>
                                        <td>
                                            <table width="100%" border="0" cellspacing="0" cellpadding="2">
                                                <tr align="center" class="fnt12">
                                                    <td valign="bottom" class="brd1blck_b">&nbsp;по маршруту:
                                                        <b><%=Route%></b>&nbsp;
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td height="15" colspan="2"></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <table width="100%" border="0" cellspacing="0" cellpadding="2">
                                                <tr align="center" class="fnt12">
                                                    <td valign="bottom" class="brd1blck_b">&nbsp;даты выезда-приезда:
                                                        <b><%=DepDate%></b>
                                                        - <b><%=ArvDate%></b>&nbsp;
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td height="15" colspan="2"></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <table width="100%" border="0" cellspacing="0" cellpadding="2">
                                                <tr align="center" class="fnt12">
                                                    <td valign="bottom" class="brd1blck_b">&nbsp;Льготный проезд считать
                                                        использованным за период: <b><%=UseBegDate%></b> - <b><%=UseEndDate%></b>&nbsp;
                                                    </td>
                                                </tr>
                                                <tr align="center">
                                                    <td class="fnt8"></td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td height="15" colspan="2"></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <table width="100%" border="0" cellspacing="0" cellpadding="2">
                                                <tr align="center" class="fnt12">
                                                    <td valign="bottom" class="brd1blck_b">&nbsp;Бухгалтерии произвести
                                                        оплату проезда в размере <b><%=formatNumber(BonusSum,2)%></b>
                                                        рублей&nbsp;
                                                    </td>
                                                </tr>
                                                <tr align="center">
                                                    <td class="fnt8"></td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>

                                @endif

                                <tr>
                                    <td height="6" colspan="2"></td>
                                </tr>
                                <tr>
                                    <td>
                                        <table width="100%" border="0" cellspacing="0" cellpadding="2"
                                               class="fnt12">
                                            <tr align="center">
                                                <td width="72" height="35" align="left" class="fnt12">Основание:
                                                </td>
                                                <td valign="bottom" class="brd1blck_b">{{$rec->reason}}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                @if($rec->ordtype->code=="jobbeg")
                                    <tr>
                                        <td height="6"></td>
                                    </tr>
                                    <tr>
                                        <td>Трудовой договор (контракт) от "&nbsp;&nbsp;&nbsp;"
                                        </td>
                                    </tr>
                                @endif

                                <tr>
                                    <td height="6"></td>
                                </tr>
                                <tr>
                                    <td height="30"></td>
                                </tr>
                                <tr>
                                    <td>
                                        <table width="100%" border="0" cellspacing="0" cellpadding="2">
                                            <tr valign="middle" class="fnt12">
                                                <td width="220" class="fnt11"><b>Руководитель организации</b></td>
                                                <td valign="bottom" class="brd1blck_b"><b>{{$rec->signer1_post}}</b></td>
                                                <td width="100" align="center" valign="bottom" class="brd1blck_b">
                                                    <b>&nbsp;</b>
                                                </td>
                                                <td align="right" valign="bottom" class="brd1blck_b">
                                                    <b>{{$rec->signer1_fio}}</b></td>
                                            </tr>
                                            <tr>
                                                <td align="center"></td>
                                                <td align="center" class="fnt8"><sup>должность</sup></td>
                                                <td align="center" class="fnt8"><sup>подпись</sup></td>
                                                <td align="center" class="fnt8"><sup>расшифровка подписи</sup></td>
                                            </tr>

                                            @if($rec->ordtype->code=="BUSNTRIP")
                                                <tr valign="middle" class="fnt12">
                                                    <td class="fnt11"><b>Согласовано:</b></td>
                                                    <td>&nbsp;</td>
                                                    <td>&nbsp;</td>
                                                    <td>&nbsp;</td>
                                                </tr>
                                                <tr>
                                                    <td height="6" colspan="4"></td>
                                                </tr>
                                                <tr valign="middle" class="fnt12">
                                                    <td width="220" class="fnt11">&nbsp;</td>
                                                    <td valign="bottom" class="brd1blck_b"><b><%=Signer2Post%></b></td>
                                                    <td width="100" align="center" valign="bottom" class="brd1blck_b">
                                                        <b>&nbsp;</b>
                                                    </td>
                                                    <td align="right" valign="bottom" class="brd1blck_b">
                                                        <b><%=Signer2FIO%></b></td>
                                                </tr>
                                                <tr>
                                                    <td align="center"></td>
                                                    <td align="center" class="fnt8"><sup>должность</sup></td>
                                                    <td align="center" class="fnt8"><sup>подпись</sup></td>
                                                    <td align="center" class="fnt8"><sup>расшифровка подписи</sup></td>
                                                </tr>
                                                <tr>
                                                    <td height="20" colspan="4"></td>
                                                </tr>
                                                <tr valign="middle" class="fnt12">
                                                    <td width="220">&nbsp;</td>
                                                    <td valign="bottom" class="brd1blck_b"><b><%=Signer3Post%></b>&nbsp;
                                                    </td>
                                                    <td width="100" align="center" valign="bottom" class="brd1blck_b">
                                                        &nbsp;
                                                    </td>
                                                    <td align="right" valign="bottom" class="brd1blck_b">
                                                        <b><%=Signer3FIO%></b>&nbsp;
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td align="center"></td>
                                                    <td align="center" class="fnt8"><sup>должность</sup></td>
                                                    <td align="center" class="fnt8"><sup>подпись</sup></td>
                                                    <td align="center" class="fnt8"><sup>расшифровка подписи</sup></td>
                                                </tr>
                                                @if($rec->Signer4ID<>"")
                                                    <tr>
                                                        <td height="20" colspan="4"></td>
                                                    </tr>
                                                    <tr valign="middle" class="fnt12">
                                                        <td width="220">&nbsp;</td>
                                                        <td valign="bottom" class="brd1blck_b">
                                                            <b><%=Signer4ID%><%=Signer4Post%></b></td>

                                                        <td width="100" align="center" valign="bottom"
                                                            class="brd1blck_b"></td>
                                                        <td align="right" valign="bottom" class="brd1blck_b">
                                                            <b><%=Signer4FIO%></b></td>
                                                    </tr>
                                                    <tr>
                                                        <td align="center"></td>
                                                        <td align="center" class="fnt8"><sup>должность</sup></td>
                                                        <td align="center" class="fnt8"><sup>подпись</sup></td>
                                                        <td align="center" class="fnt8"><sup>расшифровка подписи</sup>
                                                        </td>
                                                    </tr>
                                                @endif
                                                <tr>
                                                    <td colspan="4" height="20"></td>
                                                </tr>
                                            @endif
                                        </table>

                                    </td>
                                </tr>


                                <tr>
                                    <td height="6"></td>
                                </tr>
                                <tr>
                                    <td>
                                        <table width="100%" border="0" cellspacing="0" cellpadding="2">
                                            <tr valign="middle" class="fnt12">
                                                <td width="220" class="fnt11"><b>С приказом (распоряжением) работник
                                                        ознакомлен</b></td>
                                                <td width="100" align="center" valign="bottom" class="brd1blck_b">
                                                    <b>&nbsp;</b>
                                                </td>
                                                <td align="right" valign="bottom">&nbsp;"&nbsp;&nbsp;&nbsp;&nbsp;"
                                                    _______________
                                                    20___ года
                                                </td>
                                            </tr>
                                            <tr>
                                                <td></td>
                                                <td align="center" class="fnt8"><sup>подпись работника</sup></td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                @if($rec->ordtype->code <> "BUSNTRIP")
                                    <tr>
                                        <td height="6"></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <table border="0" cellspacing="0" cellpadding="2" class="fnt12b">
                                                <tr>
                                                    <td width="220" height="24" class="fnt11"><b>Согласовано:</b></td>
                                                    <td></td>
                                                    <td></td>
                                                </tr>
                                                <tr valign="bottom">
                                                    <td height="32">&nbsp;{{$rec->signer2_post}}&nbsp;</td>
                                                    <td width="128" class="brd1blck_b">&nbsp;&nbsp;</td>
                                                    <td>&nbsp;{{$rec->signer2_fio}}&nbsp;</td>
                                                </tr>
                                                <tr valign="bottom">
                                                    <td height="32">&nbsp;{{$rec->signer3_post}}&nbsp;</td>
                                                    <td width="128" class="brd1blck_b">&nbsp;&nbsp;</td>
                                                    <td>&nbsp;{{$rec->signer3_fio}}&nbsp;</td>
                                                </tr>
                                                @if(isset($rec->signer4id))
                                                    <tr valign="bottom">
                                                        <td height="32">&nbsp;{{$rec->signer4_post}}&nbsp;</td>
                                                        <td width="128" class="brd1blck_b">&nbsp;&nbsp;</td>
                                                        <td>&nbsp;{{$rec->signer4_fio}}&nbsp;</td>
                                                    </tr>
                                                @endif
                                            </table>
                                        </td>
                                    </tr>
                                @endif

                            </table>
                        </td>
                    </tr>
                </table>


                <a class="btn btn-warning btn-sm print-window my-2 d-print-none"
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
</script>
@endif


@if(1==0)
@endif
