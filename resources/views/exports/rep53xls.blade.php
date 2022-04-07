<table>
    <thead>
    <tr>
        <td colspan="6" align="center">Детализация баланса</td>
    </tr>
    <tr>
        <td colspan="6" align="center">
            между {{$data->org->name??'-'}}
            и {{$data->ownorg->name??'-'}}
        </td>
    </tr>
    <tr>
        <td colspan="6" align="center">
            по состоянию на {{now()}}
        </td>
    </tr>
    <tr></tr>
    <tr>
        <th>Дата</th>
        <th>Операция</th>
        <th>Кол-во</th>
        <th>Цена,руб</th>
        <th>Сумма, руб</th>
        <th>Тек. сальдо, руб</th>
    </tr>
    </thead>
    <tbody>
    <?php
    $npp = 0;
    $totSum = $curSum = (isset($data->org_saldo)) ? $data->org_saldosaldo : 0;
    ?>
    <tr>
        <td>{{ date_create($data->org_saldo->ondate)->format('d.m.Y') }}</td>
        <td>- начальное сальдо -</td>
        <td></td>
        <td></td>
        <td>{{ $data->org_saldo->saldo }}</td>
        <td>{{ $curSum }}</td>
    </tr>

    @foreach($items as $itm)
        <?php
        $npp++;
        //$totFctSum += $itm->fctpaysum;
        $totSum += $itm->opersum;
        $curSum += $itm->opersum;
        ?>
        <tr style="background-color: #ccffca">
            <td width="10">{{ date_create($itm->operdate)->format('d.m.Y') }}</td>
            <td width="40">{{ $itm->descript}}/ {{ $itm->org_placename }}</td>
            <td x:num width="10">{{ $itm->qty }}</td>
            <td x:num width="10">{{ $itm->price }}</td>
            <td x:num width="12">{{ $itm->opersum }}</td>
            <td x:num width="15">{{ $curSum }}</td>
        </tr>
    @endforeach

    <tr>
        <td colspan="4" align="right">Всего:</td>
        <td><b>{{ $totSum }}</b></td>
    </tr>
    </tbody>
</table>
