@extends('layouts.app')

@section('content')

    <?php
    $thisTitle = "Номенклатуры дел";
    $thisSysObjCode = 'org_caselists';
    ?>

    <style>
        th {
            vertical-align: top !important;
        }
    </style>
    <form name="forIndex" id="forIndex" method="post"
          action="{{ route($thisSysObjCode.'.index') }}">
        @csrf

        <link rel="stylesheet" href="/css/subnav.css">
        <link rel="stylesheet" href="/css/tags.css">

        <div class="container">

            <?php
            $breadcrumbs = [
                'Офис' => "/office?tab=nsi-info",
                $thisTitle => null,
            ];
            ?>
            @includeIf('layouts.breadcrumbs')

            @include('layouts.edit_msgs')

            <div class="row justify-content-center">
                <div class="col-md-12 col-sm-12 ">
                    <h3>{{$thisTitle}}</h3>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="subnav shift">
                                <ul>
                                    <li><a href="{{route('documents.index')}}" target="">Архив документов</a></li>
                                    <li><a href="{{route('doctypes.index')}}" target="">Типы документов</a></li>
                                    <li><a href="{{route('collectors.index')}}" target="">Коллекции</a></li>
                                </ul>
                            </div>
                        </div>

                        <div class=" col-md-6">
                        </div>
                    </div>


                <?php
                    //var_dump($sort_params);
                    ?>

                    <table class="table table-striped table-condensed">
                        <thead>
                        @php
                            $sort_params = session('sort_params');
                            if (isset($sort_params)) {
                                $sort_by = $sort_params['field'];
                                $sort_dir = $sort_params['dir'];
                            }
                        @endphp
                        <tr>
                            <td>
                                Организация
                            </td>
                            <th scope="col" class="small">
                                <a href="{{ route('set_sort',['field' => 'd.docnum','retroute'=>$thisSysObjCode.'.index']) }}"
                                   class="btn btn-sm"
                                   title="Сортировать">№{!! sort_mark('d.docnum',$sort_params) !!}</a>,
                                <a href="{{ route('set_sort',['field' => 'd.docdate','retroute'=>$thisSysObjCode.'.index']) }}"
                                   class="btn btn-sm"
                                   title="Сортировать">дата</a>{!! sort_mark('d.docdate',$sort_params) !!}
                            </th>
                            <td scope="col" class="">Период</td>
                            <td>Описание</td>

                            <td style="text-align: center;">
                                @if ($usrrights['create'])

                                    @if (isset($data->template_id))
                                        <a href="{{ route($thisSysObjCode.'.create',0)}}"
                                           class="btn btn-warning btn-sm d-print-none"
                                           title="Добавить запись с данными из шаблона"
                                           onclick="return confirm('Добавить новую запись с данными из шаблона?')">
                                            <i class="fa fa-plus"></i>
                                        </a>
                                        <a href="{{ route('user_templates.delete', $data->template_id)}}?returl={{Request::url()}}"
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Удалить текущий шаблон?')"
                                           title="Удалить текущий шаблон"
                                        >
                                            <i class="fa fa-minus-circle" aria-hidden="true"></i>
                                        </a>
                                    @else
                                        <a href="{{ route($thisSysObjCode.'.create',0)}}"
                                           class="btn btn-warning btn-sm d-print-none"
                                           title="Добавить запись">
                                            <i class="fa fa-plus"></i>
                                        </a>
                                    @endif

                                @endif
                            </td>
                        </tr>

                        <tr style="text-align: center;">
                            <td>
                                @if (isset($data->usedorgs))
                                    <div class="input-group">
                                        {!! Form::select('s_orgid',
                                         $data->usedorgs,
                                         $search_params['s_orgid']??'',
                                        ['class' => 'form-control',
                                        'style'=>'max-width:180px',
                                        'placeholder'=>'-все-']) !!}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="input-group">
                                    <input type="text" class="form-control text-center"
                                           name="s_docnum"
                                           value="{{$search_params['s_docnum'] ?? ''}}"
                                           placeholder="№ док-та"
                                    />
                                </div>
                                @if(1==0)
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1">с</span>
                                        </div>
                                        <input type="date" class="form-control" name="s_begdocdate"
                                               value="{{$search_params['s_begdocdate'] ?? ''}}"
                                               title="Дата выпуска доверенности не менее чем ..."
                                               data-toggle="tooltip"/>

                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1">по</span>
                                        </div>
                                        <input type="date" class="form-control" name="s_enddocdate"
                                               value="{{$search_params['s_enddocdate'] ?? ''}}"
                                               title="Дата выпуска доверенности не превышает ..."
                                               data-toggle="tooltip"/>

                                    </div>
                                @endif
                            </td>
                            <td>
                                @if (isset($data->usedstaff))
                                    <div class="input-group">
                                        {!! Form::select('s_grantstaffid',
                                         $data->usedstaff,
                                         $search_params['s_grantstaffid']??'',
                                        ['class' => 'form-control',
                                        'placeholder'=>'-все-']) !!}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <input type="text" class="form-control text-center"
                                       name="s_name"
                                       value="{{$search_params['s_name'] ?? ''}}"
                                       placeholder=""
                                />
                            </td>
                            <td>
                                <div class="input-group-btn">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary"
                                            formaction="{{ route($thisSysObjCode.'.index') }}"
                                            formmethod="post" title="Поиск">
                                        <i class="fa fa-search" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $curOrgID = -1;
                        ?>
                        @if (count($recs)>0)
                            @foreach($recs as $rec)
                                <?php
                                $stage_css = ""
                                ?>
                                @if($rec->orgid<>$curOrgID)
                                    <tr>
                                        <td colspan="10" class="font-italic font-weight-bold"
                                            style="background-color: #fdffd1">
                                            {{$rec->org_name}}
                                        </td>
                                    </tr>
                                    <?php
                                    $curOrgID = $rec->orgid;
                                    ?>
                                @endif
                                <tr style="{{$rec->OrdStyle}}">
                                    <td></td>
                                    <td scope="row" class="text-center">
                                        <a href="{{route($thisSysObjCode.'.edit',$rec->id)}}" target="_self">
                                            {{date_create($rec->begdate)->format('d.m.Y')}}
                                            -
                                            {{date_create($rec->enddate)->format('d.m.Y')}}
                                        </a>
                                    </td>
                                    <td>
                                        <div class="ml-3 font-italic">
                                            {{$rec->grantstaffname}}
                                        </div>
                                    </td>
                                    <td class="c">{{$rec->subj}}
                                    <td style="text-align: center;">
                                        <a href="{{ route($thisSysObjCode.'.edit',$rec->id)}}"
                                           class="btn btn-sm btn-primary"
                                           title="Просмотреть/Изменить запись">
                                            <i class="fa fa-pencil"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <?php
                            $msg = "Данные не найдены. \nПопробуйте изменить критерий поиска.";
                            ?>
                            <tr>
                                <td colspan="6" class="c">{{$msg}}</td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                    <div>
                        {{$recs->links()}}
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

