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
        $sysobjid = 822;
        $sysobjcode = 'groups';
        $thisTitle = "Группа";

        $retRoute = route('grptypes.edit', $rec->grptypeid);

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
							  action="{{ route($sysobjcode.'.update', [$rec->id,$rec->grptypeid]) }}">

							@method('PUT')
							@csrf
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
									<label for="name">Тип группы:</label>
									<b>{{$rec->grptype->name}}</b>
								</div>
								<div class="form-group">
									<label for="name">Название группы:</label>
									<input type="text" class="form-control" name="name"
										   {{$inputReadOnly}}
										   value="{{$rec->name}}"/>
								</div>
								<div class="form-group offset-md-7 col-md-5">
									<label for="name">Порядок вывода (1-255):</label>
									<input type="number" min=0 max=255 class="form-control text-right" name="ordr"
										   value="{{ $rec->ordr }}"/>
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

				<div class="col-md-6 col-sm-12">

					@if(1==1 and $rec->id<>-1)

                        <?php $LstTitle = "Идентификаторы группы во внешних системах";?>
						@include('objextids/lst_objextids')



						@if($rec->items?$rec->items->count():0>0)

							<div class="card mt-3">
								<div class="card-header">
									Состав группы
								</div>

								<table class="table-striped small p-2" style="width: 100%;" cellpadding="2">
									<thead>
									<tr>
										<td>#</td>
										<td>Объект</td>
									</tr>
									</thead>
									<tbody>
                                    <?php
                                    $curSysObjID = null;
                                    ?>
									@foreach($rec->items as $itm)
										@if($itm->sysobjid <> $curSysObjID)
											<tr>
												<td colspan="3" class="font-weight-bold">
													&nbsp;<i>{{$itm->sysobj->name}}</i>
												</td>
											</tr>

                                            <?php
                                            $curSysObjID = $itm->sysobjid;
                                            $lnkRoute = null;
                                            if ($curSysObjID == 111) {
                                                $lnkRoute = route('org_groups.edit', $itm->objid);
                                            }
                                            ?>
										@endif
										<tr>
											<td style="text-align: right;"
												class="small">{{$loop->iteration}}</td>
											<td>
												@if(isset($lnkRoute))
													<a href="{{$lnkRoute}}" target="_blank">{{$itm->objname??'...'}}</a>
												@else
													{{$itm->objname}}
												@endif
											</td>
										</tr>
									@endforeach
									</tbody>
								</table>
							</div>
						@endif
					@endif

				</div>
			</div>

		</div>
	@endif
@endsection
