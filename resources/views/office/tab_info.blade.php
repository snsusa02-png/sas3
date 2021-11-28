<?php
$userid = \Auth::user()->id;
?>
<div class="tab-pane fade" id="nsi-info" role="tabpanel" aria-labelledby="nsi-info-tab">
    <div class="list-group list-group-flush">


        @if (\App\usrsysright::isUserHasRightByCode($userid,'orgstaff.read'))
            <a href="{{route('orgcontacts.index')}}" class="list-group-item list-group-item-action">
                Контактные данные
            </a>
        @endif

        @if (\App\usrsysright::isUserHasRightByCode($userid,'news.create'))
            <a href="{{route('news.index')}}" class="list-group-item list-group-item-action">
                Новости, администрирование
            </a>
        @else
            <a href="{{route('news.public_index')}}" class="list-group-item list-group-item-action">
                Новости
            </a>
        @endif

        <a href="{{route('events.calendar')}}" class="list-group-item list-group-item-action">
            Календарь событий
        </a>

        @if (\App\usrsysright::isUserHasRightByCode($userid,'grantdocs.read'))
            <a href="{{route('grantdocs.index')}}" class="list-group-item list-group-item-action">
                Доверенности
            </a>
        @endif

        @if (\App\usrsysright::isUserHasRightByCode($userid,'projects.read'))
            <a href="{{route('proj_mails.index')}}" class="list-group-item list-group-item-action">
                Вх. почта по проектам
            </a>
        @endif

        @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'org_caselists.read'))
            <a href="{{route('org_caselists.index')}}" class="list-group-item list-group-item-action">
                Номенклатура дел
            </a>
        @else
            <li style="list-style-type: none">&nbsp;<span class=" ml-3 disable">Номенклатура дел</span>
                <span class="float-right">
                        <a href="{{route('acslst.index',1711)}}" class="float-right" target="_blank">ACL</a>
                    </span>
            </li>
        @endif


        @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'documents.read'))
            <a href="{{route('documents.index')}}" class="list-group-item list-group-item-action">
                Архив документов
            </a>
        @else
            <li style="list-style-type: none">&nbsp;<span class=" ml-3 disable">Архив документов</span>
                <span class="float-right">
                        <a href="{{route('acslst.index',1701)}}" class="float-right" target="_blank">ACL</a>
                    </span>
            </li>
        @endif

        @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'tasks.read'))
            <a href="{{route('tasks.index')}}" class="list-group-item list-group-item-action">
                Задачи
            </a>
        @else
            <li style="list-style-type: none">&nbsp;<span class=" ml-3 disable">Задачи</span>
                <span class="float-right">
                        <a href="{{route('acslst.index',691)}}" class="float-right" target="_blank">ACL</a>
                    </span>
            </li>
        @endif
    </div>
</div>
