@if (isset($recs) and $recs->count()>0)
    <table>
        <thead>
        <tr>
            <td colspan="10" align="right" style="font-size: 7px;">по состоянию на {{now()}}</td>
        </tr>
        <tr style="line-height: 16px;">
            <td colspan="10" align="center" style="line-height: 16px; font-weight: bold; font-size: 16px; ">Водители: {{$data->period_title}}</td>
        </tr>
        <tr>
            <td colspan="10"></td>
        </tr>
        <tr class="text-left small" valign="top">
            <td rowspan="2" width="30" style="text-align: center;vertical-align: middle;">ФИО</td>
            <td rowspan="2" width="20" style="text-align: center;vertical-align: middle;">Техника</td>
            <td rowspan="2" width="13" style="text-align: center;vertical-align: middle;">Кол-во рейсов</td>
            <td colspan="2" style="text-align: center;">ЗП</td>
            <td colspan="2" style="text-align: center;">Оплач. простой</td>
            <td colspan="2" style="text-align: center;">Ремонт</td>
            <td rowspan="2" style="text-align: center;vertical-align: middle;">Всего</td>
        </tr>
        <tr>
            <td style="text-align: center; font-size: 8px;">За рейс, &#8381;</td>
            <td style="text-align: center; font-size: 8px;">Сумма, &#8381;</td>

            <td style="text-align: center; font-size: 8px;">Время, ч</td>
            <td style="text-align: center; font-size: 8px;">Сумма, &#8381;</td>

            <td style="text-align: center; font-size: 8px;">Время, ч</td>
            <td style="text-align: center; font-size: 8px;">Сумма, &#8381;</td>
        </tr>
        </thead>
        <tbody>
        <?php
        $npp = 0;
        $totRaidQty = 0;
        $totSalarySum = 0;
        $totPDTHrs = $totPDTSum = 0;
        $totRepairHrs = $totRepairSum = 0;
        $totSum = 0;
        $totLoadSum = 0;
        $totUnloadSum = 0;
        ?>
        @foreach($recs as $rec)

            <tr class="text-left">
                <td class="text-left ">{{$rec->driver_name}}</td>
                <td align="center" style="font-size: 8px;">{{$rec->machine_name}}</td>

                <td class="text-right ">{{$rec->raid_qty}}
                <td>{{($rec->raid_qty>0)?round($rec->salary/$rec->raid_qty, 2):0}}
                <td class="text-right ">{{$rec->salary}}

                <td class="text-right small text-secondary">{{$rec->pdt_hrs}}
                <td class="text-right ">{{$rec->pdt_sum}}

                <td class="text-right small text-secondary">{{$rec->repair_hrs}}
                <td class="text-right ">{{$rec->repair_sum}}

                <td class="text-right ">{{$rec->salary+$rec->pdt_sum+$rec->repair_sum}}
            </tr>
            <?php
            $totRaidQty += $rec->raid_qty;
            $totSalarySum += $rec->salary;

            $totPDTHrs += $rec->pdt_hrs;
            $totPDTSum += $rec->pdt_sum;

            $totRepairHrs += $rec->repair_hrs;
            $totRepairSum += $rec->repair_sum;

            $totSum += $rec->salary + $rec->pdt_sum + $rec->repair_sum;
            ?>
        @endforeach

        @if(1==1)
            <tr>
                <td colspan="2" style="text-align: right;font-weight: bold;">Всего:</td>
                <td align="right" style="font-weight: bold">{{$totRaidQty}}</td>
                <td>{{($totRaidQty>0)?round($totSalarySum/$totRaidQty, 2):0}}</td>
                <td style="font-weight: bold">{{$totSalarySum}}</td>

                <td style="font-weight: bold">{{$totPDTHrs}}</td>
                <td style="font-weight: bold">{{$totPDTSum}}</td>

                <td style="font-weight: bold">{{$totRepairHrs}}</td>
                <td style="font-weight: bold">{{$totRepairSum}}</td>

                <td style="font-weight: bold">{{$totSum}}</td>
            </tr>
            @if(1==0)
                <tr>
                    <td colspan="8" class="text-right">
                        Баланс, &#8381;:
                    </td>
                    <td class="text-right font-weight-bold">{{$totUnloadSum-$totLoadSum-$totSum}}</td>
                </tr>
            @endif
        @endif
        </tbody>

    </table>
@else
    <table>
        <tr><td width="20" style="background-color: #ffcdcd;">Нет данных!</td></tr>
    </table>
@endif
