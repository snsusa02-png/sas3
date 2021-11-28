@if( 1==1 and isset($rec) and ($rec->id!=-1) and isset($rec->staffs))
    <div class="card mt-3">
        <div class="card-header" style="background-color: #f2ffca;">
			<span data-toggle="collapse" data-target="#staff">
				<i class="fa fa-users text-primary"
                   aria-hidden="true"></i> {{$rec->staffs_title??'Персонал, связанный с инф. объектом'}}</span>
            <div class="float-right">
                @if (count($rec->staffs)>0)
                    <button data-toggle="collapse" data-target="#staff"
                            class="btn btn-light btn-sm"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>
                @endif
                @if( $usrrights['obj_staffs.create']??false )
                    <a href="{{ route('obj_staffs.create',['sysobjid'=>$rec->sysobjid,'objid'=>$rec->id])}}?returl={{Request::url()}}"
                       class="btn btn-sm btn-warning"
                       title="Создать запись">
                        <i class="fa fa-plus"></i>
                    </a>

                @endif
            </div>
        </div>
        @if (count($rec->staffs)>0)
            <div class="card-body collapse" id="staff">

                <table class="table-striped " style="width: 100%;">
                    <thead>
                    <tr class="text-center align-middle small">
                        <td>#</td>
                        <td class="text-left">Роль</td>
                        <td class="text-left">ФИО</td>
                        <td class="text-left">Контакты</td>
                        <td class="text-left">Основание</td>
                        <td/>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    $curRoleName = '';
                    ?>
                    {{--                    @dd($rec->staff)--}}
                    @foreach($rec->staffs as $itm)
                        @if($itm->rolename <> $curRoleName)
                            <tr class="align-top " style="background-color: #d9eede">
                                <td colspan="6" class="text-left small font-weight-bold pl-3">
                                    {{$itm->rolename}}
                                </td>
                            <?php
                            $curRoleName = $itm->rolename;
                            ?>
                        @endif
                        <?php
                        //$stfnameclass = (isset($itm->userid)) ? 'text-success ' : '';
                        ?>
                        <tr class="align-top ">
                            <td class="small text-left">{{++$npp}}</td>
                            <td class="text-left small font-weight-bold ">

                            </td>
                            <td class="text-left small">
                                @if(isset($itm->staffid))
                                    <span class="font-weight-bold">{{($itm->staffname)}}</span>
                                    @if( $usrrights['orgstaff.read']??false)
                                        <a href="{{route('orgstaff.edit',$itm->staffid)}}" target="_blank" class="ml-0"
                                           style="color:dodgerblue"><img src="/images/signs/extlink.svg"
                                            /></a>
                                    @endif
                                    @if(isset($itm->orgstaff->userid))
                                        @if($itm->orgstaff->user->isOnline())
                                            <span class="text-success font-size-12">
												<i class="fa fa-user-circle-o" aria-hidden="true"
                                                   title="Пользователь сейчас в системе"></i>
											</span>
                                        @else
                                            <span class="text-outline font-size-12">
											<i class="fa fa-user-circle" aria-hidden="true"
                                               style="color:silver;" title="Пользователь не в системе"></i>
										</span>
                                        @endif
                                    @endif
                                    <div class="small ml-2">
                                        {{$itm->org_name}}
                                        @if( $usrrights['orgs.read']??false and isset($itm->orgid))
                                            <a href="{{route('orgs.edit',$itm->orgid)}}" target="_blank"
                                               class="ml-0"><img src="/images/signs/extlink.svg"/></a>
                                        @endif
                                        , {{$itm->post_name}}
                                    </div>
                                @else
                                    <span class="font-weight-bold">{{($itm->orgname)}}</span>
                                @endif
                            </td>
                            <td class="text-left small">
                                <ul>
                                    @if(isset($itm->staffid))
                                        @if(isset($itm->phone))
                                            <li><a href="tel:{{$itm->phone}}">{{$itm->phone}}</a></li>
                                        @endif
                                        @if(isset($itm->email))
                                            <li><a href="mailto:{{$itm->email}}">{{$itm->email}}</a></li>
                                        @endif
                                    @else
                                        @if(isset($itm->orgphone))
                                            <li><a href="tel:{{$itm->orgphone}}">{{$itm->orgphone}}</a></li>
                                        @endif
                                        @if(isset($itm->orgemail))
                                            <li><a href="mailto:{{$itm->orgemail}}">{{$itm->orgemail}}</a></li>
                                        @endif
                                    @endif
                                </ul>
                            </td>
                            <td class="text-left small">
                                {{$itm->reason}}
                            </td>
                            <td class="text-right">
                                @if(1==1)
                                    <a href="{{ route('obj_staffs.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
                                       class="btn btn-sm btn-light"
                                       title="Просмотреть/Изменить запись">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @if (count($rec->staffs)>0)
                <div class="card-footer">
                    <div class="small text-right"> всего записей: {{count($rec->staffs)}}</div>
                </div>
            @endif
        @endif
    </div>
@endif
