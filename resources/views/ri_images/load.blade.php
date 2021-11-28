@extends('layouts.edit')
@section('content')

	{{--<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.js"></script>--}}
	{{--<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">--}}
	{{--<link rel="stylesheet" href="/css/font-awesome-4.7.0/css/font-awesome.css">--}}


	<script>
        //$(document).ready(function () {
        var ready = function () {
            //alert(123);
            $(".btn-warning").click(function () {
                var html = $(".clone").html();
                $(".increment").after(html);
            });

            $("body").on("click", ".btn-danger", function () {
                $(this).parents(".control-group").remove();
            });
        };
        document.addEventListener("DOMContentLoaded", ready);

	</script>
	<style>
		body {
			background-color: snow;
		}

		.imggallery {
			display: grid;
			grid-gap: 12px;
			/*grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));*/
			grid-template-columns: repeat(auto-fit, minmax(120px, 160px));
			grid-template-rows: repeat(2, 220px);

			background-color: gray;
			padding: 1.5rem;
			min-height: 100vh;
		}

		.imggallery > div {
			overflow: hidden;
			text-overflow: ellipsis;
		}

		.imggallery0 > div > img {
			width: 100%;
			height: 90%;
			object-fit: contain;
		}

		.imggallery > div > figure {
			/*max-height: 220px;*/
			overflow: hidden;
			position: relative;
		}

		.imggallery > div > figure > a > img {
			width: 100%;
			border: 1px solid silver;
		}

		.imggallery > div > figure > figcaption {
			position: absolute;
			bottom: 0;
			background-color: rgba(0, 0, 0, .5);
			width: 100%;
		}

		.imggallery > div > figure > figcaption > h3 {
			color: white;
			padding: .75rem;
			font-size: 1.15rem;
		}

		a:hover {
			opacity: .6;
		}

		.imgcard {
			border-radius: .3rem;
			background-color: snow;
			padding: 0.8rem;
			position: relative;
		}

		.imgcard > header {
			margin-bottom: 0.5rem;
			text-align: right;
		}

		.imgcard > .imglbl {
			margin-top: 0.7rem;
			position: absolute;
			bottom: 8px;
			right: 10px;
		}

		/* footer */
		footer {
			background-color: #333;
			padding: .75rem;
			color: white;
			text-align: center;
			font-size: .75rem;
		}
	</style>

    <?php
    $refitmid = $refitem->id;
    ?>
	@if(session()->get('success'))
		<div class="row">
			<div class="col-md-12">
				<div class="alert alert-success">
					{{ session()->get('success') }}
				</div>
			</div>
		</div>
	@endif
	@if(session()->get('warning'))
		<div class="row">
			<div class="col-md-12">
				<div class="alert alert-warning">
					{{ session()->get('warning') }}
				</div>
			</div>
		</div>
	@endif
	@if(session()->get('error'))
		<div class="row">
			<div class="col-md-12">
				<div class="alert alert-danger">
					{{ session()->get('error') }}
				</div>
			</div>
		</div>
	@endif

	<div class="container">
		{{--	url('form')--}}
		<h3 class="jumbotron">Фото для продукта "{{$refitem->name}}" </h3>

		<div class="row">

			<div class="col-md-3"></div>

			<div class="col-md-6">

				<form method="post" action="{{route('ri_image.upload')}}" enctype="multipart/form-data">
					{{csrf_field()}}
					{{ Form::hidden('refitmid', $refitmid) }}

					<label for="Product Name">(можно выбрать сразу несколько фото):</label>

					<br/>

					<input type="file" class="form-control" name="photos[]" multiple/>


					{{--<input type="submit" class="btn btn-primary" value="Upload"/>--}}
					<div style="margin:10px 0 10px 0" class="row">
						<div class="md-col-4">
							<button type="submit" class="btn btn-success">
								<i class="fa fa-upload" aria-hidden="true"></i>
								Загрузить на сервер
							</button>
						</div>
						<div class="offset-sm-0 offset-md-4 md-col-4 ">
							<a class="btn btn-close btn-info" href="{{ route('refitems.edit',$refitmid) }}">
								<i class="fa fa-window-close-o" aria-hidden="true"></i>
								Закрыть
							</a>
						</div>
					</div>

				</form>

			</div>

		</div>

	</div>
	<div class="imggallery">
        <?php
        $imgs = App\ri_image::where('refitmid', $refitmid)
            ->select('id', 'filename', 'filesize')
            ->get();
        ?>
		@foreach ($imgs as $img)
			<div class="imgcard">
				<header>
					<a href="{{ route('ri_img.destroy',$img->id)}}"
					   class="btn btn-outline btn-sm" title="Удалить фото">
						<i class="fa fa-times" aria-hidden="true" style="font-size: 10px"></i>
					</a>
				</header>
				<figure>
					<a href="/{{$img->filename}}" target="_blank">
						<img src="{{Storage::disk('local')->url($img->filename)}}" alt="/{{$img->filename}}">
						{{--				<figcaption><h3>{{$img->filename}}</h3></figcaption>--}}
					</a>
				</figure>
				<div class="imglbl small text-center">
					размер: {{$img->filesize}} байт
				</div>
			</div>
		@endforeach
	</div>
	<span class="helptags" data="refitems.images"/>
@endsection
