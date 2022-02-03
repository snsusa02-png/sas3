@extends('layouts.edit')

@section('content')

	@if (!isset( $rec ))
        <?php
        redirect()->route('/');
        header("Location:/");
        die();
        ?>
	@else
        <?php
        $sysobjid = 153;
        $sysobjcode = 'contract_prices';
        $thisTitle = "Контрактная цена";
        $retRoute = ($rec->retURL)
            ? route($rec->retURL, $rec->objid)
            : route('contracts.edit', $rec->contractid);
        $sysobjlbl = 'контракт'; //todo: определить в контролере

        //для блокировки текстовых полей пользователям, не имеющим право на редактирование
        $inputReadOnly = "readonly";
        if ($usrrights['save']) $inputReadOnly = "";

        ?>
		<style>
			label {
				color: gray;
				margin-bottom: 0px;
			}

			.btn {
				margin-bottom: 4px;
			}

		</style>
		<div class="container">

			<div class="row">
				<div class="col-md-6 col-sm-12">

					<div class="card mt-3">

						@includeIf('layouts.edit_msgs')

						<form name="forEdit" id="forEdit" method="post"
							  action="{{ route($sysobjcode.'.update', [$rec->id, $rec->sysobjid, $rec->objid]) }}">

							@method('PUT')
							@csrf
							{{ Form::hidden('retURL', $rec->retURL) }}
							{{ Form::hidden('contractid', $rec->contractid) }}
							{{ Form::hidden('sysobjid', $rec->sysobjid) }}

							<div class="card-header">
								{{$thisTitle}}

								<a class="btn btn-close btn-info btn-sm"
								   style="float:right;"
								   href="{{ $retRoute }}"
								   title="Вернуться к списку">
									<i class="fa fa-times" aria-hidden="true"></i>
								</a>
							</div>
							<div class="card-body">
								@if ($errors->any())
									<div class="alert alert-danger">
										<ul>
											@foreach ($errors->all() as $error)
												<li>{{ $error }}</li>
											@endforeach
										</ul>
									</div>
								@endif

								<div class="form-group">
									<label for="name">{{$sysobjlbl}}:</label>
									<b>{{$rec->contract->name}}</b>
								</div>

								@if($rec->sysobjid==482)
								<div class="row">
									<div class="form-group col-md-12">
										<label for="address">Техника:</label>
										{!! Form::select('objid', $rec->machines, $rec->objid,
										 [
										 'class' => 'form-control',
										 'placeholder' => '-выбор-',
										 ]) !!}
									</div>
								</div>
								@else
								<div class="form-group">
									<label for="name">Объект:</label>
									<input type="text" class="form-control" name="objid"
										   {{$inputReadOnly}}
										   value="{{$rec->objid}}"/>
								</div>
								@endif


								<div class="row">
								<div class="form-group offset-md-8 col-md-4">
									<label for="price" class="small">Цена, &#8381;/{{$rec->contract->price_unit}}:</label>
									<input type="number" class="form-control text-right"
										   name="price" min="0"
										   value="{{old('price',$rec->price)}}"
									/>
								</div>
								</div>

								<hr size="1">
								@if ($usrrights['save'])
									<button type="submit" class="btn btn-success"
											title="Сохранить изменения">
										<i class="fa fa-floppy-o" aria-hidden="true"></i>
										Сохранить
									</button>
								@endif

								<a class="btn btn-close btn-info" href="{{ $retRoute }}">
									<i class="fa fa-window-close-o" aria-hidden="true"></i>
									Закрыть
								</a>

								@if ($usrrights['delete'])
									<button type="submit"
											class="btn btn-danger btn-sm"
											style="margin-left:24px"
											formaction="{{ route($sysobjcode.'.delete', $rec->id)}}"
											formmethod="post"
											onclick="return confirm('Вы действительно хотите удалить запись?')"
											title="Удалить запись"
									>
										<i class="fa fa-trash-o" aria-hidden="true"></i>
									</button>
								@endif

								@if ($rec->id != -1)
									<div class="small" style="color: gray; margin:8px;">
										создана: {{$rec->created_at}} / {{$rec->whocrt->name}} &nbsp;
										изменена: {{$rec->updated_at}} / {{$rec->whoupd->name}} &nbsp;
										<a href="{{route('objevntlog',['sysobjid'=>$sysobjid, 'objid'=>$rec->id,'route'=>Route::current()->getName()])}}">журнал</a>
									</div>
								@endif
							</div>
						</form>
						&nbsp;
					</div>
				</div>

			</div>

		</div>
	@endif
@endsection
