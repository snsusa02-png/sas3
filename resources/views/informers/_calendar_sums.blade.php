@if( isset($data->calendar_sums_items) and count($data->calendar_sums_items)>0)

    <div class="col-lg-3 col-md-3 col-sm-6">
        <div class="card card-stats mt-3">
            <div class="card-header">
                <i class="fa " aria-hidden="true"></i>
                {{$data->calendar_sums_title??''}}
            </div>
            <div class="card-body " style="">

                <table class="table-condensed table-bordered table-striped w-100">
                    <thead>
                    <tr class="text-center small">
                        <th class="px-1">Дата</th>
                        <th class="px-1">Рейсы, шт</th>
                        <th class="px-1">Приход, тыс.руб</th>
                        <th class="px-1">Расход, тыс.руб</th>
                    </tr>
                    </thead>
                    <tbody>

                    <?php
                    $new_row = true;
                    $pre_dow = 9;
                    $now_day = now()->format('d');
                    ?>

                    @foreach($data->calendar_sums_items as $itm)
                        <?php
                        $tclass = ($itm->day == $now_day) ? 'text-primary font-weight-bold ' : 'text-secondary';
                        $tr_style = ($itm->dow == 6) ? 'background-color:#fdffd5' : '';

                        $tclass2 = (isset($itm->raid_qty)) ? 'text-danger' : 'text-secondary ';
                        $inp_paysum = (isset($itm->inp_paysum) ? number_format($itm->inp_paysum / 1000, 1) : ' ');
                        $out_paysum = (isset($itm->out_paysum) ? number_format($itm->out_paysum / 1000, 1) : ' ');
                        ?>
                        <tr style="{{$tr_style}}">
                            <td class="align-text-top small {{$tclass}}">{{date_create($itm->date)->format('d.m.Y')}}</td>
                            <td class="text-right">{{$itm->raid_qty??' '}}</td>
                            <td class="text-right">{{$inp_paysum}}</td>
                            <td class="text-right">{{$out_paysum}}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

            </div>
        </div>
    </div>
@endif
