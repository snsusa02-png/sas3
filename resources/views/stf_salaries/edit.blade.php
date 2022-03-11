@extends('layouts.edit')
@section('content')

    <style>
        label {
            color: gray;
            margin-bottom: 0px;
        }
    </style>
    @guest
        <?php
        redirect()->route('login');
        //Почемуто не страбатывает в Firefox (иногда потому добавим переходов)
        header("Location:" . route('login'));
        die();
        ?>
    @else
        @if (!isset( $rec))
            <?php
            redirect()->route('home');
            header("Location:" . route('home'));
            die();
            ?>
        @else
            <?php
            $thisSysObjId = 1923;
            $sysobjid = $thisSysObjId;
            $sysobjcode = 'obj_addresses';
            $thisTitle = "Адрес";

            $retRoute = $rec->retURL;

            //для блокировки текстовых полей пользователям, не имеющим право на редактирование
            $inputReadOnly = "readonly";
            if ($usrrights['save']) $inputReadOnly = "";
            ?>

            <div class="container">

                @include('layouts.edit_msgs')

                <div class="row ">

                    <div class="col-md-7">
                        <div class="card mt-3">
                            <div class="card-header">
                                {{$thisTitle}}
                                <a class="btn btn-close btn-light btn-sm"
                                   style="float:right;"
                                   href="{{ $retRoute }}"
                                   title="Вернуться">
                                    <i class="fa fa-times" aria-hidden="true"></i>
                                </a>
                            </div>
                            <div class="card-body">

                                @include('layouts.err_msgs')

                                <form name="forEdit" id="forEdit" method="post"
                                      action="{{ route($sysobjcode.'.update', $rec->id) }}">
                                    @method('PUT')
                                    @csrf
                                    {{ Form::hidden('sysobjid', $rec->sysobjid) }}
                                    {{ Form::hidden('objid', $rec->objid) }}
                                    {{ Form::hidden('id', $rec->id,['id'=>'id']) }}


                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-12">
                                            <label for="name" class="">Для:</label>
                                            <input type="text" class="form-control"
                                                   readonly
                                                   value="{{ $rec->_obj_info }}"/>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-6">
                                            <label for="addresstypeid" class="required">Тип:</label>
                                            {!! Form::select('addresstypeid',  $rec->addresstypes??[], $rec->addresstypeid??null,
                                             [
                                             'id' => 'addresstypeid',
                                             'class' => 'form-control',
                                             'placeholder' => '',
                                             'required' => 'required',
                                             ]) !!}
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-12 pb-3" style="background-color: #fff9c9">
                                            <label for="street_adr" class="">Адрес:</label>
                                            <div class="input-group ">
                                                <input type="text" class="form-control" name="address"
                                                       id="address" maxlength="90"
                                                       value="{{ old('address',$rec->address) }}"/>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-3">
                                            <label for="zip" class="">Индекс:</label>
                                            <input type="text" class="form-control" name="zip" maxlength="6"
                                                   value="{{ old('zip',$rec->zip) }}"/>
                                        </div>
                                        <div class="form-group offset-md-3 col-md-6">
                                            <label for="country" class="">Страна:</label>
                                            <input type="text" class="form-control" name="country" maxlength="30"
                                                   value="{{ old('country',$rec->country) }}"/>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-6">
                                            <label for="region" class="">Область/Край:</label>
                                            <input type="text" class="form-control" name="region" maxlength="60"
                                                   value="{{ old('region',$rec->region) }}"/>
                                        </div>
                                        <div class="form-group offset-md-0 col-md-6">
                                            <label for="region" class="">Район:</label>
                                            <input type="text" class="form-control" name="district" maxlength="60"
                                                   value="{{ old('district',$rec->district) }}"/>
                                        </div>
                                        <div class="form-group offset-md-0 col-md-6">
                                            <label for="city" class="">Город/Насел. пункт:</label>
                                            <input type="text" class="form-control" name="city" maxlength="60"
                                                   value="{{ old('city',$rec->city) }}"/>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-12">
                                            <label for="street_adr" class="">Улица, корпус, дом, офис/квартира:</label>
                                            <div class="input-group">
                                                {{--                                                {!! Form::select('streettypeid',  ['ул.','пр-кт','переулок', 'проезд'], $rec->streettypeid??null,--}}
                                                {{--                                             [--}}
                                                {{--                                             'id' => 'streettypeid',--}}
                                                {{--                                             'class' => 'form-control',--}}
                                                {{--                                             'placeholder' => '',--}}
                                                {{--                                             'required' => 'required',--}}
                                                {{--                                             'style'=>'width:1%',--}}
                                                {{--                                             ]) !!}--}}

                                                <input type="text" class="form-control" name="street_adr"
                                                       id="street_adr" maxlength="60"
                                                       value="{{ old('street_adr',$rec->street_adr) }}"/>
                                            </div>
                                        </div>
                                    </div>

                                    {{--                                    <div class="row">--}}
                                    {{--                                        <div class="form-group offset-md-0 col-md-4">--}}
                                    {{--                                            <label for="corpus" class="">Корпус:</label>--}}
                                    {{--                                            <input type="text" class="form-control" name="corpus" maxlength="16"--}}
                                    {{--                                                   value="{{ old('corpus',$rec->corpus) }}"/>--}}
                                    {{--                                        </div>--}}
                                    {{--                                        <div class="form-group offset-md-0 col-md-4">--}}
                                    {{--                                            <label for="building" class="">Здание:</label>--}}
                                    {{--                                            <input type="text" class="form-control" name="building" maxlength="16"--}}
                                    {{--                                                   value="{{ old('building',$rec->building) }}"/>--}}
                                    {{--                                        </div>--}}
                                    {{--                                        <div class="form-group offset-md-0 col-md-4">--}}
                                    {{--                                            <label for="appartment" class="">Офис/Квартира:</label>--}}
                                    {{--                                            <input type="text" class="form-control" name="appartment" maxlength="16"--}}
                                    {{--                                                   value="{{ old('appartment',$rec->appartment) }}"/>--}}
                                    {{--                                        </div>--}}
                                    {{--                                    </div>--}}

                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-12">
                                            <label for="name" class="">Примечание:</label>
                                            <input type="text" class="form-control" name="notes" maxlength="160"
                                                   value="{{ old('notes',$rec->notes) }}"/>
                                        </div>
                                    </div>


                                    <div class="form-group">
                                        <label for="active" style="color:rgb(73, 80, 87);">актуально:</label>
                                        {!! Form::checkbox('active', 1, $rec->active==1) !!}
                                    </div>

                                    <div class="row">
                                        <div class="form-group offset-md-0 col-md-12 text-secondary">
                                            {{$rec->address}}
                                        </div>
                                    </div>

                                    @if ($usrrights['save'])
                                        <button type="submit" class="btn btn-success">
                                            <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                            Сохранить
                                        </button>
                                    @endif
                                    &nbsp;
                                    <a class="btn btn-close btn-info" href="{{ $retRoute }}">
                                        <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                        Закрыть
                                    </a>
                                    &nbsp;
                                    @if ($usrrights['delete'])
                                        <button type="submit"
                                                class="btn btn-danger btn-sm"
                                                style="margin-left:24px"
                                                formaction="{{ route($sysobjcode.'.delete', $rec->id)}}"
                                                formmethod="post"
                                                title="Удалить"
                                                onclick="return confirm('Вы действительно хотите удалить запись?')"
                                        >
                                            <i class="fa fa-trash-o" aria-hidden="true"></i>
                                        </button>
                                    @endif
                                </form>

                                @include('layouts._who_when')
                            </div>


                        </div>
                    </div>

                    @if($rec->id<>-1)
                        <div class="col-md-5">


                        </div>
                    @endif

                </div>
            </div>
            @if(1==1 or $rec->id==-1)
                <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
                <link href="https://cdn.jsdelivr.net/npm/suggestions-jquery@21.6.0/dist/css/suggestions.min.css"
                      rel="stylesheet"/>
                <script src="https://cdn.jsdelivr.net/npm/suggestions-jquery@21.6.0/dist/js/jquery.suggestions.min.js"
                        defer></script>
            @endif
            <script src="{{ asset('js/obj_address_edit.js') }}" defer></script>

        @endif
    @endguest
@endsection
