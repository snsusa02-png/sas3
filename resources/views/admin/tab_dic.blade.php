<div class="tab-pane fade " id="nsi-dic" role="tabpanel" aria-labelledby="nsi-fsd-tab">
    <div class="list-group list-group-flush">

        @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'orgs.read'))
            <a href="{{route('orgs.index')}}"
               class="list-group-item list-group-item-action">Контрагенты
                <div class="description small font-italic" style="margin-left:2em;">
                    Организации, банковские реквизиты, офисы, персонал, ...
                </div>
            </a>
        @endif

        @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'orgstaff.read'))
            <a href="{{route('orgstaff.index')}}"
               class="list-group-item list-group-item-action">Персонал организаций
                <div class="description small font-italic" style="margin-left:2em;">
                </div>
            </a>
        @endif

        @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'contracts.read'))
            <a href="{{route('contracts.index')}}"
               class="list-group-item list-group-item-action">Договоры с контрагентами
                <div class="description small font-italic" style="margin-left:2em;">
                </div>
            </a>
        @endif

        @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'machines.read'))
            <a href="{{route('machines.index')}}"
               class="list-group-item list-group-item-action">Спецтехника
            </a>
        @endif

        @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'mchntypes.read'))
            <a href="{{route('mchntypes.index')}}"
               class="list-group-item list-group-item-action">Типы спецтехники (машин и механизмов)
            </a>
        @endif

        @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'refitems.read'))
            <a href="{{route('refitems.index')}}"
               class="list-group-item list-group-item-action">Номенклатура
            </a>
        @endif

        @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'unittypes.read'))
            <a href="{{route('unittypes.index')}}"
               class="list-group-item list-group-item-action">Единицы измерения
            </a>
        @endif

        @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'itmtypes.read'))
            <a href="{{route('itmtypes.index')}}"
               class="list-group-item list-group-item-action">Категории номенклатуры
            </a>
        @endif

        @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'refitems.read'))
            <a href="{{route('ri_sup_prices.index')}}"
               class="list-group-item list-group-item-action">Прайслист
            </a>
        @endif

        @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'wrhs.read'))
            <a href="{{route('wrhs.index')}}"
               class="list-group-item list-group-item-action">Склады предприятия
            </a>
        @endif

        @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'paydocs.read'))
            <a href="{{route('fuelcards.index')}}"
               class="list-group-item list-group-item-action">Топливные карты
            </a>
        @endif

        @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'paydocs.read'))
            <a href="{{route('idcards.index')}}"
               class="list-group-item list-group-item-action">Идентификационные карты сотрудников
            </a>
        @endif

        @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'route_points.read'))
            <a href="{{route('route_points.index')}}"
               class="list-group-item list-group-item-action">Баллы по маршрутам
            </a>
        @endif

        @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'reports.read'))
            <a href="{{route('reports.index').'#reports'}}"
               class="list-group-item list-group-item-action">Отчеты
            </a>
        @endif

    </div>
</div>
