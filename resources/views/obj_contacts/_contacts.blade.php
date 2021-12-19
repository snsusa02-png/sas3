@if ($rec->id != -1 and isset($rec->contacts) )
    <div class="row">

        <div class="col-md-12">
            <div class="card mt-3">
                <div class="card-header">
                    <i class="fa fa-volume-control-phone text-info" aria-hidden="true"></i>
                    Контактные данные

                    <span class="float-right">
                        <button data-toggle="collapse" data-target="#_contacts"
                                class="btn btn-light btn-sm">
                            <i class="fa fa-eye-slash" aria-hidden="true"></i>
                            <span class="badge badge-info">{{count($rec->contacts)}}</span>
                        </button>
                        @if($usrrights['save']??true)
                            <a href="{{ route('obj_contacts.create',['sysobjid'=>$sysobjid, 'objid'=>$rec->id])}}"
                               class="btn btn-warning btn-sm ml-1">
                            <i class="fa fa-plus"></i>
                        </a>
                        @endif
                    </span>
                </div>

                @if (count($rec->contacts)>0)
                    <div class="card-body collapse" id="_contacts">
                        <table class="table table-striped w-100" style="">
                            {{--                            <thead>--}}
                            {{--                            <tr>--}}
                            {{--                                <td>#</td>--}}
                            {{--                                <td>Название</td>--}}
                            {{--                                <td></td>--}}
                            {{--                            </tr>--}}
                            {{--                            </thead>--}}
                            <tbody>
                            @foreach($rec->contacts as $itm)
                                <?php
                                $href = null;
                                if ($itm->contacttypeid == 1) {
                                    $contact = trim($itm->contact);
//                                    if (mb_substr($contact, 0, 1) <> '+')
//                                        $contact = '+' . $contact;
                                    $href = 'tel:' . $contact;
                                } elseif ($itm->contacttypeid == 2)
                                    $href = 'mailto:' . $itm->contact;
                                ?>
                                <tr>
                                    <td style="text-align: right;"
                                        class="small">{{$loop->iteration}}</td>

                                    <td class="text-left">
                                        <span class="font-weight-bold" title="{{$itm->contacttype_name}}">
                                            @if(isset( $href))
                                                <a href="{{$href}}">{{$itm->contact}}</a>
                                            @else
                                                {{$itm->contact}}
                                            @endif
                                        </span>
                                        <span class="small text-secondary">{{$itm->notes}}</span>
                                    </td>
                                    <td style="text-align: right;">
                                        <a href="{{ route('obj_contacts.edit',$itm->id)}}"
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
