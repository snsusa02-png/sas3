<?php
$userid = \Auth::user()->id;
?>
<div class="tab-pane fade" id="nsi-rqsts" role="tabpanel" aria-labelledby="nsi-rqsts-tab">
    <div class="list-group list-group-flush">

        <?php
        $menu_itms = [
            [
                'right' => 'mchn_raids.read',
                'route' => 'mchn_raids.index',
                'title' => 'Все перевозки',
                'sysobjid' => '1106',
            ],
//            [
//                'right' => 'mchn_raids.read',
//                'route' => 'mchn_raids.index',
//                'title' => 'Тральные перевозки',
//                'sysobjid' => '1106',
//            ],
//            [
//                'right' => 'mchn_raids.read',
//                'route' => 'mchn_raids.index',
//                'title' => 'Манипуляторы',
//                'sysobjid' => '1106',
//            ],
//            [
//                'right' => 'mchn_raids.read',
//                'route' => 'mchn_raids.index',
//                'title' => 'Экскаваторы и бульдозеры',
//                'sysobjid' => '1106',
//            ],
//            [
//                'right' => 'mchn_raids.read',
//                'route' => 'mchn_raids.index',
//                'title' => 'Земляные работы',
//                'sysobjid' => '1106',
//            ],
//            [
//                'right' => 'mchn_raids.read',
//                'route' => 'mchn_raids.index',
//                'title' => 'Топливозаправщики',
//                'sysobjid' => '1106',
//            ],
//            [
//                'right' => 'mchn_raids.read',
//                'route' => 'mchn_raids.index',
//                'title' => 'Бетон',
//                'sysobjid' => '1106',
//            ],
//            [
//                'right' => 'mchn_raids.read',
//                'route' => 'mchn_raids.index',
//                'title' => 'Швинги',
//                'sysobjid' => '1106',
//            ],
            [
                'right' => 'driver_works.read',
                'route' => 'driver_works.index',
                'title' => 'Рабочее время',
                'sysobjid' => '1107',
            ],
            [
                'right' => 'mchn_raids.read',
                'route' => 'fuelcard_pays.index',
                'title' => 'Заправки',
                'sysobjid' => '562',
            ],
            [
                'right' => 'mchn_spare_usages.read',
                'route' => 'mchn_spare_usages.index',
                'title' => 'Запчасти',
                'sysobjid' => '489',
            ],
        ];
        ?>
        @foreach($menu_itms as $mnu)

            @if (1==1 and \App\usrsysright::isUserHasRightByCode_cached($userid, $mnu['right']))
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
