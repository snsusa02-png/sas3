@if ($rec->id != -1 and isset($rec->flags) )
    <div class="row">

        <div class="col-md-12">
            <div class="card mt-3">
                <div class="card-header">
                    Особенности

                    @if($usrrights['save']??true)
                        <a href="{{ route('objflags.create',['sysobjid'=>$sysobjid, 'objid'=>$rec->id])}}"
                           class="btn btn-warning btn-sm"
                           style="margin-left:16px;float: right;">
                            <i class="fa fa-plus"></i>
                        </a>
                    @endif
                </div>

                @if (count($rec->flags)>0)
                    <div class="card-body">
                        <table class="table table-striped w-100" style="">
{{--                            <thead>--}}
{{--                            <tr>--}}
{{--                                <td>#</td>--}}
{{--                                <td>Название</td>--}}
{{--                                <td></td>--}}
{{--                            </tr>--}}
{{--                            </thead>--}}
                            <tbody>
                            @foreach($rec->flags as $itm)
                                <tr>
                                    <td style="text-align: right;"
                                        class="small">{{$loop->iteration}}</td>

                                    <td class="text-left">&nbsp;{{$itm->flagtype_name}}</td>
                                    <td style="text-align: right;">
                                        <a href="{{ route('objflags.edit',$itm->id)}}"
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
        </div>
    </div>
@endif
