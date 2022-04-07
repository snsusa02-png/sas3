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
        <th width="10">Дата</th>
        <th width="36">Операция</th>
        <th width="10">Кол-во</th>
        <th width="10">Цена,руб</th>
        <th width="12">Сумма, руб</th>
        <th width="15">Тек. сальдо, руб</th>
    </tr>
    </thead>
    <tbody>
    <?php
    $npp = 0;
    ?>
    @if(isset($data->org_saldo))
        <?php
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
    @else
        <?php
        $totSum = $curSum = 0;
        ?>
    @endif

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
