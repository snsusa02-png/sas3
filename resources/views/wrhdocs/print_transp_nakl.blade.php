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

        td {
            padding-top: 2px;
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
            @for ($i = 0; $i < 1; $i++)
                <div class="text-center font-weight-bold sm-caps" style="font-size: 14px;"> Транспортная накладная</div>
                <table class="table0 table-bordered" style="width:100%" border="1" cellspacing="0"
                       style="font-size: 10px;">
                    <tr class="align-middle">
                        <td colspan=4 class="w-50 doc_title font-weight-bold text-center"
                            style="vertical-align: middle">
                            Транспортная накладная
                        </td>
                        <td colspan="4" class="w-50 doc_title font-weight-bold text-center"
                            style="vertical-align: middle">
                            Заказ (заявка)
                        </td>
                    </tr>
                    <tr>
                        <td class="">
                            <span class=""> Дата</span>
                        </td>
                        <td class="w-25">
                            <div
                                class="font-weight-bold doc_title">{{date_create($rec->docdate)->format('d.m.Y')}}</div>
                        </td>
                        <td class="">
                            <span class=""> №</span>
                        </td>
                        <td class="w-25">
                            <div class="font-weight-bold doc_title">{{$rec->docnum}}</div>
                        </td>

                        <td class="">
                            <span class=""> Дата</span>
                        </td>
                        <td class="w-25">
                            <div
                                class="font-weight-bold doc_title"></div>
                        </td>
                        <td class="">
                            <span class=""> №</span>
                        </td>
                        <td class="w-25">
                            <div class="font-weight-bold doc_title"></div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">Экземпляр №</td>
                        <td colspan="2" class="w-25 font-weight-bold doc_title">
                            1
                        </td>
                        <td colspan="4"></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="font-weight-bold">1.Грузоотправитель</td>
                        <td colspan="4" rowspan="2">1а Заказчик услуг по организации<br>перевозки груза (при наличии)
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">является экспедитором</td>
                        <td style="border-width: 2px; border-color: black;"></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="">
                            @if($rec->saleorgid)
                                <b>{{$rec->saleorg->name}}</b>,
                                <span class="sm-caps">ИНН:{{$rec->saleorg->inn}} КПП:{{$rec->saleorg->kpp}}</span>
                                <br>{{$rec->saleorg->address}}
                        </td>
                        @else
                            <b>{{$rec->ownorg->name}}</b>,
                            <span class="sm-caps">ИНН:{{$rec->ownorg->inn}} КПП:{{$rec->ownorg->kpp}}</span>
                            <br>{{$rec->ownorg->address}}
                            @endif

                            </td>
                            <td colspan="4">
                                @if($rec->saleorgid)
                                    <b>{{$rec->saleorg->name}}</b>,
                                    <span class="sm-caps">ИНН:{{$rec->saleorg->inn}} КПП:{{$rec->saleorg->kpp}}</span>
                                    <br>{{$rec->saleorg->address}}
                            </td>
                            @else
                                <b>{{$rec->ownorg->name}}</b>,
                                <span class="sm-caps">ИНН:{{$rec->ownorg->inn}} КПП:{{$rec->ownorg->kpp}}</span>
                                <br>{{$rec->ownorg->address}}
                                @endif

                                </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="small">
                            (реквизиты, позволяющие идентифицировать Грузоотправителя)
                        </td>
                        <td colspan="4" class="small">
                            (реквизиты, позволяющие идентифицировать Заказчика услуг
                            по организации перевозки груза)
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4"></td>
                        <td colspan="4">Договор на перевозку №230 КСА-МРТ от 23.01.2024
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="small">
                            реквизиты документа, определяющего основания осуществления расчетов по договору перевозки
                            иным лицом, отличным от грузоотправителя (при наличии)
                        </td>
                        <td colspan="4" class="small">
                            (реквизиты договора на выполнение услуг по организации перевозки груза)
                        </td>
                    </tr>

                    <tr>
                        <td colspan="8" class="font-weight-bold text-center">
                            2. Грузополучатель
                        </td>
                    </tr>

                    <tr>
                        <td colspan="8" class="text-center">
                            <b>{{$rec->org->name}}</b>,
                            <span class="sm-caps">ИНН:{{$rec->org->inn}} КПП:{{$rec->org->kpp}}</span>,
                            {{$rec->org->address}}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="8" class="text-center small">
                            (реквизиты, позволяющие идентифицировать Грузополучателя)
                        </td>
                    </tr>
                    <tr>
                        <td colspan="8" class="text-center">
                            !!! 680033, Хабаровский край, Хабаровск г, Трехгорная ул, дом 131
                        </td>
                    </tr>
                    <tr>
                        <td colspan="8" class="text-center small">
                            (адрес места доставки груза)
                        </td>
                    </tr>

                    <tr>
                        <td colspan="8" class="font-weight-bold text-center">
                            3. Груз
                        </td>
                    </tr>

                        <?php
                        $npp = 0;
                        $totalsum = 0;
                        $ri_grossweight = 0;
                        ?>
                    @foreach($rec->items as $itm)
                        {{--                        <tr>--}}
                        {{--                            <td class="small text-right">{{++$npp}}</td>--}}
                        {{--                            <td class="l">--}}
                        {{--                                {{$itm->refitem->name}}--}}
                        {{--                            </td>--}}
                        {{--                            <td class="text-center small">{{$itm->refitem->unit?:'шт'}}</td>--}}
                        {{--                            <td class="text-right">{{number_format($itm->qty, $itm->decimal_dgts)}}</td>--}}
                        {{--                            <td class="text-right">{{number_format($itm->price,2)}}</td>--}}
                        {{--                            <td class="text-right">{{number_format($itm->price * $itm->qty,2)}}</td>--}}
                        {{--                            <td class="text-right">{{trim(number_format($itm->ri_grossweight*$itm->qty,3),'0')}}</td>--}}
                        {{--                        </tr>--}}
                        <tr>
                            <td colspan="4" class="text-center">
                                {{$itm->refitem->name}}
                            </td>
                            <td colspan="4" class="text-center">
                                {{number_format($itm->qty, $itm->decimal_dgts)}} {{$itm->refitem->unit?:'шт'}}
                            </td>
                        </tr>
                            <?php
                            $totalsum += $itm->price * $itm->qty;
                            $ri_grossweight += $itm->ri_grossweight * $itm->qty;
                            ?>
                    @endforeach

                    <tr>
                        <td colspan="4" class="text-center small">
                            (отгрузочное наименование груза (для опасных грузов - в соответствии с ДОПОГ), его состояние
                            и другая необходимая информация о грузе)
                        </td>
                        <td colspan="4" class="text-center small">
                            (количество грузовых мест, маркировка, вид тары и способ упаковки)
                        </td>
                    </tr>
                    <tr>
                        <td colspan="8" class="text-center">
                            !!!
                        </td>
                    </tr>
                    <tr>
                        <td colspan="8" class="text-center small">
                            (масса груза брутто в килограммах, масса груза нетто в килограммах (при возможности ее
                            определения), размеры (высота, ширина, длина) в метрах (при перевозке крупногабаритного
                            груза), объем груза в кубических метрах и плотность груза в соответствии с документацией на
                            груз (при необходимости), дополнительные характеристики груза, учитывающие отраслевые
                            особенности (при необходимости)
                        </td>
                    </tr>

                    <tr>
                        <td colspan="4" class="text-center ">
                            !!!
                        </td>
                        <td colspan="4" class="text-center ">
                            !!! 5405336 (Пять миллионов четыреста пять тысяч триста тридцать шесть рублей 00 копеек)
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-center small">
                            (в случае перевозки опасного груза - информация по каждому опасному веществу, материалу или
                            изделию в соответствии с пунктом 5.4.1 ДОПОГ)
                        </td>
                        <td colspan="4" class="text-center small">
                            (объявленная стоимость (ценность) груза (при необходимости)
                        </td>
                    </tr>

                    <tr>
                        <td colspan="8" class="font-weight-bold text-center">
                            4. Сопроводительные документы на груз (при наличии)
                        </td>
                    </tr>
                    <tr>
                        <td colspan="8" class="text-center">
                            !!!1
                        </td>
                    </tr>
                    <tr>
                        <td colspan="8" class="small text-center">
                            (перечень прилагаемых к транспортной накладной документов, предусмотренных ДОПОГ,
                            санитарными, таможенными (при наличии), карантинными, иными правилами в соответствии с
                            законодательством Российской Федерации, либо регистрационные номера указанных документов,
                            если такие документы (сведения о таких документах) содержатся в государственных
                            информационных системах)
                        </td>
                    </tr>
                    <tr>
                        <td colspan="8" class="text-center">
                            !!!2
                        </td>
                    </tr>
                    <tr>
                        <td colspan="8" class="small text-center">
                            (перечень прилагаемых к грузу сертификатов, паспортов качества, удостоверений и других
                            документов, наличие которых установлено законодательством Российской Федерации, либо
                            регистрационные номера указанных документов, если такие документы (сведения о таких
                            документах) содержатся в государственных информационных системах)
                        </td>
                    </tr>
                    <tr>
                        <td colspan="8" class="text-center">
                            !!!3
                        </td>
                    </tr>
                    <tr>
                        <td colspan="8" class="small text-center">
                            (реквизиты, позволяющие идентифицировать документ(-ы), подтверждающий(-ие) отгрузку товаров)
                            (при наличии), реквизиты сопроводительной ведомости (при перевозке груженых контейнеров или
                            порожних контейнеров)
                        </td>
                    </tr>

                    <tr>
                        <td colspan="8" class="font-weight-bold text-center">
                            5. Указания грузоотправителя по особым условиям перевозки
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-center">
                            !!!
                        </td>
                        <td colspan="4" class="text-center">
                            !!!
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="small text-center">
                            (маршрут перевозки, дата и время/сроки доставки груза (при необходимости)
                        </td>
                        <td colspan="4" class="small text-center">
                            (контактная информация о лицах, по указанию которых может осуществляться переадресовка)
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-center">
                            !!!
                        </td>
                        <td colspan="4" class="text-center">
                            !!!
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="small text-center">
                            (указания, необходимые для выполнения фитосанитарных, санитарных, карантинных, таможенных и
                            прочих требований, установленных законодательством Российской Федерации)
                        </td>
                        <td colspan="4" class="small text-center">
                            (температурный режим перевозки груза (при необходимости), сведения о запорно-пломбировочных
                            устройствах (в случае их предоставления грузоотправителем), запрещение перегрузки груза)
                        </td>
                    </tr>

                    <tr>
                        <td colspan="8" class="font-weight-bold text-center">
                            6. Перевозчик
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-center">
                            !!! ООО "МИРАЛИТ" ИНН 2543124362 КПП 250201001 692481, Приморский край, м.р-н Надеждинский,
                            с.п. Надеждинское, с Вольно-Надеждинское, ул Полевая, домовладение 23
                        </td>
                        <td colspan="4" class="text-center">
                            !!! Копай Д.Г.(серия и номер вод.удостоверения): 2533 №742717
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="small text-center">
                            (реквизиты, позволяющие идентифицировать Перевозчика)
                        </td>
                        <td colspan="4" class="small text-center">
                            (реквизиты, позволяющие идентифицировать водителя(-ей)
                        </td>
                    </tr>

                    <tr>
                        <td colspan="8" class="font-weight-bold text-center">
                            7. Транспортное средство
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-center">
                            !!! Sitrak
                        </td>
                        <td colspan="4" class="text-center">
                            !!! Р379ХС125, АМ586825
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="small text-center">
                            (тип, марка, грузоподъемность (в тоннах), вместимость (в кубических метрах)
                        </td>
                        <td colspan="4" class="small text-center">
                            (регистрационный номер транспортного средства)
                        </td>
                    </tr>

                    <tr>
                        <td colspan="7" class="font-weight-bold text-center">
                            Тип владения: 1 - собственность; 2 - совместная собственность супругов; 3 - аренда; 4 -
                            лизинг; 5 - безвозмездное пользование
                        </td>
                        <td style="border-width: 2px; border-color: black;"></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-center">
                            !!!
                        </td>
                        <td colspan="4" class="text-center">
                            !!!
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="small text-center">
                            (реквизиты документа(-ов), подтверждающего(-их) основание владения грузовым автомобилем
                            (тягачом, а также прицепом (полуприцепом) (для типов владения 3, 4, 5)
                        </td>
                        <td colspan="4" class="small text-center">
                            (номер, дата и срок действия специального разрешения, установленный маршрут движения
                            тяжеловесного и (или) крупногабаритного транспортного средства или транспортного средства,
                            перевозящего опасный груз) (при наличии)
                        </td>
                    </tr>

                    <tr>
                        <td colspan="8" class="font-weight-bold text-center">
                            8. Прием груза
                        </td>
                    </tr>
                    <tr>
                        <td colspan="8" class="text-center">
                            !!! ООО "Компания СИМ-авто", ИНН 7729588182, КПП 774301001, 125130, г. Москва, вн. тер. г.
                            муниципальный округ Войковский, ул. Выборгская, д.22, стр 3
                        </td>
                    </tr>
                    <tr>
                        <td colspan="8" class="small text-center">
                            (реквизиты лица, осуществляющего погрузку груза в транспортное средство)
                        </td>
                    </tr>
                    <tr>
                        <td colspan="8" class="text-center">
                            !!! ООО "Компания СИМ-авто", ИНН 7729588182
                        </td>
                    </tr>
                    <tr>
                        <td colspan="8" class="small text-center">
                            (наименование (ИНН) владельца объекта инфраструктуры пункта погрузки)
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-center">
                            !!! Хабаровск, ул. Шкотова 13
                        </td>
                        <td colspan="4" class="text-center">
                            !!!
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="small text-center">
                            (адрес места погрузки)
                        </td>
                        <td colspan="4" class="small text-center">
                            (заявленные дата и время подачи транспортного средства под погрузку)
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-center">
                            !!! 30.03.2026
                        </td>
                        <td colspan="4" class="text-center">
                            !!! 30.03.2026
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="small text-center">
                            (фактические дата и время прибытия под погрузку)
                        </td>
                        <td colspan="4" class="small text-center">
                            (фактические дата и время убытия)
                        </td>
                    </tr>

                    <tr>
                        <td colspan="8" class="text-center">
                            !!!
                        </td>
                    </tr>
                    <tr>
                        <td colspan="8" class="small text-center">
                            (масса груза брутто в килограммах и метод ее определения (определение разницы между массой
                            транспортного средства после погрузки и перед погрузкой по общей массе или взвешиванием
                            поосно или расчетная масса груза)
                        </td>
                    </tr>

                    <tr>
                        <td colspan="4" class="text-center">
                            !!! 1 шт.
                        </td>
                        <td colspan="4" class="text-center">
                            !!!
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="small text-center">
                            (количество грузовых мест)
                        </td>
                        <td colspan="4" class="small text-center">
                            (тара, упаковка (при наличии)
                        </td>
                    </tr>

                    <tr>
                        <td colspan="8" class="text-center">
                            !!!
                        </td>
                    </tr>
                    <tr>
                        <td colspan="8" class="small text-center">
                            (оговорки и замечания перевозчика (при наличии) о дате и времени прибытия/убытия, о
                            состоянии, креплении груза, тары, упаковки, маркировки, опломбирования, о массе груза и
                            количестве грузовых мест, о проведении погрузочных работ)
                        </td>
                    </tr>

                    <tr>
                        <td colspan="4" class="text-center">
                            !!!
                        </td>
                        <td colspan="4" class="text-center">
                            !!! Копай Д.Г.
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="small text-center">
                            (подпись, расшифровка подписи лица, осуществившего погрузку груза, с указанием реквизитов
                            документа, подтверждающего полномочия лица на погрузку груза)
                        </td>
                        <td colspan="4" class="small text-center">
                            (подпись, расшифровка подписи водителя, принявшего груз для перевозки)
                        </td>
                    </tr>

                    <tr>
                        <td colspan="8" class="font-weight-bold text-center">
                            9. Переадресовка (при наличии)
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-center">
                            !!!
                        </td>
                        <td colspan="4" class="text-center">
                            !!!
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="small text-center">
                            (дата, вид переадресовки на бумажном носителе или в электронном виде
                            (с указанием вида доставки документа)
                        </td>
                        <td colspan="4" class="small text-center">
                            (адрес нового пункта выгрузки, новые дата и время подачи транспортного средства под
                            выгрузку)
                        </td>
                    </tr>

                    <tr>
                        <td colspan="4" class="text-center">
                            !!!
                        </td>
                        <td colspan="4" class="text-center">
                            !!!
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="small text-center">
                            (реквизиты лица, от которого получено указание на переадресовку)
                        </td>
                        <td colspan="4" class="small text-center">
                            (при изменении получателя груза - реквизиты нового получателя)
                        </td>
                    </tr>

                    <tr>
                        <td colspan="8" class="font-weight-bold text-center">
                            10. Выдача груза
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-center">
                            !!! 680033, Хабаровский край, Хабаровск г, Трехгорная ул, дом 131
                        </td>
                        <td colspan="4" class="text-center">
                            !!!
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="small text-center">
                            (адрес места выгрузки)
                        </td>
                        <td colspan="4" class="small text-center">
                            (заявленные дата и время подачи транспортного средства под выгрузку)
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-center">
                            !!!
                        </td>
                        <td colspan="4" class="text-center">
                            !!!
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="small text-center">
                            (фактические дата и время прибытия)
                        </td>
                        <td colspan="4" class="small text-center">
                            (фактические дата и время убытия)
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-center">
                            !!!
                        </td>
                        <td colspan="4" class="text-center">
                            !!!
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="small text-center">
                            (фактическое состояние груза, тары, упаковки, маркировки, опломбирования)
                        </td>
                        <td colspan="4" class="small text-center">
                            (количество грузовых мест)
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-center">
                            !!!
                        </td>
                        <td colspan="4" class="text-center">
                            !!!
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="small text-center">
                            (масса груза брутто в килограммах, масса груза нетто в килограммах (при возможности ее
                            определения), плотность груза в соответствии с документацией на груз (при необходимости)
                        </td>
                        <td colspan="4" class="small text-center">
                            (оговорки и замечания перевозчика (при наличии) о дате и времени прибытия/убытия, о
                            состоянии груза, тары, упаковки, маркировки, опломбирования, о массе груза и количестве
                            грузовых мест)
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-center">
                            !!!
                        </td>
                        <td colspan="4" class="text-center">
                            !!! Копай Д.Г.
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="small text-center">
                            (должность, подпись, расшифровка подписи грузополучателя или уполномоченного
                            грузоотправителем лица)
                        </td>
                        <td colspan="4" class="small text-center">
                            (подпись, расшифровка подписи водителя, сдавшего груз грузополучателю или уполномоченному
                            грузополучателем лицу)
                        </td>
                    </tr>

                    <tr>
                        <td colspan="8" class="font-weight-bold text-center">
                            11. Отметки грузоотправителей, грузополучателей, перевозчиков (при необходимости)
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-center">
                            !!!
                        </td>
                        <td colspan="2" class="text-center">
                            !!!
                        </td>
                        <td colspan="2" class="text-center">
                            !!!
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="small text-center">
                            "(краткое описание обстоятельств, послуживших основанием для отметки, сведения о
                            коммерческих и иных актах, в том числе
                            о погрузке/выгрузке груза)"
                        </td>
                        <td colspan="2" class="small text-center">
                            (расчет и размер штрафа)
                        </td>
                        <td colspan="2" class="small text-center">
                            (подпись, дата)
                        </td>
                    </tr>

                    <tr>
                        <td colspan="8" class="font-weight-bold text-center">
                            12. Стоимость перевозки груза (установленная плата) в рублях (при необходимости)
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" class="text-center">
                            !!!
                        </td>
                        <td colspan="2" class="text-center">
                            !!!
                        </td>
                        <td colspan="2" class="text-center">
                            !!!
                        </td>
                        <td colspan="2" class="text-center">
                            !!!
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" class="small text-center">
                            (стоимость перевозки без налога - всего)
                        </td>
                        <td colspan="2" class="small text-center">
                            (налоговая ставка)
                        </td>
                        <td colspan="2" class="small text-center">
                            (сумма налога, предъявляемая покупателю)
                        </td>
                        <td colspan="2" class="small text-center">
                            (стоимость перевозки с налогом - всего)
                        </td>
                    </tr>

                    <tr>
                        <td colspan="8" class="text-center">
                            !!!
                        </td>
                    </tr>
                    <tr>
                        <td colspan="8" class="small text-center">
                            (порядок (механизм) расчета (исчислений) платы) (при наличии порядка (механизма)
                        </td>
                    </tr>

                    <tr>
                        <td colspan="4" class="text-center">
                            !!! ООО "МИРАЛИТ" ИНН 2543124362 КПП 250201001 692481, Приморский край, м.р-н Надеждинский,
                            с.п. Надеждинское, с Вольно-Надеждинское, ул Полевая, домовладение 23
                        </td>
                        <td colspan="4" class="text-center">
                            !!! ООО "Компания СИМ-авто", ИНН 7729588182, КПП 774301001, 125130, г. Москва, вн. тер. г.
                            муниципальный округ Войковский, ул. Выборгская, д.22, стр 3
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="small text-center">
                            (реквизиты, позволяющие идентифицировать Экономического субъекта, составляющего первичный
                            учетный документ о факте хозяйственной жизни со стороны Перевозчика)
                        </td>
                        <td colspan="4" class="small text-center">
                            (реквизиты, позволяющие идентифицировать Экономического субъекта, составляющего первичный
                            учетный документ о факте хозяйственной жизни со стороны Грузоотправителя)
                        </td>
                    </tr>

                    <tr>
                        <td colspan="4" class="text-center">
                            !!! Договор на перевозку №230 КСА-МРТ от 23.01.2024
                        </td>
                        <td colspan="4" class="text-center">
                            !!! Договор на перевозку №230 КСА-МРТ от 23.01.2024
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="small text-center">
                            (основание, по которому Экономический субъект является составителем документа о факте
                            хозяйственной жизни)
                        </td>
                        <td colspan="4" class="small text-center">
                            (основание, по которому Экономический субъект является составителем документа о факте
                            хозяйственной жизни)
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-center">
                            !!!
                        </td>
                        <td colspan="4" class="text-center">
                            !!! ООО "Компания СИМ-авто", ИНН 7729588182, КПП 774301001, 125130, г. Москва, вн. тер. г.
                            муниципальный округ Войковский, ул. Выборгская, д.22, стр 3
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="small text-center">

                        </td>
                        <td colspan="4" class="small text-center">
                            (реквизиты, позволяющие идентифицировать лицо, от которого будут поступать денежные
                            средства)
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-center">
                            !!!
                        </td>
                        <td colspan="4" class="text-center">
                            !!!
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="small text-center">
                            (подпись, расшифровка подписи лица, ответственного за оформление факта хозяйственной жизни
                            со стороны Перевозчика (уполномоченного лица)
                        </td>
                        <td colspan="4" class="small text-center">
                            (подпись, расшифровка подписи лица, ответственного за оформление факта хозяйственной жизни
                            со стороны Грузоотправителя (уполномоченного лица)
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-center">
                            !!!
                        </td>
                        <td colspan="4" class="text-center">
                            !!!
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="small text-center">
                            (должность, основание полномочий физического лица, уполномоченного Перевозчиком
                            (уполномоченным лицом), дата подписания)
                        </td>
                        <td colspan="4" class="small text-center">
                            (должность, основание полномочий физического лица, уполномоченного Грузоотправителем
                            (уполномоченным лицом), дата подписания)
                        </td>
                    </tr>
                </table>


                @if(1==0)
                    <br>
                    <hr><br>
                    <table class="table table-bordered text-center" style="width:100%" border="1" cellspacing="0">
                        @if($rec->saleorgid)
                            <tr>
                                <td>Продавец:</td>
                                <td><b>{{$rec->saleorg->name}}</b></td>
                                <td class="text-left">
                                    <span class="sm-caps">ИНН:{{$rec->saleorg->inn}} КПП:{{$rec->saleorg->kpp}}</span>
                                    <br>{{$rec->saleorg->address}}
                                </td>
                            </tr>
                        @else
                            <tr>
                                <td>{{$rec->doctype->ownorg_label}}:</td>
                                <td><b>{{$rec->ownorg->name}}</b></td>
                                <td class="text-left">
                                    <span class="sm-caps">ИНН:{{$rec->ownorg->inn}} КПП:{{$rec->ownorg->kpp}}</span>
                                    <br>{{$rec->ownorg->address}}
                                </td>
                            </tr>
                        @endif
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
                                    <span class="sm-caps">ИНН:{{$rec->org->inn}} КПП:{{$rec->org->kpp}}</span>,
                                    {{$rec->org->address}}
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
                            <td class="text-right">{{$data->src_signer_label}}</td>
                            <td style="width:100px; border-bottom: 1px solid silver"></td>
                            <td style="border-bottom: 1px solid silver">{{$data->src_signer_name}}</td>
                            <td class="w-25 text-right">&nbsp;</td>
                            <td class="text-right">{{$data->tgt_signer_label}}</td>
                            <td style="width:100px; border-bottom: 1px solid silver"></td>
                            <td style="border-bottom: 1px solid silver">{{$data->tgt_signer_name}}</td>
                        </tr>
                    </table>
                @endif
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

            // window.document.onload = window.print();

            window.onafterprint = function () {
                setTimeout(function () {
                    window.close();
                }, 500);
            }

            window.onfocus = function () {
                setTimeout(function () {
                    window.close();
                }, 1500);
            }
        </script>
    </div>

@endsection('content')
