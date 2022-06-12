@if( isset($data->calendar_sums_items) and count($data->calendar_sums_items)>0)

    <div class="col-lg-4 col-md-4 col-sm-8">
        <div class="card card-stats mt-3">
            <div class="card-header">
                <i class="fa " aria-hidden="true"></i>
                {{$data->calendar_sums_title??''}}
                <button data-toggle="collapse" data-target="#cal_sums_data"
                        class="btn btn-light btn-sm float-right"><i class="fa fa-eye-slash" aria-hidden="true"></i>
                </button>
            </div>
            <div class="card-body collapse show" id="cal_sums_data" style="">

                <table class="table-condensed table-bordered table-striped w-100">
                    <thead>
                    <tr class="text-center small">
                        <th class="px-1">Дата</th>
                        <th class="px-1">Рейсы, шт</th>
                        <th class="px-1" title="Отгружено продукции покупателям">Отгрузка, тыс.руб</th>
                        <th class="px-1" title="Оплата, приход">Приход, тыс.руб</th>
                        <th class="px-1" title="Оплата, расход">Расход, тыс.руб</th>
                    </tr>
                    </thead>
                    <tbody>

                    <?php
                    $new_row = true;
                    $pre_dow = 9;
                    $now_day = now()->format('d');
                    $retURL = Request::url();
                    ?>

                    @foreach($data->calendar_sums_items as $itm)
                        <?php
                        $tclass = ($itm->day == $now_day) ? 'text-primary font-weight-bold ' : 'text-secondary';
                        $tr_style = ($itm->dow == 6) ? 'background-color:#fdffd5' : '';

                        $tclass2 = (isset($itm->raid_qty)) ? 'text-danger' : 'text-secondary ';
                        $inp_paysum = (isset($itm->inp_paysum) ? number_format($itm->inp_paysum / 1000, 1) : ' ');
                        $out_paysum = (isset($itm->out_paysum) ? number_format($itm->out_paysum / 1000, 1) : ' ');
                        $prod_salesum = (isset($itm->prod_salesum) ? number_format($itm->prod_salesum / 1000, 1) : ' ');
                        ?>
                        <tr style="{{$tr_style}}">
                            <td class="align-text-top small {{$tclass}}">{{date_create($itm->date)->format('d.m.Y')}}</td>
                            <td class="text-right">{{$itm->raid_qty??' '}}</td>
                            <td class="text-right">
                                @if(isset($itm->prod_salesum))
                                    <a href="{{route('reports.rep55',['date'=>$itm->date])}}?returl={{$retURL}}"
                                       title="Детализация производства и отгрузки продукции за день">
                                        {{$prod_salesum}}
                                    </a>
                                @else
                                    {{$prod_salesum}}
                                @endif
                            </td>
                            <td class="text-right">
                                @if(isset($itm->inp_paysum))
                                    <a href="{{route('reports.rep54',['date'=>$itm->date])}}?returl={{$retURL}}"
                                       title="Детализация платежей">
                                        {{$inp_paysum}}
                                    </a>
                                @else
                                    {{$inp_paysum}}
                                @endif
                            </td>
                            <td class="text-right">
                                @if(isset($itm->out_paysum))
                                    <a href="{{route('reports.rep54',['date'=>$itm->date])}}?returl={{$retURL}}"
                                       title="Детализация платежей">
                                        {{$out_paysum}}
                                    </a>
                                @else
                                    {{$out_paysum}}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

            </div>
        </div>
    </div>
@endif
