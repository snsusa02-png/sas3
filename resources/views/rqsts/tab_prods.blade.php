<?php
$userid = \Auth::user()->id;
?>
<div class="tab-pane fade" id="nsi-prods" role="tabpanel" aria-labelledby="nsi-prods-tab">
    <div class="list-group list-group-flush">

        <?php
        $menu_itms = [
            [
                'right' => 'wrhdocs.read',
                'route' => 'wrhdocs.index',
                'title' => 'Учет движения по складу',
                'sysobjid' => '1107',
            ],
            [
                'right' => 'ri_compounds.read',
                'route' => 'ri_compounds.index',
                'title' => 'Состав изделий',
                'sysobjid' => '147',
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
