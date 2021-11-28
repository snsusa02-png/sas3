<div class="tab-pane fade" id="nsi-admin" role="tabpanel" aria-labelledby="nsi-admin-tab">
	<div class="list-group list-group-flush">

		@if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'users.read'))
			<a href="{{route('users.index')}}"
			   class="list-group-item list-group-item-action">Пользователи</a>
		@endif
{{--        @dd(\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'acs.admin'))--}}

		@if(\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'acs.read'))
			<a href="{{route('acs.index')}}"
			   class="list-group-item list-group-item-action">Категории информации
				<div class="description small font-italic" style="margin-left:2em;">
                    Для доступа пользователей
				</div>
			</a>
		@endif
		@if(\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'extsystems.read'))
			<a href="{{route('extsystems.index')}}"
			   class="list-group-item list-group-item-action">Внешние системы
				<div class="description small font-italic" style="margin-left:2em;">
					Справочник источников информации
				</div>
			</a>
		@endif
		@if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'loadExtData'))
			<a href="{{route('importfiles.index')}}"
			   class="list-group-item list-group-item-action">Загрузка
				внешних данных
				<div class="description small font-italic" style="margin-left:2em;">Цены -
					Характеристики - Документы отгрузки - Остатки товаров
				</div>
			</a>
		@endif
		@if(1==0)
			<a href="#" class="list-group-item list-group-item-action">
				Журнал
			</a>
		@endif
	</div>
</div>
