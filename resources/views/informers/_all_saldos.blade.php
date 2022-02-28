@if( isset($data->all_saldos) and count($data->all_saldos)>0)

    <div class="col-lg-12 col-md-12 col-sm-6">
        <div class="card card-stats mt-3">
            <div class="card-header">
                <i class="fa fa-balance-scale text-danger" aria-hidden="true"></i>
                Текущий Баланс
            </div>
            <div class="card-body " style="">
                @foreach($data->all_saldos as $itm)
                    <?php
                    $td_class = ($itm->saldo < 0) ? 'text-danger' : (($itm->saldo > 0) ? 'text-success' : '');

                    if ($itm->saldo < 0) {
                        $saldo_title = "Задолженность клиентов";
                    } elseif ($itm->saldo == 0) {
                        $saldo_title = "Баланс с клиентами";
                    } else {
                        $saldo_title = "Авансирование от клиентов";
                    }
                    ?>
                    <div class="row mb-3">
                        <div class="col-md-5">{{$itm->orgname}}</div>
                        <div class="col-md-5">{{$itm->ownorgname}}</div>
                        <div class="col-md-2 text-right font-weight-bold {{$td_class}}"
                             title="{{$saldo_title}}"
                             style="font-size: 16px">{{number_format($itm->saldo,0)}}</div>
                    </div>
                @endforeach
            </div>

        </div>
    </div>
@endif
