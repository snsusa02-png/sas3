<table>
    <thead>
    <tr>
        <td colspan="7" align="center">Сводка расходов/доходов авто</td>
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
        <td class="text-left">№ Авто</td>
        <td class="text-right">Выручка, &#8381;</td>
        <td class="text-right">Инертные, &#8381;</td>
        <td class="text-right">Топливо, &#8381;</td>
        <td class="text-right">ЗП водителя, &#8381;</td>
        <td class="text-right">Заработок, &#8381;</td>
    </tr>
    </thead>
    <tbody>
    <?php
    $npp = 0;
    $totSum = 0;
    ?>
    @foreach($recs as $rec)
        <?php
        $line_sum = $rec->sale_sum - $rec->buy_sum - $rec->fuel_sum - $rec->salary_sum;
        $td_class = ($line_sum < 0) ? 'text-danger' : (($line_sum > 0) ? 'text-success' : '');
        ?>

        {{--                            <tr class="text-left collapse show date_{{$tr_date}} multi-collapse">--}}
        <tr class="text-left collapse show date_{{$tr_date??''}} multi-collapse">
            <td width="15">{{$rec->regnum}}</td>
            <td x:num width="15">{{$rec->sale_sum}}</td>
            <td x:num width="15">{{$rec->buy_sum}}</td>
            <td x:num width="15">{{$rec->fuel_sum}}</td>
            <td x:num width="15">{{$rec->salary_sum}}</td>
            <td x:num width="15">{{$line_sum}}</td>
        </tr>
        <?php
        $totSum += $line_sum;
        ?>
    @endforeach

    <tr>
        <td colspan="5" align="right">Всего:</td>
        <td><b>{{ $totSum }}</b></td>
    </tr>
    </tbody>
</table>

