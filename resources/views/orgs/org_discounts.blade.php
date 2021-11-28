@extends('layouts.edit')

@section('content')
	@guest
        <?php

        redirect()->route('login');
        //Почемуто не страбатывает в Firefox (иногда потому добавим переходов)
        header("Location:" . route('login'));
        die();


        ?>
	@else
		@if (!isset( $org))
            <?php
            redirect()->route('orgs.index');
            header("Location:" . route('orgs.index'));
            die();
            ?>
		@else
			{{--dd(get_defined_vars())--}}

			<style>
				.uper {
					margin-top: 36px;
				}

				label {
					color: gray;
					margin-bottom: 0px;
				}

				.org-aux {
					width: 100%
				}
			</style>
			<div class="container">
				<div class="row ">
					@if ($org->id != -1)
						<div class="col-md-12">
							<span class="helptags" data="org_discounts"/>

							<div class="card uper">

								@if(session()->get('success'))
									<div class="alert alert-success">
										{{ session()->get('success') }}
									</div><br/>
								@endif
								@if(session()->get('warning'))
									<div class="alert alert-warning">
										{{ session()->get('warning') }}
									</div><br/>
								@endif
								@if(session()->get('error'))
									<div class="alert alert-error">
										{{ session()->get('error') }}
									</div><br/>
								@endif

								<div class="card-header">
									Скидки для клиента "<b>{{$org->name}}</b>"

									<a class="btn btn-close btn-info btn-sm"
									   style="float:right"
									   href="{{ route('orgs.edit',$org->id) }}"
									   title="вернуться в карточку клиента"
									>
										<i class="fa fa-times" aria-hidden="true"></i>
									</a>
									@if(!\App\ri_org_price::isValidOrgPrices($org->id))
										<a href="{{ route('orgdiscount.recalc',$org->id)}}"
										   class="btn btn-warning btn-sm"
										   style="float:right; margin-right:6px;"
										   title="Рекомендуется перерасчитать скидки для клиента"
										>
											<i class="fa fa-refresh" aria-hidden="true"></i>
											Перерасчитать скидки
										</a>
									@endif
								</div>
								<div class="card-body">

									<table class="table">
										<thead>
										<tr>
											<td>Порядок применения</td>
											<td>
												Условия применения
											</td>
											<td>Скидка</td>
											<td>Статистика применения</td>
											<td style="text-align: center;">
												<a href="{{ route('orgdiscount.create',[$org->id,-1])}}"
												   class="btn btn-warning btn-sm"
												   title="Добавить скидку">
													<i class="fa fa-plus"></i>
												</a>
											</td>
										</tr>
										<!--
                                <tr style="text-align: center;">
                                    <td/>
                                    <td>
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="searchname"
                                                value="{{$searchname ?? ''}}" />
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group">
                                             <input type="text" class="form-control" name="searchdsct"
                                                value="{{$searchdsct ?? ''}}" />
                                         </div>
                                     </td>
                                     <td>
                                            <div class="input-group">
                                                 <input type="text" class="form-control" name="searchprice"
                                                    value="{{$searchprice ?? ''}}" />
                                             </div>
                                         </td>
                                    <td>
                                        <div class="input-group-btn">
                                            <button type="submit" class="btn btn-sm btn-info"
                                                    formaction="{{-- route('orgs.search') --}}"
                                                    formmethod="post"
                                                    title="Поиск">

                                                <i class="fa fa-search" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                -->
										</thead>
										<tbody>
                                        <?php
                                        $bgcols = array('#FeFeFe', '#EfEfEf', '#FFBFBF', '#FFcccc', '#F9F5BD', '#FCFADC', '#BFF9B9', '#ccffcc', '#79D3FF', '#CCECF9', '#CC9999', '#E2C7C7');
                                        $rec0 = 1;

                                        $curItmTypeID = -1;
                                        $curCondsQty = -1;
                                        ?>

										@foreach($discount as $itm)
                                            <?php
                                            $colshift = (1 - $itm->active) * 2;
                                            $tr_bg_col = $bgcols[$colshift + ($loop->index + $rec0) % 2];
                                            ?>

											@if ($itm->itmtypeid != $curItmTypeID)
												@if ($curItmTypeID != -1)
													<tr class="font-italic small" style="background-color: #ffffdd;">
														<td colspan="3" class="text-right">
															итого, применение:
														</td>
														<td class="text-center">
															{{$curCnt}}
															@if ($curItmtypeTotCnt<>0)
																из {{$curItmtypeTotCnt}}
																({{ round(100*$curCnt/$curItmtypeTotCnt,0) }}%)
															@endif
														</td>
														<td/>
													</tr>
												@endif
												<tr>
													<td colspan="4">
														<span class="font-italic font-weight-bold">
															{{$itm->itmtypename}}
															{{--															{{$itm->itm->name}}--}}
															<a href="#" name="{{$itm->itmtypeid}}"/>

														</span>
													</td>
													<td style="text-align: center;">
														<a href="{{ route('orgdiscount.create',[$org->id,$itm->itmtypeid])}}"
														   class="btn btn-outline-dark btn-sm"
														   title="Добавить скидку для текущей категории товаров">
															<i class="fa fa-plus"></i>
														</a>
													</td>
												</tr>
                                                <?php
                                                $curItmTypeID = $itm->itmtypeid;
                                                $curItmtypeTotCnt = $itm->itmtypetotcnt;
                                                //$curCondsQty = $itm->condsqty;
                                                $curCnt = 0;
                                                ?>
											@endif
                                            <?php
                                            if ($itm->condsqty <> $curCondsQty) {
                                                $topbrdstyle = "border-top:2px solid gray;";
                                                $curCondsQty = $itm->condsqty;
                                            } else    $topbrdstyle = "";
                                            ?>
											<tr style="{{$topbrdstyle}} background-color: {{$tr_bg_col}} ">
												<td class="small" style="text-align: right'">
													{{$itm->condsqty}}-{{$itm->ordr}}
													<a href="{{ route('orgdiscount.ordr_up',[$org->id,$itm->id])}}"
													   class="btn btn-sm"
													   style="color:#0077b5"
													   title="up">
														<i class="fa fa-arrow-up" aria-hidden="true"></i>
													</a>
													<a href="{{ route('orgdiscount.ordr_down',[$org->id,$itm->id])}}"
													   class="btn btn-sm"
													   style="color:#0077b5"
													   title="up">
														<i class="fa fa-arrow-down" aria-hidden="true"></i>
													</a>

												</td>
												<td>
													{{--													<label>категория:</label> "<b>{{$itm->catname}}</b>"--}}
													@isset ($itm->catsubname)
														<br/><label>подкатегория:</label> "<b>{{$itm->catsubname}}</b>"
													@endisset
													@isset ($itm->brand)
														<br/><label>бренд:</label> "<b>{{$itm->brand}}</b>"
													@endisset
													@isset ($itm->itmname)
														<br/><label>товар:</label> "<b>{{$itm->itmname}}</b>"
													@endisset
													@isset ($itm->specconds)
														<br/><label>спец-характеристики:</label> "
														<b>{{$itm->specconds}}</b>"
													@endisset

												</td>
												<td class="r">

													@isset ($itm->dscntpcnt)
														{{$itm->dscntpcnt}}%
													@endisset
												</td>
												<td class="text-center">
													{{$itm->pricecnt}}
													из {{$itm->itmtypetotcnt}}
												</td>
												<td style="text-align: center;">
													<a href="{{ route('orgdiscount.edit',$itm->id)}}"
													   class="btn btn-sm btn-primary"
													   title="Изменить запись">
														<i class="fa fa-pencil"></i>
													</a>
												</td>
											</tr>
											@php($curCnt=$curCnt+$itm->pricecnt)
										@endforeach
										@if ($curItmTypeID != -1)
											<tr class="font-italic small" style="background-color: #ffffdd;">
												<td colspan="3" class="text-right">
													итого, применение:
												</td>
												<td class="text-center">
													{{$curCnt}}
													@if ($curItmtypeTotCnt<>0)
														из {{$curItmtypeTotCnt}}
														({{ round(100*$curCnt/$curItmtypeTotCnt,0) }}%)
													@endif
												</td>
												<td/>
											</tr>
										@endif
										</tbody>
									</table>

								</div>
							</div>
						</div>
					@endif

				</div>
			</div>
		@endif
	@endguest
@endsection
