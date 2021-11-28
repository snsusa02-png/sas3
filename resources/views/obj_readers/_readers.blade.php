@if( 1==1 and isset($rec) and ($rec->id!=-1) and isset($rec->readers))
    <div class="card mt-3 d-none d-sm-block">
        <div class="card-header">
			<span data-toggle="collapse" data-target="#readers"><i class="fa fa-users text-success"
                                                                   aria-hidden="true"></i> Доступ / Ознакомление</span>

            <div class="float-right">
                @if (count($rec->readers)>0)
                    <button data-toggle="collapse" data-target="#readers"
                            class="btn btn-light btn-sm "><i class="fa fa-eye-slash" aria-hidden="true"></i>
                        <span class="badge badge-info">{{count($rec->readers)}}</span>
                    </button>
                @endif

                @if( $usrrights['save'])
                    <a href="{{ route('obj_readers.create',['sysobjid'=>$sysobjid, 'objid'=>$rec->id])}}?returl={{Request::url()}}"
                       class="btn btn-warning btn-sm ">
                        <i class="fa fa-plus"></i>
                    </a>
                @endif
            </div>
        </div>

        @if (count($rec->readers)>0)

            <div class="card-body collapse" id="readers">
                <table class="table-condensed table-striped small" style="width: 100%;">
                    <thead>
                    <tr>
                        <td>#</td>
                        <td colspan="2">Пользователь</td>
                        <td/>
                    </tr>
                    </thead>
                    <tbody>
                    <tr></tr>
                    <?php
                    $totReadCnt = 0;
                    $MustReadNoReadCnt = 0;

                    //Route::currentRouteName()
                    $retURL = Request::url();

                    ?>
                    @foreach($rec->readers as $itm)
                        <?php
                        $avatar_img = $itm->user->image ?? '/images/signs/user-no-photo.jpg';

                        $fio_class = "";

                        if ($itm->mustread == 1) {

                            if ($itm->read_cnt == 0)
                                $MustReadNoReadCnt++;

                            $fio_class = "font-weight-bold";
                        }
                        ?>
                        <tr class="align-top">
                            <td class="small" rowspan="2">{{$loop->iteration}}</td>
                            <td rowspan="2">
                                <img src="{{$avatar_img}}" style="max-height: 30px; max-width: 40px;"/>
                            </td>
                            <td>
                                <span class="{{$fio_class}}"> {{$itm->user->fullname}}</span>
                                <div class="small mk-3">
                                    {{$itm->roletype_name}}
                                </div>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('obj_readers.edit',$itm->id)}}?returl={{$retURL}}"
                                   class="btn btn-sm btn-light"
                                   title="Просмотреть/Изменить запись">
                                    <i class="fa fa-pencil small"></i>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" class="text-center small">
                                @if($itm->read_cnt==0)
                                    <span class="font-weight-bold text-danger"> - не заходил -</span>
                                @else

                                    {{date_create($itm->firstread_at)->format('d.m.Y H:i')}}
                                    - {{date_create($itm->lastread_at)->format('d.m.Y')}}: {{$itm->read_cnt}}
                                @endif
                            </td>
                        </tr>
                        @php($totReadCnt+=$itm->read_cnt)
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <div class="small text-right"> всего читателей: {{count($rec->readers)}},
                    подходов: {{number_format($totReadCnt,0)}}
                </div>

                @if (1==0 and $MustReadNoReadCnt>0)
                    <a class="btn btn-close btn-primary ml-2 btn-sm"
                       href="{{ route('contracts.notify_mustreaders', $rec->id) }}"
                       title="Уведомить обязательных читателей">
                        <i class="fa fa-paper-plane" aria-hidden="true"></i>
                    </a>
                @endif
            </div>
        @endif
    </div>
@endif
