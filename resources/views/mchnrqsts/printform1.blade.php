@extends('layouts.edit')

@if (!isset( $rec))
    <?php
    redirect()->route('machine_rqsts.index');
    header("Location:" . route('machine_rqsts.index'));
    die();
    ?>
@else
    <?php
    $sysobjid = 483;
    $objcode = 'mchnrqsts';
    $ThisTitle = "Заявка на спец-технику";

    $route_index = route($objcode . '.edit', $rec->id). '#' . 'printform1';
    ?>

		@section('content')

			<style>
				.sheet {
					font-family: serif;
					font-size: 21px;
					margin-top:20px;
					background-color: white;
					padding: 3em;
					width: 1000px;
					line-height: 2.3em;
				}

				h2 {margin: 0 auto 1em;
					text-align:center;
				}

				.grafa::before {
				     content: "\00A0";
				   }
					 .grafa::after {
	 				     content: "\00A0";
	 				   }
				.grafa{
					text-decoration: underline;
					font-weight: bold;
					padding: 0.5em;
					white-space: normal;
				}

				.this_rqst {
					background-color: #e1f0c6;
				}
			</style>

			<div class="container" onload="window.print();">

			<div class="sheet">


					<div class="float-right">Приложение к Договору {{$rec->contractinfo}}</div>
					<div class="">Исх. №_____ от "___" _____________ 20__г.</div>

					<div class="float-right0" style="margin-top: 4em; margin-bottom:2em; text-align:right;">Руководителю</div>

					<div style="margin-top: 2em;">
						<h2>Заявка</h2>
						<p>Прошу Вас на основании Договора {{$rec->contractinfo}} предоставить технику с экипажем на следующих условиях:
							<br>Место проведения работ: <span class="grafa">{{$rec->tgt_addr}}</span>
							<br>Предполагаемый объем работ: <span class="grafa">{{$rec->duration}}</span> час
							<br> Ф.И.О. ответственного лица Заказчика за использование техники на объекте и его контактный телефон
              <span class="grafa">{{$rec->respinfo}}</span>
							<br> Марка, тип, модель (характеристики) требуемой техники, ее количество:
							<br><span class="grafa">{{$rec->mcnhinfo}}</span>

							<br>Дата и время подачи техники: <span class="grafa">{{date_format(date_create($rec->plnbegdt),"d.m.Y H:i") }}</span>
							<br>Дополнительные сведения: <span class="grafa">{{$rec->descript}}</span>

							<br><br>Ф.И.О., должность лица ответственного за подачу заявки: <span class="grafa">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span> {{$rec->inituser->short_fio()}}
						</p>
					</div>

					<a class="btn btn-warning btn-sm print-window mt-2 d-print-none"
						onclick="window.print();"
						title="печать">
						<i class="fa fa-print" aria-hidden="true"></i>
					</a>

				</div>
			</div>
		@endsection
		<script type="text/javascript">
			//$(document).ready(function() { window.print(); });

			window.document.onload = window.print();
		</script>
@endif
