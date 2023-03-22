{{--<ul>--}}
{{--    @foreach($categories as $category)--}}
{{--        <li>{{ $category->name }}</li>--}}
{{--        @if($category->ProductCategory->count() > 0)--}}
{{--            @include('itmtypes.sub_tree', ['categories' => $category->ProductCategory])--}}
{{--        @endif--}}
{{--    @endforeach--}}
{{--</ul>--}}

<ul>
    @foreach($categories as $category)
        <li>
            <span class="itmtypeid " data-id="{{$category->id}}">{{$category->name}}</span>
            @if($category->sub->count())
                <input type="checkbox" id="chk{{$category->id}}"><label
                        for="chk{{$category->id}}"></label>
                @include('itmtypes.sub_tree', ['categories' => $category->sub])
            @endif
        </li>
    @endforeach
</ul>
