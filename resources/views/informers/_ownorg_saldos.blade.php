@if( isset($data->ownorg_saldos) and count($data->ownorg_saldos)>0)

    <div class="col-lg-3 col-md-3 col-sm-6">
        <div class="card card-stats mt-3">
            <div class="card-header">
                <i class="fa fa-balance-scale text-danger" aria-hidden="true"></i>
                Текущий Баланс
            </div>
            <div class="card-body " style="">
                @foreach($data->ownorg_saldos as $itm)
                    <?php
                    $td_class = ($itm->saldo < 0) ? 'text-danger' : (($itm->saldo > 0) ? 'text-success' : '');
                    $td_class_sup = ($itm->saldo_sup < 0) ? 'text-danger' : (($itm->saldo_sup > 0) ? 'text-success' : '');

                    if ($itm->saldo < 0) {
                        $saldo_title = "Задолженность клиентов";
                    } elseif ($itm->saldo == 0) {
                        $saldo_title = "Баланс";
                    } else {
                        $saldo_title = "Сальдо с клиентами";
                    }
                    if ($itm->saldo_sup < 0) {
                        $saldo_sup_title = "Задолженность перед поставщиками";
                    } elseif ($itm->saldo_sup == 0) {
                        $saldo_sup_title = "Баланс";
                    } else {
                        $saldo_sup_title = "Сальдо с поставщиками";
                    }
                    ?>
                    <div class="row mb-3">
                        <div class="col-md-12">{{$itm->ownorgname}}</div>
                        <div class="col-md-6 text-right font-weight-bold {{$td_class}}"
                             title="{{$saldo_title}}"
                             style="font-size: 16px">{{number_format($itm->saldo,2)}}</div>
                        <div class="col-md-6 text-right font-weight-bold {{$td_class_sup}}"
                             title="{{$saldo_sup_title}}"
                             style="font-size: 16px">{{number_format($itm->saldo_sup,2)}}</div>
                    </div>
                @endforeach
            </div>

        </div>
    </div>
@endif
