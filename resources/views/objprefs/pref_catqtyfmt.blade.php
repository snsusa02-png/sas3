@extends('layouts.edit')

@section('content')

    <?php
    $sysobjid = 3;
    $ThisTitle = "Установка формата отображения остатка товара в каталоге для клиентов";

    //для блокировки текстовых полей пользователям, не имеющим право на редактирование
    $inputReadOnly = "readonly";
    $usrrights = array();

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
			<div class="col-md-7 col-sm-12">
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
						  action="{{ route('pref_catqtyfmt.set') }}">

						@method('PUT')
						@csrf
						<div class="card-header">
							{{$ThisTitle}}

							<a class="btn btn-close btn-info btn-sm"
							   style="float:right;"
							   href="/nsi?tab=nsi-aux"
							   title="Вернуться ">
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

							@if(1==1)
								{!! Form::select('prefvalue', $rec->prefvals, $rec->prefvalue,
												[
												'class000' => 'form-control',
												'required' => 'required',
												])
												!!}
							@endif

							<hr size="1">

							@if (1==1)
								<button type="submit" class="btn btn-success">
									<i class="fa fa-floppy-o" aria-hidden="true"></i>
									Сохранить
								</button>
							@endif
							<a class="btn btn-close btn-info" href="/nsi?tab=nsi-stock">
								<i class="fa fa-window-close-o" aria-hidden="true"></i>
								Закрыть
							</a>


							<div class="small" style="color: gray; margin:8px;">
								<a href="{{route('objevntlog',['sysobjid'=>14, 'objid'=>$rec->id,'route'=>Route::current()->getName()])}}">журнал</a>
							</div>
						</div>
					</form>
					&nbsp;
				</div>

			</div>

		</div>

	</div>

@endsection
