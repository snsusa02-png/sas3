<?php
//extends('itmtypes_layout')
?>
@extends('layouts.app')
@section('content')

    <?php
    $sysobjid = 1221;
    $sysobjcode = 'payrolltypes';

    $thisTitle = "Схемы расчета заработной платы сотрудников";
    $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;
    ?>

    <link rel="stylesheet" href="/css/subnav.css">

    <div class="container">

        <?php
        $breadcrumbs = [
            'Сервис' => "/admin",
            'ЗП' => "/admin?tab=nsi-salary",
            $thisTitle => null,
        ];
        ?>
        @includeIf('layouts.breadcrumbs')


		<div class="row justify-content-center">
			<div class="col-md-8">
				<h3>{{$thisTitle}}</h3>

				@include('layouts.edit_msgs')

                <div class="row mb-2">
                    <div class="col-md-8 ">
                        <div class="subnav shift">
                            <ul>
                                <li><a href="{{route('org_charges.index')}}" title="Начисления/Удержания применяемые в организациях">Для организаций</a></li>
{{--                                <li><a href="{{route('equiprqsts.index')}}" title="Заявки на материалы">Заявки</a></li>--}}
                            </ul>
                        </div>
                    </div>
                    <div class="input-group col-md-4">
                    </div>
                </div>

				<form name="forIndex" id="forIndex" method="post" action="{{ route($sysobjcode.'.index') }}">
					@csrf

					<div class="mt-3">


						<table class="table table-striped">
							<thead>
							<tr>
								<td>#</td>
								<td>
									Наименование<br>
								</td>

{{--								<td class="text-center">Доступность</td>--}}
								<td class="text-center">Порядок вывода</td>
								<td style="text-align: center;">
									@if ($usrrights['create'])
										<a href="{{ route($sysobjcode.'.create',0)}}" class="btn btn-warning btn-sm">
											<i class="fa fa-plus"></i>
										</a>
									@endif
								</td>
							</tr>
							<tr style="text-align: center;">
								<td/>
								<td>
									<div class="input-group">
										<input type="text" class="form-control" name="s_name"
											   value="{{$search_params['s_name'] ?? ''}}"/>
									</div>
								</td>
{{--								<td>--}}
{{--									{!! Form::select('s_active'--}}
{{--									, $s_actives--}}
{{--									, $search_params['s_active']--}}
{{--									, [--}}
{{--									'class' => 'form-control small',--}}
{{--									'placeholder' => '',--}}
{{--									]) !!}--}}

{{--								</td>--}}
								<td/>
								<td>
									<div class="input-group-btn">
										<button type="submit" class="btn btn-sm btn-light"
												formaction="{{ route($sysobjcode.'.index') }}"
												formmethod="post">
											<i class="fa fa-search" aria-hidden="true"></i>
										</button>
									</div>
								</td>
							</tr>
							</thead>
							<tbody>
                            <?php
                                $cur_dir = 0;
                            ?>
							@foreach($recs as $itm)
                                @if($itm->dir <> $cur_dir)
                                    <tr>
                                        <td colspan="4" class="font-weight-bold font-italic">{{$data->dirs[$itm->dir]??'???'}}</td>
                                    </tr>
                                    <?php
                                    $cur_dir = $itm->dir;
                                    ?>
                                @endif
                                <?php
                                $linestyle = ($itm->active == 0) ? "background-color:#ffebeb;" : '';
                                ?>
                                <tr style="{{$linestyle}}">
									<td class="small text-right">
										{{$rec0++}} <a name="{{$itm->id}}"/>
									</td>
									<td>
                                        <a href="{{ route($sysobjcode.'.edit',$itm->id)}}">{{$itm->name}}</a>
										<div class="small" style="margin-left:16px; color:gray;">
                                            <?php echo str_replace(chr(13) . chr(10), '<br>', $itm->descript) ?>
										</div>
									</td>
{{--									<td class="text-center">--}}
{{--										{{($itm->active==1)?'да':'нет'}}--}}
{{--									</td>--}}
									<td class="text-center">
										{{$itm->ordr}}
									</td>
									<td style="text-align: center;">
										<a href="{{ route($sysobjcode.'.edit',$itm->id)}}"
										   class="btn btn-sm btn-primary">
											<i class="fa fa-pencil"></i>
										</a>
									</td>
								</tr>
							@endforeach
							</tbody>
						</table>

						<div>
							{{$recs->links()}}
						</div>
				</form>

			</div>
		</div>
@endsection

