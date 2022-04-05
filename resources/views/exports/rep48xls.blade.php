<table>
    <thead>
    <tr>
        <td colspan="6">Детализация баланса</td>
    </tr>
    <tr>
        <td colspan="6">
            между  <b>{{$data->org->name??'-'}}</b>
            и  <b>{{$data->ownorg->name??'-'}}</b>
        </td>
    </tr>
    <tr>
        <td colspan="6">
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
    $totSum = $curSum = $data->org_saldo->saldo;
    ?>
    <tr>
        <td>{{ $data->org_saldo->ondate }}</td>
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
        <tr>
            <td>{{ $itm->operdate }}</td>
            <td>{{ $itm->descript}}/ {{ $itm->org_placename }}</td>
            <td>{{ $itm->qty }}</td>
            <td>{{ $itm->price }}</td>
            <td>{{ $itm->opersum }}</td>
            <td>{{ $curSum }}</td>
        </tr>
    @endforeach

    <tr>
        <td colspan="4" align="right">Всего:</td>
        <td><b>{{ $totSum }}</b></td>
    </tr>
    </tbody>
</table>
