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
        //dd($showPrice);

        //Отображать или нет кол-во из предшествующего документа определяется типом документа
        $showPreQry = ($rec->wrhdoc->doctype->need_predoc == 1
            and $rec->wrhdoc->doctypeid == 8);

        $inputMode = "";
        if (!$usrrights['save']) {
            $inputMode = " readonly";
        }
        $ri_InputMode = "";
        if (!$usrrights['refitm.edit']) {
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

                                <div class="form-group">
                                    <label for="refitmid">Товар:&nbsp;</label>
                                    <div class="input-group mb-3 input-group-sm">

                                        <input type="text" class="form-control text-center" style="max-width:120px;"
                                               name="code"
                                               id="code"
                                               value="{{$rec->refitem->id}}" readonly>
                                        <input type="text" class="form-control font-weight-bold " name="refitmname"
                                               id="refitmname"
                                               value="{{$ri_name}}"
                                            {{$ri_InputMode}}
                                        />
                                        <input type="text" class="form-control text-center small"
                                               style="display: none; border: #d7f3e3;" id="ac_refitmid" readonly>
                                        <input type="hidden" name="refitmid" id="refitmid"
                                               value="{{$rec->refitmid}}">


                                        @if ($usrrights['refitm.edit'])
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

                                <div class="form-group">
                                    <label for="specinfo">доп. характеристики:&nbsp;</label>
                                    <div class="input-group mb-3 input-group-sm small offset-md-1">

                                        <span id="specinfo" style="border: 1px solid silver;">
{{--										   {{$rec->refitem->RI_specinfo('; ')}}--}}
                                            {{$rec->refitem->descript}}
									</span>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="offset-md-8 col-md-3 offset-sm-4 col-sm-4 col-xs-6">
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
                                        <div class="offset-md-8 col-md-3 col-sm-4 col-xs-6">
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
        </div>
    @endif
@endsection
