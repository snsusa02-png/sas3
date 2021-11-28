<?php
$userid = \Auth::user()->id;
?>
<div class="tab-pane fade" id="nsi-meet" role="tabpanel" aria-labelledby="nsi-meet-tab">
	<div class="list-group list-group-flush">


		@if (\App\usrsysright::isUserHasRightByCode($userid,'equiprqsts.read'))
			<a href="{{route('equiprqsts.index')}}" class="list-group-item list-group-item-action">
				Материалы и оборудование
			</a>
		@endif

	</div>
</div>
