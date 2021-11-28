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
                'title' => 'Самосвальные перевозки'
            ],
            [
                'right' => 'mchn_raids.read',
                'route' => 'mchn_raids.index',
                'title' => 'Тральные перевозки'
            ],
            [
                'right' => 'mchn_raids.read',
                'route' => 'mchn_raids.index',
                'title' => 'Манипуляторы'
            ],
            [
                'right' => 'mchn_raids.read',
                'route' => 'mchn_raids.index',
                'title' => 'Экскаваторы и бульдозеры'
            ],
            [
                'right' => 'mchn_raids.read',
                'route' => 'mchn_raids.index',
                'title' => 'Земляные работы'
            ],
            [
                'right' => 'mchn_raids.read',
                'route' => 'mchn_raids.index',
                'title' => 'Топливозаправщики'
            ],
            [
                'right' => 'mchn_raids.read',
                'route' => 'mchn_raids.index',
                'title' => 'Бетон'
            ],
            [
                'right' => 'mchn_raids.read',
                'route' => 'mchn_raids.index',
                'title' => 'Швинги'
            ],
            [
                'right' => 'driver_works.read',
                'route' => 'driver_works.index',
                'title' => 'Рабочее время водителей'
            ],
        ];
        ?>
        @foreach($menu_itms as $mnu)

            @if (1==1 and \App\usrsysright::isUserHasRightByCode($userid, $mnu['right']))
                <a href="{{route($mnu['route'])}}"
                   class="list-group-item list-group-item-action font-weight-bold">{{$mnu['title']}}
                </a>
            @else
                <span class="list-group-item disable" style="color: silver">{{$mnu['title']}}
                    @if(1==0 or \App\usrsysright::isUserHasRightByCode($userid,'admin-global'))
                        <span class="small float-right">
                        <a href="{{route('acslst.index',520)}}" class="float-right" target="_blank">ACL</a>
                        </span>
                    @endif
                </span>
            @endif

        @endforeach

    </div>
</div>
