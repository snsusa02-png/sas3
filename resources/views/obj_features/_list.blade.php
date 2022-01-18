@if ($rec->id != -1 and isset($rec->features) )
    <div class="row">

        <div class="col-md-12">
            <div class="card mt-3">
                <div class="card-header">
                    <i class="fa fa-barcode" aria-hidden="true"></i>
                    Характеристики

                    <span class="float-right">
                        <button data-toggle="collapse" data-target="#_features"
                                class="btn btn-light btn-sm">
                            <i class="fa fa-eye-slash" aria-hidden="true"></i>
                            <span class="badge badge-info">{{count($rec->features)}}</span>
                        </button>
                        @if($usrrights['save']??true)
                            <a href="{{ route('obj_features.create',['sysobjid'=>$sysobjid, 'objid'=>$rec->id])}}"
                               class="btn btn-warning btn-sm ml-1">
                            <i class="fa fa-plus"></i>
                        </a>
                        @endif
                    </span>
                </div>

                @if (count($rec->features)>0)
                    <div class="card-body collapse" id="_features">
                        <table class="table table-striped w-100" style="">
                            {{--                            <thead>--}}
                            {{--                            <tr>--}}
                            {{--                                <td>#</td>--}}
                            {{--                                <td>Название</td>--}}
                            {{--                                <td></td>--}}
                            {{--                            </tr>--}}
                            {{--                            </thead>--}}
                            <tbody>
                            @foreach($rec->features as $itm)
                                <?php
                                ?>
                                <tr>
                                    <td style="text-align: right;"
                                        class="small">{{$loop->iteration}}</td>

                                    <td class="text-center">
                                        <span class="text-secondary small"> {{$itm->featuretype_name}}:</span>
                                        <span class="font-weight-bold" title="">
                                                {{$itm->val}}
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <a href="{{ route('obj_features.edit',$itm->id)}}"
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
