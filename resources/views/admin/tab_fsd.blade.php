<div class="tab-pane fade " id="nsi-fsd" role="tabpanel" aria-labelledby="nsi-fsd-tab">
	<div class="list-group list-group-flush">

		@if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'events.update'))
			<a href="{{route('events.index')}}"
			   class="list-group-item list-group-item-action">Расписание игр
			</a>
		@endif

	</div>
</div>
