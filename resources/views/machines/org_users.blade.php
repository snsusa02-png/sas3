@extends('layouts.edit')

@section('content')
@guest
<?php

redirect()->route('login');
//Почемуто не страбатывает в Firefox (иногда потому добавим переходов)
header("Location:" .route('login'));
die();


?>
@else
@if (!isset( $org))
<?php
    redirect()->route('orgs.index');
    header("Location:" .route('orgs.index'));
    die();
?>
@else
{{--dd(get_defined_vars())--}}

<style>
    .uper {
        margin-top: 36px;
    }
    label {
        color:gray;
        margin-bottom: 0px;
    }
    .org-aux{
        width:100%
    }
    .itm_not_active{
        color: gray !important;
        b0ackground-color:silver;
    }
    .itm_active{

    }
</style>
<div class="container">
    <div class="row ">
        @if ($org->id != -1)
            <div class="col-md-12">
                <div class="card uper">
                    <div class="card-header">
                        Представители клиента "<b>{{$org->name}}</b>" ({{$org->id}})

                        <a class="btn btn-close btn-info btn-sm"
                           style="float:right"
                           href="{{ route('orgs.edit',$org->id) }}"
                           title = "вернуться в карточку клиента"
                        >
                            <i class="fa fa-times" aria-hidden="true"></i>
                        </a>
                    </div>
                    <div class="card-body">
                        @if(session()->get('success'))
                            <div class="alert alert-success">
                                {{ session()->get('success') }}
                            </div><br />
                        @endif

                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <td>#</td>
                                <td>
                                    ФИО
                                </td>
                                <td>Должность</td>
                                <td>Телефон</td>
                                <td>E-Mail</td>
                                <td style="text-align: center;">
                                </td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($stafflist as $itm)
                                <?php
                                    $tr_itm_class = "itm_not_active";
                                    if ($itm->active==1){
                                        $tr_itm_class = "itm_active";
                                    }
                                ?>
                                <tr class="{{$tr_itm_class}}">
                                    <td class="small" style="text-align: right'">
                                        {{--$local->count--}}
                                    </td>
                                    <td >
                                        {{$itm->name}}
                                    </td>
                                    <td class="c">
                                        {{$itm->post}}
                                    </td>
                                    <td class="l">
                                        <a href="tel:{{$itm->phone}}">{{$itm->phone}}</a>
                                    </td>
                                    <td class="l">
                                        <a href="mailto:{{$itm->email}}">{{$itm->email}}</a>
                                    </td>
                                    <td style="text-align: center;">
                                        <a href="{{ route('userorgs.edit',$itm->id)}}" class="btn btn-sm btn-primary"
                                           title="Просмотреть/Изменить запись">
                                            <i class="fa fa-pencil"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>
@endsection
@endif
@endguest
