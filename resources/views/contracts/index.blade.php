<?php
//extends('itmtypes_layout')
?>
@extends('layouts.app')
@section('content')
    <?php
    $thisTitle = "Договоры";
    $thisSysObjCode = 'contracts';
    //dd($usrrights);
    ?>
    <style>
        .scroll {
            color: steelblue;
            cursor: pointer;
            position: fixed;
            top: 50%;
            right: 1%;
            font-size: 32px;
            z-index: 10000;
            opacity: 0.6;
            display: none;
        }

        .scroll:hover {
            opacity: 1;
        }

        .scrollup {
            top: 10px;
        }

        .scrolldown {
            top: 95%;
        }
    </style>

    <div class="container-fluid">

        <?php
        $breadcrumbs = [
            'Сервис' => "/admin",
            'Справочники' => "/admin?tab=nsi-dic",
            $thisTitle => null,
        ];
        ?>
        @includeIf('layouts.breadcrumbs')


        <div class="row justify-content-center">
            <div class="col-md-12">
                <h3>{{$thisTitle}}</h3>
                <link rel="stylesheet" href="/css/subnav.css">
                <link rel="stylesheet" href="/css/tags.css">
                <style>
                    .owngrp {
                        background-color: #ffffcc !important;
                    }

                    .searchby {
                        background-color: #dbfbce;
                        font-weight: bold;
                    }
                </style>

                @if(1==1 and $data->user_all_contract_cnt>0)
                    <div class="mt-2">
                        @include('layouts.edit_msgs')
                        <form name="forIndex" id="forIndex" method="post"
                              action="{{ route($thisSysObjCode.'.index') }}">
                            @csrf

                            <div class="row">

                                <div class="col-md-4 ">
                                    <div class="subnav shift">
                                        <ul>
{{--                                            <li><a href="{{route('reports.rep23')}}">Реестр</a></li>--}}
{{--                                            <li><a href="{{route('documents.index')}}">Архив документов</a></li>--}}
                                            <li><a href="{{route('orgs.index')}}">Контрагенты</a></li>
                                        </ul>
                                    </div>
                                </div>

{{--                                <div class="form-group offset-md-0 col-md-2">--}}
{{--                                    <label for="descript">Связь с объектом:</label>--}}
{{--                                    {!! Form::select('s_buildobjid', $usedbuildobjs, $search_params['s_buildobjid'] ?? '',--}}
{{--                 [--}}
{{--                 'class' => 'form-control',--}}
{{--                 'placeholder' => '-объект-',--}}
{{--                 'title' => 'Выберите объект строительства',--}}
{{--                  'data-toggle' => 'tooltip',--}}
{{--                 ]) !!}--}}
{{--                                </div>--}}
                                <div class="form-group offset-md-0 col-md-2">
                                    <label for="descript">Образ документа:</label>
                                    {!! Form::select('s_file_doctypeid',  $data->file_doctypes, $search_params['s_file_doctypeid'] ?? '',
                 [
                 'class' => 'form-control',
                 'placeholder' => '-',
                 'title' => 'Можно выбрать тип документа, приложенного к договору',
                  'data-toggle' => 'tooltip',
                 ]) !!}
                                </div>
                                <div class="form-group offset-md-0 col-md-2">
                                    <label for="descript">Тэг:</label>
                                    <div class="input-group">
                                        {!! Form::select('s_tag_type', $data->tagtypes??[], $search_params['s_tag_type'] ?? '',
                                            [
                                            'class' => 'form-control',
                                            'placeholder' => '-',
                                            'title' => 'Выберите тип тега из списка использованных вариантов',
                                            'data-toggle' => 'tooltip',
                                            ]) !!}
                                        <span class="px-1" style="">:</span>
                                        <input type="text" class="form-control" name="s_tag_val"
                                               value="{{$search_params['s_tag_val'] ?? ''}}"
                                               placeholder="-значение-"
                                               title="Укажите значение для тега"
                                               data-toggle="tooltip"/>
                                    </div>
                                    @if(1==0)
                                        {!! Form::select('s_tag', $usedtags, $search_params['s_tag'] ?? '',
        [
        'class' => 'form-control',
        'placeholder' => '-',
        'title' => 'Выберите тег из списка использованных вариантов',
        'data-toggle' => 'tooltip',
        ]) !!}
                                    @endif

                                </div>
                                <div class="form-group offset-md-0 col-md-1">
                                    <label for="descript">Ознакомление:</label>
                                    {!! Form::select('s_new4me', $data->new4me, $search_params['s_new4me'] ?? '',
    [
    'class' => 'form-control',
    'placeholder' => '-',
    'title' => 'Видел/не видел',
    'data-toggle' => 'tooltip',
    ]) !!}

                                </div>
                            </div>

                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <td>#</td>
                                    <td>Холдинг
                                    </td>
                                    <td>Категория, тип,
                                        <a href="{{ route('set_sort',['field' => 'c.regnum_num','retroute'=>$thisSysObjCode.'.index']) }}"
                                           class="btn btn-sm"
                                           title="Сортировать">Рег.
                                            № {!! sort_mark('c.regnum_num',$sort_params) !!}</a>
                                    </td>
                                    <td>Контрагент</td>
                                    <td>
                                        <a href="{{ route('set_sort',['field' => 'c.docnum','retroute'=>$thisSysObjCode.'.index']) }}"
                                           class="btn btn-sm"
                                           title="Сортировать">№ {!! sort_mark('c.docnum',$sort_params) !!}</a>,
                                        <a href="{{ route('set_sort',['field' => 'c.docdate','retroute'=>$thisSysObjCode.'.index']) }}"
                                           class="btn btn-sm"
                                           title="Сортировать">Дата {!! sort_mark('c.docdate',$sort_params) !!}</a>,
                                        Предмет договора
                                    </td>
                                    <td>
                                        <a href="{{ route('set_sort',['field' => 'c.begdate','retroute'=>$thisSysObjCode.'.index']) }}"
                                           class="btn btn-sm"
                                           title="Сортировать">Период
                                            действия {!! sort_mark('c.begdate',$sort_params) !!}</a>
                                    </td>
                                    <Td>
                                        <a href="{{ route('set_sort',['field' => 'c.docsum','retroute'=>$thisSysObjCode.'.index']) }}"
                                           class="btn btn-sm"
                                           title="Сортировать">Сумма договора,
                                            руб {!! sort_mark('c.docsum',$sort_params) !!}</a>
                                    </Td>

                                    <td>Статус</td>

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

                                <tr class="d-print-none small text-center">
                                    <td/>
                                    <td>
                                        {!! Form::select('s_ownorgid', $usedownorgs, $search_params['s_ownorgid'] ?? '',
                                                 [
                                                 'class' => 'form-control small',
                                                 'placeholder' => '-любой-',
                                                 'onChange' => 'submit()',
                                                 ]) !!}
                                        {!! Form::select('s_inituserid', $data->initusers, $search_params['s_inituserid'] ?? '',
                                                 [
                                                 'class' => 'form-control small',
                                                 'placeholder' => '-инициатор-',
                                                 'onChange' => 'submit()',
                                                 ]) !!}
                                    </td>
                                    <td>

                                        {!! Form::select('s_categoryid', $usedcategories, $search_params['s_categoryid'] ?? '',
                                             [
                                             'class' => 'form-control',
                                             'placeholder' => '-любая-',
                                             'title' => 'Выберите нужную категорию договора',
                                             'data-toggle' => 'tooltip',
                                             'onChange' => 'submit()',
                                             ]) !!}
                                        {!! Form::select('s_typeid', $usedtypes, $search_params['s_typeid'] ?? '',
                                             [
                                             'class' => 'form-control',
                                             'placeholder' => '-',
                                             'title' => 'Выберите нужный тип договора из списка существующих вариантов',
                                             'data-toggle' => 'tooltip',
                                             'onChange' => 'submit()',
                                             ]) !!}
                                        <div class="input-group">
                                            {!! Form::select('s_regnumstatus', $data->regnumstatuses, $search_params['s_regnumstatus'] ?? '',
                                             [
                                             'class' => 'form-control',
                                             'placeholder' => '-',
                                             'onChange' => 'submit()',
                                             ]) !!}
                                            <input type="number" class="form-control small" name="s_regnum"
                                                   value="{{$search_params['s_regnum'] ?? ''}}"
                                                   placeholder="-"
                                                   {{--                                               title="Укажите регистрационный номер договора, включая букву и лидирующий ноль. Например 'Р075'"--}}
                                                   title="Укажите регистрационный номер договора без начальной буквы. Например '93'"
                                                   min="0"
                                                   data-toggle="tooltip"
                                            />
                                        </div>
                                    </td>
                                    <td><input type="text" class="form-control" name="s_orgname"
                                               value="{{ $search_params['s_orgname'] ?? ''}}"
                                               placeholder="-любой-"
                                               title="Укажите характерную часть названия компании-контрагента"
                                               data-toggle="tooltip"
                                        /></td>
                                    <td>
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="s_docnum"
                                                   value="{{$search_params['s_docnum'] ?? ''}}"
                                                   placeholder="№"
                                                   title="Укажите характерную часть номера договора"
                                                   data-toggle="tooltip"/>
                                            <input type="text" class="form-control" name="s_name"
                                                   value="{{ $search_params['s_name'] ?? ''}}"
                                                   placeholder="-"
                                                   title='Укажите характерную часть из предмета договора'
                                                   data-toggle="tooltip"
                                            />
                                        </div>
                                        <div class="input-group">
                                            {{--                                        <input type="date" class="form-control" name="s_docdate"--}}
                                            {{--                                               value="{{$search_params['s_docdate'] ?? ''}}"--}}
                                            {{--                                               placeholder="Дата"/>--}}

                                            <input type="date" class="form-control" name="s_begdocdate"
                                                   value="{{$search_params['s_begdocdate'] ?? ''}}"
                                                   title="Дата договора не менее чем ..."
                                                   data-toggle="tooltip"/>

                                            <input type="date" class="form-control" name="s_enddocdate"
                                                   value="{{$search_params['s_enddocdate'] ?? ''}}"
                                                   title="Дата договора не превышает ..." data-toggle="tooltip"/>

                                        </div>

                                    </td>
                                    <td>
                                        {!! Form::select('s_end_at', $end_variants, $search_params['s_end_at'] ?? '',
                                             [
                                             'class' => 'form-control',
                                             'placeholder' => '-',
                                             'onChange' => 'submit()',
                                             ]) !!}
                                    </td>
                                    <td></td>


                                    <td>{!! Form::select('s_statusid', $statuses, $search_params['s_statusid'] ?? '',
										 [
										 'class' => 'form-control',
										 'placeholder' => '-любой-',
                                         'onChange' => 'submit()',
										 ]) !!}</td>
                                    <td>
                                        <div class="input-group-btn">
                                            <button type="submit" class="btn btn-sm btn-success"
                                                    formaction="{{ route($thisSysObjCode.'.index') }}"
                                                    formmethod="post" title="Поиск">
                                                <i class="fa fa-search" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                </thead>
                                <tbody>
                                @if(isset($recs) and !is_null($recs) and count($recs)>0)
                                    <?php
                                    $bgcols = array(
                                        '#FeFeFe', '#EfEfEf', '#FFBFBF', '#FFcccc', '#F9F5BD', '#FCFADC'
                                    , '#BFF9B9', '#ccffcc', '#79D3FF', '#CCECF9', '#CC9999', '#E2C7C7'
                                    , '#aaccaa', '#bbccbb');

                                    $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;
                                    $curOwnMark = "";
                                    $curOwnOrgID = -1;
                                    ?>
                                    @foreach($recs as $item)
                                        <?php
                                        $colshift = (1 - $item->active) * 2;
                                        $tr_bg_col = $bgcols[$colshift + ($loop->index + $rec0) % 2];
                                        $colshift = (isset($item->enddate) and $item->enddate < now()) ? 2 : 0;
                                        $period_bg_col = $bgcols[$colshift + ($loop->index + $rec0) % 2];

                                        $tclass = ($item->categoryid == 1) ? 'badge-success'
                                            : (($item->categoryid == 2) ? 'badge-danger' : 'badge-warning');
                                        ?>
                                        @if($item->ownorgid<>$curOwnOrgID)
                                            <tr>
                                                <td colspan="10" class="font-italic font-weight-bold"
                                                    style="background-color: #fdffd1">
                                                    {{$item->ownorgname}}
                                                </td>
                                            </tr>
                                            <?php
                                            $curOwnOrgID = $item->ownorgid;
                                            ?>
                                        @endif
                                        <tr style="background-color: {{$tr_bg_col}}">
                                            <td class="small text-right">
                                                {{$loop->index + $rec0}} <a name="{{$item->id}}"></a>
                                            </td>
                                            <td></td>
                                            <td>
                                                {{$item->categoryname}} / {{$item->contracttypename}}
                                                <br><a href="{{ route($thisSysObjCode.'.edit',$item->id)}}" style="">
											<span class="badge badge-pill {{$tclass}}"
                                                  style="font-size: 16px;">{{$item->regnum}}</span>
                                                </a>
                                            </td>
                                            {{--                                    <td></td>--}}
                                            <td>
                                                @if(isset($item->controrg_lst))
                                                    <?php
                                                    $controrgs = explode(';', $item->controrg_lst);
                                                    ?>
                                                    <ul style="padding-left: 8px;" class="small">
                                                        @foreach($controrgs as $controrg)
                                                            <li>{{$controrg}}</li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    {{$item->orgname}}
                                                @endif
                                            </td>
                                            <td class="small text-left">
                                                {{$item->name}}
                                                №<b>{{$item->docnum}}</b>
                                                от {{date_create($item->docdate)->format('d.m.Y')}}
                                                <div class="mt-1 ml-3">
                                                    <a href="{{ route($thisSysObjCode.'.edit',$item->id)}}" style="">
                                                        {{$item->descript}}
                                                    </a>
                                                </div>
                                                {{--										tags--}}
                                                <div class="tagcloud01 mt-2 ml-3">
                                                    <ul>
                                                        @foreach($item->tags as $tag)
                                                            <li>
                                                                {{--                                                                <a href="?s_tag={{$tag->tag}}">{{((isset($tag->type))?$tag->type.':':'').$tag->tag}}</a>--}}
                                                                <a href="?s_tag={{$tag->tag}}" title="{{$tag->tag}}">{{$tag->tag}}</a>
                                                            </li>
                                                            </a>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                                @if(isset($item->lstImageDocs))
                                                    <div class="float-right">
                                                        <?php
                                                        $imgdoctypes = explode(';', $item->lstImageDocs);
                                                        ?>
                                                        <i class="fa fa-file-text-o" aria-hidden="true"></i>
                                                        <ul class=" no-bullets">
                                                            @foreach($imgdoctypes as $imgdoctype)
                                                                <li>- {{$imgdoctype}}</li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="small text-center" style="background-color: {{$period_bg_col}}">
                                                <?php
                                                $shBegDate = (isset($item->begdate)) ? date_create($item->begdate)->format('d.m.Y') : '...';
                                                $shEndDate = (isset($item->enddate)) ? date_create($item->enddate)->format('d.m.Y') : '...';
                                                ?>
                                                {{$shBegDate}} - {{$shEndDate}}
                                            </td>
                                            <td class="small text-right">
                                                {{number_format($item->docsum,2)}}
                                            </td>
                                            <td>{{$statuses[$item->statusid]??'-'}}
                                                <div class="small ml-3">{{$item->status_notes}}</div>
                                            </td>
                                            <td style="text-align: center;">
                                                <a href="{{ route($thisSysObjCode.'.edit',$item->id)}}"
                                                   class="btn btn-sm btn-primary no-print d-print-none"
                                                   title="Просмотреть/Изменить запись">
                                                    <i class="fa fa-pencil"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="9" class="text-center text-danger">
                                            Данные не найдены - уточните критерий отбора
                                        </td>
                                    </tr>
                                @endif
                                </tbody>
                            </table>

                            @includeIf('layouts.paginate_links')

                        </form>

                    </div>

                @else
                    <div class="text-center text-danger">Нет данных</div>
                @endif
            </div>
        </div>

        <div id="btn_up" class="scroll scrollup" title="Наверх" onclick="to_top();">
            <i class="fa fa-caret-square-o-up" aria-hidden="true"></i>
        </div>
        <div id="btn_down" class="scroll scrolldown" title="Вниз" onclick="to_bottom();">
            <i class="fa fa-caret-square-o-down" aria-hidden="true"></i>
        </div>

        <script>
            function to_top() {
                window.scroll({
                    top: 0,
                    left: 0,
                    behavior: 'smooth'
                });
            }

            function to_bottom() {
                window.scroll({
                    top: 999990,
                    left: 0,
                    behavior: 'smooth'
                });
            }
        </script>
        <script src="{{ asset('js/contract_index.js') }}" defer></script>

    </div>

@endsection
@section('title')
    {{$thisTitle}}
@endsection
