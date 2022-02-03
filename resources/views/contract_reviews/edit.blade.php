@extends('layouts.edit')

@section('content')

    @if (!isset( $rec ))
        <?php
        redirect()->route('/');
        header("Location:/");
        die();
        ?>
    @else
        <?php
        $sysobjid = 156;
        $sysobjcode = 'contract_reviews';
        $thisTitle = "Согласование";
        $retRoute = ($rec->retURL)
            ? ($rec->retURL . '#contract_reviews')
            : route('contracts.edit', $rec->contractid);
        $sysobjlbl = 'Согласование'; //todo: определить в контролере

        //для блокировки текстовых полей пользователям, не имеющим право на редактирование
        $inputReadOnly = "readonly";
        if ($usrrights['save']) $inputReadOnly = "";

        ?>
        <style>
            label {
                color: gray;
                margin-bottom: 0px;
            }

            .btn {
                margin-bottom: 4px;
            }

        </style>
        <div class="container">

            <div class="row">
                <div class="col-md-7 col-sm-12">

                    <div class="card mt-3">

                        @includeIf('layouts.edit_msgs')

                        <form name="forEdit" id="forEdit" method="post"
                              action="{{ route($sysobjcode.'.update', [$rec->id, $rec->sysobjid, $rec->objid]) }}">

                            @method('PUT')
                            @csrf
                            {{ Form::hidden('retURL', $rec->retURL) }}
                            {{ Form::hidden('contractid', $rec->contractid) }}

                            <div class="card-header" style="background-color: #f5e476;">
                                <i class="fa fa-comments" aria-hidden="true"></i> {{$thisTitle}}

                                <a class="btn btn-close btn-light btn-sm"
                                   style="float:right;"
                                   href="{{ $retRoute }}"
                                   title="Вернуться к списку">
                                    <i class="fa fa-times" aria-hidden="true"></i>
                                </a>
                            </div>
                            <div class="card-body" style="background-color: #fcfaec;">

                                @include('layouts.err_msgs')

                                <div class="form-group">
                                    <label for="name">{{$sysobjlbl}}:</label>
                                    <b>{{$rec->contract->info}}</b>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-12">
                                        <label for="descript" class="required">Цель согласования:</label>
                                        @if ($usrrights['save'])
                                            <textarea class="form-control rounded-0" name="descript" id="descript"
                                                      rows="3">{{ old('descript',$rec->descript) }}</textarea>
                                        @else
                                            <div class="font-weight-bold">
                                                {{($rec->descript)}}
                                            </div>
                                        @endif

                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="plnbegdt">План. начало согласования:</label>
                                        <input type="datetime-local" class="form-control" name="plnbegdt" readonly
                                               value="{{old('plnbegdt',$rec->plnbegdt)}}"/>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="plnenddt" class="required">Завершить согласование до:</label>
                                        @if ($usrrights['save'])
                                            <input type="datetime-local" class="form-control"
                                                   name="plnenddt"
                                                   value="{{old('plnenddt',$rec->plnenddt)}}"/>
                                        @else
                                            <div class="font-weight-bold">
                                                {{date_create($rec->plnenddt)->format('d.m.Y H:i')}}
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group offset-md-0 col-md-4">
                                        <label for="address">Статус:</label>
                                        @if ($usrrights['change_status']??false)
                                            {!! Form::select('statusid', $rec->statuses??[], $rec->statusid,
                                             [
                                             'class' => 'form-control',
                                             ]) !!}
                                        @else
                                            <div
                                                    class="font-weight-bold">{{$rec->statuses[$rec->statusid]??'-?-'}}</div>
                                        @endif
                                    </div>
                                </div>


                                <hr size="1">
                                @if ($usrrights['save'] or $usrrights['change_status']??false)
                                    <button type="submit" class="btn btn-success"
                                            title="Сохранить изменения">
                                        <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                        Сохранить
                                    </button>
                                @endif

                                <a class="btn btn-close btn-info" href="{{ $retRoute }}">
                                    <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                    Закрыть
                                </a>

                                @if ($usrrights['delete'])
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

                            </div>
                            @if ($rec->id != -1)
                                <div class="card-footer">
                                    <div class="small" style="color: gray; margin:8px;">
                                        создана: {{$rec->created_at}} / {{$rec->whocrt->name}} &nbsp;
                                        изменена: {{$rec->updated_at}} / {{$rec->whoupd->name}} &nbsp;
                                        <a href="{{route('objevntlog',['sysobjid'=>$sysobjid, 'objid'=>$rec->id,'route'=>Route::current()->getName()])}}">журнал</a>
                                    </div>
                                </div>
                            @endif
                        </form>
                    </div>
                </div>

                <div class="col-md-5">
                    @include('objfiles.obj_files')
                    @include('contract_reviews._readers')
                </div>
            </div>

            <script src="{{ asset('js/contract_exe_edit.js') }}" defer></script>

        </div>
    @endif
@endsection
