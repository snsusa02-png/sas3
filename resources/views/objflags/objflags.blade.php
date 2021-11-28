@if (isset($ObjFlags) and count($ObjFlags) > 0)
	{{--	@if (count($ObjFlags) > 0)--}}
	<div class="card d-none d-sm-block  p-2 my-2 my-md-3"
		 style="min-width:400px !important;">

		<div class="hdr">
			<div class="hdr-btn">
			</div>
			<div class="title">
				<i class="fa fa-flag-checkered text-warning" aria-hidden="true"></i> {{$FlagsHeader}}
			</div>
		</div>
		<ul>
			@foreach($ObjFlags as $Flag)
				<div class="px-1 small" style="{{$Flag->flagtype->css_style}}">
					<li>{{ $Flag->flagtype->name }}
						<span class="small"> ( {{ $Flag->created_at }}, {{ $Flag->whocrt->name}} ) </span></li>
				</div>
			@endforeach
		</ul>
	</div>
	{{--	@endif--}}
@endif	

