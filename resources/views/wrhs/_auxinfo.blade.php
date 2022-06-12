@if ($rec->id != -1 and isset($auxinfo) and is_array($auxinfo) and count($auxinfo)>0)

    <div class="card mt-3">
        <div class="card-header">
            Доп. информация
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
                            @if ($itm['route'] != "")
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
                            {{$itm['sample']}}
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
