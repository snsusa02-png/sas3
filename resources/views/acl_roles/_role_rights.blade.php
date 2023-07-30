@if((isset($rec->role_rights) and count($rec->role_rights)>0) or $usrrights['save'])
    <div class="card mt-3">
        <div class="card-header">
            Права
            @if ($usrrights['save'])
                <span class="float-right">
                <a class="btn btn-sm btn-info"
                   href="{{ route('acl_roles.edit_rights', ['id'=>$rec->id]) . '?returl='.url()->full() }}"
                   title="Установить/Отозвать права пользователя"
                   style1="float: right"
                ><i class="fa fa-pencil"></i>
                </a>
                </span>
            @endif

        </div>
        <div class="card-body rights_list">
            <?php $prevObjID = ""; ?>
            @foreach($rec->role_rights as $r)
            @if ($prevObjID != $r->objid)
            @if ($prevObjID != "")
            </ul>
            @endif
            <div class="sysobjname">{{$r->objname}}</div>
            <?php $prevObjID = $r->objid; ?>
            <ul>
                @endif
                <li> {{$r->funcname}}</li>
                @endforeach
            </ul>

        </div>
        @if ($usrrights['save'])
            <a class="btn btn-close btn-info mx-1 mb-1"
               href="{{ route('acl_roles.edit_rights', ['id'=>$rec->id]) . '?returl='.url()->full() }}"
               title="Установить/Отозвать права пользователя"
            >
                Редактировать
            </a>
        @endif
    </div>
@endif
