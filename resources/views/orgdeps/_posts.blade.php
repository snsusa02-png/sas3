@if ($rec->id != -1 )
    <div class="card mt-3">
        <div class="card-header">
            <a name="staff"/>Должности подразделения

            <div class="float-right">
                @if(1==1 and $usrrights['orgposts.create']??true)
                    <a href="{{ route('orgposts.create',$rec->orgid)}}?depid={{$rec->id}}&returl={{Request::url()}}"
                       class="btn btn-sm btn-warning"
                       title="Создать запись">
                        <i class="fa fa-plus"></i>
                    </a>
                @endif
            </div>
        </div>
        <div class="card-body">

            @if(isset($rec->posts) and count($rec->posts)>0)
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <td>#</td>
                        <td class="small">Должность</td>
                        <td>Штатных единиц</td>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($rec->posts as $itm)
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
                                <a href="{{ route('orgposts.edit',$itm->id)}}?returl={{Request::url()}}"
                                   title="Открыть запись о должности">{{$itm->name}}</a>
                            </td>
                            <td class="text-right">
                                {{$itm->stdlimunits}}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endif

