@if( 1==1 and isset($rec) and ($rec->id!=-1) and isset($rec->files))
	<div class="card mt-3 d-none d-sm-block">
		<div class="card-header">
			<span data-toggle="collapse" data-target="#obj_files"><i class="fa fa-file-pdf-o text-danger" aria-hidden="true"></i> Файлы</span>

			<div class="float-right">
				@if (count($rec->files)>0)
					<button data-toggle="collapse" data-target="#obj_files"
							class="btn btn-light btn-sm "><i class="fa fa-eye-slash" aria-hidden="true"></i></button>
				@endif

				@if( $usrrights['save'])
					<a href="{{ route('objfiles.load',['sysobjid'=>$sysobjid, 'objid'=>$rec->id])}}"
					   class="btn btn-warning btn-sm ">
						<i class="fa fa-plus"></i>
					</a>
				@endif
			</div>
		</div>

		@if (count($rec->files)>0)
			<div class="card-body collapse" id="obj_files">
				<table class="table-condensed table-striped small" style="width: 100%;">
					<thead>
					<tr>
						<td>#</td>
						<td>тип</td>
						<td>описание, имя файла</td>
						<td/>
					</tr>
					</thead>
					<tbody>
					@php($totFileSize=0)
					@foreach($rec->files as $itm)
                        <?php
                        //                    $url = Storage::disk('local')->url($itm->filename);
                        //$url = url($itm->filename);
                        $url = Storage::disk('local')->url($itm->systemfilename);
                        ?>
						<tr class="align-top">
							<td class="small">{{$loop->iteration}}</td>
							<td>
								@if (substr($itm->mimetype->mimetype,0,6)=='image/')
									{{--									    //отображаемое напрямую--}}
									<a href="{{$url}}" target="_blank"><img src="{{$url}}"
																			style="max-height: 50px; max-width: 80px;"/></a>
								@else
									{{--									    //отобразим иконкой типа файла--}}
                                    <?php
                                    $iconfile = $itm->mimetype->iconfile;
                                    ?>
									@if(isset($iconfile))
										<a href="{{$url}}" target="_blank"><img src="{{$iconfile}}"
																				style="max-height: 40px; max-width: 64px;"/></a>
									@endif
								@endif
							</td>
							<td style="max-width:200px;">
								<div class="font-weight-bold">{{$itm->notes}}</div>
								<a href="{{$url}}" class="ml-3 mt-1" target="_blank">{{$itm->publicfilename}}</a>
							</td>
							<td class="text-right">
								<a href="{{ route('objfiles.edit',$itm->id)}}?returl={{Request::url()}}"
								   class="btn btn-sm btn-primary"
								   title="Просмотреть/Изменить запись">
									<i class="fa fa-pencil"></i>
								</a>
							</td>
						</tr>
						@php($totFileSize+=$itm->filesize)
					@endforeach
					</tbody>
				</table>
			</div>
			<div class="card-footer">
				<div class="small text-right"> всего файлов: {{count($rec->files)}},  размер: {{number_format($totFileSize,0)}} Б</div>
			</div>
		@endif
	</div>
@endif
