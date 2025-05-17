<table>
    <thead>
    <tr>
        <td colspan="8" align="center">{!! $data->title !!}</td>
    </tr>
    <tr>
        <td colspan="8" align="center">
            {!! $data->sub_title !!}
        </td>
    </tr>
    <tr>
        <td colspan="8" align="center">
            по состоянию на {{now()}}
        </td>
    </tr>
    <tr></tr>
    <tr class="text-left small" valign="top">
        <td class="text-left">№ Авто</td>
        <td class="text-center">Тип авто</td>
        <td class="text-center">№ карты</td>
        <td class="text-right">Объем топлива, л</td>
        <td class="text-right">Сумма, &#8381;</td>
        <td class="text-right">Средняя цена, &#8381;/л</td>
        <td class="text-right">Кол-во заправок</td>
        <td class="text-right">Средняя заправка, л</td>
    </tr>
    </thead>

    <tbody>
    <?php
    $paySum = $fuelQty = $payQty = 0;
    ?>
    @foreach($recs as $rec)
        <tr class="text-left ">
            <td class="text-left small" WIDTH="40">{{$rec->machine_name}}</td>
            <td class="text-center small" width="10">{{$rec->mchntype_name}}</td>
            <td class="text-center small" width="10">{{$rec->card_num}}</td>
            <td class="text-right" with="15">{{round($rec->fuelqty,0)}}</td>
            <td class="text-right" with="15">{{round($rec->paysum,2)}}</td>
            <td class="text-right small" width="15">{{round($rec->paysum/$rec->fuelqty,2)}}</td>
            <td class="text-right small" width="15">{{round($rec->payqty,0)}}</td>
            <td class="text-right small" width="15">{{round($rec->fuelqty/$rec->payqty,0)}}</td>
        </tr>
        <?php
        $paySum += $rec->paysum;
        $fuelQty += $rec->fuelqty;
        $payQty += $rec->payqty;
        ?>
    @endforeach

    @if(1==1)
        <tr class="text-left" style="background-color: #dacf64">
            <td colspan="7" class="text-left pl-2"></td>
        </tr>
        <tr>
            <td colspan="3" class="text-right">Всего:</td>
            <td class="text-right font-weight-bold">{{round($fuelQty,2)}}</td>
            <td class="text-right font-weight-bold">{{round($paySum,2)}}</td>
            <td class="text-right font-weight-bold small">{{round($paySum/$fuelQty,2)}}</td>
            <td class="text-right font-weight-bold small">{{round($payQty,0)}}</td>
        </tr>
    @endif
    </tbody>
    <tfoot>
</table>

