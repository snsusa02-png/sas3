@if ($rec->id != -1 and $usrrights['read']??false and isset($rec->ref_staff) )

    <div class="row">

        <div class="col-md-12">
            <div class="card mt-3">
                <div class="card-header">
                    <i class="fa fa-list-ol text-info" aria-hidden="true"></i>
                    Работники, использующие эту схему расчета ЗП в настоящий момент

                    <span class="float-right">
                        @if(count($rec->ref_staff)>0)
                            <button data-toggle="collapse" data-target="#_salaries"
                                    class="btn btn-light btn-sm">
                            <i class="fa fa-eye-slash" aria-hidden="true"></i>
                            <span class="badge badge-info">{{count($rec->ref_staff)}}</span>
                        </button>
                        @endif
                    </span>
                </div>

                @if (isset($rec->ref_staff) and count($rec->ref_staff)>0)
                    <div class="card-body collapse show" id="_salaries">
                        <table class="table table-striped w-100" style="">
                            <thead>
                            <tr>
                                <td class="text-left">ФИО</td>
                                <td class="text-center small">Период</td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($rec->ref_staff as $itm)
                                <tr>
                                    <td class="small text-left">{{$itm->name}}
                                        <div class="float-right small">{{$itm->postname}} {{$itm->org_name}}
                                        , стаж: {{$itm->stf_stage}}</div>
                                    </td>
                                    <td class="small text-left">{{date_create($itm->begdate)->format('d.m.Y')}}</td>
                                    <td class="text-right">
                                        <a href="{{ route('orgstaff.edit',$itm->id)}}?returl={{Request::url()}}"
                                           class="btn btn-sm btn-primary">
                                            <i class="fa fa-pencil">
                                            </i>
                                        </a>
                                    </td>
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
