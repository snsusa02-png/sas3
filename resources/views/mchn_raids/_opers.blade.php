@if ($rec->id != -1 )
    <div class="row">

        <div class="col-md-12">
            <div class="card mt-3">
                <div class="card-header">
                    <i class="fa fa-money text-success" aria-hidden="true"></i>
                    Операции

                    <span class="float-right">
                        <button data-toggle="collapse" data-target="#_opers"
                                class="btn btn-light btn-sm">
                            <i class="fa fa-eye-slash" aria-hidden="true"></i>
                            <span class="badge badge-info">{{count($rec->opers)}}</span>
                        </button>
                        @if($usrrights['save']??true)
                            <a href="{{ route('mr_opers.create',['mr_id'=>$rec->id])}}"
                               class="btn btn-warning btn-sm ml-1">
                            <i class="fa fa-plus"></i>
                        </a>
                        @endif
                    </span>
                </div>

                @if (count($rec->opers)>0)
                    <div class="card-body " id="_opers">
                        <table class="table table-striped w-100" style="">
                            <thead>
                            <tr>
                                <td>#</td>
                                <td class="small">Наименование</td>
                                <td>Заказчик/Плательщик
                                    <div class="small">место</div>
                                </td>
                                <td>Исполнитель/Поставщик
                                    <div class="small">место</div>
                                </td>
                                <td>Товар/Услуга</td>
                                <td>Кол-во, ЕИ</td>
                                <td>Цена, &#8381;</td>
                                <td>Сумма, &#8381;</td>
                                <td></td>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $gk_sum = 0;
                            ?>
                            @foreach($rec->opers as $itm)
                                <tr>
                                    <td style="text-align: right;"
                                        class="small">{{$loop->iteration}}
                                    </td>

                                    <td class="small">
                                        <a name="oper_{{$itm->id}}" id="oper_{{$itm->id}}"></a>
                                        <a href="{{ route('mr_opers.edit',$itm->id)}}">
                                            {{$rec->saledirs[$itm->sale_dir]??'?'}}
                                            <div>{{$itm->name}}</div>
                                        </a>
                                    </td>
                                    <td class="text-left">
                                        <?php
                                        $bold = ($itm->org_gk == 0) ? 'font-weight-bold' : '';
                                        ?>
                                        <span class="{{$bold}}" title="">
                                            {{$itm->org_name}}
                                        <div class="mr-3 small">
                                            {{$itm->org_place_name??$itm->org_placename}}
                                        </div>
                                        </span>
                                    </td>
                                    <td class="text-left">
                                        <?php
                                        $bold = ($itm->sup_gk == 0) ? 'font-weight-bold' : '';
                                        ?>
                                        <span class="{{$bold}}"
                                              title="">
                                            {{$itm->sup_name}}
                                        </span>
                                        <div class="mr-3 small">
                                            {{$itm->sup_place_name??$itm->sup_placename}}
                                        </div>
                                    </td>
                                    <td>
                                        <div>{{$itm->itm_name}}

                                        </div>
                                    </td>
                                    <td class="text-right">
                                        {{$itm->itm_qty}}
                                        <div class="float-right font-weight-bold">{{$itm->unittype_name}}</div>
                                    </td>
                                    <td class="text-right">
                                        {{$itm->itm_price}}
                                    </td>
                                    <td class="text-right">
                                        {{$itm->itm_qty*$itm->itm_price}}
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('mr_opers.edit',$itm->id)}}"
                                           class="btn btn-sm btn-primary">
                                            <i class="fa fa-pencil">
                                            </i>
                                        </a>
                                    </td>
                                </tr>
                                <?php
                                $gk_sum += $itm->sale_dir * $itm->itm_qty * $itm->itm_price;
                                ?>
                            @endforeach
                            <tr style="background-color: #fff5c6">
                                <td class="text-right" colspan="7">Баланс по ГК:</td>
                                <td class="text-right font-weight-bold">{{$gk_sum}}</td>
                                <td></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
