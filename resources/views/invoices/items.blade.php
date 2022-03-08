@if($rec->id<>-1 and isset($rec->items))
    <?php
    $TotPaySum = 0;
    ?>

    <div class="card mt-2">
        <div class="card-header" style="background-color: #caecff;">

			<span data-toggle="collapse" data-target="#items">
				<i class="fa fa-list-ol text-primary" aria-hidden="true"></i> Состав / Использование</span>

            <div class="float-right">
                @if (count($rec->items)>0)
                    <button data-toggle="collapse" data-target="#items"
                            class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>
                @endif
            </div>

        </div>
        @if (count($rec->items)>0)
            <style>
            </style>

            <form name="forEdit" id="forGetQty" method="post"
                  action="{{ route($sysobjcode.'.update_upd_items', $rec->id) }}"
            >
                @method('PUT')
                @csrf
                {{ Form::hidden('ttt', 1) }}
                {{ Form::hidden('docid', $rec->id) }}
                {{ Form::hidden('doctypeid', $rec->doctypeid) }}

                <div class="card-body  show" id="items">
                    <table class="table-striped " style="width: 100%;">
                        <thead>
                        <tr class="text-center align-middle small">
                            <td>#</td>
                            <td class="text-left">Наименование</td>
                            <td class="text-center">ЕИ заявки</td>
                            <td class="text-right">Цена по счету, руб</td>
                            <td class="text-center">Заказано, ЕИ</td>
                            <td class="text-right small">Доступно, ЕИ</td>
                            @if($usrrights['save']??false)
                                <td class="text-center">Получено, ЕИ счета &nbsp;/&nbsp; Получено, ЕИ заявки</td>
                            @else
                                <td class="text-center">Получено, ЕИ счета</td>
                                <td class="text-center">Получено, ЕИ заявки</td>
                            @endif
                            <td/>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $npp = 0;
                        $totGetSum = 0;
                        ?>
                        @foreach($rec->items as $itm)
                            @if(($itm->ord_qty - $itm->otherget_qty)>0)
                                <?php
                                $npp++;

                                $linestyle = "";
                                //                        if ($itm->active == 0) {
                                //                            $linestyle = "background-color:lightsalmon;";
                                //                        }

                                $tdAgrSum_class = "";
                                $tdFctSum_class = (isset($itm->fctpaysum)) ? 'paydone' : '';

                                $qty_step = 1 / (10 ** $itm->ut_decimal_dgts);
                                $doc_qty_step = ($itm->doc_unit == $itm->unit) ? $qty_step : 0.001;
                                ?>
                                <tr class="align-top ">
                                    <td class="small text-right">{{$loop->iteration}}</td>
                                    <td class="text-left " style="{{$linestyle}}">
                                        <a href="{{ route('equiprqst_items.edit',$itm->eritmid)}}?returl={{Request::url()}}"
                                           target="_self">
                                            {{$itm->itmname}}</a>
                                        <div class="small float-right">заявка №<a
                                                href="{{ route('equiprqsts.edit',$itm->rqstid)}}?returl={{Request::url()}}"
                                                target="_blank">{{$itm->rqstid}}</a></div>
                                    </td>
                                    <td class="text-center">
                                        {{$itm->unit}}
                                    </td>
                                    <td class="text-right " style="{{$linestyle}}">
                                        {{number_format($itm->ord_price,2)}}
                                    </td>
                                    <td class="text-right pl-2" style="{{$linestyle}}" nowrap="">
                                        {{rtrim(number_format($itm->doc_qty,3,'.',' '),'0')}}
                                        <b>{{$itm->doc_unit}}</b>
                                        @if($itm->doc_unit<>$itm->unit)
                                            =
                                            {{number_format($itm->ord_qty,$itm->ut_decimal_dgts,'.',' ')}}
                                            <b>{{$itm->unit}}</b>
                                        @endif
                                    </td>
                                    <td class="text-right small" style="{{$linestyle}}">
                                        {{number_format($itm->ord_qty-$itm->otherget_qty-$itm->preget_qty,$itm->ut_decimal_dgts,'.',' ')}}
                                        <b>{{$itm->unit}}</b>
                                    </td>
                                    @if($usrrights['save']??false)
                                        <td class="text-right" colspan="2" style="max-width0: 60px;">
                                            <div class="input-group">
                                                {{ Form::hidden('eritmid[]', $itm->eritmid) }}
                                                {{ Form::hidden('offerid[]', $itm->id) }}
                                                <input type="number" name="doc_qty[]"
                                                       class="form-control text-right doc_qty"
                                                       style="min-width: 60px;"
                                                       min="0" step="{{$doc_qty_step}}"
                                                       decdgts="{{$itm->ut_decimal_dgts}}"
                                                       k_d2r="{{ $itm->doc_qty / $itm->ord_qty}}"
                                                       value="{{ round($itm->predoc_qty,$itm->ut_decimal_dgts)}}"
                                                       title="Получено в ЕИ счета">
                                                <div class="input-group-append">
                                                <span class="input-group-text"
                                                      style="font-size: 0.8em">{{$itm->doc_unit}}</span>
                                                </div>
                                                <input type="number" name="get_qty[]"
                                                       class="form-control text-right font-weight-bold get_qty"
                                                       style="min-width: 60px;"
                                                       min="0" step="{{$qty_step}}"
                                                       max="{{$itm->ord_qty-$itm->otherget_qty}}"
                                                       decdgts="{{$itm->ut_decimal_dgts}}"
                                                       value="{{round($itm->preget_qty,$itm->ut_decimal_dgts)}}"
                                                       ord_price="{{$itm->ord_price}}"
                                                       title="Получено в ЕИ заявки">
                                                <div class="input-group-append">
                                                <span class="input-group-text"
                                                      style="font-size: 0.8em">{{$itm->unit}}</span>
                                                </div>
                                            </div>
                                        </td>
                                    @else
                                        <td class="text-right">
                                            {{ round($itm->predoc_qty,$doc_qty_step)}}
                                            <span style="font-size: 0.8em">{{$itm->doc_unit}}</span>
                                        </td>
                                        <td class="text-right">
                                            <span
                                                class="font-weight-bold">{{round($itm->preget_qty,$itm->ut_decimal_dgts)}}</span>
                                            <span style="font-size: 0.8em">{{$itm->unit}}</span>
                                        </td>
                                    @endif
                                </tr>
                            @endif
                            <?php
                            $totGetSum += round($itm->ord_price * $itm->preget_qty, 2);
                            ?>
                        @endforeach
                        <tr>
                            <td colspan="8" class="text-right">Итого: <span id="totgetsum"
                                                                            class="font-weight-bold">{{number_format($totGetSum,2,'.','')}}</span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="8" class="text-right">Доп. затраты:
                                <span id="totextraexpsum"
                                      class="font-weight-bold">{{number_format($rec->totExtraExpSum??0,2,'.','')}}</span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="8" class="text-right">Всего:
                                <span id="totsum"
                                      class="font-weight-bold">{{number_format($rec->usedsum??0,2,'.','')}}</span>
                            </td>
                        </tr>

                        </tbody>
                    </table>

                    <div class="text-center">
                        @if ($usrrights['save'])
                            <hr>
                            <button type="submit" class="btn btn-success btn-sm text-right" title="Сохранить изменения">
                                <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                Сохранить
                            </button>
                        @endif
                    </div>

                </div>

            </form>
        @endif

        @if (1==0 and count($rec->items)>0)
            <div class="card-footer">
            </div>
        @endif

    </div>
@endif
