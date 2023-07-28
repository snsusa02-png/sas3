@if((isset($rec->usrsysrights) and count($rec->usrsysrights)>0) or $usrrights['edtrights'])
    <div class="card mt-3">
        <div class="card-header">
            Права
            @if ($usrrights['edtrights'])
                <span class="float-right">
                <a class="btn btn-sm btn-warning"
                   href="{{ route('users.clone_rights', $rec->id) . '?returl='.url()->full() }}"
                   title="Добавить права от другого пользователя"
                   style1="float: right"
                ><i class="fa fa-clone"></i>
                </a>
                <a class="btn btn-sm btn-info"
                   href="{{ route('users.sysrights', ['id'=>$rec->id, 'limsysobjid'=>0, 'limobjid'=>0]) . '?returl='.url()->full() }}"
                   title="Установить/Отозвать права пользователя"
                   style1="float: right"
                ><i class="fa fa-pencil"></i>
                </a>
                </span>
            @endif

        </div>
        <div class="card-body rights_list">
            <?php $prevObjID = ""; ?>
            @foreach($rec->usrsysrights as $r)
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
        @if ($usrrights['edtrights'])
            <a class="btn btn-close btn-info mx-1 mb-1"
               href="{{ route('users.sysrights', ['id'=>$rec->id, 'limsysobjid'=>0, 'limobjid'=>0]) . '?returl='.url()->full() }}"
               title="Установить/Отозвать права пользователя"
            >
                Редактировать
            </a>
        @endif
    </div>
@endif
