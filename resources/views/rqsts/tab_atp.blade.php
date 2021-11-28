<?php
$userid = \Auth::user()->id;
?>
<div class="tab-pane fade" id="nsi-info" role="tabpanel" aria-labelledby="nsi-info-tab">
    <div class="list-group list-group-flush">


        @if (\App\usrsysright::isUserHasRightByCode($userid,'mchnrqsts.read'))
            <a href="{{route('mchnrqsts.index')}}" class="list-group-item list-group-item-action" title="заявки на спецтехнику">
                Заявки
            </a>
        @endif

    </div>
</div>
