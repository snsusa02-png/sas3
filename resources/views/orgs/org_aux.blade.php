@if( 1==1 and isset($rec) and ($rec->id!=-1) and $rec->kindid==1 )

    <div class="card d-none d-sm-block  p-2 my-2 my-md-3"
         style="min-width:400px !important;">


        <div class="card-header">
            <div class="hdr-btn">
            </div>
            <div class="title">
                <i class="fa fa-info text-success" aria-hidden="true"></i>
                Доп. информация
            </div>
        </div>
        <div class="card-body">

            <table class="table">
                <tbody>
                @foreach($auxinfo as $itm)
                    <?php
                    $btn_class = "btn-warning";
                    if (isset($itm['btn-class'])) {
                        $btn_class = $itm['btn-class'];
                    }
                    ?>
                    <tr>
                        <td>
                            @if(isset($itm['route']))
                                <a href="{{ route($itm['route'],$rec->id)}}"
                                   class="btn btn-sm org-aux {{$btn_class}}"
                                   title="{{$itm['name']}}">
                                    {{$itm['name']}}
                                </a>
                            @else
                                {{$itm['name']}}
                            @endif
                        </td>
                        <td class="l small">
                            {!! $itm['sample'] !!}
                        </td>
                        <td class="r small">
                            {{$itm['reccount']}}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
