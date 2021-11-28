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
                                    Товары, поставляемые "<b>{{$org->name}}</b>" ({{$org->id}})

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
                                            <td>Наименование</td>
                                            <td class="text-right">Цена за ЕИ</td>
                                            <td class="text-center">Актуально в период</td>
                                            <td style="text-align: center;">
                                                @if($usrrights['ri_org_prices.create'])
                                                    <a href="{{ route('ri_org_prices.create',$org->id)}}?returl={{$retURL}}"
                                                       class="btn btn-warning btn-sm"
                                                       title="Добавить запись">
                                                        <i class="fa fa-plus"></i>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        //var_dump(today());
                                        ?>
                                        @foreach($recs as $itm)
                                            <?php
                                            $tr_itm_class = "itm_not_active";
                                            if ($itm->active == 1
                                                //and today() >= date_create($itm->begdate)
                                                and today() <= (($itm->enddate) ? date_create($itm->enddate) : today())
                                            ) {
                                                $tr_itm_class = "itm_active";
                                            }
                                            ?>
                                            <tr class="{{$tr_itm_class}}">
                                                <td class="small" style="text-align: right'">
                                                    {{--$local->count--}}
                                                </td>
                                                <td>
                                                    <a href="{{route('ri_org_prices.edit',$itm->id)}}?returl={{$retURL}}">
                                                        <b>{{$itm->name}}</b>
                                                    </a>
                                                </td>
                                                <td class="text-right">
                                                    {{number_format($itm->price,2)}} / {{$itm->unit}}
                                                </td>
                                                <td class="text-center small">
                                                    {{date_create($itm->begdate)->format('d.m.Y')}}
                                                    - {{($itm->enddate)?date_create($itm->enddate)->format('d.m.Y'):'...'}}
                                                </td>
                                                <td style="text-align: center;">
                                                    <a href="{{ route('ri_org_prices.edit',$itm->id)}}?returl={{$retURL}}"
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
