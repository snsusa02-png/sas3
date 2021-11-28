<div class="tab-pane fade " id="nsi-office" role="tabpanel" aria-labelledby="nsi-office-tab">
    <div class="list-group list-group-flush">

        @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'orgs.read'))
            <a href="{{route('orgcontacts.index')}}" class="list-group-item list-group-item-action">
                Контактные данные
            </a>
        @else
            <li>&nbsp;<span class="ml-1 disable">Контактные данные</span></li>
        @endif

        <span class="ml-1 disable">Архив документов</span>

        @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'tasks.read'))
            <a href="{{route('tasks.index')}}" class="list-group-item list-group-item-action">
                Задачи
            </a>
        @else
            <span class="ml-1 disable">Задачи</span>
        @endif

    </div>
</div>
