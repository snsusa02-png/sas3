@if($org->id != -1 and $usrrights['org_saldos.read']??false)
    <?php
    $TotPaySum = 0;
    ?>

	<div class="card mt-3">
		<div class="card-header" style="background-color: #dae3ff;">
			Сальдо
			@if (1==1 and $usrrights['org_saldos.create']??false)
				<a href="{{ route('org_saldos.create',['orgid'=>$org->id, 'ownorgid'=>1])}}"
				   class="btn btn-warning btn-sm ml-1" style="float: right;">
					<i class="fa fa-plus"></i>
				</a>
			@endif
			<button data-toggle="collapse" data-target="#org_saldos"
					class="btn btn-light btn-sm float-right"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>

		</div>
		@if (count($org->org_saldos)>0)
			<div class="card-body collapse" id="org_saldos">
				<table class="table-striped " style="width: 100%;">
					<thead>
					<tr class="text-center align-middle small">
						<td>#</td>
						<td class="text-center">Дата</td>
						<td class="text-right">Сальдо, руб</td>
						<td/>
					</tr>
					</thead>
					<tbody>
                    <?php
                    $lastSaldos = [];

                    $curOwnOrgId = -1;
                    $npp = 0;
                    ?>
					@foreach($org->org_saldos as $itm)
						@if($itm->ownorgid <> $curOwnOrgId)
							<tr>
								<td colspan="3" class="font-weight-bold font-italic">
									{{$itm->ownorgname}}
								</td>
								<td class="text-right">
									@if($usrrights['org_saldos.create'])
										<a href="{{ route('org_saldos.create',['ownorgid'=>$itm->ownorgid,'orgid'=>$org->id])}}?returl={{Request::url()}}"
										   class="btn btn-warning btn-sm"
										   title="Добавить запись">
											<i class="fa fa-plus"></i>
										</a>
									@endif

								</td>
							</tr>
                            <?php
                            $curOwnOrgId = $itm->ownorgid;
                            $npp = 0;
                            $lastFound = false;
                            ?>
						@endif
                        <?php
                        $npp++;

                        $lineclass = "";
                        if (!$lastFound and $itm->active == 1) {
                            $lineclass = "font-weight-bold bg-warning";
                            $lastSaldos[] = [
                                'ownorgname' => $itm->ownorgname,
                                'saldo' => $itm->saldo,
                                'ondate' => $itm->ondate,
                            ];
                            $lastFound = true;
                        }
                        $saldo_color = ($itm->active == 1) ? (($itm->saldo < 0) ? 'red' : 'green') : 'gray';
                        ?>
						<tr class="align-top ">
							<td class="small text-right">{{$loop->iteration}}</td>
							<td class="text-center small {{$lineclass}}">
								{{$itm->ondate}}
							</td>
							<td class="text-right {{$lineclass}}" nowrap style="color:{{$saldo_color}}">
								{{number_format($itm->saldo,2)}}
							</td>
							<td class="text-right">
								<a href="{{ route('org_saldos.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
								   class="btn btn-sm btn-primary"
								   title="Просмотреть/Изменить запись">
									<i class="fa fa-pencil"></i>
								</a>
							</td>
						</tr>
                        <?php
                        ?>
					@endforeach
					</tbody>
				</table>
			</div>
		@endif
		<div class="card-footer text-center">
			@if(isset($lastSaldos))
				<ul class="ml-3 p-1 px-2">
					@foreach($lastSaldos as $itm)
						<li>"{{$itm['ownorgname']}}": <b>{{number_format($itm['saldo'],2)}}</b> руб, на
							дату: {{$itm['ondate']}}</li>
					@endforeach
				</ul>
			@else
				<p class="text-center">Нет данных о сальдо</p>
			@endif
			<p class="text-center"><a href="{{ route('rep-orgs.rep002',[$org->id,1])}}" target="_blank">
					Операции
				</a></p>
		</div>
	</div>
@endif
