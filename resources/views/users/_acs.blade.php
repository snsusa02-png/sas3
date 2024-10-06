@if($usrrights['user_acs.read']??true)
    <div class="card mt-3">
        <div class="card-header">
            <i class="fa fa-key text-success" aria-hidden="true"></i>
            Доступные категории информации
{{--            @if($usrrights['acs.admin']??false)--}}
            @if($usrrights['user_acs.create']??false)
                <a href="{{ route('user_acs.create',['userid'=>$rec->id,])}}"
                   class="btn btn-warning btn-sm" style="float: right">
                    <i class="fa fa-plus"></i>
                </a>
            @endif
        </div>

        @if(isset($rec->user_acs) and $rec->user_acs->count()>0)
            <div class="card-body">
                <table class="table-striped small p-2" style="width: 100%;" cellpadding="2">
                    <thead>
                    <tr>
                        <td>#</td>
                        <td>Категория</td>
                        <td/>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($rec->user_acs as $itm)
                        <tr>
                            <td style="text-align: right;"
                                class="small">{{$loop->iteration}}</td>
                            <td>{{$itm->ac_name}}</td>
                            <td style="text-align: right;">
                                <a href="{{ route('user_acs.edit',$itm->id)}}"
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
