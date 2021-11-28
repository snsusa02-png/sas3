<div class="tab-pane fade" id="nsi-stock" role="tabpanel"
	 aria-labelledby="nsi-stock-tab">
	<div class="list-group list-group-flush">
		@if(Module::collections()->has('Stock'))
			@if (1==1 or (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'stock')
			and \App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'wrhdocs.read')))
				<a href="/stock/wrhdocs"
				   class="list-group-item list-group-item-action">Документы</a>
			@endif
			@if (1==1 and \App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'wrhdoctypes.read'))
				<a href="#" class="list-group-item list-group-item-action">
					Типы документов склада
				</a>
			@endif
		@endif
		@if(Module::collections()->has('Stock') or Module::collections()->has('StockSimple'))
			@if (1==1 or \App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'stock'))
				<a href="{{route('wrhs.index')}}" class="list-group-item list-group-item-action">Справочник складов</a>
			@endif

			<a href="{{route('pref_catqtyfmt')}}"
			   class="list-group-item list-group-item-action">Формат отображения остатка товара в каталоге
			</a>

		@endif

		<div>
			@if(!(Module::collections()->has('Stock') or Module::collections()->has('StockSimple')))
				<h2>Модуль "Учет склада" не подключен</h2>
			@endif
			<hr>
			<p>
				Модуль "Учет склада" позволяет учитывать остатки товаров в разрезе складов и организаций-владельцев.
			<ul>
				<li>загрузка данных из учетных систем холдинга или самостоятельное ведение документооборота движения
					товаров по складам;
				</li>
				<li>отображение остатка товара в каталоге для клиента;</li>
				<li>анализ товарных запасов;</li>
				<li>уведомления о снижении запасов ниже заданных значений;</li>
			</ul>
			<hr>
			</p>
		</div>

	</div>
</div>
