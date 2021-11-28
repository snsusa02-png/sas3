<?php
$usrrights['edtrights'] = $usrrights['edtrights'] ?? false;
?>
@if((isset($rec->usrsysrights) and count($rec->usrsysrights)>0) or $usrrights['edtrights'])
    <div class="card mt-3">
        <div class="card-header">
            Права (в пределах представляемой организации)
            @if ($usrrights['edtrights']??false)
                <a class="btn btn-sm btn-info"
                   href="{{ route('users.sysrights', ['id'=>$rec->userid, 'limsysobjid'=>111, 'limobjid'=>$rec->orgid]) . '?returl='.url()->full() }}"
                   title="Установить/Отозвать права пользователя"
                   style="float: right"
                ><i class="fa fa-pencil"></i>
                </a>
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
            <a class="btn btn-close btn-info mx-2"
               href="{{ route('users.sysrights', ['id'=>$rec->userid, 'limsysobjid'=>111, 'limobjid'=>$rec->orgid]) . '?returl='.url()->full()}}"
               title="Установить/Отозвать права пользователя"
            >
                Редактировать
            </a>
        @endif
    </div>
@endif
