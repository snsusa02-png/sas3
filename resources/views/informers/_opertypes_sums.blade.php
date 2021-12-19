@if( isset($data->ownorg_saldos) and count($data->ownorg_saldos)>0)

    <div class="col-lg-6 col-md-6 col-sm-12">
        <div class="card card-stats mb-1 mt-3">
            <div class="card-header">
                <i class="fa fa-calculator text-success" aria-hidden="true"></i>
                Показатели по видам деятельности
            </div>
            <div class="card-body " style="">
                <table class="table">
                    <tr>
                        <td>Вид</td>
                        <td>Сегодня</td>
                        <td>Вчера</td>
                        <td>За 7 дней</td>
                        <td>За 30 дней</td>
                    </tr>
                    @foreach($data->opertypes_sums as $itm)
                        <?php
                        $td_class = ($itm->saldo < 0) ? 'text-danger' : (($itm->saldo > 0) ? 'text-success' : '');
                        ?>
                        <tr>
                            <td>{{$itm->opertype_name}}</td>
                            <td class="text-right font-weight-bold {{$td_class}}">
                                <div class="text-danger">{{number_format($itm->today_load_sum,2)}}</div>
                                <div class="text-success">{{number_format($itm->today_unload_sum,2)}}</div>
                                <div
                                    style="font-size: 16px;border-top:1px solid gray;">{{number_format($itm->today_unload_sum-$itm->today_load_sum,2)}}
                                </div>
                            </td>
                            <td class="text-right font-weight-bold {{$td_class}}">
                                <div class="text-danger">{{number_format($itm->yesterday_load_sum,2)}}</div>
                                <div class="text-success">{{number_format($itm->yesterday_unload_sum,2)}}</div>
                                <div
                                    style="font-size: 16px;border-top:1px solid gray;">{{number_format($itm->yesterday_unload_sum-$itm->yesterday_load_sum,2)}}
                                </div>
                            </td>
                            <td class="text-right font-weight-bold {{$td_class}}">
                                <div class="text-danger">{{number_format($itm->d7_load_sum,2)}}</div>
                                <div class="text-success">{{number_format($itm->d7_unload_sum,2)}}</div>
                                <div
                                    style="font-size: 16px;border-top:1px solid gray;">{{number_format($itm->d7_unload_sum-$itm->d7_load_sum,2)}}
                                </div>
                            </td>
                            <td class="text-right font-weight-bold {{$td_class}}">
                                <div class="text-danger">{{number_format($itm->d30_load_sum,2)}}</div>
                                <div class="text-success">{{number_format($itm->d30_unload_sum,2)}}</div>
                                <div
                                    style="font-size: 16px;border-top:1px solid gray;">{{number_format($itm->d30_unload_sum-$itm->d30_load_sum,2)}}
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>

        </div>
    </div>
@endif
