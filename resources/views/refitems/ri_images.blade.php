@if( 1==1 and isset($rec) and ($rec->id!=-1) and isset($rec->images))
    <div class="card mt-3 d-none d-sm-block">
        <div class="card-header">
            Фото для продукта
            @if( $usrrights['save'])
                <a href="{{ route('ri_images.load',$rec->id)}}"
                   class="btn btn-warning btn-sm float-right">
                    <i class="fa fa-plus"></i>
                </a>
            @endif
        </div>

        @if (count($rec->images)>0)
            <div class="card-body">
                <table class="table-condensed small" style="width: 100%;">
                    <thead>
                    <tr>
                        <td>#</td>
                        <td>файл</td>
                        <td>Инфо</td>
                        <td/>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($rec->images as $itm)
                        <?php
                        $url = Storage::disk('local')->url($itm->filename);
                        ?>
                        <tr class="align-top">
                            <td class="small">{{$loop->iteration}}</td>
                            <td style="max-width:200px;">
                                {{$itm->filename}}
                            </td>
                            <td>
                                <img src="{{$url}}" style="max-height: 50px; max-width: 80px;"/>
                            </td>
                            <td/>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
