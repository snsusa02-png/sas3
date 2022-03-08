@extends('layouts.edit')

@section('content')
    <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
    <script src="{{ asset('js/jquery-ui.js') }}" defer></script>

    <script src="{{ asset('js/callListRefItems.js') }}" defer></script>
    <script src="{{ asset('js/ri_ac_er_items.js') }}" defer></script>

    <?php
    $thisTitle = "Позиция документа";
    $thisSysObjCode = 'invoice_items';
    $thisSysObjId = 916;
    ?>

    @guest
        <?php

        redirect()->route('login');
        //Почему то не страбатывает в Firefox (иногда потому добавим переходов)
        header("Location:" . route('login'));
        die();


        ?>
    @else
        @if (!isset( $rec))
            <?php
            redirect()->route('invoices.index');
            header("Location:" . route('invoices.index'));
            die();
            ?>
        @else
            <style>
                .photo {
                    display: block;
                    max-width: 116px;
                    /*max-height: 136px;*/
                    width: auto;
                    height: auto;
                    margin: auto;
                }

                label {
                    color: gray;
                    margin-bottom: 0px;
                }

                label.required:after {
                    font-size: 75%;
                    vertical-align: super;
                    content: "*";
                    color: red;
                }

                .highlight {
                    color: green;
                    font-weight: bold;
                }

                .ui-menu-item .ui-menu-item-wrapper.ui-state-active {
                    background: #fff9c6 !important;
                    font-weight: bold !important;
                    color: #000 !important;
                }

                .ui-autocomplete .m-icon {
                    float: left;
                    max-height: 32px;
                    max-width: 32px;
                }

                .ui-autocomplete .x-icon {
                    float: left;
                    width: 32px;
                / / opacity: .5;
                    font-size: small;
                    text-align: center;
                }

                .ui-autocomplete .m-name {
                    display: block;
                    margin-left: 40px;
                }

                .ui-autocomplete div::after {
                    content: "";
                    display: table;
                    clear: both;
                }

                .ac-fail {
                    background-color: cornsilk;
                }

                .ac-success {
                    background-color: #d4ffd4;
                }
            </style>
            <?php
            $inputMode = "";
            if (!$usrrights['save']) {
                $inputMode = " readonly";
            }

            $docname = ($rec->doctypeid == 2) ? 'УПД' : 'счета';
            ?>
            <div class="container">

                @include('layouts.edit_msgs')

                <div class="row ">
                    <div class="col-md-6 col-sm-12">
                        <div class="card mt-3">
                            <div class="card-header">
                                Позиция {{$docname??'документа'}} № {{$rec->invoiceid}}
                                <span class="float-right">
                                     <a class="btn btn-close btn-light btn-sm"
                                        href="{{ route('invoices.edit',$rec->invoiceid) }}">
                                            <i class="fa fa-times" aria-hidden="true"></i>
                                        </a>
                                </span>
                            </div>
                            <div class="card-body" style="background-color: #f0f5ef">

                                @include('layouts.err_msgs')

                                <form name="forEdit" id="forEdit" method="post"
                                      action="{{ route($thisSysObjCode.'.update', $rec->id) }}">

                                    @method('PUT')
                                    @csrf

                                    {{ Form::hidden('invoiceid', $rec->invoiceid) }}

                                    @if(1==1 )
                                        <div class="form-group mb-3 ">
                                            <label for="refitmid" class="required">Материал:</label>
                                            <div class="input-group input-group-sm">
                                                <input type="text" class="form-control font-weight-bold "
                                                       name="itmname" id="itmname"
                                                       value="{{old('itmname',$rec->itmname)}}"
                                                       autocomplete="off"
                                                    {{$inputMode}}
                                                />
                                                <input type="text" class="form-control text-center small"
                                                       style="display: none; border: #d7f3e3;" id="ac_refitmid"
                                                       readonly>
                                                <input type="hidden" name="refitmid" id="refitmid"
                                                       value="{{old('refitmid',$rec->refitmid)}}">

                                                @if ($usrrights['save'])
                                                    <div class="input-group-append">
                                                        <a onclick="callListRefItems({{$rec->invoice->orgid}})"
                                                           title="Поиск"
                                                           class="btn btn-sm btn-info">
                                                            <i class="fa fa-search" aria-hidden="true"></i>
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group list-inline">
                                                    <label for="">Материал:</label>
                                                    @if ($usrrights['save'])
                                                        <input type="text"
                                                               class="form-control text-left font-weight-bold"
                                                               name="itmname" required
                                                               value="{{old('itmname',$rec->itmname)}}"
                                                        />
                                                    @else
                                                        <input type="text" class="form-control font-weight-bold"
                                                               readonly value="{{$rec->itmname}}"/>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="row">
                                        <div class="offset-md-1 col-md-3">
                                            <div class="form-group list-inline">
                                                <label for="" class="required">Ед. изм.:</label>
                                                @if ($usrrights['save'])
                                                    {!! Form::select('unittypeid', $rec->unittypes,
                                                    old('unittypeid',$rec->unittypeid),
                                                    [
                                                        'id' => 'unittypeid',
                                                        'class' => 'form-control',
                                                        'required'=>'required',
                                                        'placeholder'=>'',
                                                    ]) !!}
                                                    {{--                                                    {{ Form::hidden('unittypeid', $rec->unittypeid)--}}
                                                    {{--                                                        ,['id' => 'unittypeid_aux','disabled'=>true] }}--}}
                                                    <input type="hidden" name="unittypeid" id="unittypeid_aux"
                                                           {{($rec->refitmid<>'')?'':'disabled'}}
                                                           value="{{$rec->unittypeid}}">
                                                    {{ Form::hidden('unit', $rec->unit, ['id'=>'unit']) }}
                                                @else
                                                    <input type="text" class="form-control font-weight-bold text-center"
                                                           readonly value="{{$rec->unit}}"/>
                                                @endif
                                            </div>
                                        </div>
                                        <div class=" col-md-4">
                                            <div class="form-group list-inline">
                                                <label for="" class="required">Количество, ЕИ:</label>
                                                @if ($usrrights['save'])
                                                    <input type="number"
                                                           class="form-control text-right font-weight-bold"
                                                           id="qty" name="qty"
                                                           required min="0" step="{{$rec->rqst_qty_step??0.001}}"
                                                           value="{{old('qty',$rec->qty)}}"
                                                    />
                                                @else
                                                    <input type="text" class="form-control font-weight-bold text-right"
                                                           readonly value="{{$rec->qty}}"/>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="form-group col-md-4">
                                            <label for="itmsum" class="required">Сумма, &#8381;:</label>
                                            @if ($usrrights['save'])
                                                <input type="number"
                                                       class="form-control text-right"
                                                       min="0" step="0.01"
                                                       name="itmsum"
                                                       id="itmsum"
                                                       value="{{old('itmsum',$rec->itmsum)}}"
                                                />
                                            @else
                                                <input type="text"
                                                       class="form-control font-weight-bold text-right"
                                                       readonly value="{{$rec->itmsum}}"/>
                                            @endif
                                        </div>

                                    </div>

                                    <hr>
                                    <div class="row">
                                    </div>

                                    <div class="row">
                                        <div class="form-group offset-md-8 col-md-4">
                                            <label for="est_sum">Цена, &#8381;:</label>
                                            <input type="number" class="form-control form-control-sm text-right"
                                                   readonly
                                                   id="price"
                                                   value="{{$rec->price}}"
                                            />
                                        </div>
                                    </div>

                                    <div class="actions">
                                        <hr>
                                        @if ($usrrights['save'] and $rec->ordstatusid != 9)
                                            <button type="submit" class="btn btn-success">
                                                <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                                Сохранить
                                            </button>
                                        @endif
                                        <?php
                                        ?>
                                        <a class="btn btn-close btn-info"
                                           href="{{ route('invoices.edit',$rec->invoiceid)."#items" }}">
                                            <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                            Закрыть
                                        </a>
                                        @if ($usrrights['delete'])
                                            <button type="submit"
                                                    class="btn btn-danger btn-sm"
                                                    style="margin-left:24px"
                                                    formaction="{{ route('invoice_items.delete', $rec->id)}}"
                                                    formmethod="post"
                                                    onclick="return confirm('Вы действительно хотите удалить позицию?')"
                                                    title="Удалить"
                                            >
                                                <i class="fa fa-trash-o" aria-hidden="true"></i>
                                            </button>
                                        @endif
                                    </div>
                                </form>
                            </div>
                            <div class="card-footer">
                                @include('layouts._who_when')
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-sm-12">
                        @include('invoice_items._pre_items')
                    </div>

                </div>

                <div class="row">
                    <div class="col-md-12">
                    </div>
                </div>
            </div>
            <script src="{{ asset('js/invoice_item_edit.js') }}" defer></script>
        @endif
    @endguest
@endsection
