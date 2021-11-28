@if( isset($data->ownorg_saldos) and count($data->ownorg_saldos)>0)

    <div class="col-lg-3 col-md-3 col-sm-6">
        <div class="card card-stats mb-1 mt-3">
            <div class="card-header">
                <i class="fa fa-balance-scale text-danger" aria-hidden="true"></i>
                Текущий Баланс
            </div>
            <div class="card-body " style="">
                @foreach($data->ownorg_saldos as $itm)
                    <?php
                    $td_class = ($itm->saldo < 0) ? 'text-danger' : (($itm->saldo > 0) ? 'text-success' : '');
                    ?>
                    <div class="row">
                        <div class="col-md-12">{{$itm->ownorgname}}</div>
                        <div class="col-md-12 text-right font-weight-bold {{$td_class}}"
                             style="font-size: 24px">{{number_format($itm->saldo,2)}}</div>
                    </div>
                @endforeach
            </div>

        </div>
    </div>
@endif
