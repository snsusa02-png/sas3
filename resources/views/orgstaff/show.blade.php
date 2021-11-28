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
            redirect()->route('orgstaff.index');
            header("Location:" . route('orgstaff.index'));
            die();
            ?>
        @else
            <?php
            $orgid = $rec->orgid;
            ?>
            <div class="container">
                @include('layouts.edit_msgs')

                <div class="row ">
                    <div class="col-md-8">
                        <div class="card mt-3">
                            <div class="card-header">
                                Сотрудник
                                <a class="btn btn-close btn-light btn-sm"
                                   style="float:right;"
                                   href="{{ route('orgcontacts.index') }}"
                                   title="Вернуться в список сотрудников">
                                    <i class="fa fa-times" aria-hidden="true"></i>
                                </a>
                            </div>
                            <div class="card-body">

                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label for="lname">ФИО:</label>
                                            <br><b>{{ $rec->lname }} {{ $rec->fname }} {{ $rec->mname }}</b>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="post">Должность:</label>
                                        <br><b>{{ $rec->postname }}</b>
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="depname">Подразделение:</label>
                                        <br><b>{{$rec->orgdep->name}}</b>
                                    </div>
                                </div>

                                @if(isset($rec->bossname))
                                    <div class="row">
                                        <div class="form-group offset-md-5 col-md-4">
                                            <label for="post">Руководитель:</label>
                                            <br>{{ $rec->bossname }}
                                        </div>
                                    </div>
                                @endif

                                <div class="row offset-md-5 col-md-7">
                                    <div class="form-group">
                                        <label for="orgid">Организация:</label>
                                        <br><b>{{$rec->org->name}}</b>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="offset-md-0 col-md-4">
                                        <div class="form-group">
                                            <label for="phone">Телефон:</label>
                                            <br><b><a href="tel:{{ $rec->phone }}">{{ $rec->phone }}</a></b>
                                        </div>
                                    </div>
                                    <div class=" col-md-4">
                                        <div class="form-group">
                                            <label for="email">e-Mail:</label>
                                            <br><b><a href="tel:{{ $rec->email }}">{{ $rec->email }}</a></b>
                                        </div>
                                    </div>
                                </div>

                                @if(isset($rec->jobduties))
                                    <div class="row mt-3">
                                        <div class="offset-md-4 col-md-8">
                                            <div class="form-group">
                                                <label for="jobduties">Рабочие компетенции:</label>
                                                <div style="border-bottom: 1px solid #cbcbcb;" class="font-weight-bold">
                                                    {{$rec->jobduties}}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if(isset($rec->jobduties))
                                    <div class="row  mt-3">
                                        <div class="offset-md-4 col-md-8">
                                            <div class="form-group">
                                                <label for="gendoctypes">Создает документы (тип):</label>
                                                <div style="border-bottom: 1px solid #cbcbcb;" class="font-weight-bold">
                                                    {{ $rec->gendoctypes}}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="offset-md-4 col-md-8">
                                            <div class="form-group">
                                                <label for="cnfrmdoctypes">Согласует документы:</label>
                                                <div style="border-bottom: 1px solid #cbcbcb;" class="font-weight-bold">
                                                    {{$rec->cnfrmdoctypes}}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="offset-md-4 col-md-8">
                                            <div class="form-group">
                                                <label for="aprvdoctypes">Утверждает документы:</label>
                                                <div style="border-bottom: 1px solid #cbcbcb;" class="font-weight-bold">
                                                    {{$rec->aprvdoctypes}}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <hr size="1">
                                <a class="btn btn-close btn-light" href="{{ route('orgcontacts.index') }}">
                                    <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                    Закрыть
                                </a>
                                @if($usrrights['orgstaff.update']??false)
                                    <a class="btn btn-close btn-light float-right"
                                       href="{{ route('orgstaff.edit',$rec->id) }}" target="_blank">
                                        <i class="fa fa-pencil" aria-hidden="true"></i>
                                        Редактировать
                                    </a>
                                @endif
                                &nbsp;
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        @endif
    @endguest
@endsection
