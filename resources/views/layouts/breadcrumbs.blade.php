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
                @if(isset($data->sysobj))
                    <span class="ml-3">
                        <a href="{{route('acslst.index',$data->sysobj)}}" class="" target="_blank"><i class="fa fa-key text-secondary" aria-hidden="true"></i></a>
                    </span>
                @endif
            </nav>
        </div>
    </div>
@endif
