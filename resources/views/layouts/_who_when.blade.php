@if ($rec->id != -1)
    <div class="small" style="margin-top: 8px; color:gray">
        создана: {{$rec->created_at}} / {{$rec->whocrt->FirstLast??''}}
        &nbsp;&nbsp;
        изменена: {{$rec->updated_at}} / {{$rec->whoupd->FirstLast??''}}
        @if(isset($rec->lock_reason))
            <br>заблокировано: {{$rec->lock_reason}}
        @endif
        <br><a
            href="{{route('objevntlog',['sysobjid'=>($thisSysObjId??$sysobjid), 'objid'=>$rec->id,'route'=>Route::current()->getName()])}}">журнал</a>
    </div>
@endif
