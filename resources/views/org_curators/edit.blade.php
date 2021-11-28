@extends('layouts.edit')

@section('content')

	<style>
		.uper {
			margin-top: 36px;
		}

		label {
			color: gray;
			margin-bottom: 0px;
		}
	</style>
	@guest
        <?php
        redirect()->route('login');
        //Почемуто не страбатывает в Firefox (иногда потому добавим переходов)
        header("Location:" . route('login'));
        die();
        ?>
	@else

        <?php
        $sysobjid = 122;
        $ThisTitle = "Куратор клиента";

        //для блокировки текстовых полей пользователям, не имеющим право на редактирование
        //        $inputReadOnly = "readonly";
        //        if ($usrrights['save']) $inputReadOnly = "";
        $inputReadOnly = "";
        ?>
		<div class="container">
			<div class="row ">
				<div class="col-md-6">
					<div class="card uper">
						<div class="card-header">
							Куратор для клиента "<b>{{$rec->org->name}}</b>"

							<a class="btn btn-close btn-info btn-sm"
							   style="float:right"
							   href="{{ route('org_curators.index', $rec->orgid) }}"
							   title="вернуться в карточку клиента"
							>
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
								</div><br/>
							@endif
                            <?php
                            ?>
							<form name="forEdit" id="forEdit" method="post"
								  action="{{ route('org_curator.update', $rec->id) }}">

								@method('PUT')
								@csrf
								<input type="hidden" name="orgid" value="{{$rec->orgid}}">

								<div class="form-group">
									<label for="orgid">Куратор:</label>
									{!! Form::select('userid', $rec->curators,
									 $rec->userid,
									 ['class' => 'form-control']
									 ) !!}

								</div>
								<div class="row">
									<div class="form-group col-md-6">
										<label for="begdt">Начало:</label>
										<input type="date" class="form-control "
											   name="begdt"
											   {{$inputReadOnly}}
											   value="{{ old('begdt', date('Y-m-d',strtotime($rec->begdt))) }}"/>
									</div>
									@php($enddate = isset($rec->enddt)?date('Y-m-d',strtotime($rec->enddt)):null)
									<div class="form-group col-md-6">
										<label for="enddt">Окончание:</label>
										<input type="date" class="form-control "
											   name="enddt"
											   {{$inputReadOnly}}
											   value="{{ old('enddt', $enddate) }}"/>
									</div>
								</div>

								<div class="form-group">
									<label for="active" style="color: rgb(73, 80, 87);">Действует:</label>
									{!! Form::checkbox('active', 1, $rec->active==1) !!}
								</div>

								<hr size="1">
								<button type="submit" class="btn btn-success">
									<i class="fa fa-floppy-o" aria-hidden="true"></i>
									Сохранить
								</button>
								&nbsp;
								<a class="btn btn-close btn-info" href="{{ route('org_curators.index', $rec->orgid) }}">
									<i class="fa fa-window-close-o" aria-hidden="true"></i>
									Закрыть
								</a>
								&nbsp;
								@if ($rec->id != -1)
									<button type="submit"
											class="btn btn-danger"
											style="margin-left:24px"
											formaction="{{ route('org_curator.delete', $rec->id)}}"
											formmethod="post"
											onclick="return confirm('Вы действительно хотите удалить запись?')"
											title="Удалить"
									>
										<i class="fa fa-trash-o" aria-hidden="true"></i>
									</button>

									<div class="small" style="margin-top: 8px;">
{{--										создана: {{$rec->created_at}} / {{$rec->whocrt}}--}}
{{--										<br>&nbsp;&nbsp;--}}
{{--										изменена: {{$rec->updated_at}} / {{$rec->whoupd}}--}}
										<a href="{{route('objevntlog',['sysobjid'=>$sysobjid, 'objid'=>$rec->id,'route'=>Route::current()->getName()])}}">журнал</a>
									</div>
								@endif
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	@endif
@endsection
