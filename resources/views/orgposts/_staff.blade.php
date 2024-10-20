@if ($rec->id != -1 )
    <div class="card mt-3">
        <div class="card-header">
            <a name="staff"/>Сотрудники

            <div class="float-right">
                @if(1==0 and $usrrights['orgdeps.create']??false)
                    <a href="{{ route('orgdeps.create',$rec->id)}}?returl={{Request::url()}}"
                       class="btn btn-sm btn-warning"
                       title="Создать запись">
                        <i class="fa fa-plus"></i>
                    </a>
                @endif
            </div>
        </div>
        <div class="card-body">

            @if(isset($rec->staff) and count($rec->staff)>0)
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <td>#</td>
                        <td>ФИО</td>
                        <td class="small">Период работы</td>
                        <td style="text-align: center;">
                        </td>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($rec->staff as $itm)
                        <?php
                        $tr_itm_class = "itm_not_active";
                        if ($itm->active == 1) {
                            $tr_itm_class = "itm_active";
                        }

                        $wrkperiod = (isset($itm->begdate)) ? date_create($itm->begdate)->format('d.m.Y') : '...';
                        if (isset($itm->enddate))
                            $wrkperiod .= ' - ' . date_create($itm->enddate)->format('d.m.Y');
                        ?>
                        <tr class="{{$tr_itm_class}}">
                            <td class="small" style="text-align: right'">
                                {{--$local->count--}}
                            </td>
                            <td>{{$itm->name}}</td>
                            <td class="small">{{$wrkperiod}}</td>
                            <td style="text-align: center;">
                                <a href="{{ route('orgstaff.edit',$itm->id)}}?returl={{Request::url()}}"
                                   class="btn btn-sm btn-primary"
                                   title="Просмотреть/Изменить запись">
                                    <i class="fa fa-pencil"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endif

