@if( $rec->id<>-1 and isset($rec->orgplnpay_items) and $rec->doctypeid==1 )

    <style>
        .current {
            background-color: #f0f0f0;
            color: gray;
        }
    </style>
    <div class="card mt-3 mb-3">
        <div class="card-header" style="background-color: #daf2d5;">
            <i class="fa fa-rub" aria-hidden="true"></i> Данные по оплате
        </div>
        <div class="card-body" id="">

            @if(count($rec->orgplnpay_items)>0)
                <table class="table table-striped table-sm" style="width: 100%;">
                    <tr class="small">
                        <td>Запрошено сумма, дата</td>
                        <td class="text-right">Согласование</td>
                        {{--                        <td>соглас. 2, руб</td>--}}
                        <td>Оплачено, руб</td>

                    </tr>
                    <?php
                    $totPlnPaySum = 0;
                    $totAgr1Sum = 0;
                    $totAgr2Sum = 0;
                    $totFctPaySum = 0;
                    //dd($rec->orgplnpay_item->doc)
                    ?>
                    @foreach($rec->orgplnpay_items as $itm)
                        <?php
                        if (isset($itm->agr1_by)) {
                            $sh_agr1_sum = number_format($itm->agr1_sum, 2);
                            $sh_agr1_title = $sh_agr1_sum . ' - согласовано ' . $itm->agr1_username;
                        } else {
                            $sh_agr1_sum = '-';
                            $sh_agr1_title = 'Не согласовано руководителем';
                        }
                        if (isset($itm->agr2_by)) {
                            $sh_agr2_sum = number_format($itm->agr2_sum, 2);
                            $sh_agr2_title = $sh_agr2_sum . ' - согласовано ' . $itm->agr2_username;
                        } else {
                            $sh_agr2_sum = '-';
                            $sh_agr2_title = 'Не согласовано Фин. директором';
                        }
                        //$sh_agr1_sum = (isset($itm->agr1_by)) ? number_format($itm->agr1_sum, 2) : '-';
                        //$sh_agr2_sum = (isset($itm->agr2_by)) ? number_format($itm->agr2_sum, 2) : '-';

                        $agr_class = (($itm->agr1_sum <> $itm->agr2_sum)
                            or $sh_agr1_sum == '-' or $sh_agr2_sum == '-') ? 'text-danger' : 'text-success';

                        $totPlnPaySum += $itm->plnpaysum;
                        $totFctPaySum += $itm->fctpaysum;
                        ?>
                        <tr class="">
                            <td><a href="{{route('orgplnpay_items.edit',$itm->id)}}" target="_blank" class="small">
                                    <div class="text-right">{{$itm->plnpaysum}}</div>
                                    {{date_create($itm->updated_at)->format('d.m.Y H:i')}} <i
                                            class="fa fa-external-link" aria-hidden="true"></i></a></td>
                            <td class="text-right small {{$agr_class}}">
                                <span title="{{$sh_agr1_title}}">{{$sh_agr1_sum}}</span>
                                <br><span title="{{$sh_agr2_title}}">{{$sh_agr2_sum}}</span>
                            </td>
                            {{--                            <td class="text-right small {{$agr_class}}">{{$sh_agr2_sum}}</td>--}}
                            <td class="text-right">
                                @if(isset($itm->fctpay_at))
                                    <div class="font-weight-bold ">{{number_format($itm->fctpaysum,2)}}</div>
                                    <div class="small">{{date_create($itm->fctpay_at)->format('d.m.Y H:i')}}</div>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    <tr class="text-right">
                        <td colspan="1">Всего: {{number_format($totPlnPaySum,2)}}</td>
                        <td></td>
                        <td class="font-weight-bold">{{number_format($totFctPaySum,2)}}</td>
                    </tr>

                </table>
            @else
                - нет данных -
            @endif
        </div>
    </div>

@endif

