{{--@extends('itmtypes_layout')--}}
@extends('layouts.edit')

@section('content')
	@if (!isset( $spec))
        <?php
        redirect()->route('itmtypes.index');
        header("Location:" . route('itmtypes.index'));
        die();
        ?>
	@else
        <?php
        $sysobjid = 142;
        $parsysobjid = 105;

        $ThisTitle = "Специфическая характеристика";
        ?>
		{{--dd(get_defined_vars())--}}
		<style>
			.uper {
				margin-top: 36px;
			}

			label {
				color: gray;
				margin-bottom: 0px;
			}
		</style>
		<!--<div class="container">
			<div class="row ">
			-->

		<div class="col-md-6">
			<div class="card mt-3">
				<div class="card-header">
					Специальное свойство для:<br><b>{{$itmname}}</b>
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

					<form name="forEdit" id="forEdit" method="post"
						  action="{{ route('ri_specinfo.update', $spec->id) }}">
						@method('PUT')
						@csrf
						<input type="hidden" name="refitmid" id="refitmid" value="{{$spec->refitmid}}"/>

						<div class="row">
							<div class="form-group col-md-12">
								<label for="name">Свойство:</label>
								@if ($spec->id == -1)
									{!! Form::select('spinf', $specinftype) !!}
								@else
									<strong>{{$specinftype}}</strong>
									<input type="hidden" name="spinf" id="spinf" value="{{$spec->specinfotypeid}}"/>
								@endif
							</div>
						</div>

						<div class="row">
							<div class="col-md-6">
								<div class="form-group">
									<label for="specinfovalue">Значение:</label>
									{!! Form::text('specinfovalue', $spec->specinfovalue,['class="text-center"']) !!}
								</div>
							</div>

							<div class="col-md-6">
								<div class="form-group">
									<label for="numvalue">Числовой эквивалент:</label>
									{!! Form::text('numvalue', $spec->numvalue,['class="text-center"']) !!}
								</div>
							</div>
						</div>


						<hr>
						<button type="submit" class="btn btn-success">
							<i class="fa fa-floppy-o" aria-hidden="true"></i>
							Сохранить
						</button>
						&nbsp;
						<a class="btn btn-close btn-info" href="{{ route('refitems.edit',$spec->refitmid) }}">
							<i class="fa fa-window-close-o" aria-hidden="true"></i>
							Закрыть
						</a>
						&nbsp;
						@if  ($spec->id != -1)
							<button type="submit"
									class="btn btn-danger btn-sm"
									style="margin-left:24px"
									formaction="{{ route('ri_specinfo.delete', $spec->id)}}"
									formmethod="post"
									onclick="return confirm('Вы действительно хотите удалить запись?')"
									title="Удалить запись"
							>
								<i class="fa fa-trash-o" aria-hidden="true"></i>
							</button>
						@endif
					</form>
				</div>
				@if ($spec->id != -1)
					<div class="card-footer small" style="color: gray; margin:8px;">
						создана: {{$spec->created_at}} / {{$spec->whocrt->name}} &nbsp;
						изменена: {{$spec->updated_at}} / {{$spec->whoupd->name}} &nbsp;
						<a href="{{route('objevntlog',['sysobjid'=>$sysobjid, 'objid'=>$spec->id,'route'=>Route::current()->getName()])}}">журнал</a>
					</div>
				@endif

			</div>
		</div>
		@if(isset($spec->helptags))
			<span class="helptags" data="{{$spec->helptags}}"/>
		@endif
@endsection
@endif
