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
        $sysobjid = 32;
        $sysobjcode = 'objextids';
        $thisTitle = "Идентификатор объекта во внешней системе";

        $retRoute = "/";
        $retURL = $rec->retRoute ?? Request::get('returl');

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
				<div class="col-md-5 col-sm-12">

					<div class="card mt-3">
						@if(session()->get('success'))
							<div class="alert alert-success">
								{{ session()->get('success') }}
							</div>
						@endif
						@if(session()->get('warning'))
							<div class="alert alert-warning">
								{{ session()->get('warning') }}
							</div>
						@endif
						@if(session()->get('error'))
							<div class="alert alert-danger">
								{!! str_replace(chr(10),'<br>', session()->get('error')) !!}
							</div>
						@endif

						<form name="forEdit" id="forEdit" method="post"
							  action="{{ route($sysobjcode.'.update', [$rec->id]) }}">

							@method('PUT')
							@csrf
							{!! Form::hidden('retURL', $retURL) !!}

							<div class="card-header">
								{{$thisTitle}}

								<a class="btn btn-close btn-info btn-sm"
								   style="float:right;"
								   href="{{ $retURL }}"
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
									<label for="sysobjid">Для объекта:</label>
									{!! Form::hidden('sysobjid', $rec->sysobjid) !!}
									{!! Form::hidden('objid', $rec->objid) !!}
									"<b>{{$rec->objname}}"</b>,<br> тип: "{{$rec->sysobj->name}}"
								</div>


								<div class="form-group">
									<label for="extsysid">Внешняя система:</label>
									{!! Form::select('extsysid', $rec->extsystems, old('extsysid',$rec->extsysid),
									['class' => 'form-control',
									'placeholder'=>'-укажите систему-']) !!}

								</div>
								<div class="form-group">
									<label for="extid">Идентификатор:</label>
									<input type="text" class="form-control" name="extid"
										   {{$inputReadOnly}}
										   value="{{old('extid',$rec->extid)}}"/>
								</div>

								<hr size="1">
								@if ($usrrights['save'])
									<button type="submit" class="btn btn-success"
											title="Сохранить изменения">
										<i class="fa fa-floppy-o" aria-hidden="true"></i>
										Сохранить
									</button>
								@endif

								<a class="btn btn-close btn-info" href="{{ $retURL }}">
									<i class="fa fa-window-close-o" aria-hidden="true"></i>
									Закрыть
								</a>

								@if ($usrrights['delete'])
									<button type="submit"
											class="btn btn-danger btn-sm ml-3"
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

				<div class="col-md-6 col-sm-12">

				</div>
			</div>

		</div>
	@endif
@endsection
