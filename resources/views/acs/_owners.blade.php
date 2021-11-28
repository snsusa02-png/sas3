@if( 1==1 and isset($rec) and ($rec->id!=-1) and isset($rec->owners))
    <div class="card mt-3 d-none d-sm-block">
        <div class="card-header">
			<span data-toggle="collapse" data-target="#owners"><i class="fa fa-users text-info"
                                                                  aria-hidden="true"></i> Обладатели</span>

            <div class="float-right">
                @if (count($rec->owners)>0)
                    <button data-toggle="collapse" data-target="#owners"
                            class="btn btn-light btn-sm "><i class="fa fa-eye-slash" aria-hidden="true"></i>
                        <span class="badge badge-info">{{count($rec->owners)}}</span>
                    </button>
                @endif
            </div>
        </div>

        @if (count($rec->owners)>0)
            <div class="card-body collapse" id="owners">
                <table class="table-condensed table-striped small" style="width: 100%;">
                    <thead>
                    <tr>
                        <td>#</td>
                        <td colspan="2">Пользователь</td>
                        <td>Предоставлено</td>
                        <td/>
                    </tr>
                    </thead>
                    <tbody>
                    <tr></tr>
                    <?php
                    $totReadCnt = 0;
                    $MustReadNoReadCnt = 0;
                    ?>
                    @foreach($rec->owners as $itm)
                        <?php
                        $avatar_img = $itm->user->image ?? '/images/signs/user-no-photo.jpg';

                        ?>
                        <tr class="align-top">
                            <td class="small">{{$loop->iteration}}</td>
                            <td>
                                <img src="{{$avatar_img}}" style="max-height: 30px; max-width: 40px;"/>
                            </td>
                            <td>
                                <span class=""> {{$itm->name}}</span>
                            </td>
                            <td class="text-center">{{$itm->begdt}}</td>
                            <td class="text-right">
                                <a href="{{ route('users.edit',$itm->id)}}?returl={{Route::currentRouteName()}}"
                                   class="btn btn-sm btn-light"
                                   title="Просмотреть/Изменить запись">
                                    <i class="fa fa-pencil small"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
            </div>
        @endif
    </div>
@endif
