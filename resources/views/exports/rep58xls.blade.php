<table>
    <thead>
    <tr>
        <td colspan="7" align="center">Сводка по рабочим часам водителей</td>
    </tr>
    <tr>
        <td colspan="7" align="center">
            {{$data->period_title}}
        </td>
    </tr>
    <tr>
        <td colspan="7" align="center">
            по состоянию на {{now()}}
        </td>
    </tr>
    <tr></tr>

    <tr>
        <td rowspan="2" style="text-align: right; vert-align: top;">#пп</td>
        <td rowspan="2" width="36">Работник</td>
        <td rowspan="2" width="16">Режим работы</td>

        <td rowspan="2" width="5" style="text-align: center;">Раб. дней</td>

        <td rowspan="1" colspan="3" style="text-align: center;">Работа, день</td>
        <td rowspan="1" colspan="3" style="text-align: center;">Работа, ночь</td>

        <td rowspan="1" colspan="2" style="text-align: center;">Простой, Всего</td>
        <td rowspan="1" colspan="2" style="text-align: center;">Простой, Ремонт</td>
        <td rowspan="1" colspan="2" style="text-align: center;">Простой, Сон</td>
        <td rowspan="1" colspan="2" style="text-align: center;">Простой, Иное</td>

        <td rowspan="2" width="12" style="text-align: center; vert-align: middle;">Всего, руб</td>
    </tr>
    <tr align="center">
        <td width="13" style="text-align: center;">ставка, от .. до</td>
        <td width="8" style="text-align: center;">часов</td>
        <td width="8" style="text-align: center;">сумма</td>

        <td width="13" style="text-align: center;">ставка, от .. до</td>
        <td width="7" style="text-align: center;">часов</td>
        <td width="8" style="text-align: center;">сумма</td>

        <td width="6" style="text-align: center;">часов</td>
        <td width="10" style="text-align: center;">сумма</td>

        <td width="6" style="text-align: center;">часов</td>
        <td width="10" style="text-align: center;">сумма</td>

        <td width="6" style="text-align: center;">часов</td>
        <td width="10" style="text-align: center;">сумма</td>

        <td width="6" style="text-align: center;">часов</td>
        <td width="10" style="text-align: center;">сумма</td>

    </tr>

    </thead>
    <tbody>
    <?php
    $npp = 0;
    $totSum = 0;
    $totDayWrkHrs = 0;
    $totNightWrkHrs = 0;
    $totBrkHrs = 0;
    $totBrk11Hrs = 0;
    $totBrk21Hrs = 0;
    $totBrk22Hrs = 0;
    $totWrkHrs = 0;
    ?>
    @foreach($recs as $itm)
        <?php
        $staff_name = $itm->staff_lname;
        if (isset($itm->staff_fname)) {
            $staff_name .= ' ' . mb_substr($itm->staff_fname, 0, 1) . '.';
            if (isset($itm->staff_mname))
                $staff_name .= mb_substr($itm->staff_mname, 0, 1) . '.';
        }
        $day_hr_rate = $itm->day_hr_rate;
        $day_hr_rate_min = $itm->day_hr_rate_min;
        if ($day_hr_rate_min <> $day_hr_rate)
            $day_hr_rate = $day_hr_rate_min . ' .. ' . $day_hr_rate;

        $night_hr_rate = $itm->night_hr_rate;
        $night_hr_rate_min = $itm->night_hr_rate_min;
        if ($night_hr_rate_min <> $night_hr_rate)
            $night_hr_rate = $night_hr_rate_min . ' .. ' . $night_hr_rate;
        ?>
        <tr>
            <td x:num width="5">{{++$npp}}</td>
            <td>{{$itm->staff_name}}, {{$itm->postname}}</td>
            <td style="text-align: center;">{{$itm->wrktype_name}}</td>

            <td x:num>{{$itm->wrkdays}}</td>

            <td style="text-align: center;"><b>{{$day_hr_rate}}</b></td>
            <td x:num>{{$itm->day_wrkhrs}}</td>
            <td x:num>{{$itm->day_hr_sum}}</td>

            <td style="text-align: center;"><b>{{$night_hr_rate}}</b></td>
            <td x:num>{{$itm->night_wrkhrs}}
            <td x:num>{{$itm->night_hr_sum}}</td>

            <td x:num>{{$itm->brkhrs}}</td>
            <td x:num>{{$itm->breaks_sum}}</td>

            <td x:num >{{$itm->brk_11_hrs}}</td>
            <td x:num >{{$itm->brk_11_sum}}</td>

            <td x:num >{{$itm->brk_21_hrs}}</td>
            <td x:num >{{$itm->brk_21_sum}}</td>

            <td x:num >{{$itm->brk_22_hrs}}</td>
            <td x:num >{{$itm->brk_22_sum}}</td>

            <td x:num >{{$itm->day_hr_sum + $itm->night_hr_sum + $itm->breaks_sum}}</td>
        </tr>
        <?php
        $totDayWrkHrs += $itm->day_wrkhrs;
        $totNightWrkHrs += $itm->night_wrkhrs;
        $totBrkHrs += $itm->brkhrs;
        $totBrk11Hrs += $itm->brk_11_hrs;
        $totBrk21Hrs += $itm->brk_21_hrs;
        $totBrk22Hrs += $itm->brk_22_hrs;

        $totSum += $itm->day_hr_sum + $itm->night_hr_sum + $itm->breaks_sum;
        ?>
    @endforeach

    <tr>
        <td colspan="5" style="text-align: right;">Итого:</td>
        <td x:num><b>{{$totDayWrkHrs}}</b></td>
        {{--        <td x:num width="10" class="text-right">=СУММ(E7:E104)</td>--}}
        <td></td>
        <td></td>
        <td x:num><b>{{$totNightWrkHrs}}</b></td>
        <td></td>
        <td x:num><b>{{$totBrkHrs}}</b></td>
        <td></td>
        <td x:num><b>{{$totBrk11Hrs}}</b></td>
        <td></td>
        <td x:num><b>{{$totBrk21Hrs}}</b></td>
        <td></td>
        <td x:num><b>{{$totBrk22Hrs}}</b></td>
        <td></td>
        <td x:num><b>{{$totSum}}</b></td>
    </tr>
    </tbody>
</table>

