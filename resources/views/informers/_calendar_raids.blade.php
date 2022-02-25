@if( isset($data->calendar_raid_qtys) and count($data->calendar_raid_qtys)>0)

    <div class="col-lg-3 col-md-3 col-sm-6">
        <div class="card card-stats mt-3">
            <div class="card-header">
                <i class="fa " aria-hidden="true"></i>
                {{$data->calendar_raid_title??''}}
            </div>
            <div class="card-body " style="">

                <table class="table-condensed table-bordered table-striped w-100" >
                    <thead>
{{--                    <tr>--}}
{{--                        <th colspan="7">--}}
{{--                            <a class="btn"><i class="icon-chevron-left"></i></a>--}}
{{--                            <a class="btn"></a>--}}
{{--                            <a class="btn"><i class="icon-chevron-right"></i></a>--}}
{{--                        </th>--}}
{{--                    </tr>--}}
                    <tr class="text-center">
                        <th class="px-1">Пн</th>
                        <th class="px-1">Вт</th>
                        <th class="px-1">Ср</th>
                        <th class="px-1">Чт</th>
                        <th class="px-1">Пт</th>
                        <th class="px-1">Сб</th>
                        <th class="px-1">Вс</th>
                    </tr>
                    </thead>
                    <tbody>

                    <?php
                    $new_row = true;
                    $pre_dow = 9;
                    $now_day = now()->format('d');
                    ?>

                    @foreach($data->calendar_raid_qtys as $itm)

                        @if($itm->dow < $pre_dow)
                            @if($pre_dow<>9)
                                {!! '</tr>' !!}
                            @endif
                            {!! '<tr>' !!}
                            <?php
                            $dow = 1;
                            ?>
                            @while($dow < $itm->dow)
                                <td class="muted">&nbsp;</td>
                                <?php
                                $dow++;
                                ?>
                            @endwhile
                        @endif

                        <?php
                        $tclass = ($itm->day == $now_day) ? 'text-primary font-weight-bold ' : 'text-secondary';
                        $tclass2 = (isset($itm->raid_qty)) ? 'text-danger' : 'text-secondary ';
                        $pre_dow = $itm->dow;
                        ?>
                        <td class="align-text-top"><span class="small  {{$tclass}}"> {{$itm->day}}</span>
                            <div class="text-right {{$tclass2}}">{{$itm->raid_qty??'-'}}</div>
                        </td>
                        @endforeach
                        </tr>
                    </tbody>
                </table>

            </div>
        </div>
    </div>
@endif
