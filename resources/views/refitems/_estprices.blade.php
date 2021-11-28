@if($rec->id != -1 and isset($rec->estprices) )
    <?php
    $TotPaySum = 0;
    ?>
    <div class="card mt-3">
        <div class="card-header" style="background-color: #c8ffa0;">

            <a name="estprices"></a>

            <span data-toggle="collapse" data-target="#estprices" title="Мониторинг актуальных предложений товара">
				<i class="fa fa-compress" aria-hidden="true"></i> Актуальные предложения </span>

            <div class="float-right">
                @if (count($rec->estprices)>0)
                    <button data-toggle="collapse" data-target="#estprices"
                            class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>
                @endif

                @if($usrrights['ri_estprices.create']??true)
                    <a href="{{ route('ri_estprices.create',$rec->id)}}"
                       class="btn btn-warning btn-sm"
                       style="margin-left:16px;float: right;">
                        <i class="fa fa-plus"></i>
                    </a>
                @endif
            </div>

        </div>
        @if (count($rec->estprices)>0)
            <div class="card-body collapse show" id="estprices">

                <table class="table-striped table-bordered0" style="width: 100%;" cellpadding="2">
                    <thead>
                    <tr class="text-center align-middle small">
                        <td>#</td>
                        <td class="text-right">Цена, руб</td>
                        <td class="text-right">Срок, дн</td>
                        <td class="text-left">Поставщик, период доступности</td>
                        <td style="width:32px">
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    $totSum = 0;
                    $minPrice = $rec->estprices->min('price');
                    $avgPrice = 0;
                    $maxPrice = $rec->estprices->max('price');
                    $avgPrice = $rec->estprices->avg('price');
                    $minSupDays = $rec->estprices->min('actsupwrkdays');
                    $cur_act_status = -1;
                    $act_statuses = [1 => 'сейчас', 2 => 'в будущем'];

                    //dd($minPrice, $maxPrice, $avgPrice);
                    ?>
                    @foreach($rec->estprices as $itm)
                        @if($itm->act_status<>$cur_act_status)
                            <tr>
                                <td colspan="5">
                                    <b>{{$act_statuses[$itm->act_status]??'-'}}</b>
                                </td>
                            </tr>
                            <?php
                            $cur_act_status = $itm->act_status;
                            ?>
                        @endif

                        <?php
                        $npp++;

                        $begdate = $itm->begdate;
                        if (isset($begdate) and $itm->act_status > 1) {
                            $begdate = date_create($begdate);
                            $sh_begdate = $begdate->format('d.m.Y');
                        } else {
                            $sh_begdate = '';
                        }

                        $enddate = $itm->enddate;
                        if (isset($enddate)) {
                            $enddate = date_create($enddate);
                            $sh_enddate = $enddate->format('d.m.Y');
                        } else
                            $sh_enddate = '...';

                        $td_price_style = ($itm->price == $minPrice)
                            ? 'background-color:#C9FDD6'
                            : (($itm->price == $maxPrice) ? 'background-color:#FDD6D6' : '');
                        $td_supdays_style = ($itm->actsupwrkdays == $minSupDays)
                            ? 'background-color:#C9FDD6' : '';
                        ?>
                        <tr class="align-top ">
                            <td class="small text-right">{{$loop->iteration}}</td>
                            <td class="text-right pl-1" style="{{$td_price_style}}">
                                <a href="{{ route('ri_estprices.edit',['id'=>$itm->id])}}?returl={{Request::url()}}">
                                    {{number_format($itm->price,2)}}
                                </a>
                            </td>
                            <td class="text-right " style="{{$td_supdays_style}}">
                                {{$itm->actsupwrkdays}}
                            </td>
                            <td class="text-left small pl-2" style="">
                                {{$itm->suporg->name}}
                                <div class="ml-3">{{$sh_begdate}} - {{$sh_enddate}}</div>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('ri_estprices.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
                                   class="btn btn-sm btn-light"
                                   title="Просмотреть/Изменить запись">
                                    <i class="fa fa-pencil"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if (isset($npp))
            <div class="card-footer">
                <div class="small text-right">
                    <ul>
                        <li>Min цена: {{number_format($minPrice,2)}}</li>
                        <li>Max цена: {{number_format($maxPrice,2)}}</li>
                        <li>Средняя цена: {{number_format($avgPrice,2)}}</li>
                    </ul>

                </div>
            </div>
        @endif

    </div>
@endif
