<div class="tab-pane fade " id="nsi-mchnrqsts" role="tabpanel" aria-labelledby="nsi-mchnrqsts-tab">
	<div class="list-group list-group-flush">

		@if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'mchnrqsts.update'))
			<a href="{{route('reports.rep1')}}"
			   class="list-group-item list-group-item-action font-weight-bold">
				 <i class="fa fa-file-text-o" aria-hidden="true"></i>
				 ---Отчет о работе спецтехники и механизмов
			</a>
		@endif

	</div>
</div>
