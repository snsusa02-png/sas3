@if ($rec->id != -1)
<div class="small" style="margin-top: 8px; color:gray">
    создана: {{$rec->created_at}} / {{$rec->whocrt}}
    &nbsp;&nbsp;
    изменена: {{$rec->updated_at}} / {{$rec->whoupd}}
    <br><a
        href="{{route('objevntlog',['sysobjid'=>$sysobjid, 'objid'=>$rec->id,'route'=>Route::current()->getName()])}}">журнал</a>
</div>
@endif
