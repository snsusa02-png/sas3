@if( $rec->id<>-1 and isset($rec->equiprqst_items) )

    <style>
        .current {
            background-color: #f0f0f0;
            color: gray;
        }
    </style>
    <div class="card mt-3 mb-3">
        <div class="card-header" style="background-color: #f2ecc0;">
            <i class="fa fa-list-ul" aria-hidden="true"></i> Использование в заявках на материалы
        </div>
        <div class="card-body" id="">
            @if(count($rec->equiprqst_items)>0)
                <table class="table table-striped table-sm" style="width: 100%;">
                    <tr class="small">
                        <td></td>
                        <td>Заявка №</td>
                        <td>Позиция</td>
                        <td class="text-center">ЕИ</td>
                        <td class="text-right">Кол-во, ЕИ</td>
                        <td class="text-right">Цена, руб</td>
                        <td class="text-right">Сумма, руб</td>

                        {{--                        <td class="text-center">План. дата поставки</td>--}}
                        <td class="text-right">Получено факт., ЕИ</td>
                        <td class="text-right">Получено по УПД, ЕИ</td>

                    </tr>
                    <?php
                    $totSum = 0;
                    ?>
                    @foreach($rec->equiprqst_items as $itm)
                        <?php
                        $url = "";
                        ?>
                        <tr class="align-top">
                            <td class="small">{{$loop->iteration}}</td>
                            <td><a href="{{ route('equiprqsts.edit',$itm->rqstid)}}?returl={{Request::url()}}"
                                   target="_self">
                                    {{$itm->rqstid}}</a></td>
                            <td><a href="{{ route('equiprqst_items.edit',$itm->eritmid)}}?returl={{Request::url()}}"
                                   target="_self">
                                    {{$itm->itmname}}</a></td>

                            <td class="text-center">
                                {{$itm->unit}}
                            </td>
                            <td class="text-right">
                                {{rtrim(number_format($itm->ord_qty,3),'0')}}
                            </td>
                            <td class="text-right small">
                                {{rtrim($itm->ord_price,'0')}}
                            </td>
                            <td class="text-right">
                                {{number_format($itm->ord_sum,2)}}
                            </td>
                            <?php
                            $plngetdate = (isset($itm->plngetdate))
                                ? date_create($itm->plngetdate)->format('d.m.Y')
                                : '-';

                            $dlvrd_style = ($itm->dlvrd_qty == $itm->ord_qty) ? 'color:green' : 'color:darkred';
                            $upd_style = ($itm->get_qty == $itm->ord_qty) ? 'color:green' : 'color:darkred';
                            ?>
                            @if(1==0)
                                <td class="text-center">
                                    {{$plngetdate}}
                                </td>
                            @endif
                            <td class="text-right" style="{{$dlvrd_style}}">
                                {{rtrim(number_format($itm->dlvrd_qty,3),'0')}}
                            </td>
                            <td class="text-right" style="{{$upd_style}}">
                                {{rtrim(number_format($itm->get_qty,3),'0')}}
                            </td>
                        </tr>
                        @php($totSum+=$itm->ord_qty*$itm->ord_price)
                    @endforeach
                    <tr class="">
                        <td colspan="6"></td>
                        <td class="">
                            <div class="font-weight-bold text-right">{{number_format($totSum,2)}}</div>
                            <div class="small"></div>
                        </td>
                        <td></td>
                    </tr>
                </table>
            @else
                - не используется -
            @endif
        </div>
    </div>

@endif

