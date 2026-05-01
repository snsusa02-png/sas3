{{--@if ($rec->id != -1 and $usrrights['obj_docs.read']??false and isset($rec->docs) )--}}
{{--@dd($rec->docs)--}}
@if ($rec->id != -1 and isset($rec->docs) )
    <div class="row">

        <div class="col-md-12">
            <div class="card mt-3">
                <div class="card-header">
                    <i class="fa fa-book text-danger" aria-hidden="true"></i>
                   Документы
                    <span class="float-right">
                        @if(count($rec->docs)>0)
                            <button data-toggle="collapse" data-target="#_salaries"
                                    class="btn btn-light btn-sm">
                            <i class="fa fa-eye-slash" aria-hidden="true"></i>
                            <span class="badge badge-info">{{count($rec->docs)}}</span>
                        </button>
                        @endif
                        @if($usrrights['save']??true)
                            <a href="{{ route('obj_docs.create',['sysobjid'=>$sysobjid, 'objid'=>$rec->id])}}"
                                   class="btn btn-warning btn-sm ml-1">
                            <i class="fa fa-plus"></i>
                        </a>
                        @endif
                    </span>
                </div>

                @if (count($rec->docs)>0)
                    <div class="card-body collapse show" id="_salaries">
                        <table class="table table-striped w-100" style="">
                            <thead>
                            <tr>
                                <td class="text-center">Документ</td>
                                <td class="text-center">Период действия</td>
                                <td></td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($rec->docs as $itm)
                                <?php
                                $period = date_create($itm->begdate)->format('d.m.Y')
                                    . ' - ' . date_create($itm->enddate)->format('d.m.Y');
                                ?>
                                <tr>
                                    <td class="text-left">
                                        {{$itm->doctype->name}}
                                        <div class="small">{{$itm->docseria}} {{$itm->docnum}}</div>
                                    </td>
                                    <td class="small text-center">{{$period}}</td>
                                    <td class="text-right">
                                        <a href="{{ route('obj_docs.edit',$itm->id)}}?returl={{Request::url()}}"
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
