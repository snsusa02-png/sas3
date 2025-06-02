@extends('layouts.app')


@section('content')
    <link href="{{  asset('css/ItemsQty.css') }}" rel="stylesheet">
    <script src="{{ asset('js/orderprint.js') }}" defer></script>
    <style>
        @media print {
            .no-print, .no-print * {
                display: none !important;
            }

            .page, .page-break {
                /*break-after: page;*/
                page-break-inside: avoid;
                page-break-before: always;
            }
        }

        .sheet {
            /*font-family: serif;*/
            font-size: 12px;
            margin-top: 10px;
            background-color: white;
            padding-left: 10px;
            padding-right: 1em;
            width: 1040px;
            /*line-height: 2.3em;*/
        }

        .doc_title {
            font-size: 1.3em;
            font-weight: bold;
            line-height: 1.3em;
        }

        .grid-container > div {
            /*border: 1px solid gray*/
        }

        .grid-container {
            min-width: 820px;
            max-width: 990px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 60px;
            grid-template-rows: 30px 30px auto 30px auto 24px;
            grid-template-areas: "ordnum_lbl ordnum ordnum QRCode" "orddate_lbl orddate orddate QRCode" "buyer_lbl buyer buyer buyer" "owner_lbl owner owner owner" "items items items items" "buttons buttons buttons buttons";
        }

        .item_spec {
            font-family: 'Times New Roman, serif';
            font-size: 13pt;
        }

        .item_spec li {
            font-family: 'Times New Roman, serif';
            font-size: 13pt;
            margin-bottom: 0cm;
            line-height: 150%
        }

        .items {
            grid-area: items;
            justify-self: center;
        }

        .buttons {
            grid-area: buttons;
            justify-self: center;
        }

        .QRCode {
            grid-area: QRCode;
        }

        .ordnum_lbl {
            grid-area: ordnum_lbl;
            justify-self: right;
            padding-right: 6px;
        }

        .orddate_lbl {
            grid-area: orddate_lbl;
            justify-self: right;
            padding-right: 6px;
        }

        .ordnum {
            grid-area: ordnum;
        }

        .orddate {
            grid-area: orddate;
        }

        .buyer_lbl {
            grid-area: buyer_lbl;
            justify-self: right;
            padding-right: 6px;
        }

        .buyer {
            grid-area: buyer;
        }

        .owner_lbl {
            grid-area: owner_lbl;
            justify-self: right;
            padding-right: 6px;
        }

        .owner {
            grid-area: owner;
        }

        .sm-caps {
            font-size: 11px;
            font-variant: small-caps;
        }

    </style>

    <div class="container">
        <div class="sheet">
            @for ($i = 0; $i < 1; $i++)

                <?php
                $npp = 0;
                $totalsum = 0;
                $ri_grossweight = 0;
                ?>
                @foreach($rec->items as $itm)
                    <DL class="page-break">
                        <DD>
                            <TABLE WIDTH=95% BORDER=2 BORDERCOLOR="#f79646" CELLPADDING=7 CELLSPACING=0 FRAME=BELOW
                                   RULES=ROWS BGCOLOR="#ffffff" style="font-family: Times New Roman, serif;">
                                <COL WIDTH=200>
{{--                                <COL WIDTH=600>--}}
                                <TR VALIGN=TOP>
                                    <TD  BGCOLOR="#ffffff">
                                        <IMG SRC="/images/logos/investstroitorg.png" ALIGN=BOTTOM WIDTH=200 BORDER=0>
                                    </TD>
                                    <TD  BGCOLOR="#ffffff">
                                        <P CLASS="western" ALIGN=CENTER STYLE="margin-bottom: 0cm"><BR>
                                        </P>
                                        <P CLASS="western" ALIGN=CENTER STYLE="margin-bottom: 0cm">
                                            <span style="COLOR:#984806;font-size: 16pt;">
                                                <B>{{$rec->saleorg->name}}</B></span></P>
                                        <P ALIGN=CENTER STYLE="margin-left: 1.27cm; margin-bottom: 0cm">
                                            <span style="COLOR:#833c0b; font-size: 12pt">
                                                {{$rec->saleorg->address}}</span></P>
                                    </TD>
                                </TR>
                                <TR>
                                    <TD COLSPAN=2 VALIGN=TOP BGCOLOR="#ffffff">
                                        <P ALIGN=CENTER STYLE="margin-bottom: 0cm">
                                            <FONT COLOR="#984806">СИСТЕМА ДОБРОВОЛЬНОЙ СЕРТИФИКАЦИИ</FONT>
                                        </P>
                                        <P ALIGN=CENTER STYLE="margin-bottom: 0cm"><FONT
                                                COLOR="#984806">&laquo;СТЭЙЛ.ОЦЕНКА
                                                И ПОДТВЕРЖДЕНИЕ СООТВЕТСТВИЯ ИСПЫТАТЕЛЬНЫХ
                                                ЛАБОРАТОРИЙ (ЦЕНТРОВ)&raquo;</FONT></P>
                                        <P ALIGN=CENTER STYLE="margin-bottom: 0cm"><FONT
                                                COLOR="#984806">Аттестат
                                                аккредитации испытательной лаборатории ООО
                                                &laquo;СПЕЦБЕТОН&raquo;</FONT></P>
                                        <P CLASS="western" ALIGN=CENTER><FONT COLOR="#984806">№ИЛ-ССК-00481
                                                от 05.05.2023 г. до 05.05.2028 г.</FONT></P>
                                    </TD>
                                </TR>
                            </TABLE>
                    </DL>
                    <P ALIGN=CENTER
                       STYLE="margin-right: -0.25cm; margin-top: 0.07cm; margin-bottom: 0cm; line-height: 115%; widows: 0; orphans: 0">
                        <BR>
                    </P>
                    <P CLASS="western" ALIGN=CENTER STYLE="text-indent: 1.25cm; margin-bottom: 0cm; line-height: 100%">
                        <span style="font-family: Times New Roman, serif; font-size: 18pt;">
                            <B>Паспорт качества № {{$itm->id}} от {{date_create($rec->docdate)->format('d.m.Y')}} </B></span>
                    </P>
                    <P CLASS="western" ALIGN=CENTER STYLE="text-indent: 1.25cm; margin-bottom: 0cm; line-height: 100%">
                        на изделия бетонные и железобетонные</P>
                    <P CLASS="western" ALIGN=CENTER STYLE="text-indent: 1.25cm; margin-bottom: 0cm; line-height: 100%">
                        <BR>
                    </P>
                    <OL class="item_spec">
                        <LI>Наименование организации &ndash; изготовителя:
                            <b>{{$rec->saleorg->name}}</b>, {{$rec->saleorg->address}}</LI>
                        <LI>Наименование организации потребителя: <b>{{$rec->org->name}}</b>, {{$rec->org->address}}
                        </LI>
                        <LI>Наименование и марка изделия : <b>{{$itm->refitem->name}}</b></li>
                        <LI>Количество изделий ({{$itm->refitem->unit?:'шт'}}):
                            <b>{{number_format($itm->qty, $itm->decimal_dgts)}}</b> {{$itm->refitem->unit?:'шт'}}</LI>
                        <div class="item_spec">
                        <?php
                            if (isset($itm->refitem->specification)) {
                                $tarr = explode(chr(13) . chr(10), $itm->refitem->specification);
                                foreach ($tarr as $elm) {
                                    echo "<li>{$elm}</li>";
                                }
                                //dd($tarr);
                            }
                            ?>
                        </div>
                    </OL>

                    <P ALIGN=JUSTIFY STYLE="margin-left: 1.27cm; line-height: 150%"></P>
                    <P ALIGN=JUSTIFY style="margin-left: 1.27cm; font-family: 'Times New Roman, Times, serif'; font-size: 13pt;">
                        <br>{{$rec->saleorg->boss_postname}} {{$rec->saleorg->name}} {{$rec->saleorg->boss_name??$rec->saleorg->boss_fullname}}
                    </P>

                @endforeach
            @endfor
        </div>

        <div class="buttons no-print">
            <a class="btn btn-close btn-info btn-sm" href="javascript:window.close();">
                <i class="fa fa-window-close-o" aria-hidden="true"></i>
                закрыть
            </a>
        </div>

        <script type="text/javascript" defer>

            window.document.onload = window.print();

            window.onafterprint = function () {
                setTimeout(function () {
                    window.close();
                }, 300);
            }

            window.onfocus = function () {
                setTimeout(function () {
                    window.close();
                }, 500);
            }
        </script>
    </div>


@endsection('content')
