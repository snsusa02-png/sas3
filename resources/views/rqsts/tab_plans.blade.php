<?php
$userid = \Auth::user()->id;
?>
<div class="tab-pane fade" id="nsi-plans" role="tabpanel" aria-labelledby="nsi-plans-tab">
    <div class="list-group list-group-flush">

        <?php
        $menu_itms = [
            [
                'right' => null,
                'route' => 'tasks.index',
                'title' => 'Задачи',
                'sysobjid' => '961',
            ],
        ];
        ?>
        @foreach($menu_itms as $mnu)
            <?php
            if (isset($mnu['right']))
                $may_access = \App\usrsysright::isUserHasRightByCode_cached($userid, $mnu['right']);
            else
                $may_access = true;
            ?>

            @if ($may_access)
                <a href="{{route($mnu['route'])}}"
                   class="list-group-item list-group-item-action font-weight-bold">{{$mnu['title']}}
                </a>
            @elseif(isset($mnu['sysobjid']))

                <span class="list-group-item disable" style="color: silver">{{$mnu['title']}}
                    @if(1==0 or \App\usrsysright::isUserHasRightByCode($userid,'admin-global'))
                        <span class="small float-right">
                        <a href="{{route('acslst.index',$mnu['sysobjid'])}}" class="float-right" target="_blank">ACL</a>
                        </span>
                    @endif
                </span>
            @endif

        @endforeach

    </div>
</div>
