@if ($rec->id != -1 and isset($rec->role_users) )

    <div class="row">

        <div class="col-md-12">
            <div class="card my-3">
                <div class="card-header">
                    <i class="fa fa-list-ol text-info" aria-hidden="true"></i>
                    Пользователи, имеющие данную роль

                    <span class="float-right">
                        @if(count($rec->role_users)>0)
                            <button data-toggle="collapse" data-target="#role_users"
                                    class="btn btn-light btn-sm">
                            <i class="fa fa-eye-slash" aria-hidden="true"></i>
                            <span class="badge badge-info">{{count($rec->role_users)}}</span>
                        </button>
                        @endif
                    </span>
                </div>

                @if (isset($rec->role_users) and count($rec->role_users)>0)
                    <div class="card-body collapse " id="role_users">
                        <table class="table table-striped w-100" style="">
                            <thead>
                            <tr>
                                <td class="small">#</td>
                                <td class="text-left">ФИО</td>
                                <td>ЭП</td>
                            </tr>
                            </thead>
                            <tbody>
                            @php($npp=0)
                            @foreach($rec->role_users as $itm)
                                <tr>
                                    <td class="small text-right">{{++$npp}}</td>
                                    <td class="small text-left"><a href="{{ route('users.edit',$itm->id)}}?returl={{Request::url()}}">
                                        {{$itm->name}}</a>
                                    </td>
                                    <td class="small text-left">{{$itm->email}}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
