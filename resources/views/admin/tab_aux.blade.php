<div class="tab-pane fade" id="nsi-aux" role="tabpanel" aria-labelledby="nsi-aux-tab">

	<div class="list-group list-group-flush">

		<a href="{{route('grptypes.index')}}" class="list-group-item list-group-item-action">
			Группы объектов
			<div class="description small font-italic" style="margin-left:2em;">
				Типы групп - Группы объектов
			</div>
		</a>

		@if(\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'translittypes.read'))
			<a href="{{route('ri.fillsearchnames')}}"
			   class="list-group-item list-group-item-action">Заполнение поисковых наименований
				товаров
			</a>
			<a href="{{route('translittypes.index')}}"
			   class="list-group-item list-group-item-action">
				Способы транслитерации названий</a>
		@endif
	</div>
</div>