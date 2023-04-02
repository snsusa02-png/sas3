@if (isset($recs) and $recs->count()>0)
    <table>
        <thead>
        <tr>
            <td colspan="11" align="right" style="font-size: 7px;">по состоянию на {{now()}}</td>
        </tr>
        <tr style="line-height: 16px; ">
            <td colspan="11" align="center" style="line-height: 16px; font-weight: bold; font-size: 16px; ">Оборот по
                работе: {{$data->period_title}}
            </td>
        </tr>
        <tr><td colspan="11"></td> </tr>

        <tr valign="top">
            <td width="20">Компания/физ. лицо</td>
            <td width="20">Диспетчер</td>
            <td width="13">Кол-во рейсов</td>
            <td width="25">Товар/Услуга</td>
            <td width="5" style="text-align: center">ЕИ</td>
            <td width="16">Объем отгрузки</td>
            <td>Цена за ЕИ</td>
            <td width="12">Сумма, &#8381;</td>
            <td>Сальдо, &#8381;</td>
            <td width="16" style="font-size: 8px">Место работы</td>
            <td width="16" style="font-size: 8px">Период работы</td>
        </tr>
        </thead>
        <tbody>
        <?php
        $npp = 0;
        $curOwnOrgID = -1;
        $curOwnOrgName = '';
        $curRqstOrgID = -1;
        $curPayDate = -1;
        $totUnloadSum = $totRaidQty = $daySum = $dayRaidQty = 0;
        ?>

        @foreach($recs as $rec)

            <?php
            $wrkdate = date_create($rec->wrkdate)->format('d.m.Y');
            ?>
            @if(1==0 and $wrkdate<>$curPayDate)
                @if($curPayDate <>-1 )
                    <tr >
                        <td colspan="2" style="background-color: #fcf9c2;">Итого за {{$curPayDate}}:</td>
                        <td style="background-color: #fffee8; font-weight: bold">{{number_format($dayRaidQty,0)}}</td>
                        <td style="background-color: #fffee8;"></td>
                        <td style="background-color: #fffee8;"></td>
                        <td style="background-color: #fffee8;"></td>
                        <td style="background-color: #fffee8;"></td>
                        <td style="background-color: #fffee8;font-weight: bold">{{number_format($daySum,2)}}</td>
                        <td style="background-color: #fffee8;"></td>
                    </tr>
                @endif
                <?php
                $tr_date = date_create($rec->wrkdate)->format('dmY');
                ?>
                <tr class="text-left" style="background-color: #edffe0">
                    <td colspan="12" style="background-color: #fdfcdc;">
                        <b>{{$wrkdate}}</b>
                    </td>
                </tr>
                <?php
                $curPayDate = $wrkdate;
                $daySum = $dayRaidQty = 0;
                ?>
            @endif
            <?php
            $td_class = ($rec->org_saldo < 0) ? 'text-danger' : (($rec->org_saldo > 0) ? 'text-success' : '');
            ?>
            <tr class="text-left">
                <td class="small" style="text-align: center" colspan="1">
                    {{$rec->orgname}}
                </td>
                <td class="text-left small" style="">{{$rec->dispuser_name}}</td>
                <td class="text-right small">{{$rec->raid_qty}}</td>
                <td style="">{{$rec->refitm_name}}</td>
                <td class="text-center small" style="text-align: center">{{$rec->unit}}</td>
                <td class="text-right small">{{$rec->unload_qty}}
                <td class="text-right small">
                    @if($rec->unload_qty>0)
                        {{$rec->itm_price}}
                    @else
                        0
                    @endif
                </td>
                <td class="text-right">{{$rec->unload_sum}}
                <td class="text-right {{$td_class}}">{{$rec->org_saldo}}
                <td style="text-align:center; font-size: 8px;">
                    {{$rec->unload_placename}}
                </td>
                <td style="text-align:center; font-size: 8px;">
                    {{date_create($rec->min_wrkdate)->format('d.m.Y')}}
                    .. {{date_create($rec->max_wrkdate)->format('d.m.Y')}}
                </td>
            </tr>
            <?php
            $totUnloadSum += $rec->unload_sum;
            $daySum += $rec->unload_sum;

            $dayRaidQty += $rec->raid_qty;
            $totRaidQty += $rec->raid_qty;
            ?>
        @endforeach
        @if(1==1)
            @if($curPayDate <>-1 )
                <tr >
                    <td colspan="3" style="background-color: #fffee8;text-align: right;font-weight: bold;">Итого за {{$curPayDate}}:</td>
                    <td style="background-color: #fffee8; font-weight: bold">{{$dayRaidQty}}</td>
                    <td style="background-color: #fffee8;"></td>
                    <td style="background-color: #fffee8;"></td>
                    <td style="background-color: #fffee8;"></td>
                    <td style="background-color: #fffee8;"></td>
                    <td style="background-color: #fffee8;font-weight: bold">{{$daySum}}</td>
                    <td style="background-color: #fffee8;"></td>
                </tr>
            @endif

            <tr>
                <td colspan="2" align="right" style="font-weight: bold">Всего:</td>
                <td style="font-weight: bold">{{$totRaidQty}}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td style="font-weight: bold">{{$totUnloadSum}}</td>
                <td></td>
            </tr>
        @endif

        </tbody>
    </table>
@endif

