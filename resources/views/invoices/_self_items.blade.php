@if($rec->id<>-1 and isset($rec->self_items))
    <?php
    $TotPaySum = 0;
    ?>

    <div class="card mt-2">
        <div class="card-header" style="background-color: #caecff;">

			<span data-toggle="collapse" data-target="#items">
				<i class="fa fa-list-ol text-primary" aria-hidden="true"></i> Состав документа поставщика</span>

            <div class="float-right">
                @if (count($rec->self_items)>0)
                    <button data-toggle="collapse" data-target="#self_items"
                            class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>
                @endif
                @if ($usrrights['save'])
                    <a href="{{ route('invoice_items.create',$rec->id)}}"
                       class="btn btn-warning btn-sm">
                        <i class="fa fa-plus"></i>
                    </a>
                    @if (1==1)
                        <a href="{{ route('invoice_items.create',$rec->id)}}"
                           class="btn btn-success btn-sm" title="Загрузить состав из XLS-файла">
                            <i class="fa fa-plus"></i>
                        </a>
                    @endif
                @endif

            </div>

        </div>
        @if (count($rec->self_items)>0)
            <style>
            </style>


            <div class="card-body  show" id="self_items">
                <table class="table-striped " style="width: 100%;">
                    <thead>
                    <tr class="text-center align-middle small">
                        <td>#</td>
                        <td class="text-left">Артикул</td>
                        <td class="text-left">Товары (работы, услуги)</td>
                        <td class="text-center">Кол-во, ЕИ</td>
                        <td class="text-right">Цена, руб</td>
                        <td class="text-right">Сумма, руб</td>
                        <td/>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    $docsum = 0;
                    ?>
                    @foreach($rec->self_items as $itm)
                        <?php
                        $npp++;

                        $linestyle = "";

                        //$docsum += $itm->qty * $itm->price;
                        $docsum += $itm->itmsum;
                        ?>
                        <tr class="align-top ">
                            <td class="small text-right">{{$loop->iteration}}</td>
                            <td class="text-left " style="{{$linestyle}}">
                                {{$itm->code}}
                            </td>
                            <td class="text-left " style="{{$linestyle}}">
                                <a href="{{ route('invoice_items.edit',$itm->id)}}"
                                   class="">
                                    {{$itm->itmname}}
                                </a>

                                @if(isset($itm->refitmid))
                                    <a href="{{ route('refitems.edit',$itm->refitmid)}}" target="_blank"
                                       class="float-right">
                                        <i class="fa fa-book text-success" aria-hidden="true"></i>
                                    </a>
                                    @if($itm->refitmname<>$itm->itmname)
                                        <div class="small">{{$itm->refitmname}}</div>
                                    @endif

                                @elseif($usrrights['add2refitems']??false)
                                    <a href="{{ route('invoice_items.add2refitems',$itm->id)}}"
                                       onclick="return confirm('Вы хотите добавить эту запись в справочник Номенклатуры?')"
                                       class="float-right">
                                        <i class="fa fa-plus text-warning" aria-hidden="true"></i>
                                    </a>
                                @endif

                            </td>
                            <td class="text-right " style="{{$linestyle}}" nowrap="">
                                {!!  \App\Traits\StringUtil::beauty_decimal($itm->qty,$itm->ut_dec_dgts)!!}
                                <b>{{$itm->unit}}</b>
                            </td>
                            <td class="text-right small" style="{{$linestyle}}">
                                {{--                                {{number_format($itm->price,2)}}--}}
                                {{rtrim(number_format($itm->itmsum/$itm->qty,4),'0')}}
                            </td>
                            <td class="text-right" style="{{$linestyle}}">
                                {!!  \App\Traits\StringUtil::beauty_decimal($itm->itmsum,2)!!}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <td colspan="5" class="text-right">
                            @if($usrrights['make_equiprqst']??false)
                                <a href="{{route('invoices.make_equiprqst',$rec->id)}}" class="btn bnt-sm btn-warning mr-2">Создать
                                    заявку на
                                    материалы</a>
                            @endif
                            Всего:
                        </td>
                        <td class="text-right"><b> {!!  \App\Traits\StringUtil::beauty_decimal($docsum,2)!!}</b></td>
                    </tr>
                    </tfoot>
                </table>

            </div>

        @endif

        @if (1==0 and count($rec->self_items)>0)
            <div class="card-footer">
            </div>
        @endif

    </div>
@endif
