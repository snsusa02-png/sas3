@extends('layouts.edit')

@section('content')
    <?php
    $sysobjid = 911;
    $sysobjcode = 'orgacnt_sums';
    $ThisTitle = "Остаток на р/счете";

    $route_index = route($sysobjcode . '.index') . "?page=" . session($sysobjcode . '_pageno') . '#' . $rec->id;
    ?>

    @if (!isset( $rec))
        <?php
        redirect()->route($sysobjcode . '.index');
        header("Location:" . route($sysobjcode . '.index'));
        die();
        ?>
    @endif

    <script src="{{ asset('js/collapse.js') }}" defer></script>

    <style>
        label {
            color: gray;
            margin-bottom: 0px;
        }
    </style>

    <div class="container">

        @includeIf('layouts.edit_msgs')

        <div class="row ">
            <div class="col-md-8">
                <div class="card p-2 my-2 my-md-3" style="background-color: #fbf6f6">
                    <div class="card-header" style="background-color: #ead9e2">
                        <b>{{$ThisTitle}}</b>
                        <a class="btn btn-close btn-light btn-sm"
                           style="float:right;"
                           href="{{ $route_index }}"
                           title="Вернуться в список">
                            <i class="fa fa-times" aria-hidden="true"></i>
                        </a>
                    </div>
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div><br/>
                        @endif

                        <form name="forEdit" id="forEdit" method="post"
                              action="{{ route($sysobjcode.'.update', $rec->id) }}"
                              enctype="multipart/form-data">
                            @method('PUT')
                            @csrf
                            {{ Form::hidden('ttt', 1) }}


                            <div class="row">

                                <div class="form-group col-md-4">
                                    <label for="orgid">Организация:</label>
                                    {!! Form::select('orgid', $rec->ownorgs, $rec->orgid,
                                     [
                                     'id' => 'orgid',
                                     'class' => 'form-control',
                                     'placeholder' => '-выбор-',
                                     'required' => '-выбор-',
                                     ]) !!}
                                </div>
                                <div class="form-group col-md-8">
                                    <label for="acnts">Расчетный счет:</label>
                                    {!! Form::select('acntid', $rec->acnts, $rec->acntid,
                                     [
                                     'id' => 'acntid',
                                     'class' => 'form-control',
                                     'placeholder' => '-выбор-',
                                     'required' => '-выбор-',
                                     ]) !!}
                                </div>
                            </div>

                            <div class="row">
                                <div class="form-group offset-md-4 col-md-4 col-sm-6">
                                    <label for="docnum">Дата:</label>
                                    <input type="date" class="form-control" name="ondate"
                                           value="{{old('ondate',$rec->ondate)}}"
                                    readonly/>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="plnqty">Остаток на начало дня:</label>
                                    <input type="text" class="form-control text-right font-weight-bold"
                                           name="restsum"
                                           id="restsum"
                                           min="0" step="0.01"
                                           value="{{old('restsum',$rec->restsum)}}"/>
                                </div>
                            </div>

                            <div class="row">
                                <div class="form-group offset-md-8 col-md-4">
                                    <label for="plnqty">Приход за день:</label>
                                    <input type="text" class="form-control text-right font-weight-bold"
                                           name="inpsum"
                                           id="inpsum"
                                           min="0" step="0.01"
                                           value="{{old('inpsum',$rec->inpsum)}}"/>
                                </div>
                            </div>

                            <div class="row">
                                <div class="form-group offset-md-8 col-md-4">
                                    <label for="plnqty">Итого:</label>
                                    <input type="text" class="form-control text-right font-weight-bold"
                                           id="cursum"
                                           name="cursum"
                                           min="0" step="0.01" readonly
                                           value="{{old('cursum',$rec->cursum)}}"/>
                                </div>
                            </div>


                            <div class="row">
                                @if(1==0)
                                <div class="form-group offset-md-0 col-md-4">
                                    <label for="forpay" style="color: rgb(73, 80, 87);">Можно использовать для оплаты счетов:</label>
                                    {!! Form::checkbox('forpay', 1, $rec->forpay==1,
                                     [
                                     'class' => 'form-control',
                                     ]) !!}
                                </div>
                                @endif
                                <div class="form-group offset-md-4 col-md-8">
                                    <label for="notes">Примечание:</label>
                                    <input type="text" class="form-control" name="notes"
                                           maxlength="160"
                                           value="{{old('notes',$rec->notes)}}"/>
                                </div>
                            </div>

                            <hr>
                            @if ($usrrights['save'])
                                <button type="submit" class="btn btn-success" title="Сохранить изменения">
                                    <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                    Сохранить
                                </button>
                            @endif
                            &nbsp;
                            <a class="btn btn-close btn-info" href="{{ $route_index }}"
                               title="Вернуться в список">
                                <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                Закрыть
                            </a>
                            @if ($rec->id != -1 and $usrrights['delete'])
                                <button type="submit"
                                        class="btn btn-danger btn-sm"
                                        style="margin-left:24px"
                                        formaction="{{ route($sysobjcode.'.delete', $rec->id)}}"
                                        formmethod="post"
                                        onclick="return confirm('Вы действительно хотите удалить запись?')"
                                        title="Удалить запись"
                                >
                                    <i class="fa fa-trash-o" aria-hidden="true"></i>
                                </button>
                            @endif
                        </form>
                    </div>
                    <div class="card-footer">
                        @if ($rec->id != -1)
                            <div class="small" style="margin-top: 8px; color:gray;">
                                создана: {{$rec->created_at}} / {{$rec->whocrt->FirstLast}},
                                изменена: {{$rec->updated_at}} / {{$rec->whoupd->FirstLast}}
                                <br><a href="{{route('objevntlog',['sysobjid'=>$sysobjid, 'objid'=>$rec->id,'route'=>Route::current()->getName()])}}">журнал</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
{{--                @include($sysobjcode.'.obj_files')--}}
            </div>
        </div>

    </div>

    <script src="{{ asset('js/orgacntsum_edit.js') }}" defer></script>
@endsection
