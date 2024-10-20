@if ($rec->id != -1 )
    <div class="card mt-3">
        <div class="card-header">
            <a name="staff"/>Сотрудники подразделения

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
                        <td class="small">Должность</td>
                        <td>ФИО</td>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($rec->staff as $itm)
                        <?php
                        $tr_itm_class = "itm_not_active";
                        if ($itm->active == 1) {
                            $tr_itm_class = "itm_active";
                        }
                        ?>
                        <tr class="{{$tr_itm_class}}">
                            <td class="small" style="text-align: right'">
                                {{$loop->iteration}}
                            </td>
                            <td class="small">
                                @if(isset($itm->postid))
                                    <a href="{{ route('orgposts.edit',$itm->postid)}}?returl={{Request::url()}}"
                                       title="Открыть запись о должности">{{$itm->postname}}</a>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                <a href="{{ route('orgstaff.edit',$itm->id)}}?returl={{Request::url()}}"
                                   title="Открыть запись о сотруднике">
                                    {{$itm->name}}
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

