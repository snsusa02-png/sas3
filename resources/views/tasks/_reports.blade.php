@if( 1==1 and isset($rec) and ($rec->id!=-1) and isset($rec->reports))
    <div class="card mt-3 d-none d-sm-block">
        <div class="card-header">
			<span data-toggle="collapse" data-target="#reports"><i class="fa fa-reports text-danger"
                                                                 aria-hidden="true"></i> Отчеты по задаче</span>

            <div class="float-right">
                @if (count($rec->reports)>0)
                    <button data-toggle="collapse" data-target="#reports"
                            class="btn btn-light btn-sm "><i class="fa fa-eye-slash" aria-hidden="true"></i>
                        <span class="badge badge-info">{{count($rec->reports)}}</span>
                    </button>
                @endif

                @if( $usrrights['task_reports.create']??false)
                    <a href="{{ route('task_reports.create',['taskid'=>$rec->id])}}"
                       class="btn btn-warning btn-sm ">
                        <i class="fa fa-plus"></i>
                    </a>
                @endif
            </div>
        </div>

        @if (count($rec->reports)>0)
            <div class="card-body collapse" id="reports">
                <table class="table-condensed table-striped small" style="width: 100%;">
                    <thead>
                    <tr>
                        <td>#</td>
                        <td class="text-left">Период</td>
                        <td>Отчет</td>
                        <td>Исполнение</td>
                        <td/>
                    </tr>
                    </thead>
                    <tbody>
                    <tr></tr>
                    <?php
                    $totReadCnt = 0;
                    $MustReadNoReadCnt = 0;

                    $roletypes = \App\task_user::roletypes();

                    ?>
                    @foreach($rec->reports as $itm)
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
                            <td>
                                <span class="small"> {{date_create($itm->wrkbegdt)->format('d.m.Y')}} - {{date_create($itm->wrkenddt)->format('d.m.Y')}}</span>
                            </td>
                            <td>{{mb_substr($itm->report,0,160)}} ...</td>
                            <td class="text-center">{{$itm->progress}}%</td>
                            <td class="text-right">
                                <a href="{{ route('task_reports.edit',$itm->id)}}?returl={{Route::current()->getName()}}"
                                   class="btn btn-sm btn-light"
                                   title="Просмотреть/Изменить запись">
                                    <i class="fa fa-pencil small"></i>
                                </a>
                            </td>
                        </tr>
                            <tr></tr>
                        @php($totReadCnt+=$itm->read_cnt)
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <div class="small text-right"> всего: {{count($rec->reports)}},
{{--                    подходов: {{number_format($totReadCnt,0)}}--}}
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
