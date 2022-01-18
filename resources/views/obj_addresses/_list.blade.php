@if ($rec->id != -1 and isset($rec->addresses) )
    <div class="row">

        <div class="col-md-12">
            <div class="card mt-3">
                <div class="card-header">
                    <i class="fa fa-envelope-o" aria-hidden="true"></i>
                    Адреса

                    <span class="float-right">
                        <button data-toggle="collapse" data-target="#_addresses"
                                class="btn btn-light btn-sm">
                            <i class="fa fa-eye-slash" aria-hidden="true"></i>
                            <span class="badge badge-info">{{count($rec->addresses)}}</span>
                        </button>
                        @if($usrrights['save']??true)
                            <a href="{{ route('obj_addresses.create',['sysobjid'=>$sysobjid, 'objid'=>$rec->id])}}"
                               class="btn btn-warning btn-sm ml-1">
                            <i class="fa fa-plus"></i>
                        </a>
                        @endif
                    </span>
                </div>

                @if (count($rec->addresses)>0)
                    <div class="card-body collapse" id="_addresses">
                        <table class="table table-striped w-100" style="">
                            {{--                            <thead>--}}
                            {{--                            <tr>--}}
                            {{--                                <td>#</td>--}}
                            {{--                                <td>Название</td>--}}
                            {{--                                <td></td>--}}
                            {{--                            </tr>--}}
                            {{--                            </thead>--}}
                            <tbody>
                            @foreach($rec->addresses as $itm)
                                <?php
                                $href = null;

                                $adr = $itm->street_adr;
                                if (!empty($itm->city))
                                    $adr .= ', ' . $itm->city;
                                if (!empty($itm->region))
                                    $adr .= ', ' . $itm->region;
                                if (!empty($itm->zip))
                                    $adr .= ', ' . $itm->zip;
                                if (!empty($itm->country))
                                    $adr .= ', ' . $itm->country;
                                ?>
                                <tr>
                                    <td style="text-align: right;"
                                        class="small">{{$loop->iteration}}</td>

                                    <td class="text-left">
                                        <span class="font-weight-bold" title="{{$itm->addresstype_name}}">
                                            @if(isset( $href))
                                                <a href="{{$href}}">{{$adr}}</a>
                                            @else
                                                {{$adr}}
                                            @endif
                                        </span>
                                        <span class="small text-secondary">{{$itm->addresstype_name}} {{$itm->notes}} </span>
                                        <a href="{{ route('obj_addresses.print_envelope',$itm->id)}}"
                                           class="btn btn-sm btn-secondary ml-2" target="_blank">
                                            <i class="fa fa-print"></i>
                                        </a>
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('obj_addresses.edit',$itm->id)}}"
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
