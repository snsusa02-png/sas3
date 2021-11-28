{{--@if( 1==1 and isset($rec) and ($rec->id!=-1) and isset($rec->files))--}}
@if( 1==1 and isset($rec) and isset($rec->files))
    {{--	<div class="card mt-3 d-none d-sm-block">--}}
    <div class="card mt-3">
        <div class="card-header">
            <span data-toggle="collapse" data-target="#obj_files"><i class="fa fa-files-o" aria-hidden="true"></i> Файлы</span>

            <div class="float-right">
                @if (count($rec->files)>0)
                    <button data-toggle="collapse" data-target="#obj_files"
                            class="btn btn-light btn-sm "><i class="fa fa-eye-slash" aria-hidden="true"></i>
                        <span class="badge badge-info">{{count($rec->files)}}</span>
                    </button>
                @endif

                @if( $usrrights['save'] or $usrrights['files.create']??false)
                    @if(1==0)
                        <a href="{{ route('objfiles.load',['sysobjid'=>$sysobjid, 'objid'=>$rec->id])}}"
                           class="btn btn-warning btn-sm ">
                            <i class="fa fa-plus"></i>
                        </a>
                    @endif
                    @if(1==1)
                        <a href="{{ route('objfiles.create',['sysobjid'=>$sysobjid, 'objid'=>$rec->id]).'?returl='.Request::url()}}"
                           class="btn btn-success btn-sm ">
                            <i class="fa fa-plus"></i>
                        </a>
                    @endif
                @endif
            </div>
        </div>

        @if (count($rec->files)>0)

            <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js"></script>

            <link rel="stylesheet"
                  href="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.css"/>
            <script src="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.js"></script>

            <div class="card-body collapse" id="obj_files">
                <table class="table-condensed table-striped small" style="width: 100%;">
                    <thead>
                    <tr>
                        <td>#</td>
                        <td>имя файла, тип документа, описание</td>
                        <td/>
                    </tr>
                    </thead>
                    <tbody>
                    @php($totFileSize=0)
                    @foreach($rec->files as $itm)
                        <?php
                        //                    $url = Storage::disk('local')->url($itm->filename);
                        //$url = url($itm->filename);
                        //$url = Storage::disk('local')->url($itm->systemfilename);
                        $url = Storage::disk($itm->disk ?? 'local')->url($itm->systemfilename);
                        ?>
                        <tr class="align-top">
                            <td class="small">{{$loop->iteration}}</td>
                            <td>
                                {{--                                <a href="{{$url}}" target="_blank">--}}
                                <a href="{{route('objfiles.getbyid',$itm->id)}}" target="_blank">
                                    @if (substr($itm->mimetype->mimetype,0,6)=='image/')
                                        {{--									    //отображаемое напрямую--}}
                                        <a href="{{$url}}" target="_blank" data-fancybox="gallery"><img src="{{$url}}"
                                                                                                        style="max-height: 100px; max-width: 160px;"/></a>
                                    @else
                                        {{-- //отобразим иконкой типа файла--}}
                                        <?php
                                        $iconfile = $itm->mimetype->iconfile;
                                        ?>
                                        @if(isset($iconfile))
                                            <img src="{{$iconfile}}" style="max-height: 40px; max-width: 64px;"/>
                                        @endif
                                    @endif
                                    {{$itm->publicfilename}}
                                </a>
                                <span class="float-right">{{round($itm->filesize/1024,0)}}КБ</span>

                                <div>{{$itm->docsubtype_name??$itm->doctype_name}}</div>
                                <span class="font-weight-bold">{{$itm->notes}}</span>
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
                <div class="small text-right"> всего файлов: {{count($rec->files)}},
                    размер: {{number_format($totFileSize,0)}} Б
                </div>
            </div>
        @endif
    </div>
@endif
