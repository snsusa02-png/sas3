<div class="tab-pane fade " id="nsi-rep" role="tabpanel" aria-labelledby="nsi-rep-tab">
    <div class="list-group list-group-flush">


        @if(isset($data->reports))
            <div class="tagcloud01 mt-2 ml-3">
                <ul>
                    @foreach($data->rep_usedtags as $key=>$tag)
                        <li>
                            <a href="?s_tag={{rawurlencode($tag)}}"
                               title="{{$tag}}">{{$tag}}</a>
                        </li>
                    @endforeach
                </ul>
            </div>
            @foreach($data->reports as $rep)
                <div class="list-group-item ">
                    <a href="{{route('reports.rep'.$rep->id)}}"
                       class="list-group-item-action font-weight-bold">
                        <i class="fa fa-file-text-o" aria-hidden="true"></i>
                        {{$rep->name??'-'}}
                    </a>
                    {{--tags--}}
                    <div class="tagcloud01 mt-2 ml-3">
                        <ul>
                            @foreach($rep->tags as $tag)
                                <li>
                                    <a href="?s_tag={{rawurlencode($tag->tag)}}"
                                       title="{{$tag->tag}}">{{$tag->tag}}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="small ml-3">{{$rep->descript}}</div>
                    @if($data->usrrights['edit_report'])
                        <div class="small ml-3">{{$rep->lastuse_dt}}: {{$rep->lastuse_username}}</div>
                    @endif
                </div>
            @endforeach
        @endif

        @if (1==0)
            @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'orgplnpays.read'))
                <a href="{{route('reports.rep13')}}"
                   class="list-group-item list-group-item-action font-weight-bold">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    Ожидание оплаты счетов
                </a>
                <hr>
            @endif

            @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'mchnrqsts.approve')
            or \App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'mchnrqsts.finapprove')
            or \App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'mchnrqsts.reports')
            )
                <a href="{{route('reports.rep1')}}"
                   class="list-group-item list-group-item-action font-weight-bold">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    Отчет о работе спецтехники и механизмов
                </a>
                <a href="{{route('mchnrqsts.rep02_esm3')}}"
                   class="list-group-item list-group-item-action font-weight-bold">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    Рапорт о работе строительной машины (механизма). Форма №ЭСМ-3
                </a>
                <a href="{{route('reports.rep3')}}"
                   class="list-group-item list-group-item-action font-weight-bold">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    Сводный отчет о работе спецтехники и механизмов
                </a>
                <a href="{{route('reports.rep6')}}"
                   class="list-group-item list-group-item-action font-weight-bold">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    График занятости спецтехники и механизмов за период
                </a>
                <a href="{{route('reports.rep7')}}"
                   class="list-group-item list-group-item-action font-weight-bold">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    Сводный отчет о работе ИП
                </a>
                <a href="{{route('reports.rep8')}}"
                   class="list-group-item list-group-item-action font-weight-bold">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    График занятости ИП за период
                </a>
                <a href="{{route('reports.rep9')}}"
                   class="list-group-item list-group-item-action font-weight-bold">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    Акты выполненных работ по договорам на перевозку грузов
                </a>
                <a href="{{route('reports.rep26')}}"
                   class="list-group-item list-group-item-action font-weight-bold">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    Отчет о работе спецтехники и механизмов по заявкам арендатора
                </a>
                <hr>
            @endif
            @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'prodplan_facts.read')		)
                <a href="{{route('reports.rep4')}}"
                   class="list-group-item list-group-item-action font-weight-bold">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    Объемы выполненных работ
                </a>
                <hr>
            @endif

            @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'users.reports'))
                <a href="{{route('reports.rep5')}}"
                   class="list-group-item list-group-item-action font-weight-bold">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    Статистика входов пользователей в ИС "ГК Баско"
                </a>
                <a href="{{route('reports.rep10')}}"
                   class="list-group-item list-group-item-action font-weight-bold">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    Статистика операций пользователей в ИС "ГК Баско"
                </a>
                <a href="{{route('reports.rep11')}}"
                   class="list-group-item list-group-item-action font-weight-bold">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    Статистика операций в течение суток
                </a>
                <hr>
            @endif

            @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'equiprqsts.read'))

                <a href="{{route('reports.rep33')}}"
                   class="list-group-item list-group-item-action font-weight-bold">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    Товарный запас
                </a>
                <a href="{{route('reports.rep12')}}"
                   class="list-group-item list-group-item-action font-weight-bold">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    Кандидаты для накладных на отпуск материалов на сторону (форма М-15)
                </a>
                <a href="{{route('reports.rep15')}}"
                   class="list-group-item list-group-item-action font-weight-bold">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    Поставки материалов
                </a>
                <a href="{{route('reports.rep18')}}"
                   class="list-group-item list-group-item-action font-weight-bold">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    Сводка материалов по виду работ бюджета
                </a>
                <a href="{{route('reports.rep19')}}"
                   class="list-group-item list-group-item-action font-weight-bold">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    Сопоставление материалов РВ и заявок по виду работ объекта
                </a>
                <a href="{{route('reports.rep20')}}"
                   class="list-group-item list-group-item-action font-weight-bold">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    Заявки с нераспределенными по счетам доп. затратами
                </a>
                <a href="{{route('reports.rep21')}}"
                   class="list-group-item list-group-item-action font-weight-bold">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    Исполнение по материалам по месяцами (по данным УПД)
                </a>
                <a href="{{route('reports.rep22')}}"
                   class="list-group-item list-group-item-action font-weight-bold">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    Сводное исполнение контракта по месяцам
                </a>
                <a href="{{route('reports.rep27')}}"
                   class="list-group-item list-group-item-action font-weight-bold">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    Исполнение контрактов по подрядчику
                </a>

                @php
                    $rep = \App\report::find(38);
                @endphp
                @if(isset($rep))
                    <div class="list-group-item ">
                        <a href="{{route('reports.rep38')}}"
                           class="list-group-item-action font-weight-bold">
                            <i class="fa fa-file-text-o" aria-hidden="true"></i>
                            {{$rep->name??'-'}}
                        </a>
                        <div class="small ml-3">{{$rep->descript}}</div>
                        @if(\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'admin-global'))
                            <div class="small ml-3">{{$rep->lastuse_dt}}: {{$rep->lastuse_username}}</div>
                        @endif
                    </div>
                @endif
                <hr>
            @endif

            @if (\App\usrsysright::isUserHasAnyRightByCode(\Auth::user()->id,'orgplnpays.read'))

                <a href="{{route('reports.rep43')}}"
                   class="list-group-item list-group-item-action font-weight-bold">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    График платежей
                </a>
                <a href="{{route('reports.rep31')}}"
                   class="list-group-item list-group-item-action font-weight-bold">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    Оплаченные счета
                </a>
                <a href="{{route('reports.rep14')}}"
                   class="list-group-item list-group-item-action font-weight-bold">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    Оплаченные счета на материалы
                </a>
                <a href="{{route('reports.rep16')}}"
                   class="list-group-item list-group-item-action font-weight-bold">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    Состояние оплаты счетов на материалы по всем плательщикам
                </a>
                <a href="{{route('reports.rep17')}}"
                   class="list-group-item list-group-item-action font-weight-bold">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    Анализ ожидания оплаты счетов
                </a>
                <a href="{{route('reports.rep34')}}?returl={{Request::url()}}#nsi-rep"
                   class="list-group-item list-group-item-action font-weight-bold">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    Реестр счетов
                </a>

                @php
                    $rep = \App\report::find(36);
                @endphp
                @if(isset($rep))
                    <div class="list-group-item ">
                        <a href="{{route('reports.rep36')}}"
                           class="list-group-item-action font-weight-bold">
                            <i class="fa fa-file-text-o" aria-hidden="true"></i>
                            {{$rep->name??'-'}}
                        </a>
                        <div class="small ml-3">{{$rep->descript}}</div>
                        @if(\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'admin-global'))
                            <div class="small ml-3">{{$rep->lastuse_dt}}: {{$rep->lastuse_username}}</div>
                        @endif
                    </div>
                @endif

            @endif



            @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'invoices.read'))

                <a href="{{route('reports.rep24')}}"
                   class="list-group-item list-group-item-action font-weight-bold">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    УПД, полученные за период
                </a>
                <a href="{{route('reports.rep25')}}"
                   class="list-group-item list-group-item-action font-weight-bold">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    Расхождение получателя по УПД и владельца использованного в заявке бюджета
                </a>
                <hr>
            @endif

            @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'refitems.read'))

                <a href="{{route('reports.rep28')}}"
                   class="list-group-item list-group-item-action font-weight-bold">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    Позиции заявок не из справочника номенклатуры
                </a>
            @endif

            @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'jobtimesheets.read'))

                <a href="{{route('reports.rep29')}}"
                   class="list-group-item list-group-item-action font-weight-bold">
                    <i class="fa fa-file-text-o" aria-hidden="true"></i>
                    {{\App\report::find(29)->name??'-'}}
                </a>
            @endif

            @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'analitics.reports'))
                @php
                    $rep = \App\report::find(32);
                @endphp
                @if(isset($rep))
                    <div class="list-group-item ">
                        <a href="{{route('reports.rep32')}}"
                           class="list-group-item-action font-weight-bold">
                            <i class="fa fa-file-text-o" aria-hidden="true"></i>
                            {{$rep->name??'-'}}
                        </a>
                        <div class="small ml-3">{{$rep->descript}}</div>
                        @if(\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'admin-global'))
                            <div class="small ml-3">{{$rep->lastuse_dt}}: {{$rep->lastuse_username}}</div>
                        @endif
                    </div>
                @endif
            @endif

            @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'analitics.reports'))
                @php
                    $rep = \App\report::find(35);
                @endphp
                @if(isset($rep))
                    <div class="list-group-item ">
                        <a href="{{route('reports.rep35')}}"
                           class="list-group-item-action font-weight-bold">
                            <i class="fa fa-file-text-o" aria-hidden="true"></i>
                            {{$rep->name??'-'}}
                        </a>
                        <div class="small ml-3">{{$rep->descript}}</div>
                        @if(\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'admin-global'))
                            <div class="small ml-3">{{$rep->lastuse_dt}}: {{$rep->lastuse_username}}</div>
                        @endif
                    </div>
                @endif
            @endif

            @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'documents.read'))

                @php
                    $repid=37;
                    $rep = \App\report::find($repid);
                @endphp
                @if(isset($rep))
                    <div class="list-group-item ">
                        <a href="{{route('reports.rep'.$repid)}}"
                           class="list-group-item-action font-weight-bold">
                            <i class="fa fa-file-text-o" aria-hidden="true"></i>
                            {{$rep->name??'-'}}
                        </a>
                        <div class="small ml-3">{{$rep->descript}}</div>
                        @if(\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'admin-global'))
                            <div class="small ml-3">{{$rep->lastuse_dt}}: {{$rep->lastuse_username}}</div>
                        @endif
                    </div>
                @endif
            @endif

            {{--        @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'analitics.reports'))--}}
            @if (1==1)
                @php
                    $rep_id = 39;
                    $rep = \App\report::find($rep_id);
                @endphp
                @if(isset($rep))
                    <div class="list-group-item ">
                        <a href="{{route('reports.rep'.$rep_id)}}"
                           class="list-group-item-action font-weight-bold">
                            <i class="fa fa-file-text-o" aria-hidden="true"></i>
                            {{$rep->name??'-'}}
                        </a>
                        <div class="small ml-3">{{$rep->descript}}</div>
                        @if(\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'admin-global'))
                            <div class="small ml-3">{{$rep->lastuse_dt}}: {{$rep->lastuse_username}}</div>
                        @endif
                    </div>
                @endif
            @endif

            @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'analitics.reports'))
                @php
                    $rep_id = 40;
                    $rep = \App\report::find($rep_id);
                @endphp
                @if(isset($rep))
                    <div class="list-group-item ">
                        <a href="{{route('reports.rep'.$rep_id)}}"
                           class="list-group-item-action font-weight-bold">
                            <i class="fa fa-file-text-o" aria-hidden="true"></i>
                            {{$rep->name??'-'}}
                        </a>
                        <div class="small ml-3">{{$rep->descript}}</div>
                        @if(\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'admin-global'))
                            <div class="small ml-3">{{$rep->lastuse_dt}}: {{$rep->lastuse_username}}</div>
                        @endif
                    </div>
                @endif
            @endif

            @if (\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'analitics.reports'))
                @php
                    $rep_id = 42;
                    $rep = \App\report::find($rep_id);
                @endphp
                @if(isset($rep))
                    <div class="list-group-item ">
                        <a href="{{route('reports.rep'.$rep_id)}}"
                           class="list-group-item-action font-weight-bold">
                            <i class="fa fa-file-text-o" aria-hidden="true"></i>
                            {{$rep->name??'-'}}
                        </a>
                        <div class="small ml-3">{{$rep->descript}}</div>
                        @if(\App\usrsysright::isUserHasRightByCode(\Auth::user()->id,'admin-global'))
                            <div class="small ml-3">{{$rep->lastuse_dt}}: {{$rep->lastuse_username}}</div>
                        @endif
                    </div>
                @endif
            @endif

        @endif
    </div>
</div>
