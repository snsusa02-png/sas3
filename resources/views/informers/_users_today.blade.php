<div class="col-lg-3 col-md-6 col-sm-6">
    <div class="card card-stats mt-3">
        <div class="card-header">
            Сейчас на сайте
        </div>
        <div class="card-body ">
            <div class="row">
                <div class="col-md-2">
                    <div class="icon-big text-center icon-warning" style="font-size: 32px">
                        <i class="uil uil-user text-success"></i>
                    </div>
                </div>
                <div class="col-md-10">
                    <div class="numbers">
                        <p class="card-title"
                           style="font-size: 24px">{{UserAct::cntAllVisitorsToday()}} </p>
                    </div>

                    @if(isset($data->now_users) and count($data->now_users))
                    <ul class="small">
                        @foreach($data->now_users as $usr)
                            <li>{{$usr->name}}</li>
                        @endforeach
                    </ul>
                    @endif
                </div>
            </div>
        </div>
        {{--
        <div class="card-footer ">
            <hr>
            <div class="stats">
                <i class="fa fa-calendar-o"></i>
                сегодня
            </div>
        </div>
        --}}
    </div>
</div>
