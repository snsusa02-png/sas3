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
        $sysobjid = 810;
        $sysobjcode = 'objmsgs';
        $thisTitle = "Сообщения";
        $retRoute = ($rec->retURL)
            ? route($rec->retURL, $rec->objid)
            : route('orders.edit', $rec->objid);
        $sysobjlbl = 'заказ'; //todo: определить в контролере

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
							{{ Form::hidden('sysobjid', $rec->sysobjid) }}
							{{ Form::hidden('objid', $rec->objid) }}
							{{ Form::hidden('retURL', $rec->retURL) }}

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
									<b>{{$rec->sysobj->name}}</b>
								</div>
								<div class="form-group">
									<label for="name">Тема/Заголовок:</label>
									<input type="text" class="form-control" name="subj"
										   {{$inputReadOnly}}
										   value="{{$rec->subj}}"/>
								</div>
								<div class="form-group">
									<label for="name">Сообщение:</label>
									<textarea type="text" class="form-control" name="text" rows="5"
											   {{$inputReadOnly}}>{{$rec->text}}</textarea>
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
