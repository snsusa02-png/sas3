{{--//принятие решения--}}
@if ($usrrights['approve']??false)
	<div class="card mb-3">
		<div class="card-header" style="background-color: beige;">Согласование заявки</div>
		<div class="card-body">

			@if ($rec->statusid==1 and $errors->any())
				<div class="alert alert-danger">
					<ul>
						@foreach ($errors->all() as $error)
							<li>{{ $error }}</li>
						@endforeach
					</ul>
				</div><br/>
			@endif

			@if($rec->need_carrierorg)
				<div class="form-group">
					<label for="address">Владелец техники:</label>
					@if(1==1)
						{!! Form::select('car_orgid', $rec->car_orgs, $rec->car_orgid,
						[
						'id' => 'car_orgid',
						'class' => 'form-control',
						'placeholder' => '-выбор-',
						]) !!}
					@endif
				</div>
			@endif

			@if($rec->rqsttypeid==1)
				<div class="form-group">
					<label for="asgnmachineid">Подтвердить технику:</label>
					{!! Form::hidden('asgnmachineid', $rec->asgnmachineid) !!}
					<div class="font-weight-bold">{{$rec->asgnmachine->name}}</div>
				</div>
			@else
				<div class="form-group asgn_own" id="">
					<label for="asgnmachineid">Назначить технику:</label>
					{!! Form::select('asgnmachineid', $rec->machines, $rec->asgnmachineid,
					[
					'id' => 'asgnmachineid',
					'class' => 'form-control',
					'placeholder' => '-выбор-',
					]) !!}
				</div>

				@if(1==0)
					<div class="form-group asgn_own">
						<label for="address">Водитель:</label>
						<input type="text" class="form-control text-left"
							   name="drivername"
							   value="{{old('drivername',$rec->drivername)}}"/>
					</div>
				@endif

				@if(1==0)
					<div class="form-group asgn_own">
						<label for="name">Договор с заказчиком:</label>
						{!! Form::select('contractid', $rec->contracts, $rec->contractid,
							[
							'class' => 'form-control',
							'placeholder' => '-выбор-',
							]) !!}
					</div>
				@endif

				@if(1==0)
					<div class="form-group asgn_ext">
						<label for="name">Цена:</label>
						<input type="number" class="form-control text-left"
							   name="fct_price" min="0"
							   value="{{old('fct_price',$rec->fct_price)}}"/>
					</div>
				@endif

					<div class="form-group asgn_own00">
						<label for="name">Тип оплаты:</label>
						{!! Form::select('paytypeid', $rec->paytypes, $rec->paytypeid,
							[
							'required' => 'required',
							'class' => 'form-control',
							'placeholder' => '-выбор-',
							]) !!}
					</div>

				@endif


			<div class="form-group">
				<label for="descript">Пояснение решения:</label>
				<textarea class="form-control rounded-0" name="dcsn_descript"
						  id="dcsn_descript"
						  rows="2">{{ $rec->dcsn_descript }}</textarea>
			</div>
			<div align="center">

				<button type="submit"
						class="btn btn-success btn-sm"
						formaction="{{ route('mchnrqsts.approve', $rec->id)}}"
						formmethod="post"
						onclick="return confirm('Заявка будет согласована. Продолжить?')"
						title="Согласовать заявку"
				>
					<i class="fa fa-thumbs-up" aria-hidden="true"></i>
					Согласовать заявку
				</button>

				<button type="submit"
						class="btn btn-danger btn-sm"
						style="margin:0 auto"
						formaction="{{ route('mchnrqsts.decline', $rec->id)}}"
						formmethod="post"
						onclick="return confirm('Вы действительно хотите отказать в заявке?')"
						title="Отказать в заявке"
				>
					<i class="fa fa-thumbs-down" aria-hidden="true"></i>
					Отказать в заявке
				</button>

				<a class="btn btn-close btn-info btn-sm ml-2" href="{{ $route_index }}"
				   title="Вернуться в список">
					<i class="fa fa-window-close-o" aria-hidden="true"></i>
					Закрыть
				</a>

			</div>
		</div>
	</div>
@endif

