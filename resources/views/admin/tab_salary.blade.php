<div class="tab-pane fade " id="nsi-salary" role="tabpanel" aria-labelledby="nsi-fsd-tab">
    <div class="list-group list-group-flush">

        @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'payrolltypes.read'))
            <a href="{{route('payrolltypes.index')}}"
               class="list-group-item list-group-item-action">Схемы расчета заработной платы
                <div class="description small font-italic" style="margin-left:2em;">

                </div>
            </a>
        @endif

        @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'chargetypes.read'))
            <a href="{{route('chargetypes.index')}}"
               class="list-group-item list-group-item-action">Типы начислений/удержаний
                <div class="description small font-italic" style="margin-left:2em;">

                </div>
            </a>
        @endif

        @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'org_charges.read'))
            <a href="{{route('org_charges.index')}}"
               class="list-group-item list-group-item-action">Начисления/Удержания организации
                <div class="description small font-italic" style="margin-left:2em;">
                    Ставки, период действия
                </div>
            </a>
        @endif

        @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'stf_charges.read'))
            <a href="{{route('stf_chrg_calcs.index')}}"
               class="list-group-item list-group-item-action">Начисления/Удержания сотрудников
                <div class="description small font-italic" style="margin-left:2em;">
                </div>
            </a>
        @endif

        @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'stf_pays.read'))
            <a href="{{route('machines.index')}}"
               class="list-group-item list-group-item-action">Выплаты сотрудникам
            </a>
        @endif

        @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'stf_wrkhrs.read'))
            <a href="{{route('stf_wrkhrs.index')}}"
               class="list-group-item list-group-item-action">Учет рабочих часов сотрудников
            </a>
        @endif


    </div>
</div>
