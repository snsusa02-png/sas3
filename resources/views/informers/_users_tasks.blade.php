@if( isset($data->users_tasks))
{{--    <div class="col-lg-3 col-md-6 col-sm-6">--}}
    <div class="col-lg-12 col-md-12">
        <div class="card card-stats mt-3">
            <div class="card-header">
                <i class="fa fa-clock-o" aria-hidden="true"></i>
                <a href="{{route('tasks.index')}}?s_exe_statusid=2">Задачи к исполнению</a>
            </div>
            <div class="card-body ">
                @if(isset($data->users_tasks) and count($data->users_tasks))
                    <table class="table table-striped table-sm">
                        @foreach($data->users_tasks as $usr)
                            <tr>
                                <td class="small">{{$usr->name}}</td>
                                <td class="text-right font-weight-bold">
                                    <a href="{{route('tasks.index')}}?s_task_userid={{$usr->userid}}&s_exe_statusid=2">{{$usr->cnt}}</a></td>
                            </tr>
                        @endforeach
                    </table>
                @endif
            </div>
        </div>
    </div>
@endif
