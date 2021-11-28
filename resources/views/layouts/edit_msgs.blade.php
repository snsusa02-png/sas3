@if(session()->get('success'))
	<div class="alert alert-success">
		{{ session()->get('success') }}
	</div>
@endif
@if(session()->get('warning'))
	<div class="alert alert-warning">
		{{ session()->get('warning') }}
	</div>
@endif
@if(session()->get('error'))
	<div class="alert alert-danger">
		{!! str_replace(chr(10),'<br>', session()->get('error')) !!}
	</div>
@endif
