<?php
$thisTitle = 'Справка';
$id = request('id');
$s_docnum = request('s_docnum') ?? $rec->id;
?>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css"
      integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
<style type="text/css">

    @media print {
        .pagebreak {
            page-break-after: always;
        }
    }

    body, div, table, thead, tbody, tfoot, tr, th, td, p {
        font-family: "Calibri";
        font-size: x-small
    }

    a.comment-indicator:hover + comment {
        background: #ffd;
        position: absolute;
        display: block;
        border: 1px solid black;
        padding: 0.5em;
    }

    a.comment-indicator {
        background: red;
        display: inline-block;
        border: 1px solid black;
        width: 0.5em;
        height: 0.5em;
    }

    comment {
        display: none;
    }
</style>

<body onload="window.print();">
<div class="row mb-3">
    <div class="col-md-3">

        <div class="params no-print card mt-3 d-print-none">
            <div class="card-header font-weight-bold">
                Параметры формы "{{$thisTitle}}"
            </div>
            <div class="card-body">

                <form name="forRep01" id="forRep01" method="post"
                      action="{{ route('mchnrqsts.print_esm_7', $id) }}">
                    @csrf

                    <div class="row">
                        <div class="form-group offset-md-3 col-md-6">
                            <label for="name">Номер справки:</label>
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

<table cellspacing="0" border="0">
    <colgroup width="6"></colgroup>
    <colgroup width="67"></colgroup>
    <colgroup span="15" width="24"></colgroup>
    <colgroup width="31"></colgroup>
    <colgroup width="7"></colgroup>
    <colgroup span="2" width="24"></colgroup>
    <colgroup width="16"></colgroup>
    <colgroup span="2" width="24"></colgroup>
    <colgroup width="42"></colgroup>
    <tr>
        <td height="20" align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom colspan="9"><font face="Times New Roman" size=1>Типовая межотраслевая форма №
                ЭСМ-7</font></td>
    </tr>
    <tr>
        <td height="20" align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom colspan="9"><font face="Times New Roman" size=1>Утверждена постановлением
                Госкомстата России</font></td>
    </tr>
    <tr>
        <td height="20" align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom colspan="9"><font face="Times New Roman" size=1>от 28.11.97 № 78</font></td>
    </tr>
    <tr>
        <td height="21" align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td colspan=7 align="left" valign=top><b><font face="Times New Roman" size=3>СПРАВКА № {{$s_docnum}}</font></b>
        </td>
        <td colspan=4 align="center" valign=top><b><font
                        face="Times New Roman" size=3><br></font></b></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
    </tr>
    <tr>
        <td height="20" align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom colspan="4"><b><font face="Times New Roman">для расчетов за</font></b></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
    </tr>
    <tr>
        <td height="21" align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=top colspan="7"><font face="Times New Roman"><b>выполненные работы (услуги)</b></font>
        </td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom>
        </td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td style="border-top: 1px solid #000000; border-bottom: 2px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
            colspan=6 align="center" valign=middle><font
                    face="Times New Roman">&#1050;&#1086;&#1076;</font></td>
    </tr>
    <tr>
        <td height="20" align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom><b></b></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="right" valign=bottom colspan="3"><font face="Times New Roman">Форма по ОКУД</font></td>
        <td align="left" valign=bottom></td>
        <td style="border-top: 2px solid #000000; border-bottom: 1px solid #000000; border-left: 2px solid #000000; border-right: 2px solid #000000"
            colspan=6 align="center" valign=bottom><font face="Times New Roman">0340007</font></td>
    </tr>
    <tr>
        <td height="20" align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom><b></b></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="right" valign=bottom colspan="3"><font face="Times New Roman">Дата составления</font></td>
        <td align="left" valign=bottom></td>
        <td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 2px solid #000000; border-right: 1px solid #000000"
            colspan=2 align="center" valign=bottom>{{date_format(date_create($rec->fctenddt),'d')}}</td>
        <td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000"
            colspan=3 align="center" valign=bottom>{{date_format(date_create($rec->fctenddt),'m')}}</td>
        <td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 2px solid #000000"
            align="center" valign=bottom>{{date_format(date_create($rec->fctenddt),'y')}}</td>
    </tr>
    <tr>
        <td height="20" colspan="1" align="left" valign=middle><font face="Times New Roman" size="2">Организация</font>
        </td>
        <td align="left" valign=middle></td>
        <td style="border-bottom: 1px solid #000000" colspan=14 align="left" valign=middle><font
                    face="Times New Roman" size="2">{{$ownorg->name}} {{$ownorg->address}}</font></td>
        <td align="right" valign=bottom colspan="3"><font face="Times New Roman">по ОКПО</font></td>
        <td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 2px solid #000000; border-right: 2px solid #000000"
            colspan=6 align="center" valign=bottom>{{$ownorg->okpo}}</td>
    </tr>
    <tr>
        <td height="20" align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" colspan="8" valign=middle><font face="Times New Roman" size=1>(наименование, адрес, номер
                телефона)</font></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="right" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td style="border-top: 1px solid #000000; border-bottom: 2px solid #000000; border-left: 2px solid #000000; border-right: 2px solid #000000"
            colspan=6 rowspan=2 align="center" valign=bottom><font
                    face="Times New Roman">{{$org->okpo}}</font></td>
    </tr>
    <tr>
        <td height="36" align="left" valign=middle><font face="Times New Roman" size="2">Заказчик</font></td>
        <td align="left" valign=middle></td>
        <td style="border-bottom: 1px solid #000000" colspan=14 align="left" valign=middle><font
                    face="Times New Roman" size="2">{{$org->name}} {{$org->address}}</font></td>
        <td align="right" valign=middle colspan="3"><font face="Times New Roman">по ОКПО</font></td>
    </tr>
    <tr>
        <td height="21" align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td colspan="8" align="left" valign=middle><font face="Times New Roman" size=1>(наименование, адрес, номер
                телефона)</font></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
    </tr>
    <tr>
        <td height="21" align="left" valign=middle><font face="Times New Roman" size="2">Объект</font></td>
        <td align="left" valign=bottom></td>
        <td style="border-bottom: 1px solid #000000" colspan=13 align="left" valign=middle><font
                    face="Times New Roman" size="2">{{$rec->tgt_addr}}</font></td>
        <td align="left" valign=middle></td>
        <td style="border-top: 2px double #000000; border-bottom: 2px solid #000000; border-left: 2px double #000000; border-right: 2px double #000000"
            colspan=4 rowspan=2 align="center" valign=middle><font face="Times New Roman" size=1>Код вида
                операции</font></td>
        <td style="border-top: 2px double #000000; border-bottom: 1px solid #000000; border-left: 2px double #000000; border-right: 2px double #000000"
            colspan=5 align="center" valign=middle><font face="Times New Roman" size=1>Период
                работы</font></td>
    </tr>
    <tr>
        <td height="21" align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=top colspan="4"><font face="Times New Roman" size=1>(наименование, адрес)</font></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td style="border-top: 1px solid #000000; border-bottom: 2px solid #000000; border-left: 2px double #000000; border-right: 1px solid #000000"
            colspan=3 align="center" valign=middle><font face="Times New Roman" size=1>&#1089;</font>
        </td>
        <td style="border-top: 1px solid #000000; border-bottom: 2px solid #000000; border-left: 1px solid #000000; border-right: 2px double #000000"
            colspan=2 align="center" valign=middle><font face="Times New Roman"
                                                         size=1>&#1087;&#1086;</font></td>
    </tr>
    <tr>
        <td height="21" align="left" valign=bottom><font face="Times New Roman" size="2">Машина</font></td>
        <td align="left" valign=bottom></td>
        <td style="border-bottom: 1px solid #000000" colspan=4 align="center" valign=bottom><font
                    face="Times New Roman" size="2">{{$rec->asgnmachine->mchntype->name}}</font></td>
        <td align="left" valign=bottom></td>
        <td style="border-bottom: 1px solid #000000" colspan=8 align="center" valign=bottom><font
                    face="Times New Roman" size=2>{{$rec->asgnmachine->name}}</font></td>
        <td align="left" valign=bottom></td>
        <td style="border-top: 2px solid #000000; border-bottom: 2px solid #000000; border-left: 2px solid #000000; border-right: 2px double #000000"
            colspan=4 align="center" valign=bottom></td>
        <td style="border-top: 2px solid #000000; border-bottom: 2px solid #000000; border-left: 2px double #000000; border-right: 1px solid #000000"
            colspan=3 align="center" valign=bottom>{{date_format(date_create($rec->fctbegdt),'d.m.Y')}}</td>
        <td style="border-top: 2px solid #000000; border-bottom: 2px solid #000000; border-left: 1px solid #000000; border-right: 2px solid #000000"
            colspan=2 align="center" valign=bottom>{{date_format(date_create($rec->fctenddt),'d.m.Y')}}</td>
    </tr>
    <tr>
        <td height="20" align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td colspan=4 align="center" valign=top><font face="Times New Roman"
                                                      size=1>(наименование)</font>
        </td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td colspan=6 align="center" valign=top><font face="Times New Roman"
                                                      size=1>(марка)</font></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
    </tr>
    <tr>
        <td height="20" align="left" valign=bottom colspan=4><font face="Times New Roman" size="2">Государственный
                номерной
                знак</font></td>
        <td style="border-bottom: 1px solid #000000" colspan=24 align="center" valign=bottom><font
                    face="Times New Roman" size="2"><br>{{$rec->asgnmachine->regnum}}</font></td>
    </tr>
    <tr>
        <td height="21" align="left" valign=bottom></td>
        <td align="left" valign=bottom><font face="Times New Roman" size="2">Водитель</font></td>
        <td align="left" valign=bottom></td>
        <td style="border-bottom: 1px solid #000000" colspan=22 align="left" valign=bottom><font
                    face="Times New Roman" size=2>{{$rec->drivername}}</font></td>
    </tr>
    <tr>
        <td height="20" align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td colspan=22 align="center" valign=top><font face="Times New Roman"
                                                       size=1>(фамилия, и., о.)</font>
        </td>
    </tr>
    <tr>
        <td height="21" align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="center" valign=top></td>
        <td align="center" valign=top></td>
        <td align="center" valign=top></td>
        <td align="center" valign=top></td>
        <td align="center" valign=top></td>
        <td align="center" valign=top></td>
        <td align="center" valign=top></td>
        <td align="center" valign=top></td>
        <td align="center" valign=top></td>
        <td align="center" valign=top></td>
        <td align="center" valign=top></td>
        <td align="center" valign=top></td>
        <td align="center" valign=top></td>
        <td align="center" valign=top></td>
        <td align="center" valign=top></td>
        <td align="center" valign=top></td>
        <td align="center" valign=top></td>
        <td align="center" valign=top></td>
        <td align="center" valign=top></td>
        <td align="center" valign=top></td>
        <td align="center" valign=top></td>
        <td align="center" valign=top></td>
    </tr>
    <tr>
        <td style="border-top: 2px double #000000; border-bottom: 1px solid #000000; border-left: 2px double #000000; border-right: 2px double #000000"
            colspan=13 height="21" align="center" valign=top><font face="Times New Roman" size=1>Вид работы</font></td>
        <td style="border-top: 2px double #000000; border-bottom: 1px solid #000000; border-left: 2px double #000000; border-right: 2px double #000000"
            colspan=4 rowspan=2 align="center" valign=middle><font face="Times New Roman" size=1>Отработано
                машино-часов</font></td>
        <td style="border-top: 2px double #000000; border-bottom: 1px solid #000000; border-left: 2px double #000000; border-right: 2px double #000000"
            colspan=8 align="center" valign=top><font face="Times New Roman" size=1>Стоимость, руб. коп.</font></td>
    </tr>
    <tr>
        <td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 2px double #000000; border-right: 1px solid #000000"
            colspan=9 height="20" align="center" valign=middle><font face="Times New Roman" size=1>наименование</font>
        </td>
        <td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 2px double #000000"
            colspan=4 align="center" valign=middle><font face="Times New Roman" size=1>код</font></td>
        <td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 2px double #000000; border-right: 1px solid #000000"
            colspan=5 align="center" valign=middle><font face="Times New Roman" size=1>одного
                машино-часа</font></td>
        <td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 2px double #000000"
            colspan=3 align="center" valign=middle><font face="Times New Roman" size=1>работы</font></td>
    </tr>
    <tr>
        <td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 2px double #000000; border-right: 1px solid #000000"
            colspan=9 height="21" align="center" valign=top sdval="1" sdnum="1033;"><font face="Times New Roman" size=1>1</font>
        </td>
        <td style="border-top: 1px solid #000000; border-bottom: 2px solid #000000; border-left: 1px solid #000000; border-right: 2px double #000000"
            colspan=4 align="center" valign=top sdval="2" sdnum="1033;"><font face="Times New Roman" size=1>2</font>
        </td>
        <td style="border-top: 1px solid #000000; border-bottom: 2px solid #000000; border-left: 2px double #000000; border-right: 2px double #000000"
            colspan=4 align="center" valign=top sdval="3" sdnum="1033;"><font face="Times New Roman" size=1>3</font>
        </td>
        <td style="border-top: 1px solid #000000; border-bottom: 2px solid #000000; border-left: 2px double #000000; border-right: 1px solid #000000"
            colspan=5 align="center" valign=top sdval="4" sdnum="1033;"><font face="Times New Roman" size=1>4</font>
        </td>
        <td style="border-top: 1px solid #000000; border-bottom: 2px solid #000000; border-left: 1px solid #000000; border-right: 2px double #000000"
            colspan=3 align="center" valign=top sdval="5" sdnum="1033;"><font face="Times New Roman" size=1>5</font>
        </td>
    </tr>
    <tr>
        <td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 2px double #000000; border-right: 2px solid #000000"
            colspan=9 height="68" align="left" valign=middle><font
                    face="Times New Roman">{{isset($rec->buildopertypeid) ?$rec->buildopertype->name.': ' :''}}{{$rec->descript}}</font>
        </td>
        <td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 2px solid #000000; border-right: 2px double #000000"
            colspan=4 align="center" valign=bottom></td>
        <td style="border-top: 2px solid #000000; border-bottom: 1px solid #000000; border-left: 2px double #000000; border-right: 2px double #000000"
            colspan=4 align="center" valign=bottom sdval="19" sdnum="1033;"><font face="Times New Roman" size=1
            >{{$rec->fcthrs}}</font>
        </td>
        <td style="border-top: 2px solid #000000; border-bottom: 1px solid #000000; border-left: 2px double #000000; border-right: 1px solid #000000"
            colspan=5 align="center" valign=bottom sdval="3900" sdnum="1033;0;0.00"><font face="Times New Roman"
                                                                                          size=1>{{$rec->fct_price}}</font>
        </td>
        <td style="border-top: 2px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 2px solid #000000"
            colspan=3 align="center" valign=bottom sdval="74100"><font face="Times New Roman"
                                                                       size=1>{{$rec->fcthrs*$rec->fct_price}}</font>
        </td>
    </tr>
    <tr>
        <td height="21" align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="right" valign=bottom><font face="Times New Roman" size=1>&#1048;&#1090;&#1086;&#1075;&#1086;</font>
        </td>
        <td align="left" valign=bottom></td>
        <td style="border-top: 2px solid #000000; border-bottom: 2px solid #000000; border-left: 2px double #000000; border-right: 2px double #000000"
            colspan=4 align="center" valign=bottom><font face="Times New Roman">&#1061;</font></td>
        <td style="border-top: 2px solid #000000; border-bottom: 2px solid #000000; border-left: 2px double #000000; border-right: 2px double #000000"
            colspan=4 align="center" valign=bottom sdval="19" sdnum="1033;"><font
                    face="Times New Roman">{{$rec->fcthrs}}</font></td>
        <td style="border-top: 2px solid #000000; border-bottom: 2px solid #000000; border-left: 2px double #000000; border-right: 1px solid #000000"
            colspan=5 align="center" valign=bottom><font face="Times New Roman">&#1061;</font></td>
        <td style="border-top: 2px solid #000000; border-bottom: 2px solid #000000; border-left: 1px solid #000000; border-right: 2px solid #000000"
            colspan=3 align="center" valign=bottom sdval="74100"><font face="Times New Roman"
                                                                       size=1>{{$rec->fcthrs*$rec->fct_price}}</font>
        </td>
    </tr>
    <tr>
        <td height="21" align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="right" valign=bottom colspan="5"><font face="Times New Roman" size=1>Простои по вине заказчика</font>
        </td>
        <td align="left" valign=bottom></td>
        <td style="border-top: 2px solid #000000; border-bottom: 1px solid #000000; border-left: 2px solid #000000; border-right: 2px double #000000"
            colspan=4 align="center" valign=bottom></td>
        <td style="border-top: 2px solid #000000; border-bottom: 1px solid #000000; border-left: 2px double #000000; border-right: 2px double #000000"
            colspan=4 align="center" valign=bottom></td>
        <td style="border-top: 2px solid #000000; border-bottom: 1px solid #000000; border-left: 2px double #000000; border-right: 1px solid #000000"
            colspan=5 align="center" valign=bottom></td>
        <td style="border-top: 2px solid #000000; border-bottom: 2px solid #000000; border-left: 1px solid #000000; border-right: 2px solid #000000"
            colspan=3 align="center" valign=bottom></td>
    </tr>
    <tr>
        <td height="21" align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="right" valign=bottom><font face="Times New Roman" size=1>&#1042;&#1089;&#1077;&#1075;&#1086;</font>
        </td>
        <td align="left" valign=bottom></td>
        <td style="border-top: 1px solid #000000; border-bottom: 2px solid #000000; border-left: 2px solid #000000; border-right: 2px double #000000"
            colspan=4 align="center" valign=bottom><font face="Times New Roman">&#1061;</font></td>
        <td style="border-top: 1px solid #000000; border-bottom: 2px solid #000000; border-left: 2px double #000000; border-right: 2px double #000000"
            colspan=4 align="center" valign=bottom sdval="19" sdnum="1033;"><font
                    face="Times New Roman">{{$rec->fcthrs}}</font></td>
        <td style="border-top: 1px solid #000000; border-bottom: 2px solid #000000; border-left: 2px double #000000; border-right: 1px solid #000000"
            colspan=5 align="center" valign=bottom><font face="Times New Roman">&#1061;</font></td>
        <td style="border-top: 2px solid #000000; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 2px solid #000000"
            colspan=3 align="center" valign=bottom sdval="74100"><font face="Times New Roman"
                                                                       size=1>{{$rec->fcthrs*$rec->fct_price}}</font>
        </td>
    </tr>
    <tr>
        <td height="20" align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td colspan=4 align="right" valign=bottom><font face="Times New Roman" size=1>В том числе
                НДС </font></td>
        <td align="left" valign=bottom></td>
        <td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 2px solid #000000; border-right: 2px solid #000000"
            colspan=3 align="center" valign=bottom sdval="12350"><font
                    face="Times New Roman">{{number_format($rec->fcthrs*$rec->fct_price*20/120,2)}}</font></td>
    </tr>
    <tr>
        <td height="20" align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td colspan=5 align="right" valign=bottom><font face="Times New Roman" size=1>Всего с учетом
                НДС</font></td>
        <td align="left" valign=bottom></td>
        <td style="border-top: 1px solid #000000; border-bottom: 1px solid #000000; border-left: 2px solid #000000; border-right: 2px solid #000000"
            colspan=3 align="center" valign=bottom sdval="74100"><font
                    face="Times New Roman">{{number_format($rec->fcthrs*$rec->fct_price,2)}}</font></td>
    </tr>
    <tr>
        <td colspan=2 height="20" align="left" valign=bottom><font face="Times New Roman" size=1>Отработано
                машино-часов</font></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        @php
            $result = (new \MessageFormatter('ru-RU', '{n, spellout}'))->format(['n' => $rec->fcthrs])
        .' ' .getNumEnding($rec->fcthrs, array('час', 'часа', 'часов'));

        /**
 * Функция возвращает окончание для множественного числа слова на основании числа и массива окончаний
 * @param  $number Integer Число на основе которого нужно сформировать окончание
 * @param  $endingsArray  Array Массив слов или окончаний для чисел (1, 4, 5),
 *         например array('яблоко', 'яблока', 'яблок')
 * @return String
 */
function getNumEnding($number, $endingArray)
{
    $number = $number % 100;
    if ($number>=11 && $number<=19) {
        $ending=$endingArray[2];
    }
    else {
        $i = $number % 10;
        switch ($i)
        {
            case (1): $ending = $endingArray[0]; break;
            case (2):
            case (3):
            case (4): $ending = $endingArray[1]; break;
            default: $ending=$endingArray[2];
        }
    }
    return $ending;
}
        @endphp

        <td style="border-bottom: 1px solid #000000" colspan=19 align="left" valign=bottom><b><font
                        face="Times New Roman">{{$result}}</font></b></td>
    </tr>
    <tr>
        <td height="20" align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td colspan=19 align="center" valign=top><font face="Times New Roman"
                                                       size=1>(прописью)</font>
        </td>
    </tr>
    <tr>
        <td height="20" align="left" valign=bottom></td>
        <td align="left" valign=bottom><b><font face="Times New Roman" size=1>Заказчик</font></b></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td style="border-bottom: 1px solid #000000" colspan=6 align="center" valign=bottom><font
                    face="Times New Roman">{{$org->boss_postname}} {{$org->name}}</font></td>
        <td align="left" valign=bottom></td>
        <td style="border-bottom: 1px solid #000000" colspan=3 align="center" valign=bottom><font
                    face="Times New Roman"><br></font></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td style="border-bottom: 1px solid #000000" colspan=9 align="center" valign=bottom><font
                    face="Times New Roman" size="2">{{$org->boss_name}}</font></td>
    </tr>
    <tr>
        <td height="20" align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td colspan=6 align="center" valign=middle><font face="Times New Roman"
                                                         size=1>(должность)</font>
        </td>
        <td align="center" valign=middle></td>
        <td colspan=3 align="center" valign=middle><font face="Times New Roman"
                                                         size=1>(подпись)</font>
        </td>
        <td align="left" valign=middle></td>
        <td align="left" valign=middle></td>
        <td colspan=9 align="center" valign=middle><font face="Times New Roman"
                                                         size=1>(расшифровка
                подписи)</font></td>
    </tr>
    <tr>
        <td height="20" align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
    </tr>
    <tr>
        <td height="20" align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
    </tr>
    <tr>
        <td height="20" align="left" valign=bottom></td>
        <td align="left" valign=middle><b><font face="Times New Roman" size=1>Исполнитель</font></b>
        </td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td style="border-bottom: 1px solid #000000" colspan=6 align="center" valign=bottom><font
                    face="Times New Roman" size="1">{{$ownorg->boss_postname}} {{$ownorg->name}}</font>
        </td>
        <td align="left" valign=bottom></td>
        <td style="border-bottom: 1px solid #000000" colspan=3 align="center" valign=bottom><font
                    face="Times New Roman"><br></font></td>
        <td align="left" valign=bottom></td>
        <td style="border-bottom: 1px solid #000000" colspan=10 align="center" valign=bottom><font
                    face="Times New Roman" size="2">{{$ownorg->boss_name}}</font></td>
    </tr>
    <tr>
        <td height="20" align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td colspan=6 align="center" valign=middle><font face="Times New Roman"
                                                         size=1>(должность)</font>
        </td>
        <td align="center" valign=middle></td>
        <td colspan=3 align="center" valign=middle><font face="Times New Roman"
                                                         size=1>(подпись)</font>
        </td>
        <td align="left" valign=middle></td>
        <td align="left" valign=middle></td>
        <td colspan=9 align="center" valign=middle><font face="Times New Roman"
                                                         size=1>(расшифровка
                подписи)</font></td>
    </tr>
    <tr>
        <td height="20" align="left" valign=bottom></td>
        <td align="left" valign=bottom><font face="Times New Roman"> &#1052;.&#1055;.</font></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
        <td align="left" valign=bottom></td>
    </tr>
</table>
<!-- ************************************************************************** -->
</body>
