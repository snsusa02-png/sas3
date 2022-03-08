{{--//принятие решения--}}
@if($rec->id==-1 and isset($rec->pre_items) and count($rec->pre_items)>0)
    <?php
    //список позиций, который высвечивается при создании новых записей - для ориентирования - что уже внесено
    ?>


    <div class="card mt-3 mb-3">
        <div class="card-header" style="background-color: #f2f2f2;">Ранее введенный состав (последние 10 строк)</div>
        <div class="card-body" id="order_info">

            <table class="table table-sm table-striped" style="width: 100%;">
                <tr>
                    <td>#</td>
                    <td>Наименование</td>
                    <td class="text-right">Кол-во,ЕИ</td>
                    <td>ЕИ</td>
                </tr>
                <?php
                $npp = 1;
                ?>
                @foreach ($rec->pre_items as $itm)
                    @if($npp<11)
                        <tr class="small">
                            <td class="text-right small">{{$itm->ordr}}</td>
                            <td><a href="{{route("invoice_items.edit",$itm->id)}}">{{$itm->itmname}}</a></td>
                            <td class="text-right">
                                {!!  \App\Traits\StringUtil::beauty_decimal($itm->qty,$itm->unit_decimal_dgts??3)!!}
                            </td>
                            <td class="text-left">{{$itm->unit}}</td>
                        </tr>
                        <?php
                        $npp++;
                        ?>
                    @else
                        <tr class="small">
                            <td colspan="4" class="text-center">...</td>
                        </tr>
                    @endif
                @endforeach
            </table>

        </div>
    </div>

@endif

