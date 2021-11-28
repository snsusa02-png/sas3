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
	@if (!isset( $rec))
        <?php
        redirect()->route('orgs.index');
        header("Location:" . route('orgs.index'));
        die();
        ?>
	@else
        <?php
        $sysobjid = 208;
        $ThisTitle = "Склад для клиента ";

        //20190405 SNS. Если в $rec->orgid нет значения, то возможно есть значение $recid
        //nvl($rec->orgid,$recid);
        $recid = isset($rec->orgid) ? $rec->orgid : $rec->id;
        //echo $recid;
        //dd($curators);
        ?>
		<div class="container">
			<div class="row ">
				<div class="col-md-6">
					<div class="card uper">
						<div class="card-header">
							{{$ThisTitle}} "<b>{{$rec->org->name}}</b>"

							<a class="btn btn-close btn-info btn-sm"
							   style="float:right"
							   href="{{ route('org_wrhs.index', $recid) }}"
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

							<form name="forEdit" id="forEdit" method="post"
								  action="{{ route('org_wrhs.update', $rec->id) }}">

								@method('PUT')
								@csrf
								<input type="hidden" name="orgid" value="{{$recid}}">

								<div class="form-group">
									<label for="orgid">Склад:</label>
									{!! Form::select('wrhid', $rec->wrhs,
									 $rec->wrhid,
									 ['class' => 'form-control']
									 ) !!}

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
								<a class="btn btn-close btn-info" href="{{ route('org_wrhs.index', $recid) }}">
									<i class="fa fa-window-close-o" aria-hidden="true"></i>
									Закрыть
								</a>
								&nbsp;
								@if ($rec->id != -1)
									<button type="submit"
											class="btn btn-danger"
											style="margin-left:24px"
											formaction="{{ route('org_wrhs.delete', $rec->id)}}"
											formmethod="post"
											onclick="return confirm('Вы действительно хотите удалить запись?')"
											title="Удалить"
									>
										<i class="fa fa-trash-o" aria-hidden="true"></i>
									</button>
								@endif

								@if ($rec->id != -1)
									<div class="small" style="margin-top: 8px; color:gray;">
										создана: {{$rec->created_at}} / {{$rec->whocrt->name}}
										<br>
										изменена: {{$rec->updated_at}} / {{$rec->whoupd->name}}
										<br><a href="{{route('objevntlog',['sysobjid'=>$sysobjid, 'objid'=>$rec->id,'route'=>Route::current()->getName()])}}">журнал</a>
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
