@extends('layouts.edit')

@section('content')
{{--    <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">--}}
{{--    <script src="{{ asset('js/jquery-ui.js') }}" defer></script>--}}


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

        $inputMode = "";
        if (!$usrrights['save']) {
            $inputMode = " readonly";
        }
        $ri_InputMode = "";
        if (!$usrrights['refitm.edit']) {
            $ri_InputMode = " readonly";
        }
        ?>
        <div class="container">

            <div class="row ">
                <div class="col-md-9 col-sm-12" style="min-width:450px;">
                    @include('layouts.edit_msgs')

                    <div class="card uper">
                        <div class="card-header">
                            Комплектующая для изделия "{{$rec->ri_compound->refitem->name}}"
                            <span class="small">({{$rec->cmpndid}})</span>
                        </div>
                        <div class="card-body">
                            @include('layouts.err_msgs')

                            <?php
                            $ri_name = old('refitmname');
                            if (!isset($ri_name) and isset($rec->refitem->name))
                                $ri_name = $rec->refitem->name;

                            $rec->refitmid = old('refitmid') ?: ($rec->refitmid);
                            $rec->min_qty = (old('min_qty')) ?: $rec->min_qty;
                            ?>

                            <form name="forEdit" id="forEdit" method="post"
                                  action="{{ route('ri_cmpnd_items.update', $rec->id) }}">

                                @method('PUT')
                                @csrf

                                {{ Form::hidden('cmpndid', $rec->cmpndid,['id'=>'cmpndid']) }}

                                <div class="form-group">
                                    <label for="refitmid">Материал:&nbsp;</label>
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
                                                <a onclick="callListRefItems({{$rec->ri_compound->ownorgid}},0)"
                                                   title="Выбор из справочника товаров"
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fa fa-search" aria-hidden="true"></i>
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="offset-md-5 col-md-3 offset-sm-4 col-sm-4 col-xs-6">
                                        <div class="form-group list-inline">
                                            <label for="">Мин. количество, <span class="font-weight-bold unit_html"
                                                                            id="unit_html">{{$rec->refitem->unittype->name??'еи'}}</span>:</label>
                                            <div class="input-group">

                                                @if( 1==0)
                                                    {{ Form::hidden('min_qty', $rec->min_qty) }}
                                                    {{$rec->min_qty}}
                                                @else
                                                    <input type="text" class="form-control text-right font-weight-bold"
                                                           id="min_qty" name="min_qty" {{$inputMode}}
                                                           value="{{$rec->min_qty}}"
                                                    />
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="offset-md-0 col-md-3 offset-sm-4 col-sm-4 col-xs-6">
                                        <div class="form-group list-inline">
                                            <label for="">Макс. количество, <span class="font-weight-bold unit_html"
                                                                            id="unit_html">{{$rec->refitem->unittype->name??'еи'}}</span>:</label>
                                            <div class="input-group">

                                                @if( 1==0)
                                                    {{ Form::hidden('max_qty', $rec->min_qty) }}
                                                    {{$rec->min_qty}}
                                                @else
                                                    <input type="text" class="form-control text-right font-weight-bold"
                                                           id="max_qty" name="max_qty" {{$inputMode}}
                                                           value="{{$rec->max_qty}}"
                                                    />
                                                @endif
                                            </div>
                                        </div>
                                    </div>
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
                                       href="{{ route('ri_compounds.edit',$rec->cmpndid) }}">
                                        <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                        Закрыть
                                    </a>
                                    @if ($usrrights['delete'])
                                        <button type="submit"
                                                class="btn btn-danger"
                                                style="margin-left:24px"
                                                formaction="{{ route('ri_cmpnd_items.delete', $rec->id)}}"
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

        <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
        <script src="{{ asset('js/jquery-ui.js') }}" defer></script>
        <script src="{{ asset('js/callListRefGoods.js') }}" defer></script>
        <script src="{{ asset('js/ri_ac_wrhdoclst.js') }}" defer></script>

        <script src="{{ asset('js/ri_cmpnd_item_edit.js') }}" defer></script>

    @endif
@endsection
