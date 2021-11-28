@if(isset($breadcrumbs) and count($breadcrumbs)>0)
    <div class="row">
        <div class="col-md-12">
            <nav class="breadcrumb mt-0">
                <a class="breadcrumb-item" href="/home"><i class="fa fa-home text-info" aria-hidden="true"></i></a>
                @foreach($breadcrumbs as $name=>$url)
                    @if(isset($url))
                        <a class="breadcrumb-item" href="{{$url}}">{{$name}}</a>
                    @else
                        <span class="breadcrumb-item active">{{$name}}</span>
                    @endif
                @endforeach
            </nav>
            @if(isset($data->sysobj))
                <span class="float-right">
                        <a href="{{route('acslst.index',$data->sysobj)}}" class="float-right" target="_blank">ACL</a>
                    </span>
            @endif
        </div>
    </div>
@endif
