@if( $rec->id<>-1 and isset($rec->eritmsup_items) )

    <style>

    </style>
    <div class="card mt-3 mb-3">
        <div class="card-header" style="background-color: #f2ecc0;">
            <i class="fa fa-list-ul" aria-hidden="true"></i>*** Использование в заявках на материалы
        </div>
        <div class="card-body" id="">
            @if(count($rec->eritmsup_items)>0)
                <table class="table table-striped table-sm" style="width: 100%;">
                    <tr class="small">
                        <td></td>
                        <td>Заявка №</td>
                        <td>Позиция</td>
                        <td class="text-right">Получено по УПД, ЕИ</td>
                        <td class="text-right">Передано по М-15, ЕИ</td>
                    </tr>
                    <?php
                    $totSum = 0;
                    ?>
                    @foreach($rec->eritmsup_items as $itm)
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
                            <td class="text-right">
                                {{number_format($itm->get_qty,3)}} {{$itm->unit}}
                            </td>
                            <td class="text-right">
                                {{number_format($itm->m15_qty,3)}} {{$itm->unit}}
                            </td>
                        </tr>
                        @php($totSum+=$itm->ord_qty*$itm->ord_price)
                    @endforeach
                </table>
            @else
                - не используется -
            @endif
        </div>
    </div>

@endif

