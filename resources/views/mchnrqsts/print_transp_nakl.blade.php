<?php
$thisTitle = 'Транспортная накладная';
$id = request('id');
$s_docnum = request('s_docnum') ?? $rec->id;
$retURL = route('mchnrqsts.edit', $id);
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2//EN">
<HTML>
<HEAD>

    <META HTTP-EQUIV="CONTENT-TYPE" CONTENT="text/html; charset=windows-1251">
    <TITLE>Транспортная накладная</TITLE>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css"
          integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
    <STYLE>
        <!--
        BODY, DIV, TABLE, THEAD, TBODY, TFOOT, TR, TH, TD, P {
            font-family: "Arial";
            font-size: xx-small
        }

        .xs {
            font-size: 8px;
        }

        @media print {
            .pagebreak {
                page-break-after: always;
            }
        }

        -->
    </STYLE>

</HEAD>

<BODY TEXT="#000000">

<div class="row mb-3">
    <div class="col-md-3">

        <div class="params no-print card mt-3 d-print-none">
            <div class="card-header font-weight-bold">
                Параметры формы "{{$thisTitle}}"
            </div>
            <div class="card-body">

                <form name="forRep01" id="forRep01" method="post"
                      action="{{ route('mchnrqsts.print_transp_nakl', $id) }}">
                    @csrf

                    <div class="row">
                        <div class="form-group offset-md-3 col-md-6">
                            <label for="name">Номер накладной:</label>
                            <input type="text" class="form-control text-center"
                                   name="s_docnum"
                                   value="{{$s_docnum}}"
                                   required/>
                        </div>

                    </div>

                    <div style="border-top:1px solid silver;" class="mt-1 p-1">
                        <button type="submit" class="btn btn-sm btn-success"
                                {{--								formaction="{{ route('mchnrqsts.index') }}"--}}
                                formmethod="post">
                            <i class="fa fa-refresh" aria-hidden="true"></i>
                            Сформировать
                        </button>
                        <a class="btn btn-close btn-info btn-sm"
{{--                           href="{{ $retURL  }}"--}}
                                onclick="javascript:window.close();"
                        >
                            <i class="fa fa-window-close-o" aria-hidden="true"></i>
                            Закрыть
                        </a>
                        <span class="small" ml-2>
									<!-- <a href="{{route('objevntlog',['sysobjid'=>15, 'objid'=>82,'route'=>Route::current()->getName()])}}">журнал</a> -->
								</span>


                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="page" style="">
    <TABLE FRAME=VOID CELLSPACING=0 COLS=58 RULES=NONE BORDER=0 class="pagebreak" align="center"
           style="max-width:980px;">
        <COLGROUP>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=18>
            <COL WIDTH=17>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
        </COLGROUP>
        <TBODY>
        <TR>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD width="360" ALIGN=RIGHT class="xs" colspan="30">
                Приложение № 4
                <br>к Правилам перевозок грузов автомобильным транспортом
                <br>(в ред. Постановлений Правительства РФ от 30.12.2011 № 1208, от
                03.12.2015 № 1311)
                <br>Форма
            </TD>
            <TD WIDTH=12><br></td>
        </TR>
        <TR>
            <td colspan="23"/>
            <TD ALIGN=LEFT colspan="12">ТРАНСПОРТНАЯ НАКЛАДНАЯ</TD>
            <td colspan="23"/>
        </TR>
        <TR>
            <td colspan="57"><br></td>
        </TR>
        <TR>
            <TD STYLE="border-top: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=28 ALIGN=CENTER>Транспортная накладная
            </TD>
            <TD STYLE="border-top: 1px solid #000000; border-right: 1px solid #000000"
                ALIGN=center colspan="29">Заказ (заявка)
            </TD>
        </TR>
        <TR>
            <TD STYLE="border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                ALIGN=LEFT VALIGN=TOP
                colspan="28">Экземпляр №
            </TD>
            <TD STYLE="border-bottom: 1px solid #000000" ALIGN=LEFT VALIGN=TOP colspan="3">Дата</TD>
            <TD STYLE="border-bottom: 1px solid #000000; border-right: 1px solid #000000" colspan=12
                ALIGN=LEFT VALIGN=TOP><B><FONT
                            COLOR="#993300">
{{--                        {{date_format(date_create($rec->init_at), "d.m.Y")}}--}}
                        {{date_format(date_create($rec->fctbegdt), "d.m.Y")}}
                    </FONT></B></TD>
            <TD STYLE="border-bottom: 1px solid #000000; border-right: 1px solid #000000"
                ALIGN=LEFT VALIGN=TOP colspan="14">№<b>{{$s_docnum}}</b></TD>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=28 ALIGN=CENTER>
                <B>1. Грузоотправитель (грузовладелец)</B></TD>
            <TD STYLE="border-right: 1px solid #000000" colspan=29 ALIGN=CENTER><B>2. Грузополучатель</B></TD>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=26><br></td>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=27><br></td>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000" ALIGN=CENTER><BR></TD>
            <TD colspan=26 ALIGN=CENTER VALIGN=TOP class="xs">(фамилия, имя, отчество, адрес места жительства,<BR>номер
                телефона
                &ndash; для физического лица (уполномоченного лица))
            </TD>
            <TD STYLE="border-right: 1px solid #000000" ALIGN=CENTER><BR></TD>
            <TD ALIGN=CENTER><BR></TD>
            <TD colspan=27 ALIGN=CENTER VALIGN=TOP class="xs">(фамилия, имя, отчество, адрес места жительства,<BR>номер
                телефона
                &ndash; для физического лица (уполномоченного лица))
            </TD>
            <TD STYLE="border-right: 1px solid #000000" ALIGN=CENTER><BR></TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=26 ALIGN=CENTER VALIGN=TOP>
                <!-- ГРУЗООТПРАВИТЕЛЬ -->
                <B>
                    {{ ($rec->src_org->fullname<>'')?$rec->src_org->fullname : $rec->src_org->name}}
                    , {{$rec->src_org->address}}
                <!--{{$rec->car_org->fullname}}, {{$rec->car_org->address}}-->
                </B></TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=27 ALIGN=CENTER VALIGN=TOP>
                <!-- ГРУЗОПОЛУЧАТЕЛЬ -->
                <B>
                <!--{{$rec->tgtorg->fullname}}, {{$rec->tgtorg->address}}-->
                    {{ ($rec->org->fullname<>'')?$rec->org->fullname : $rec->org->name}}, {{$rec->org->address}}
                </B>
            </TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD colspan=26 ALIGN=CENTER VALIGN=TOP class="xs">(полное наименование, адрес места нахождения,<BR>номер
                телефона &ndash;
                для юридического лица)
            </TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
            <TD colspan=27 ALIGN=CENTER VALIGN=TOP class="xs">(полное наименование, адрес места нахождения,<BR>номер
                телефона &ndash;
                для юридического лица)
            </TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=57 ALIGN=CENTER>
                <B>3. Наименование груза</B></TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=57 ALIGN=CENTER><B><FONT SIZE=2>{{$rec->descript}}</FONT></B></TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=57 ALIGN=CENTER
                VALIGN=TOP class="xs">(отгрузочное наименование груза (для опасных грузов &ndash; в соответствии с
                ДОПОГ), его
                состояние и другая необходимая информация о грузе)
            </TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=57 ALIGN=CENTER><B><FONT SIZE=2>{{$rec->cargo_boxcnt}}</FONT></B></TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=57 ALIGN=CENTER
                VALIGN=TOP class="xs">(количество грузовых мест, маркировка, вид тары и способ упаковки)
            </TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=57 ALIGN=CENTER><br></TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=57 ALIGN=CENTER
                VALIGN=TOP class="xs">(масса нетто (брутто) грузовых мест в килограммах, размеры (высота, ширина и
                длина) в метрах,
                объем грузовых мест в кубических метрах)
            </TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=57><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=57 ALIGN=CENTER
                VALIGN=TOP class="xs">(в случае перевозки опасного груза &ndash; информация по каждому опасному
                веществу, материалу или
                изделию в соответствии с пунктом 5.4.1 ДОПОГ)
            </TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=57 ALIGN=CENTER>
                <B>4. Сопроводительные документы на груз</B></TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=57 ALIGN=CENTER><FONT SIZE=2>товарная накладная</FONT></TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=57 ALIGN=CENTER
                VALIGN=TOP class="xs">(перечень прилагаемых к транспортной накладной документов, предусмотренных ДОПОГ,
                санитарными,
                таможенными, карантинными, иными правилами в соответствии с законодательством Российской Федерации, либо
                регистрационные номера указанных документов, если такие документы (сведения о таких документах)
                содержатся в
                государственных информационных системах)
            </TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=57><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=57 ALIGN=CENTER
                VALIGN=TOP class="xs">(перечень прилагаемых к грузу сертификатов, паспортов качества, удостоверений,
                разрешений,
                инструкций, товарораспорядительных и других документов, наличие которых установлено законодательством
                Российской Федерации, либо регистрационные номера указанных документов, если такие документы (сведения о
                таких документах) содержатся в государственных информационных системах)
            </TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=57 ALIGN=CENTER>
                <B>5. Указания грузоотправителя</B></TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=57 ALIGN=CENTER><br></TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=57 ALIGN=CENTER
                VALIGN=TOP class="xs">(параметры транспортного средства, необходимые для перевозки груза (тип, марка,
                грузоподъемность,
                вместимость и др.))
            </TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=57><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=57 ALIGN=CENTER
                VALIGN=TOP class="xs">(указания, необходимые для выполнения фитосанитарных, санитарных, карантинных,
                таможенных и
                прочих требований, установленных законодательством Российской Федерации)
            </TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=57><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=57 ALIGN=CENTER
                class="xs">
                (рекомендации о предельных сроках и температурном режиме перевозки, сведения о запорно-пломбировочных
                устройствах (в случае их предоставления грузоотправителем), объявленная стоимость (ценность) груза,
                запрещение перегрузки груза)
            </TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=28 ALIGN=CENTER>
                <B>6. Прием груза</B></TD>
            <TD STYLE="border-right: 1px solid #000000" colspan=29 ALIGN=CENTER><B>7. Сдача груза</B></TD>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=26 ALIGN=CENTER VALIGN=MIDDLE>
                <FONT SIZE=1><b>{{$rec->src_addr}}</b></FONT></TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=27 ALIGN=CENTER VALIGN=MIDDLE BGCOLOR="#FFFFFF"><FONT
                        SIZE=1><b>{{$rec->tgt_addr}}</b></FONT></TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD colspan=26 ALIGN=CENTER VALIGN=TOP class="xs">(адрес места погрузки)</TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
            <TD colspan=27 ALIGN=CENTER VALIGN=TOP BGCOLOR="#FFFFFF" class="xs">(адрес места выгрузки)</TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=26 ALIGN=CENTER SDVAL="43815"><B><FONT
                    >{{date_format(date_create($rec->plnbegdt), "d.m.Y H:i")}}</FONT></B></TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=27 ALIGN=CENTER BGCOLOR="#FFFFFF"
            ><B>{{date_format(date_create($rec->plnenddt), "d.m.Y H:i")}}</B></TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD colspan=26 ALIGN=CENTER VALIGN=TOP class="xs">(дата и время подачи транспортного средства под
                погрузку)
            </TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
            <TD colspan=27 ALIGN=CENTER VALIGN=TOP BGCOLOR="#FFFFFF" class="xs">(дата и время подачи транспортного
                средства под
                выгрузку)
            </TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=13 ALIGN=CENTER
            >{{date_format(date_create($rec->fctbegdt), "d.m.Y H:i")}}</TD>
            <td/>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=12 ALIGN=CENTER><BR></TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=13 ALIGN=CENTER><BR>
            </TD>
            <TD ALIGN=LEFT BGCOLOR="#FFFFFF"><BR></TD>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=13
                ALIGN=CENTER>{{date_format(date_create($rec->fctenddt), "d.m.Y H:i")}}
            </TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD colspan=13 ALIGN=CENTER VALIGN=TOP class="xs">(фактические дата и время прибытия)</TD>
            <TD colspan=13 ALIGN=CENTER VALIGN=TOP class="xs">(фактические дата и время убытия)</TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
            <TD colspan=13 ALIGN=CENTER VALIGN=TOP BGCOLOR="#FFFFFF" class="xs">(фактические дата и время прибытия)</TD>
            <TD ALIGN=LEFT BGCOLOR="#FFFFFF"><BR></TD>
            <TD colspan=13 ALIGN=CENTER VALIGN=TOP BGCOLOR="#FFFFFF" class="xs">(фактические дата и время убытия)</TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=26 ALIGN=CENTER><B><I><FONT SIZE=2><BR></FONT></I></B>
            </TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=27 ALIGN=CENTER BGCOLOR="#FFFFFF"><B><I><FONT SIZE=1>Отработал
                            {{number_format($rec->fcthrs,0)}} часов</FONT></I></B></TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD colspan=26 ALIGN=CENTER VALIGN=TOP class="xs">(фактическое состояние груза, тары, упаковки, маркировки и
                опломбирования)
            </TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
            <TD colspan=27 ALIGN=CENTER VALIGN=TOP BGCOLOR="#FFFFFF" class="xs">(фактическое состояние груза, тары,
                упаковки,
                маркировки и опломбирования)
            </TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD colspan=13 ALIGN=CENTER BGCOLOR="#FFFFFF"><FONT SIZE=2><BR></FONT></TD>
            <TD ALIGN=LEFT BGCOLOR="#FFFFFF"><FONT SIZE=2><BR></FONT></TD>
            <TD colspan=10 ALIGN=CENTER><B><FONT SIZE=2><BR></FONT></B></TD>
            <TD ALIGN=LEFT BGCOLOR="#FFFFFF"><FONT SIZE=2><BR></FONT></TD>
            <TD ALIGN=LEFT BGCOLOR="#FFFFFF"><FONT SIZE=2><BR></FONT></TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=14 ALIGN=CENTER BGCOLOR="#FFFFFF"><BR></TD>
            <TD ALIGN=LEFT BGCOLOR="#FFFFFF"><BR></TD>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=12 ALIGN=CENTER><B><FONT SIZE=2><BR></FONT></B></TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD colspan=13 ALIGN=CENTER VALIGN=TOP class="xs">(масса груза)</TD>
            <td/>
            <TD colspan=12 ALIGN=CENTER VALIGN=TOP class="xs">(количество грузовых мест)</TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
            <TD colspan=14 ALIGN=CENTER VALIGN=TOP BGCOLOR="#FFFFFF" class="xs">(масса груза)</TD>
            <TD ALIGN=LEFT BGCOLOR="#FFFFFF"><BR></TD>
            <TD colspan=12 ALIGN=CENTER VALIGN=TOP BGCOLOR="#FFFFFF" class="xs">(количество грузовых мест)</TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=26 ALIGN=LEFT>
                <B>{{$rec->src_org->boss_postname??'Руководитель организации'}}
                    <span style="width:100px;display: inline-block">&nbsp;</span>
                    {{$rec->src_org->boss_name??$rec->src_org->boss_fullname}}
                </B>
            </TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=27 ALIGN=LEFT BGCOLOR="#FFFFFF"><B>
                    <!--Мастер Брыков А.В.--></B>
            </TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD colspan=26 ALIGN=CENTER VALIGN=TOP class="xs">(должность,подпись,расшифровка подписи
                грузоотправителя(уполномоченного лица))
            </TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
            <TD colspan=27 ALIGN=CENTER VALIGN=TOP BGCOLOR="#FFFFFF" class="xs">(должность, подпись, расшифровка подписи
                грузополучателя (уполномоченного лица))
            </TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000" ALIGN=CENTER><B><BR></B></TD>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=26 ALIGN=LEFT><B>Водитель {{$rec->drivername}}</B></TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=27 ALIGN=LEFT><B>Водитель {{$rec->drivername}} </B>
            </TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000" ALIGN=CENTER><B><BR></B></TD>
            <TD colspan=26 ALIGN=CENTER class="xs">(подпись, расшифровка подписи водителя, принявшего груз для
                перевозки)
            </TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
            <TD colspan=27 ALIGN=CENTER BGCOLOR="#FFFFFF" class="xs">(подпись, расшифровка подписи водителя, сдавшего
                груз)
            </TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=57 ALIGN=CENTER>
                <B>8. Условия перевозки</B></TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=57><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=57 ALIGN=CENTER
                class="xs"
                VALIGN=TOP>(сроки, по истечении которых грузоотправитель и грузополучатель вправе считать груз
                утраченным,
                форма уведомления о проведении экспертизы для определения размера фактических недостачи, повреждения
                (порчи)
                груза)
            </TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=57><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=57 ALIGN=CENTER
                class="xs"
                VALIGN=TOP>(размер платы и предельный срок хранения груза в терминале перевозчика, сроки погрузки
                (выгрузки)
                грузов, порядок предоставления и установки приспособлений, необходимых для погрузки, выгрузки и
                перевозки
                груза)
            </TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=57><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=57 ALIGN=CENTER
                class="xs"
                VALIGN=TOP>(порядок внесения в транспортную накладную записи о массе груза и способе ее определения,
                опломбирования крытых транспортных средств и контейнеров, порядок осуществления погрузо-разгрузочных
                работ,
                выполнения работ по промывке и дезинфекции транспортных средств)
            </TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=57><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=57 ALIGN=CENTER
                class="xs"
                VALIGN=TOP>(размер штрафа за невывоз груза по вине перевозчика, несвоевременное предоставление
                транспортного
                средства, контейнера и просрочку доставки груза; порядок исчисления срока просрочки)
            </TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=57><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=57 ALIGN=CENTER
                class="xs"
                VALIGN=TOP>(размер штрафа за непредъявление транспортных средств для перевозки груза, за задержку
                (простой)
                транспортных средств, поданных под погрузку, выгрузку, за простой специализированных транспортных
                средств и
                задержку (простой) контейнеров)
            </TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=57 ALIGN=CENTER>
                <B>9. Информация о принятии заказа (заявки) к исполнению</B></TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-bottom: 1px solid #000000; border-left: 1px solid #000000" colspan=9 ALIGN=center
                SDVAL="43815"><B><FONT SIZE=2
                                       COLOR="#993300">{{date_format(date_create($rec->fctbegdt), "d.m.Y")}}</FONT></B>
            </TD>
            <td/>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=38 ALIGN=CENTER><B><FONT
                            SIZE=2>
                    <!--{{$rec->drivername}}-->
                        {{$rec->car_org->name}}
                    </FONT></B></TD>
            <td/>
            <TD STYLE="border-bottom: 1px solid #000000; border-right: 1px solid #000000" colspan=8><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-bottom: 1px solid #000000; border-left: 1px solid #000000" colspan=9 ALIGN=CENTER
                VALIGN=TOP class="xs">(дата принятия заказа<BR>(заявки) к исполнению)
            </TD>
            <TD STYLE="border-bottom: 1px solid #000000" ALIGN=CENTER VALIGN=TOP><BR></TD>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=38 ALIGN=CENTER VALIGN=TOP class="xs">(фамилия, имя,
                отчество,
                должность лица, принявшего заказ (заявку) к исполнению)
            </TD>
            <TD STYLE="border-bottom: 1px solid #000000" ALIGN=CENTER VALIGN=TOP><BR></TD>
            <TD STYLE="border-bottom: 1px solid #000000; border-right: 1px solid #000000" colspan=8 ALIGN=CENTER
                VALIGN=TOP class="xs">
                (подпись)
            </TD>
            <td/>
        </TR>
        </TBODY>
    </TABLE>

    <TABLE FRAME=VOID CELLSPACING=0 COLS=58 RULES=NONE BORDER=0 class="pagebreak" align="center">
        <COLGROUP>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
            <COL WIDTH=12>
        </COLGROUP>
        <tbody>
        <TR>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=12><br></td>
            <TD WIDTH=144 ALIGN=RIGHT colspan="12" class="xs">Продолжение приложения № 4
                <br>Оборотная сторона
            </TD>
            <TD WIDTH=12><br></td>
        </TR>

        <TR>
            <TD STYLE="border-top: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=57 ALIGN=CENTER><B>10. Перевозчик</B></TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=55 ALIGN=CENTER BGCOLOR="#FFFFFF"><FONT SIZE=2
                                                                                                         COLOR="#000000"><BR></FONT>
            </TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD colspan=55 ALIGN=CENTER VALIGN=TOP>(фамилия, имя, отчество, адрес места жительства, номер телефона
                &ndash;
                для физического лица (уполномоченного лица))
            </TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=55 ALIGN=CENTER BGCOLOR="#FFFFFF"><B><FONT
                            SIZE=1>{{$rec->car_org->fullname??$rec->car_org->name}}
                        {{isset($rec->car_org->inn)?'ИНН '.$rec->car_org->inn:'' }} {{$rec->car_org->address}} </FONT></B>
            </TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD colspan=55 ALIGN=CENTER VALIGN=TOP>(наименование и адрес места нахождения, номер телефона &ndash; для
                юридического лица)
            </TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=55 ALIGN=CENTER><B><FONT
                            SIZE=2></FONT></B></TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD colspan=55 ALIGN=CENTER VALIGN=TOP>(фамилия, имя, отчество, данные о средствах связи (при их наличии)
                водителя (водителей))
            </TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=57 ALIGN=CENTER>
                <B>11. Транспортное средство</B></TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=35 ROWSPAN=3 ALIGN=CENTER><B><FONT
                            SIZE=2>{{$rec->asgnmachine->name}}</FONT></B>
            </TD>
            <td/>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=19 ROWSPAN=3 ALIGN=CENTER><B><FONT
                            SIZE=2>{{$rec->asgnmachine->regnum}}</FONT></B></TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <td/>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <td/>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD colspan=34 ALIGN=CENTER VALIGN=TOP class="xs">(количество, тип, марка, грузоподъемность (в тоннах),
                вместимость (в
                кубических метрах))
            </TD>
            <td/>
            <td/>
            <TD colspan=19 ALIGN=CENTER VALIGN=TOP class="xs">(регистрационные номера)</TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=57 ALIGN=CENTER>
                <B>12. Оговорки и замечания перевозчика</B></TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=26><br></td>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=27><br></td>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD colspan=26 ALIGN=CENTER VALIGN=TOP class="xs">(фактическое состояние груза, тары, упаковки, маркировки и
                опломбирования
                при приеме груза)
            </TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
            <TD colspan=27 ALIGN=CENTER VALIGN=TOP class="xs">(фактическое состояние груза, тары, упаковки, маркировки и
                опломбирования
                при сдаче груза)
            </TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=26><br></td>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=27><br></td>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD colspan=26 ALIGN=CENTER VALIGN=TOP class="xs">(изменение условий перевозки при движении)</TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
            <TD colspan=27 ALIGN=CENTER VALIGN=TOP class="xs">(изменение условий перевозки при выгрузке)</TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=57 ALIGN=CENTER>
                <B>13. Прочие условия</B></TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=57><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=57 ALIGN=CENTER
                VALIGN=TOP class="xs">(номер, дата и срок действия специального разрешения, установленный маршрут
                перевозки опасного,
                тяжеловесного или крупногабаритного груза)
            </TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=57><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=57 ALIGN=CENTER
                VALIGN=TOP class="xs">(режим труда и отдыха водителя в пути следования, сведения о коммерческих и иных
                актах)
            </TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=57 ALIGN=CENTER>
                <B>14. Переадресовка</B></TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=26><br></td>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=27><br></td>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD colspan=26 ALIGN=CENTER VALIGN=TOP class="xs">(дата, форма переадресовки (устно или письменно))</TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
            <TD colspan=27 ALIGN=CENTER VALIGN=TOP class="xs">(адрес нового пункта выгрузки, дата и время подачи
                транспортного средства
                под выгрузку)
            </TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=26><br></td>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=27><br></td>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD colspan=26 ALIGN=CENTER VALIGN=TOP class="xs">(сведения о лице, от которого получено указание на
                переадресовку
                (наименование, фамилия, имя, отчество и др.))
            </TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
            <TD colspan=27 ALIGN=CENTER VALIGN=TOP class="xs">(при изменении получателя груза &ndash; новое наименование
                грузополучателя и место его нахождения)
            </TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000; border-right: 1px solid #000000" colspan=57 ALIGN=CENTER>
                <B>15. Стоимость услуг перевозчика и порядок расчета провозной платы</B></TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=22 ALIGN=LEFT><B><I><FONT SIZE=2>1
                            {{$rec->fct_priceunit}}={{$rec->fct_price}} руб. {{$rec->vat_info}}</FONT></I></B></TD>
            <td/>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=31><br></td>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD colspan=22 ALIGN=CENTER VALIGN=TOP class="xs">(стоимость услуги в рублях, порядок (механизм) расчета
                (исчислений)
                платы)
            </TD>
            <td/>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD colspan=31 ALIGN=CENTER VALIGN=TOP class="xs">(расходы перевозчика и предъявляемые грузоотправителю
                платежи за проезд
                по платным автомобильным дорогам,
            </TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=31><br></td>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=22 ALIGN=CENTER><I><FONT SIZE=2>{{$rec->fct_qty}}
                        х{{$rec->fct_price}}={{$rec->fct_qty*$rec->fct_price}} руб {{$rec->vat_sum}}</FONT></I>
            </TD>
            <td/>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD colspan=31 ALIGN=CENTER VALIGN=TOP class="xs">за перевозку опасных, тяжеловесных и крупногабаритных
                грузов, уплату
                таможенных пошлин и сборов,
            </TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=22 ALIGN=LEFT class="xs">(размер провозной платы
                (заполняется после
                окончания перевозки) в рублях)
            </TD>
            <td/>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=31><br></td>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <td/>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD colspan=31 ALIGN=CENTER VALIGN=TOP class="xs">выполнение погрузо-разгрузочных работ, а также работ по
                промывке и
                дезинфекции транспортных средств)
            </TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000" ALIGN=CENTER><B><BR></B></TD>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=55 ALIGN=CENTER VALIGN=MIDDLE><B>
                    {{$rec->srcorg->name}}, {{$rec->srcorg->address}}, ИНН/КПП {{$rec->srcorg->inn}}
                    /{{$rec->srcorg->kpp}}, {{$rec->srcorg->bank_account_info}}</B></TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000" ALIGN=CENTER><B><BR></B></TD>
            <TD colspan=55 ALIGN=CENTER class="xs">(полное наименование организации плательщика (грузоотправителя),
                адрес, банковские
                реквизиты организации плательщика (грузоотправителя))
            </TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000" colspan=56 ALIGN=CENTER><B>16. Дата составления, подписи
                    сторон</B></TD>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-left: 1px solid #000000"><br></td>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=12 ALIGN=LEFT BGCOLOR="#FFFFFF">
                <!--ГРУЗООТПРАВИТЕЛЬ-->
                <B>
                <!--{{$rec->org->boss_postname}} {{$rec->org->name}}-->
                <!--{{$rec->car_org->boss_postname}}, {{$rec->car_org->name}}-->
                    {{$rec->src_org->boss_postname}}, {{ $rec->src_org->name}}

                </B>
            </TD>
            <td/>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=7 ALIGN=LEFT SDVAL="43815"><FONT
                        COLOR="#993300"></FONT></TD>
            <td/>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=6><br></td>
            <td/>
            <td/>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=12 ALIGN=LEFT BGCOLOR="#FFFFFF">
                <B>
                {{$rec->car_org->name}}
                <!--{{$rec->driver_org->name}}-->
                </B></TD>
            <TD ALIGN=LEFT BGCOLOR="#FFFFFF"><BR></TD>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=7 ALIGN=center SDVAL="43815"><FONT
                        COLOR="#993300">{{date_format(date_create($rec->fctbegdt), "d.m.Y")}}</FONT></TD>
            <td/>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=5><br></td>
            <TD STYLE="border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-bottom: 1px solid #000000; border-left: 1px solid #000000"><br></td>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=12 ALIGN=CENTER VALIGN=TOP class="xs">(грузоотправитель
                (грузовладелец) (уполномоченное лицо))
            </TD>
            <TD STYLE="border-bottom: 1px solid #000000" ALIGN=CENTER VALIGN=TOP><BR></TD>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=7 ALIGN=CENTER VALIGN=TOP class="xs">(дата)</TD>
            <TD STYLE="border-bottom: 1px solid #000000" ALIGN=CENTER VALIGN=TOP><BR></TD>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=6 ALIGN=CENTER VALIGN=TOP class="xs">(подпись)</TD>
            <TD STYLE="border-bottom: 1px solid #000000"><br></td>
            <TD STYLE="border-bottom: 1px solid #000000"><br></td>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=12 ALIGN=CENTER VALIGN=TOP BGCOLOR="#FFFFFF"
                class="xs">(перевозчик
                (уполномоченное лицо))
            </TD>
            <TD STYLE="border-bottom: 1px solid #000000" ALIGN=CENTER VALIGN=TOP BGCOLOR="#FFFFFF"><BR></TD>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=7 ALIGN=CENTER VALIGN=TOP BGCOLOR="#FFFFFF" class="xs">
                (дата)
            </TD>
            <TD STYLE="border-bottom: 1px solid #000000" ALIGN=CENTER VALIGN=TOP BGCOLOR="#FFFFFF"><BR></TD>
            <TD STYLE="border-bottom: 1px solid #000000" colspan=5 ALIGN=CENTER VALIGN=TOP BGCOLOR="#FFFFFF" class="xs">
                (подпись)
            </TD>
            <TD STYLE="border-bottom: 1px solid #000000; border-right: 1px solid #000000"><br></td>
            <td/>
        </TR>
        <TR>
            <TD colspan=57 ALIGN=CENTER><B>17. Отметки грузоотправителей, грузополучателей, перевозчиков</B></TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=28 ALIGN=CENTER VALIGN=MIDDLE>Краткое описание обстоятельств, послуживших основанием для
                отметки
            </TD>
            <TD STYLE="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=15 ALIGN=CENTER VALIGN=MIDDLE>Расчет и размер штрафа
            </TD>
            <TD STYLE="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=14 ALIGN=CENTER VALIGN=MIDDLE>Подпись, дата
            </TD>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=28><br></td>
            <TD STYLE="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=15><br></td>
            <TD STYLE="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=14><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=28><br></td>
            <TD STYLE="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=15><br></td>
            <TD STYLE="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=14><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=28><br></td>
            <TD STYLE="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=15><br></td>
            <TD STYLE="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=14><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=28><br></td>
            <TD STYLE="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=15><br></td>
            <TD STYLE="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=14><br></td>
            <td/>
        </TR>
        <TR>
            <TD STYLE="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=28><br></td>
            <TD STYLE="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=15><br></td>
            <TD STYLE="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
                colspan=14><br></td>
            <td/>
        </TR>
        </TBODY>
    </TABLE>
</div>
<!-- ************************************************************************** -->
</BODY>

</HTML>
