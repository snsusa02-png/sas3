<?php
$userid = \Auth::user()->id;
?>
<div class="tab-pane fade" id="nsi-pays" role="tabpanel" aria-labelledby="nsi-pays-tab">
    <div class="list-group list-group-flush">

        <?php
        $menu_itms = [
            [
                'right' => 'paydocs.read',
                'route' => 'paydocs.index',
                'title' => 'Платежи'
            ],
        ];
        ?>
        @foreach($menu_itms as $mnu)

            @if (1==1 and \App\usrsysright::isUserHasRightByCode($userid, $mnu['right']))
                <a href="{{route($mnu['route'])}}"
                   class="list-group-item list-group-item-action font-weight-bold">{{$mnu['title']}}
                </a>
            @else
                <span class="list-group-item disable" style="color: silver">{{$mnu['title']}}</span>
            @endif

        @endforeach
    </div>
</div>
