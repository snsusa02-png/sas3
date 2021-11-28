@extends('layouts.edit')

@section('content')
    @guest
        <?php

        redirect()->route('login');
        //Почемуто не страбатывает в Firefox (иногда потому добавим переходов)
        header("Location:" . route('login'));
        die();


        ?>
    @else
        @if (!isset( $org))
            <?php
            redirect()->route('orgs.index');
            header("Location:" . route('orgs.index'));
            die();
            ?>
        @else
            <?php
            $retURL = Request::url();
            ?>

            <style>
                label {
                    color: gray;
                    margin-bottom: 0px;
                }

                .org-aux {
                    width: 100%
                }

                .itm_not_active {
                    color: gray !important;
                    background-color: #fabab8 !important;
                    /*text-decoration: line-through wavy red;*/
                }

                .itm_active {

                }

                .photo {
                    display: block;
                    max-width: 90px;
                    max-height: 90px;
                    width: auto;
                    height: auto;
                    margin: auto;
                }
            </style>
            <div class="container">
                <div class="row ">
                    @if ($org->id != -1)
                        <div class="col-md-12">
                            <div class="card mt-3">
                                <div class="card-header">
                                    Сотрудники "<b>{{$org->name}}</b>" ({{$org->id}})

                                    <a class="btn btn-close btn-info btn-sm"
                                       style="float:right"
                                       href="{{ route('orgs.edit',$org->id) }}"
                                       title="вернуться в карточку клиента"
                                    >
                                        <i class="fa fa-times" aria-hidden="true"></i>
                                    </a>
                                </div>
                                <div class="card-body">

                                    @include('layouts.edit_msgs')

                                    <table class="table table-striped">
                                        <thead>
                                        <tr>
                                            <td>#</td>
                                            <td>фото</td>
                                            <td>ФИО</td>
                                            <td>Должность</td>
                                            <td>Телефон</td>
                                            <td>E-Mail</td>
                                            <td style="text-align: center;">
                                                <a href="{{ route('orgstaff.create',$org->id)}}?returl={{$retURL}}"
                                                   class="btn btn-warning btn-sm"
                                                   title="Добавить запись о новом сотруднике">
                                                    <i class="fa fa-plus"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($stafflist as $itm)
                                            <?php
                                            $tr_itm_class = "itm_not_active";
                                            if ($itm->active == 1) {
                                                $tr_itm_class = "itm_active";
                                            }
                                            ?>
                                            <tr class="{{$tr_itm_class}}">
                                                <td class="small" style="text-align: right'">
                                                    {{--$local->count--}}
                                                </td>
                                                <td>
                                                    <?php
                                                    $photo = '';
                                                    if (isset($itm->photo)) {
                                                        $url = Storage::disk('local')->url($itm->photo->systemfilename);

                                                        if (isset($url)) {
                                                            $photo = '<img src=' . $url . ' class="photo rounded">';
                                                            $photo = '<a href=' . $url . ' class="popup-image " title="' . $itm->name . '">' . $photo . '</a>';
                                                        }
                                                    }
                                                    ?>
                                                    {!! $photo !!}
                                                </td>
                                                <td>
                                                    <a href="{{route('orgstaff.edit',$itm->id)}}?returl={{$retURL}}">
                                                        <b>{{$itm->lname." ".$itm->fname." ".$itm->mname}}</b>
                                                    </a>

                                                    @if(isset($itm->userid))
                                                        <span class=" font-size-12">
														@if($itm->user->isOnline())
                                                                <i class="fa fa-user-circle-o text-success"
                                                                   aria-hidden="true"
                                                                   title="Пользователь сейчас в системе"></i>
                                                            @else
                                                                <i class="fa fa-user-circle" aria-hidden="true"
                                                                   style="color:silver;"
                                                                   title="Пользователь не в системе"></i>
                                                            @endif
														</span>
                                                    @endif

                                                </td>
                                                <td class="c">
                                                    {{$itm->postname}}
                                                </td>
                                                <td class="l">
                                                    <a href="tel:{{$itm->phone}}">{{$itm->phone}}</a>
                                                </td>
                                                <td class="l">
                                                    <a href="mailto:{{$itm->email}}">{{$itm->email}}</a>
                                                </td>
                                                <td style="text-align: center;">
                                                    <a href="{{ route('orgstaff.edit',$itm->id)}}?returl={{$retURL}}"
                                                       class="btn btn-sm btn-primary"
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
        @endif
    @endguest
@endsection
