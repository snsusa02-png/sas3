@extends('layouts.edit')

@section('content')

	@if (!isset( $rec ))
        <?php
        redirect()->route($sysobjcode . '.index');
        header("Location:" . route($sysobjcode . '.index'));
        die();
        ?>
	@else
        <?php
        $sysobjid = 31;
        $sysobjcode = 'extsystems';
        $thisTitle = "Внешняя система";


        //для блокировки текстовых полей пользователям, не имеющим право на редактирование
        $inputReadOnly = "readonly";
        if ($usrrights['save']) $inputReadOnly = "";

        ?>
		<style>
			label {
				color: gray;
				margin-bottom: 0px;
			}

			.org-aux {
				width: 100%
			}

			.btn {
				margin-bottom: 4px;
			}

		</style>
		<div class="container">

			<div class="row">
				<div class="col-md-6 col-sm-12">

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
							  action="{{ route($sysobjcode.'.update', $rec->id) }}">

							@method('PUT')
							@csrf
							<div class="card-header">
								{{$thisTitle}}

								<a class="btn btn-close btn-info btn-sm"
								   style="float:right;"
								   href="{{ route($sysobjcode.'.index') }}"
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

								<div class="row">
									<div class="form-group col-md-4">
										<label for="name">Код системы:</label>
										<input type="text" class="form-control text-center" name="code"
											   value="{{ old('code',$rec->code) }}"/>
									</div>
									<div class="form-group col-md-8">
										<label for="name">Название:</label>
										<input type="text" class="form-control" name="name"
											   {{$inputReadOnly}}
											   value="{{old('name',$rec->name)}}"/>
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

								<a class="btn btn-close btn-info" href="{{ route($sysobjcode.'.index') }}">
									<i class="fa fa-window-close-o" aria-hidden="true"></i>
									Закрыть
								</a>

								@if ($usrrights['delete'])
									<button type="submit"
											class="btn btn-danger"
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

				<div class="col-md-5 col-sm-12">

					@if($rec->id<>-1)
						<div class="card mt-3">
							<div class="card-header">
								Получатели данных
							</div>
							<table class="table-striped small p-2" style="width: 100%;" cellpadding="2">
								<thead>
								<tr>
									<td>#</td>
									<td>Справочник</td>
									<td style="text-align: right;">
										<a href="{{ route('extsys_sysobjs.create',['extsysid'=>$rec->id,])}}"
										   class="btn btn-warning btn-sm">
											<i class="fa fa-plus"></i>
										</a>
									<td>
								</tr>
								</thead>
								<tbody>
								@foreach($rec->sysobjs as $itm)
									<tr>
										<td style="text-align: right;"
											class="small">{{$loop->iteration}}</td>
										<td>{{$itm->sysobj->name}}</td>
										<td style="text-align: right;">
											<a href="{{ route('extsys_sysobjs.edit',$itm->id)}}"
											   class="btn btn-sm btn-primary">
												<i class="fa fa-pencil">
												</i>
											</a>
										<td>
									</tr>
								@endforeach
								</tbody>
							</table>
						</div>
					@endif

				</div>
			</div>

		</div>

@endsection
@endif
