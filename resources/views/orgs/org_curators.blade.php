@extends('layouts.edit')

@section('content')
    @if (!isset( $org))
        <?php
        redirect()->route('orgs.index');
        header("Location:" . route('orgs.index'));
        die();
        ?>
    @else

        {{--dd(get_defined_vars())--}}

        <style>
            label {
                color: gray;
                margin-bottom: 0px;
            }
        </style>
        <div class="container">
            <div class="row ">
                @if ($org->id != -1)
                    <div class="offset-md-1 col-md-10">
                        <div class="card mt-3">
                            <div class="card-header">
                                Кураторы клиента "<b>{{$org->name}}</b>" ({{$org->id}})

                                <a class="btn btn-close btn-light btn-sm"
                                   style="float:right"
                                   href="{{ route('orgs.edit',$org->id) }}"
                                   title="вернуться в карточку клиента"
                                >
                                    <i class="fa fa-times" aria-hidden="true"></i>
                                </a>
                            </div>
                            <div class="card-body">
                                @include('layouts.err_msgs')

                                <table class="table table-striped">
                                    <thead>
                                    <tr>
                                        <td>#</td>
                                        <td>
                                            ФИО, организация
                                        </td>
                                        <td class="text-center">Вид работ</td>
                                        <td class="text-center">Период действия</td>
                                        <td style="text-align: center;">
                                            <a href="{{ route('org_curator.create',$org->id)}}"
                                               class="btn btn-warning btn-sm"
                                               title="Добавить запись о новом сотруднике">
                                                <i class="fa fa-plus"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $bgcols = array('#FeFeFe', '#EfEfEf', '#FFBFBF', '#FFcccc', '#F9F5BD', '#FCFADC', '#BFF9B9', '#ccffcc', '#79D3FF', '#CCECF9', '#CC9999', '#E2C7C7');
                                    $rec0 = 1; //$curators->currentPage() * $curators->perPage() - $curators->perPage() + 1
                                    ?>
                                    @foreach($curators as $itm)
                                        <?php
                                        $colshift = (1 - $itm->active) * 2;
                                        $tr_bg_col = $bgcols[$colshift + ($loop->index + $rec0) % 2];
                                        ?>
                                        <tr style="background-color: {{$tr_bg_col}}">
                                            <td class="small" style="text-align: right'">
                                                {{$loop->index + $rec0}}
                                            </td>
                                            <td>
                                                <a href="{{ route('org_curator.edit',$itm->id)}}">
                                                    {{$itm->lname." ".$itm->fname." ".$itm->mname}}
                                                </a>
                                                <div class="mt-1 ml-3 text-secondary small">{{$itm->orgname}}</div>
                                            </td>

                                            <td class="text-center">
                                                <span class="small">{{$itm->opertype_name}}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="small">{{$itm->begdt}} - {{$itm->enddt}}</span>
                                            </td>
                                            <td style="text-align: center;">
                                                <a href="{{ route('org_curator.edit',$itm->id)}}"
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
@endsection
