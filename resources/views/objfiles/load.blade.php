@extends('layouts.edit')
@section('content')

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
            grid-template-rows: repeat(2, 260px);

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
    $refitmid = $objid;
    $accept = ($obj->accept) ? 'accept="' . $obj->accept . '"' : '';
    ?>

    @include('layouts.edit_msgs')

    <div class="container">
        {{--	url('form')--}}
        <h4 class="jumbotron mt-2">Файлы для записи "{{$obj->name}}" </h4>

        <div class="row">

            <div class="offset-md-3 col-md-6">

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div><br/>
                @endif


                <form method="post" action="{{route('objfiles.upload')}}" enctype="multipart/form-data">
                    {{csrf_field()}}
                    {{ Form::hidden('sysobjid', $sysobjid) }}
                    {{ Form::hidden('objid', $objid) }}

                    <label for="Product Name">(можно выбрать сразу несколько файлов):</label>

                    <br/>

                    <input type="file" class="form-control" name="photos[]" multiple
                           style="padding: 3px;" {!!$accept!!}/>


                    <div style="margin:10px 0 10px 0" class="row">
                        <div class="md-col-4">
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-upload" aria-hidden="true"></i>
                                Загрузить на сервер
                            </button>
                        </div>
                        <div class="offset-sm-0 offset-md-4 md-col-4 ">
                            <a class="btn btn-close btn-info" href="{{ $returl }}">
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
        $imgs = App\objfile::from('objfiles as f')
            ->join('mimetypes as mt', 'mt.id', 'f.mimetypeid')
            ->where('sysobjid', $sysobjid)
            ->where('objid', $objid)
            ->select('f.id', 'f.publicfilename', 'f.systemfilename', 'f.filesize', 'mt.mimetype', 'mt.iconfile')
            ->get();
        ?>
        @foreach ($imgs as $itm)
            <?php
            $url = Storage::disk('local')->url($itm->systemfilename);
            ?>
            <div class="imgcard">
                <header>
                    <a href="{{ route('objfiles.destroy',$itm->id)}}"
                       class="btn btn-outline btn-sm" title="Удалить файл">
                        <i class="fa fa-times" aria-hidden="true" style="font-size: 10px"></i>
                    </a>
                </header>
                <figure>
                    @if (substr($itm->mimetype,0,6)=='image/')
                        {{--//отображаемое напрямую--}}
                        <a href="{{$url}}" target="_blank"><img src="{{$url}}"/></a>
                    @else
                        {{--//отобразим иконкой типа файла--}}
                        <?php
                        $iconfile = $itm->iconfile;
                        ?>
                        @if(isset($iconfile))
                            <a href="{{$url}}" target="_blank"><img src="{{$iconfile}}"/></a>
                        @endif
                    @endif

                    <a href="{{$url}}" target="_blank">
                        {{--						<img src="{{$url}}" alt="/{{$itm->publicfilename}}">--}}
                        <figcaption>{{$itm->publicfilename}}</figcaption>
                    </a>
                </figure>
                <div class="imglbl small text-center">
                    размер: {{$itm->filesize}} байт
                </div>
            </div>
        @endforeach
    </div>
    <span class="helptags" data="objfiles.list"/>
@endsection
