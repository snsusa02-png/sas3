@if(true)
    <div class="card my-3">
        <div class="card-header">
            <i class="fa fa-flag text-success" aria-hidden="true"></i>
            <a name="acl_roles"/>
            Роли пользователя
            @if($usrrights['save']??false)
                <a href="{{ route('user_acl_roles.create',['userid'=>$rec->id,])}}"
                   class="btn btn-warning btn-sm" style="float: right">
                    <i class="fa fa-plus"></i>
                </a>
            @endif
        </div>

        @if(isset($rec->user_roles) and $rec->user_roles->count()>0)
            <div class="card-body">
                <table class="table-striped small p-2" style="width: 100%;" cellpadding="2">
                    <thead>
                    <tr>
                        <td>#</td>
                        <td>Роль</td>
                        <td/>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($rec->user_roles as $itm)
                        <tr>
                            <td style="text-align: right;"
                                class="small">{{$loop->iteration}}</td>
                            <td class=""><b>{{$itm->role_name}}</b>
                                @if(isset($itm->reason))
                                    ({{$itm->reason}})
                                @endif
                            </td>
                            <td style="text-align: right;">
                                <a href="{{ route('user_acl_roles.edit',$itm->id)}}"
                                   class="btn btn-sm btn-primary">
                                    <i class="fa fa-pencil">
                                    </i>
                                </a>
                            <td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
