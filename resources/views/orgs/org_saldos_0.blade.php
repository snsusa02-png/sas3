@if (isset($rec) and $rec->id != -1 and in_array($rec->kindid,[1,2]) and isset($rec->org_saldos))
    <style>
        label {
            color: gray;
            margin-bottom: 0px;
        }

    </style>
    <div class="card mt-3">
        <div class="card-header">
            <i class="fa fa-balance-scale text-info" aria-hidden="true"></i>
            Сальдо с контрагентами

            <div class="float-right">
                @if (isset($rec->org_saldos) and count($rec->org_saldos)>0)
                    <button data-toggle="collapse" data-target="#org_saldos"
                            class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>
                @endif
            </div>
        </div>

        <div class="card-body collapse" id="org_saldos">
            @if(count($rec->org_saldos)>0)
                <table class="table table-striped">
                    <thead>
                    <tr class="small">
                        <td>Контрагент</td>
                        <td>Получено, &#x20bd;</td>
                        <td>Оплачено, &#x20bd;</td>
                        <td>Сальдо, &#x20bd;</td>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    ?>
                    @foreach($rec->org_saldos as $itm)
                        <?php
                        $saldo = $itm->upd_sum - $itm->pay_sum;
                        $saldo_style = ($saldo > 0) ? 'color:green' : 'color:red';
                        ?>
                        <tr>
                            <td>
                                {{$itm->ownorg_name}}
                            </td>
                            <td class="small text-right">
                                {{number_format($itm->upd_sum,2)}}
                            </td>
                            <td class="small text-right">
                                {{number_format($itm->pay_sum,2)}}
                            </td>
                            <td class="small text-right font-weight-bold" style="{{$saldo_style}}">
                                {{number_format($itm->upd_sum-$itm->pay_sum,2)}}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endif

