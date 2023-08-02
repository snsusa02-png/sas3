
@if ($rec->id != -1 and isset($rec->child_docs) and $rec->child_docs->count()>0 )

{{--    <div class="row">--}}

{{--        <div class="col-md-12">--}}
            <div class="card mt-3">
                <div class="card-header">
                    <i class="fa fa-list-ol text-info" aria-hidden="true"></i>
                    Связанные документы

                    <span class="float-right">

                            <button data-toggle="collapse" data-target="#_salaries"
                                    class="btn btn-light btn-sm">
                            <i class="fa fa-eye-slash" aria-hidden="true"></i>
                            <span class="badge badge-info">{{$rec->child_docs->count()}}</span>
                        </button>

                    </span>
                </div>

                @if (1==1)
                    <div class="card-body collapse show" id="_salaries">
                        <table class="table table-striped w-100" style="">
                            <thead>
                            <tr>
                                <td class="text-left">Тип, №, дата документа</td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($rec->child_docs as $itm)
                                <tr>
                                    <td class="small text-left">
                                        <a href="{{ route('wrhdocs.edit',$itm->id)}}?returl={{Request::url()}}">
                                            {{$itm->info}}
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
{{--        </div>--}}
{{--    </div>--}}
@endif
