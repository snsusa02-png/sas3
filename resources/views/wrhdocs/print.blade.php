@extends('layouts.app')


@section('content')
    <link href="{{  asset('css/ItemsQty.css') }}" rel="stylesheet">
    <script src="{{ asset('js/orderprint.js') }}" defer></script>
    <style>
        @media print {
            .no-print, .no-print * {
                display: none !important;
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
            @for ($i = 0; $i < 2; $i++)

                <table class="table table-bordered text-center" style="width:100%" border="1" cellspacing="0">
                    <tr class="align-middle">
                        <td class="w-50 doc_title font-weight-bold text-center"
                            style="vertical-align: middle">{{$rec->doctype->name}}</td>
                        <td class="w-25">
                            <span class=""> Дата</span>
                            <div
                                class="font-weight-bold doc_title">{{date_create($rec->docdate)->format('d.m.Y')}}</div>
                        </td>
                        <td class="w-25">
                            <span class=""> №</span>
                            <div class="font-weight-bold doc_title">{{$rec->docnum}}</div>
                        </td>
                    </tr>
                </table>

                <table class="table table-bordered text-center" style="width:100%" border="1" cellspacing="0">
                    <tr>
                        <td>{{$rec->doctype->ownorg_label}}:</td>
                        <td><b>{{$rec->ownorg->name}}</b></td>
                        <td class="text-left">
                            <span class="sm-caps">ИНН:{{$rec->ownorg->inn}} КПП:{{$rec->ownorg->kpp}}</span>
                            <br>{{$rec->ownorg->address}}
                        </td>
                    </tr>
                    <tr>
                        <td>{{$rec->doctype->wrh_label}}:</td>
                        <td><b>{{$rec->wrh->name}}</b></td>
                        <td class="text-left">{{$rec->wrh->address}}</td>
                    </tr>
                    @if(isset($rec->orgid))
                        <tr>
                            <td>{{$rec->doctype->org_label}}:</td>
                            <td><b>{{$rec->org->name}}</b></td>
                            <td class="text-left">
                                <span class="sm-caps">ИНН:{{$rec->org->inn}} КПП:{{$rec->org->kpp}}</span>
                                <br>{{$rec->org->address}}
                            </td>
                        </tr>
                    @endif
                </table>

                <table cellpadding="5" class="table-bordered w-100">
                    <tr class="text-center">
                        <td class="small" style="width:36px;">№п/п</td>
                        <td>Товар</td>
                        <td class="small">ЕИ</td>
                        <td>Кол-во, еи</td>
                        <td>Цена за еи, &#x20bd;</td>
                        <td>Сумма, &#x20bd;</td>
                        <td>Вес, кг</td>
                    </tr>
                    <?php
                    $npp = 0;
                    $totalsum = 0;
                    $ri_grossweight = 0;
                    ?>
                    @foreach($rec->items as $itm)
                        <tr>
                            <td class="small text-right">{{++$npp}}</td>
                            <td class="l">
                                {{$itm->refitem->name}}
                            </td>
                            <td class="text-center small">{{$itm->refitem->unit?:'шт'}}</td>
                            <td class="text-right">{{number_format($itm->qty, $itm->decimal_dgts)}}</td>
                            <td class="text-right">{{number_format($itm->price,2)}}</td>
                            <td class="text-right">{{number_format($itm->price * $itm->qty,2)}}</td>
                            <td class="text-right">{{trim(number_format($itm->ri_grossweight*$itm->qty,3),'0')}}</td>
                        </tr>
                        <?php
                        $totalsum += $itm->price * $itm->qty;
                        $ri_grossweight += $itm->ri_grossweight * $itm->qty;
                        ?>
                    @endforeach
                    <tr>
                        <td class="text-right" colspan="5">Итого:</td>
                        <td class="text-right"><b>{{number_format($totalsum, 2, ".","") }}</b></td>
                        <td class="text-right">{{trim(number_format($ri_grossweight,1),'0')}}</td>
                    </tr>
                </table>

                <table class="table table-borderless text-center" style="width:100%" border="0" cellspacing="0">
                    <tr>
                        <td class="w-25 text-right">&nbsp;</td>
                        <td style="border-bottom: 1px solid silver"></td>
                        <td class="w-25 text-right"></td>
                        <td style="border-bottom: 1px solid silver"></td>
                    </tr>
                </table>
                <br>

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
                }, 500);
            }

            window.onfocus = function () {
                setTimeout(function () {
                    window.close();
                }, 500);
            }
        </script>
    </div>


@endsection('content')
