<table>
    <thead>
    <tr>
        <td colspan="7" align="center">Операции авто</td>
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
        <td class="text-center">Дата</td>
        <td class="text-left">Водитель</td>
        <td class="text-center">№ Авто</td>
        <td class="text-left">Что купили</td>
        <td class="text-left">У кого купили</td>
        <td class="text-left">Место загрузки</td>
        <td class="text-left">Что продали</td>
        <td class="text-left">Кому продали</td>
        <td class="text-left">Место выгрузки</td>
        <td class="text-left">Кол-во рейсов</td>
        <td class="text-right">Покупка, &#8381;</td>
        <td class="text-right">Продажа, &#8381;</td>
        <td class="text-right">Разница, &#8381;</td>
    </tr>
    </thead>
    <tbody>
    <?php
    $npp = 0;
    $totSum = 0;
    ?>
    @foreach($recs as $rec)
        <?php
        $line_sum = $rec->sale_sum - $rec->buy_sum;
        $td_class = ($line_sum < 0) ? 'text-danger' : (($line_sum > 0) ? 'text-success' : '');
        ?>

        <tr class="text-left">
            <td>{{date_format(date_create($rec->wrkdate),'d.m.Y')}}</td>
            <td width="25">{{$rec->lname}} {{$rec->fname}} {{$rec->mname}}</td>
            <td width="15">{{$rec->regnum}}</td>

            <td width="25">{{$rec->buy_itmname}}</td>
            <td width="25">{{$rec->sup_name}}</td>
            <td>{{$rec->sup_placename}}</td>
            <td width="25">{{$rec->sale_itmname}}</td>
            <td width="25">{{$rec->org_name}}</td>
            <td>{{$rec->org_placename}}</td>


            <td x:num width="15">{{$rec->raid_qty}}</td>
            <td x:num width="15">{{$rec->buy_sum}}</td>
            <td x:num width="15">{{$rec->sale_sum}}</td>
            <td x:num width="15">{{$line_sum}}</td>
        </tr>
        <?php
        $totSum += $line_sum;
        ?>
    @endforeach

    <tr>
        <td colspan="12" align="right">Всего:</td>
        <td><b>{{ $totSum }}</b></td>
    </tr>
    </tbody>
</table>

