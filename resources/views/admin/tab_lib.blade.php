<div class="tab-pane fade " id="nsi-lib" role="tabpanel" aria-labelledby="nsi-lib-tab">
	<div class="list-group list-group-flush">

		@if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'posts.read'))
			<a href="{{route('posts.public_index')}}"
			   class="list-group-item list-group-item-action">Библиотека публикаций
			</a>
		@endif

		@if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'posts.create'))
			<a href="{{route('posts.index')}}"
			   class="list-group-item list-group-item-action">Библиотека, администрирование
			</a>
		@endif
		@if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'posts.create'))
			<a href="#"
			   class="list-group-item list-group-item-action">Категории публикаций
			</a>
		@endif
		<hr>

		@if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'news.read'))
			<a href="{{route('news.public_index')}}"
			   class="list-group-item list-group-item-action">Новости
			</a>
		@endif

		@if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'news.create'))
			<a href="{{route('news.index')}}"
			   class="list-group-item list-group-item-action">Новости, администрирование
			</a>
		@endif

		@if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'newsbands.read'))
			<a href="{{route('newsbands.index')}}"
			   class="list-group-item list-group-item-action">Новостные ленты
			</a>
		@endif


	</div>
</div>
