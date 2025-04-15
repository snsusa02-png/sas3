<table>
    <thead>
    <tr>
        <td colspan="3" align="center">Баллы за перевозки</td>
    </tr>
    <tr>
        <td colspan="3" align="center">
            {{$data->period_title}}
        </td>
    </tr>
    <tr>
        <td colspan="3" align="center">
            по состоянию на {{now()}}
        </td>
    </tr>
    <tr></tr>
    <tr>
        <td class="text-center">Дата</td>
        <td class="text-left">Водитель</td>
        <td class="text-left">Кол-во баллов</td>
    </tr>
    </thead>
    <tbody>
    <?php
    $npp = 0;
    $totSum = 0;
    ?>
    @foreach($recs as $rec)

        <tr class="text-left">
            <td>{{date_format(date_create($rec->wrkdate),'d.m.Y')}}</td>
            <td width="25">{{$rec->drivername}}</td>
            <td x:num width="15">{{$rec->route_points}}</td>
        </tr>
        <?php
        $totSum += $rec->route_points;
        ?>
    @endforeach

    <tr>
        <td colspan="2" align="right">Всего:</td>
        <td><b>{{ $totSum }}</b></td>
    </tr>
    </tbody>
</table>

