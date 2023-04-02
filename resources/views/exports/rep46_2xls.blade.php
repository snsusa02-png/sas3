@if (isset($recs) and $recs->count()>0)
    <table>
        <thead>
        <tr>
            <td colspan="7" align="right" style="font-size: 7px;">по состоянию на {{now()}}</td>
        </tr>
        <tr style="line-height: 16px;">
            <td colspan="7" align="center" style="line-height: 16px; font-weight: bold; font-size: 16px; ">Карьеры: {{$data->period_title}}</td>
        </tr>
        <tr>
            <td colspan="7"></td>
        </tr>
        <tr style="font-weight: bold" valign="top">
            <th style="font-weight: bold; width:17px">Место</th>
            <th style="font-weight: bold;width: 20px">Поставщик</th>
            <th style="font-weight: bold; width: 34px">Товар/Услуга</th>
            <th style="font-weight: bold; text-align: center">ЕИ</th>
            <th style="font-weight: bold">Объем, ЕИ</th>
            <th style="font-weight: bold">Цена за ЕИ, &#8381;</th>
            <th style="font-weight: bold; width:10px;">Сумма, &#8381;</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $npp = 0;
        $totUnloadSum = 0;
        $totLoadSum = 0;
        ?>
        @foreach($recs as $rec)

            <tr class="text-left" valign="top">
                <td style="font-size: 8px;">{{$rec->load_placename}}</td>
                <td style="font-size: 8px;">{{$rec->suporg_name}}</td>
                <td>{{$rec->refitm_name}}</td>
                <td style="text-align: center;">{{$rec->unit}}</td>
                <td>{{$rec->load_qty}}
                <td>{{$rec->load_price}}
                <td>{{$rec->load_qty*$rec->load_price}}
            </tr>
            <?php
            $totLoadSum += $rec->load_qty * $rec->load_price;
            ?>
        @endforeach

        <tr>
            <td colspan="6" style="text-align: right;font-weight: bold;">Всего:</td>
            <td style="text-align: right;font-weight: bold;">{{$totLoadSum}}</td>
        </tr>
        @if(1==0)
            <tr>
                <td colspan="6" style="text-align: right;font-weight: bold;">Баланс, &#8381;:</td>
                <td style="text-align: right;font-weight: bold;">{{$totUnloadSum-$totLoadSum}}</td>
            </tr>
        @endif
        </tbody>

    </table>
@endif
