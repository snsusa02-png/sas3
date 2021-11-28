@if( 1==1 and isset($refitem->images))

	<div class="card uper d-none d-sm-block"
		 style="min-width:400px !important; padding:6px; margin-bottom:16px;">
		<table class="table-condensed small" style="width: 100%;">
			<thead>
			<tr>
				<td colspan="3">
					<h6>Фото для продукта</h6>
				</td>
				<td class="text-right">
					@if( $usrrights['save'])
						<a href="{{ route('ri_image.load',$refitem->id)}}"
						   class="btn btn-warning btn-sm">
							<i class="fa fa-plus"></i>
						</a>
					@endif
				</td>
			</tr>
			@if (count($refitem->images)>0)
				<tr>
					<td>#</td>
					<td>файл</td>
					<td>Инфо</td>
					<td/>
				</tr>
			</thead>
			<tbody>
			@foreach($refitem->images as $itm)
                <?php
                $filename = $itm->catalog
                    . "/"
                    . $itm->systemfilename;
                //$mime = $rec->mimetype;
                $fileuri = Storage::disk($itm->storage)->getAdapter()->applyPathPrefix($filename);
                $url = Storage::url('6.jpg');
                ?>
				<tr class="align-top">
					<td class="small">{{$loop->iteration}}</td>
					<td>
						<a href="{{route('orders.report.download',$itm->id)}}">
							{{$itm->catalog.'/'.$itm->publicfilename}}
						</a>
					</td>
					<td>
						{{$fileuri}}
						<br>
						{{$url}}
					</td>
					<td class="text-right">
						<a href="{{route('objfiles.edit',
					['id'=>$itm->id,'retroute'=>Route::currentRouteName()])}}"
						   class="btn btn-sm btn-outline-default"
						>
							<i class="fa fa-pencil"></i>
						</a>
					</td>
					<td/>
				</tr>
			@endforeach
			@endif
			</tbody>
		</table>
	</div>
@endif
