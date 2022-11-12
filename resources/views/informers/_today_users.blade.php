@if( isset($data->today_users) and count($data->today_users))
{{--    <div class="col-lg-3 col-md-6 col-sm-6">--}}
    <div class="col-lg-12">
        <div class="card card-stats mt-3">
            <div class="card-header">
                <i class="uil uil-user text-success"></i>
                Посетители сегодня
            </div>
            <div class="card-body ">
                    <table class="table table-striped table-sm">
                        @foreach($data->today_users as $usr)
                            <tr>
                                <td class="small">{{$usr->name}}</td>
                                <td class="text-center small">{{date_create($usr->first_dt)->format('H:i')}}
                                - {{date_create($usr->last_dt)->format('H:i')}}</td>
                            </tr>
                        @endforeach
                    </table>
            </div>
        </div>
    </div>
@endif
