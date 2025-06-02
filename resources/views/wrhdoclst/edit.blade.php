@extends('layouts.edit')

@section('content')
    <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
    <script src="{{ asset('js/jquery-ui.js') }}" defer></script>
    <script src="{{ asset('js/callListRefGoods.js') }}" defer></script>
    <script src="{{ asset('js/ri_ac_wrhdoclst.js') }}" defer></script>


    @if (!isset( $rec))
        <?php
        redirect()->route('wrhdocs.index');
        header("Location:" . route('wrhdocs.index'));
        die();
        ?>
    @else
        {{--dd(get_defined_vars())--}}
        @php($sysobjid = 205)
        <style>
            .uper {
                margin-top: 36px;
            }

            label {
                color: gray;
                margin-bottom: 0px;
            }


        </style>
        <?php
        //Отображать или нет Цену/Сумму определяется типом документа
        $showPrice = ($rec->wrhdoc->doctype->useprice == 1);
        $showCompound = ($rec->ri_produced == 1 or $rec->ri_disassembly == 1);
        if ($rec->prod_dir == 1)
            $prodLabel = 'Изготовлено по составу';
        elseif ($rec->prod_dir = -1)
            $prodLabel = 'Разукомплектовано по рецепту';

        //        dd($showPrice,$showCompound, $rec->ri_produced);

        //Отображать или нет кол-во из предшествующего документа определяется типом документа
        $showPreQry = ($rec->wrhdoc->doctype->need_predoc == 1
            and $rec->wrhdoc->doctypeid == 8);

        $inputMode = "";
        if (!$usrrights['save']) {
            $inputMode = " readonly";
        }
        $ri_InputMode = "";
        if (!$usrrights['refitm.edit'] or $showCompound) {
            $ri_InputMode = " readonly";
        }
        $PriceInputMode = "";
        if (!$usrrights['price.edit']) {
            $PriceInputMode = " readonly";
        }

        ?>
        <div class="container">

            <div class="row ">
                <div class="col-md-9 col-sm-12" style="min-width:450px;">
                    @include('layouts.edit_msgs')

                    <div class="card uper">
                        <div class="card-header">
                            Позиция для документа "{{$rec->wrhdoc->doctype->name}}"
                            № {{$rec->wrhdoc->docnum}} от {{$rec->wrhdoc->docdate}} <span class="small">({{$rec->docid}})</span>
                        </div>
                        <div class="card-body">
                            @include('layouts.err_msgs')

                            <?php
                            $ri_name = old('refitmname');
                            if (!isset($ri_name) and isset($rec->refitem->name))
                                $ri_name = $rec->refitem->name;

                            // $ri_name = (old('refitmname')) ?: $rec->refitem->name;
                            $rec->refitmid = old('refitmid') ?: ($rec->refitmid);
                            $rec->qty = (old('qty')) ?: $rec->qty;

                            ?>

                            <form name="forEdit" id="forEdit" method="post"
                                  action="{{ route('wrhdoclst.update', $rec->id) }}">

                                @method('PUT')
                                @csrf

                                {{ Form::hidden('docid', $rec->docid,['id'=>'docid']) }}
                                {{ Form::hidden('ownorgid', $rec->wrhdoc->ownorgid,['id'=>'ownorgid']) }}
                                {{ Form::hidden('ri_produced', $rec->ri_produced,['id'=>'ri_produced']) }}
                                {{ Form::hidden('cmpnd_ownorgid', $rec->cmpnd_ownorgid,['id'=>'cmpnd_ownorgid']) }}
                                {{ Form::hidden('cmpnd_on_date', $rec->cmpnd_on_date,['id'=>'cmpnd_on_date']) }}

                                @if ($showCompound)
                                    <div class="row">
                                        <div class="offset-md-0 col-md-12">
                                            <div class="form-group list-inline">
                                                <label for="">{{$prodLabel}}</label>
                                                <div class="input-group">
                                                    @if( 1==0)
                                                        {{ Form::hidden('cmpndid', $rec->cmpndid, ['id'=>'cmpnd_on_date', 'class'=>'ac_id']) }}
                                                        {{$rec->cmpnd_name}}
                                                    @else
                                                        <div class="input-group mb-3 ">
                                                            <input type="text" name="cmpnd_name" id="cmpnd_name"
                                                                   class="ac_name ac_cmpnd_name form-control font-weight-bold"
                                                                   {{$inputMode}}
                                                                   value="{{old('cmpnd_name',$rec->cmpnd_name)}}">
                                                            <input type="text"
                                                                   class="form-control text-center small ac_status"
                                                                   title=""
                                                                   style="display: none; border: #d7f3e3; max-width: 30px"
                                                                   readonly>
                                                            <input type="hidden" name="cmpndid" class="ac_id"
                                                                   id="cmpndid"
                                                                   value="{{old('cmpndid',$rec->cmpndid)}}">
                                                            <a class="btn btn-light id_lnk" data-id="cmpndid"
                                                               data-obj="ri_compounds"
                                                               target="_blank">
                                                                <i class="fa fa-info text-info" aria-hidden="true"></i>
                                                            </a>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif


                                <div class="row">
                                    <div class="offset-md-0 col-md-12">
                                        <div class="form-group">
                                            <label for="refitmid">Товар:&nbsp;</label>
                                            <div class="input-group mb-3 input-group-sm">

                                                <input type="text" class="form-control text-center ac_id"
                                                       style="max-width:120px;"
                                                       name="code"
                                                       id="code"
                                                       value="{{$rec->refitem->id}}" readonly>
                                                <input type="text" class="form-control font-weight-bold ac_refitm_name"
                                                       name="refitmname"
                                                       id="refitmname"
                                                       value="{{$ri_name}}"
                                                    {{$ri_InputMode}}
                                                />
                                                <input type="text" class="form-control text-center small"
                                                       style="display: none; border: #d7f3e3;" id="ac_refitmid"
                                                       readonly>
                                                <input type="hidden" name="refitmid" id="refitmid" class="ac_id"
                                                       value="{{$rec->refitmid}}">


                                                @if ($usrrights['refitm.edit'] and !$showCompound)
                                                    <div class="input-group-append">
                                                        <a onclick="callListRefItems({{$rec->wrhdoc->ownorgid}},{{($rec->wrhdoc->doctype->forstock==-1)?$rec->wrhdoc->boxid:0}})"
                                                           title="Выбор из справочника товаров"
                                                           class="btn btn-sm btn-primary">
                                                            <i class="fa fa-search" aria-hidden="true"></i>
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @if (isset($rec->refitem->descript))
                                    <div class="form-group">
                                        <label for="specinfo">доп. характеристики:&nbsp;</label>
                                        <div class="input-group mb-3 input-group-sm small offset-md-1">

                                        <span id="specinfo" style="border: 1px solid silver;">
{{--										   {{$rec->refitem->RI_specinfo('; ')}}--}}
                                            {{$rec->refitem->descript}}
									</span>
                                        </div>
                                    </div>
                                @endif

                                <div class="row">

                                    <div class="offset-md-6 col-md-3 offset-sm-4 col-sm-4 col-xs-6">
                                        <div class="form-group list-inline">
                                            <label for="">Количество, <span class="font-weight-bold"
                                                                            id="unit_html">{{$rec->refitem->unittype->name?:'еи'}}</span>:</label>
                                            <div class="input-group">

                                                @if( 1==0)
                                                    {{ Form::hidden('qty', $rec->qty) }}
                                                    {{$rec->qty}}
                                                @else
                                                    <input type="text" class="form-control text-right font-weight-bold"
                                                           id="qty" name="qty"
                                                           value="{{$rec->qty}}"
                                                    />
                                                @endif

                                                @if ($showPreQry)
                                                    <div class="input-group-prepend">
													<span class="input-group-text"
                                                          id="basic-addon1"
                                                          style="background-color:white; font-size:11px;"
                                                    >из {{$rec->prelst->qty}}
													<input type="hidden" name="preqty" value="{{$rec->prelst->qty}}"
                                                    />
													</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @if ($showPrice)
                                        <div class="offset-md-0 col-md-3 col-sm-4 col-xs-6">
                                            <div class="form-group list-inline">
                                                <label for="price">Цена, &#x20bd;:</label>
                                                <input type="text" class="form-control text-right bold"
                                                       id="price" name="price"
                                                       value="{{$rec->price}}"
                                                />
                                            </div>
                                        </div>
                                    @endif

                                </div>

                                <hr>
                                <div class="actions">
                                    @if ($usrrights['save'])
                                        <button type="submit" class="btn btn-success">
                                            <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                            Сохранить
                                        </button>
                                    @endif
                                    <?php
                                    ?>
                                    <a class="btn btn-close btn-info"
                                       href="{{ route('wrhdocs.edit',$rec->docid) }}">
                                        <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                        Закрыть
                                    </a>
                                    @if ($usrrights['delete'])
                                        <button type="submit"
                                                class="btn btn-danger"
                                                style="margin-left:24px"
                                                formaction="{{ route('wrhdoclst.delete', $rec->id)}}"
                                                formmethod="post"
                                                onclick="return confirm('Вы действительно хотите удалить позицию?')"
                                                title="Удалить"
                                        >
                                            <i class="fa fa-trash-o" aria-hidden="true"></i>
                                        </button>
                                    @endif
                                </div>

                                @include('layouts._who_when')
                            </form>
                        </div>
                    </div>
                </div>

            </div>

            @if ($rec->id>0 and isset($rec->cmpnd_lst))
                <div class="row">
                    <div class="col-md-7 offset-md-3">
                        {{--                    @if (count($rec->cmpnd_lst)>0)--}}
                        <?php
                        //$curDate = date_format(date_create(), 'Y-m-d');
                        $cur_wrkdate = -1;
                        ?>
                        <table class="table-sm table-striped mt-3" style="background-color: whitesmoke">
                            <caption>
                            </caption>
                            <tr>
                                <td colspan="4" class="text-right"><h6 class="mr-2">Планируемый расход материалов на
                                        производство</h6></td>
                            </tr>
                            <tr>
                                <th>Материал</th>
                                <th>Ед.изм.</th>
                                <th>Расход на 1 изделие</th>
                                <th>Всего на {{trim(number_format($rec->qty,3),'0')}} изд.</th>

                            </tr>
                            @foreach($rec->cmpnd_lst as $item)
                                <tr>
                                    <td><span class="small">{{$item->refitmid}}</span> {{$item->name}}</td>
                                    <td class="text-center">{{$item->unit}}</td>
                                    <td class="text-right">{{$item->max_qty}}</td>
                                    <td class="text-right">{{number_format($item->max_qty * $rec->qty, $item->decimal_dgts)}}</td>
                            @endforeach
                        </table>
                    </div>
                </div>
            @endif

            @if (isset($rec->raw_in_prod_lst))
                <?php
                $tot_raw_qty = 0;
                $dec_dgts = $rec->refitem->unittype->decimal_dgts;
                $raw_unit = $rec->refitem->unit;
                ?>
                <div class="row">
                    <div class="col-md-7 offset-md-3">
                        <table class="table-sm table-striped mt-3" style="background-color: #f6f6ec">
                            <caption>
                            </caption>
                            <tr>
                                <td colspan="4" class="text-right"><h6 class="mr-2">Расход материала
                                        "<b>{{$rec->refitem->name}}</b>" в произведенных изделиях</h6></td>
                            </tr>
                            <tr>
                                <th>Изделие</th>
                                <th>Кол-во изделий</th>
                                <th>Расход на 1 изделие, {{$raw_unit}}</th>
                                <th>Расход на все изд., {{$raw_unit}}</th>

                            </tr>
                            @foreach($rec->raw_in_prod_lst as $item)
                                <tr>
                                    <td><span class="small">{{$item->refitmid}}</span>
                                        <a href="{{ route('wrhdoclst.edit', $item->id) }}">{{$item->refitm_name}}</a>
                                    </td>
                                    <td class="text-right">{{number_format($item->prod_qty, $item->prod_dec_dgts??3)}}</td>
                                    <td class="text-right">{{number_format($item->max_qty, $dec_dgts)}}</td>
                                    <td class="text-right">{{number_format($item->tot_qty, $dec_dgts)}}</td>
                                @php($tot_raw_qty = $tot_raw_qty + $item->tot_qty )
                            @endforeach
                            <tr>
                                <td colspan="3" class="text-right">Итого, {{$raw_unit}}:</td>
                                <td class="text-right font-weight-bold">{{number_format($tot_raw_qty, $dec_dgts)}}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            @endif
        </div>
        <script src="{{ asset('js/wrhdoclst_edit.js') }}" defer></script>
    @endif
@endsection
