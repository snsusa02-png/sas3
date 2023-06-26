@extends('layouts.edit')

@section('content')

    @if (!isset( $rec ))
        <?php
        redirect()->route('refitems.search');
        header("Location:" . route('refitems.search'));
        die();
        ?>
    @else
        <?php
        $sysobjid = 105;
        $ThisTitle = "Номенклатура";

        $qty_decimal = 0;

        $route_index = "/refitems/?page=" . session('pageno') . '#' . $rec->id;
        ?>

        {{--		<link href="/media/k2/assets/css/magnific-popup.css" rel="stylesheet" type="text/css"/>--}}
        {{--		<script src="/js/jquery.magnific-popup.min.js" type="text/javascript" defer></script>--}}

        <style type="text/css">
            label {
                color: gray;
                margin-bottom: 0px;
            }

            .photo {
                display: block;
                max-width: 116px;
                max-height: 136px;
                width: auto;
                height: auto;
                margin: auto;
            }

        </style>
        <link rel="stylesheet" href="/css/itmtypes_tree.css">

        <div class="container">
            <span id="helptags" data="{{$rec->helptags}}"/>

            @include('layouts.edit_msgs')

            <div class="row ">
                <div class="col-md-8">

                    <div class="card mt-3">
                        <div class="card-header">
                            Номенклатура (Товар/Услуга)

                            <a class="btn btn-close btn-light" href="{{$route_index}}"
                               style="float: right;"
                               title="Вернуться в список">
                                <i class="fa fa-times" aria-hidden="true"></i>
                            </a>
                        </div>
                        <div class="card-body">
                            @include('layouts.err_msgs')

                            <form name="forEdit" id="forEdit" method="post"
                                  action="{{ route('refitems.update', $rec->id) }}">
                                @method('PUT')
                                @csrf


                                <div class="row">
                                    <div class="col-md-3">
                                        <?php
                                        $photo = '<img src="/images/signs/no-image.png" class="photo">';
                                        if ($rec->id != -1)
                                            $photo = '<a href="' . route('ri_images.load', $rec->id) . '" title="Добавить фото">' . $photo . '</a>';

                                        if ($rec->photourl != "") {
                                            $filename = $_SERVER['DOCUMENT_ROOT'] . '/' . $rec->photourl;
                                            if (file_exists($filename)) {
                                                $photo = '<img src="/' . $rec->photourl . '" class="photo">';
                                                $photo = '<a href="/' . $rec->photourl . '" class="popup-image" title="' . $rec->name . '">' . $photo . '</a>';
                                            }
                                        }
                                        ?>
                                        {!! $photo!!}
                                    </div>
                                    <div class="col-md-9">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="name">Название:</label>
                                                    <input type="text" class="form-control font-weight-bold" name="name"
                                                           value="{{old('name',$rec->name)}}"/>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <?php
                                                $readonly = ($rec->id == -1) ? '' : ' readonly';
                                                ?>
                                                <div class="form-group">
                                                    <label for="code">Код учетной системы:</label>
                                                    <input type="text" class="form-control text-center" name="code"
                                                           {{$readonly}}
                                                           value="{{old('code', $rec->code)}}"/>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="name">Тип номенклатуры:</label>
                                                    {!! Form::select('producttypeid', $rec->producttypes,
                                                    $rec->producttypeid,
                                                    [
                                                        'class' => 'form-control',
                                                        'placeholder' => '-',
                                                    ]) !!}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="name">Единица измерения:</label>
                                            {{--                                            @dd($rec->unittypeid,$unittypes)--}}

                                            {{--                                            @if(array_key_exists((int)$rec->unittypeid,$unittypes))--}}
                                            @if(isset($unittypes))
                                                {!! Form::select('unittypeid', $unittypes,
                                                $rec->unittypeid,
                                                ['class' => 'form-control']) !!}

                                            @else
                                                <div class="font-weight-bold text-center">{{$rec->unittype->name}}
                                                    {!! Form::hidden('unittypeid', $rec->unittypeid)!!}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="price">Цена за ЕИ, руб:</label>
                                            <input type="text" class="form-control text-right" name="price"
                                                   value="{{$rec->price}}"/>
                                        </div>
                                    </div>
                                    @if(1==0)
                                        <div class="offset-md-0 col-md-4">
                                            <div class="form-group">
                                                <label for="price">{{Config::get('constants.pricenames.retail')}}
                                                    :</label>
                                                <input type="text" class="form-control text-right" name="retailprice"
                                                       value="{{$rec->retailprice}}"/>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="itmtypeid">Категория:</label>
                                            @if(1==0)
                                                {!! Form::select('itmtypeid', $rec->itmtypes,
                                                $rec->itmtypeid,
                                                ['class' => 'form-control',
                                                'placeholder'=>'-']) !!}
                                            @endif

                                            <div class="input-group">
                                                <input type="hidden" name="itmtypeid" id="itmtypeid"
                                                       value="{{ $rec->itmtypeid}}">
                                                <input type="text" id="itmtypename" readonly
                                                       value="{{$rec->itmtype->name}}"
                                                       class="form-control font-weight-bold"
                                                       style="background-color: snow"
                                                       data-toggle="collapse"
                                                       data-target="#categories"
                                                >
                                                <button type="button" class="btn btn-info" data-toggle="collapse"
                                                        data-target="#categories"><i class="fa fa-caret-down"
                                                                                     aria-hidden="true"></i>
                                                </button>
                                            </div>
                                            <ul class="collapse border" style="cursor: pointer" id="categories">
                                                @foreach($rec->categories as $category)
                                                    <li>
                                                        <span class="itmtypeid "
                                                              data-id="{{$category->id}}">{{$category->name}}</span>

                                                        @if($category->sub->count())
                                                            <input type="checkbox" id="chk{{$category->id}}"><label
                                                                for="chk{{$category->id}}"></label>

                                                            @include('itmtypes.sub_tree', ['categories' => $category->sub])
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>

                                    </div>
                                    @if(1==0)
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="itmtypeid">Статья бюджета:</label>
                                                {!! Form::select('bdgtacnttypeid', [21=>'материалы',25=>'накладные'],
                                                $rec->bdgtacnttypeid,
                                                ['class' => 'form-control',
                                                'placeholder'=>'-']) !!}
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="row">
                                    @if(1==0)
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Брэнд:</label>
                                                {!! Form::select('brandid', $rec->brands, $rec->brandid,
                                                [
                                                'class' => 'form-control',
                                                'placeholder' => '',
                                                ]) !!}
                                                {{--											<input type="text" class="form-control text-center" name="brand" value="{{$rec->brand}}"/>--}}

                                            </div>
                                        </div>
                                    @endif
                                    @if(1==0)
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Производитель:</label>
                                                <input type="text" class="form-control text-center" name="manufacturer"
                                                       value="{{$rec->manufacturer}}"/>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                @if(1==0)
                                    <div class="form-group">
                                        <label for="name">Публичное название (для общедоступного каталога):</label>
                                        <input type="text" class="form-control font-weight-bold" name="publicname"
                                               value="{{old('publicname',$rec->publicname)}}"/>
                                    </div>
                                @endif
                                <div class="form-group">
                                    <label for="descript">Описание:</label>
                                    <textarea class="form-control rounded-0" name="descript" id="descript"
                                              rows="3">{{$rec->descript}}</textarea>
                                </div>

                                <?php
                                $length_unit_name = env("LENGTH_UNIT_NAME", "см");
                                ?>

                                <div id="accordionAux">

                                    <div class="header" id="heading_dimensions">
                                        <h2 class="mb-0">
                                            <button type="button" class="btn btn-link"
                                                    style="background-color: aliceblue"
                                                    data-toggle="collapse"
                                                    data-target="#collapse_dimensions">
                                                Коды, Размеры, Вес
                                            </button>
                                        </h2>
                                    </div>
                                    <div id="collapse_dimensions" class="collapse"
                                         aria-labelledby="heading_dimensions"
                                         data-parent="#accordionAux">


                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="partnumber">Код товара (Part Number):</label>
                                                    <input type="text" class="form-control text-center"
                                                           name="partnumber"
                                                           value="{{$rec->partnumber}}"/>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="upc">UPC (Universal Product Code):</label>
                                                    <input type="text" class="form-control text-center" name="upc"
                                                           value="{{$rec->upc}}"/>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="ean">EAN (European Article Number):</label>
                                                    <input type="text" class="form-control text-center" name="ean"
                                                           value="{{$rec->ean}}"/>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="offset-md-0 col-md-4">
                                                <div class="form-group">
                                                    <label for="name">Ширина упаковки 1 ед, {{$length_unit_name}}
                                                        :</label>
                                                    <input type="number" class="form-control text-right"
                                                           name="pkg_width"
                                                           min="0.1" step="0.1"
                                                           value="{{$rec->pkg_width}}"/>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="name">Высота упаковки 1 ед, {{$length_unit_name}}
                                                        :</label>
                                                    <input type="number" class="form-control text-right"
                                                           name="pkg_height"
                                                           min="0.1" step="0.1"
                                                           value="{{$rec->pkg_height}}"/>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="name">Глубина упаковки 1 ед, {{$length_unit_name}}
                                                        :</label>
                                                    <input type="number" class="form-control text-right"
                                                           name="pkg_depth"
                                                           min="0.1" step="0.1"
                                                           value="{{$rec->pkg_depth}}"/>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="offset-md-4 col-md-4">
                                                <div class="form-group">
                                                    <label for="grossweight">Вес брутто 1 ед, кг:</label>
                                                    <input type="number" class="form-control text-right"
                                                           name="grossweight"
                                                           min="0.001" step="0.001"
                                                           value="{{old('grossweight',$rec->grossweight)}}"/>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="grossweight">Порядок вывода в каталоге:</label>
                                                    <input type="number" class="form-control text-right"
                                                           name="ordr"
                                                           min="1" step="1"
                                                           value="{{old('ordr',$rec->ordr)}}"/>
                                                </div>
                                            </div>
                                        </div>


                                    </div>
                                </div>

                                @if(1==0)
                                    <div class="row">
                                        <div class=" col-md-6">

                                            <label for="photourl">URL фото:</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" name="photourl"
                                                       value="{{$rec->photourl}}" readonly/>
                                                <div class="input-group-prepend">
                                                    <a href="{{ route('ri_images.load',$rec->id)}}"
                                                       class="btn btn-warning btn-sm">
                                                        <i class="fa fa-plus"></i>
                                                    </a>
                                                </div>
                                            </div>

                                        </div>
                                        <div class="col-md-6 text-center">
                                            <?php
                                            $photo = "";
                                            if ($rec->photourl != "") {
                                                $filename = $_SERVER['DOCUMENT_ROOT'] . '/' . $rec->photourl;
                                                if (file_exists($filename)) {
                                                    $photo = '<img src="/' . $rec->photourl . '" class="photo">';
                                                } else {
                                                    $photo = "Файл $rec->photourl не существует";
                                                }
                                                echo $photo;
                                            }
                                            ?>
                                        </div>

                                    </div>
                                @endif

                                @if(1==0 and $rec->itmtype->isservice == 0 and isset($stock_On) and $stock_On)
                                    <div class="row">
                                        <div class="offset-md-0 col-md-6">
                                            <div class="form-group">
                                                <label for="outoffstock_msg">Сообщение при отсутствии на
                                                    складе:</label>
                                                <input type="text" class="form-control" name="outoffstock_msg"
                                                       value="{{$rec->outoffstock_msg}}"
                                                       placeholder="нет в наличии"/>
                                            </div>
                                        </div>
                                    </div>
                                @endif


                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="active">Доступен для продажи:</label>
                                            {!! Form::checkbox('active', 1, $rec->active==1, ['class="form-control"']) !!}
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="name">Начало продаж:</label>
                                        <input type="text" class="form-control text-center" name="salebegdate"
                                               readonly
                                               value="{{$rec->salebegdate}}"/>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="name">Окончание продаж:</label>
                                        <input type="date" class="form-control text-center" name="saleenddate"
                                               value="{{$rec->saleenddate}}"/>
                                    </div>
                                </div>

                                <div>
                                    <hr size="1">
                                    @if ($usrrights['save'])
                                        <button type="submit" class="btn btn-success"
                                                title="Сохранить изменения">
                                            <i class="fa fa-floppy-o" aria-hidden="true"></i>
                                            Сохранить
                                        </button>
                                    @endif
                                    <?php
                                    ?>
                                    <a class="btn btn-close btn-info" href="{{$route_index}}">
                                        <i class="fa fa-window-close-o" aria-hidden="true"></i>
                                        Закрыть
                                    </a>
                                    @if ($rec->id != -1 and $usrrights['delete'])
                                        <button type="submit"
                                                class="btn btn-danger btn-sm"
                                                style="margin-left:24px"
                                                formaction="{{ route('refitems.del', $rec->id)}}"
                                                formmethod="post"
                                                title="Удалить запись"
                                                onclick="return confirm('Вы действительно хотите удалить запись из прайс-листа?')"
                                        >
                                            <i class="fa fa-trash-o" aria-hidden="true"></i>
                                        </button>
                                        @if($rec->id != -1 and $usrrights['admindelete'])
                                            <button type="submit"
                                                    class="btn btn-danger btn-sm"
                                                    style="margin-left:24px"
                                                    formaction="{{ route('refitems.admindelete', $rec->id)}}"
                                                    formmethod="post"
                                                    title="Удалить административно"
                                                    onclick="return confirm('Вы действительно хотите удалить запись со всеми зависимостями?')"
                                            >
                                                <i class="fa fa-bomb" aria-hidden="true"></i>
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </form>
                        </div>
                        <div class="card-footer">
                            @if ($rec->id != -1)

                                @include('layouts._who_when')

                                @if ( isset($rec->searchname))
                                    <div class="form-group">
                                        <label for="descript">Поисковые названия:</label>
                                        {{$rec->searchname}}
                                    </div>
                                @endif
                                @if ( isset($rec->usage_stat))
                                    <div class="form-group small" style="color: gray;">
                                        <label for="descript">Связи:</label>
                                        {{$rec->usage_stat}}
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>


                <div class="col-md-4">

                    {{--					@include('refitems/ri_specinfo')--}}

                    @include('refitems/ri_images')
                    @include('refitems/ri_auxinfo')

                    {{--                    @include('refitems/obj_files')--}}
                    @includeif('objfiles.obj_files')

                    @includeif('obj_names/_names')
                    @includeif('refitems/_units')
                    {{--                    @include('refitems/_estprices')--}}
                    @include('refitems/_offers')
                    {{--                    @includeif('refitems/_last_equiprqsts')--}}
                    {{--                    @includeif('refitems/_last_bot_ri_lims')--}}

                    {{--					@include('refitems/ri_saleactions')--}}

                    @include('refitems/ri_extids')

                    @includeif('refitems/_compounds')

                    {{--					@include('refitems/ri_images')--}}




                    {{--Запас на складах--}}
                    @if(isset($ri_stocks_view))
                        @includeIf($ri_stocks_view)
                    @endif

                    @if (isset($ri_stock_docs))
                        <div class="row">

                            <div class="col-md-12">
                                <div class="card mb-3 mx-0">
                                    <div class="card-header">
                                        Документы склада
                                    </div>
                                    <table class="table-striped p-2" style0="width: 280px;">
                                        <thead>
                                        <tr valign="top">
                                            <td rowspan="2">#</td>
                                            <td rowspan="2">Документ</td>
                                            <td colspan="2">Количество, еи</td>
                                        </tr>
                                        <tr class="text-center small">
                                            <td>факт.</td>
                                            <td>план.</td>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        $TotQty = 0;
                                        $TotPlnQty = 0;
                                        $curOwnOrgID = "";

                                        $bgcols = array('#FeFeFe', '#EfEfEf', '#FFBFBF', '#FFcccc', '#F9F5BD', '#FCFADC', '#BFF9B9', '#ccffcc', '#79D3FF', '#CCECF9', '#CC9999', '#E2C7C7');
                                        $rec0 = 0;
                                        ?>
                                        @foreach($ri_stock_docs as $itm)
                                            <?php
                                            $colshift = (1 - $itm->docsigned) * 2;
                                            $tr_bg_col = $bgcols[$colshift + ($loop->index + $rec0) % 2];


                                            if ($itm->qty == 0) $shQty = "";
                                            else $shQty = number_format($itm->qty, $qty_decimal);

                                            if ($itm->plnqty == 0) $shPlnQty = "";
                                            else $shPlnQty = number_format($itm->plnqty, $qty_decimal);

                                            if ($itm->ownorgid <> $curOwnOrgID){
                                            if ($curOwnOrgID <> ""){
                                            ?>
                                            <tr>
                                                <td colspan="2">
                                                </td>
                                                <td class="text-right">
                                                    &nbsp;<b>{{$curTotQty}}</b>
                                                </td>
                                                <td class="text-right small">
                                                    &nbsp;<b>{{$curTotPlnQty}}</b>
                                                </td>
                                            </tr>
                                            <?php
                                            }

                                            $curOwnOrgID = $itm->ownorgid;
                                            $curTotQty = 0;
                                            $curTotPlnQty = 0;
                                            ?>
                                            <tr>
                                                <td colspan="4">
                                                    &nbsp;<b>{{$itm->ownorgname}}</b>
                                                </td>
                                            </tr>
                                            <?php
                                            }
                                            ?>
                                            <tr style="background-color: {{$tr_bg_col}}">
                                                <td style="text-align: right;"
                                                    class="small">{{$loop->iteration}}</td>

                                                <td class="text-left small">&nbsp;
                                                    <a href="{{route("wrhdocs.edit",$itm->docid)}}" target="_blank">
                                                        {{$itm->docdate}}
                                                        {{$itm->doctypename}}
                                                        № {{$itm->docnum}}
                                                    </a>
                                                </td>
                                                <td class="text-right">
                                                    &nbsp;{{$shQty}}
                                                </td>
                                                <td class="text-right small">
                                                    {{$shPlnQty}}
                                                </td>
                                            </tr>
                                            <?php
                                            $TotQty = $TotQty + $itm->qty;
                                            $TotPlnQty = $TotPlnQty + $itm->plnqty;
                                            $curTotQty = $curTotQty + $itm->qty;
                                            $curTotPlnQty = $curTotPlnQty + $itm->plnqty;
                                            ?>
                                        @endforeach
                                        @if ($curOwnOrgID <> "")
                                            <tr>
                                                <td colspan="2"></td>
                                                <td class="text-right">
                                                    &nbsp;<b>{{$curTotQty}}</b>
                                                </td>
                                                <td class="text-right small">
                                                    &nbsp;<b>{{$curTotPlnQty}}</b>
                                                </td>
                                            </tr>
                                        @endif

                                        <tr>
                                            <td colspan="2" class="text-right">Всего:</td>
                                            <td class="text-right font-weight-bold">{{number_format($TotQty,$qty_decimal)}}</td>
                                            <td class="text-right font-weight-bold small">{{number_format($TotPlnQty,$qty_decimal)}}</td>
                                        </tr>
                                        <tr style="color:darkgrey;">
                                            <td colspan="2" class="text-right">Всего, с учетом планируемого:</td>
                                            <td colspan="2"
                                                class="text-center font-weight-bold">{{number_format($TotQty+$TotPlnQty,$qty_decimal)}}</td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @if(isset($rec->helptags))
            <span class="helptags" data="{{$rec->helptags}}"/>
        @endif

        <script type="text/javascript" defer>
            // $(document).ready(function () {
            //     $('.popup-image').magnificPopup({type: 'image'});
            // });
        </script>
        <script src="{{ asset('js/refitems_edit.js') }}" defer></script>
    @endif
@endsection
