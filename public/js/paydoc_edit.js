$(document).ready(function () {


    var pre_docdate = $("#docdate").val();
    //console.log('------------------', pre_docdate)

    $("#paydir").change(function () {
        //Меняем подписи к контрагентам в зависимости от направления платежа
        if ($(this).val() == -1) {
            //Расход
            $("#ownorg_aux_lbl").html('(Плательщик)')
            $("#org_aux_lbl").html('(Получатель)')
        } else if ($(this).val() == +1) {
            $("#ownorg_aux_lbl").html('(Получатель)')
            $("#org_aux_lbl").html('(Плательщик)')
        } else {
            $("#ownorg_aux_lbl").html('')
            $("#org_aux_lbl").html('')
        }
    });

    $("#docdate").change(function () {
        const docdate = $("#docdate").val()
        const paydate = $("#paydate").val()
        //console.log(paydate, pre_docdate, paydate == pre_docdate)
        if (!paydate || paydate == pre_docdate) {
            $("#paydate").val(docdate);
        }
        pre_docdate = $("#docdate").val();
    })

    if ($(".ac_org_name").length > 0) {

        //Поиск контрагента
        $(".ac_org_name").autocomplete({


            source: function (request, response) {
                var ft = ($("#sale_dir").val() == -1 || $("#sale_dir").val() == 0) ? 12 : null;

                $.ajax({
                    //url: "/orgs/autocomplete/search",
                    url: "/api/orgs/for_ac",
                    dataType: "json",
                    data: {
                        name_inn: request.term,
                        flagtypeid: ft,
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (data) {
                        //console.log(data);
                        response($.map(data, function (item, index) {
                            if (index == 16) {
                                var n = data.length - 16;
                                return {
                                    // label: "- Показаны не все варианты (есть еще " + n + " записей), уточните критерий поиска!"
                                    label: " ... Показаны не все варианты! Уточните критерий поиска!"
                                }
                            }

                            if (index > 16) return null;

                            //var lbl = item.code + " - " + item.name;
                            var lbl = item.name;
                            return {
                                label: lbl,
                                value: item.name,
                                id: item.id
                            }
                        }));
                    }
                });
            },
            delay: 250,
            minLength: 1,
            autoFill: true,
            cacheLength: 1,
            // autoFocus: true,

            select: function (event, ui) {
                if (ui.item.id) {
                    var set_id = $(this).parent().find('.ac_id');

                    //$('#set_id').val(ui.item.id);
                    set_id.val(ui.item.id);
                    $(this).val(ui.item.label);

                    let ac_status = $(this).parent().find('.ac_status');
                    ac_status.hide().removeClass("ac-fail");

                    $(this).addClass("ac-act");

                    set_id.change();  //для срабатывания слушателей за изменением этого поля
                }
                event.preventDefault();
            },
            search: function () {
                var set_id = $(this).parent().find('.ac_id');
                set_id.val('');
                $(this).removeClass("ac-fail").removeClass("ac-warn").addClass("ac-act");

                const ac_status = $(this).parent().find('.ac_status');
                ac_status.val("поиск...")
                    .removeClass("ac-fail").removeClass("ac-warn")
                    .addClass("ac-act").show();
            },
            response: function (event, ui) {
                $(this).removeClass("ac-act");
                ac_status = $(this).parent().find('.ac_status');
                if (ui.content.length == 0) {
                    ac_status.val('Варианты не найдены.').removeClass("ac-act").addClass("ac-fail");
                    $(this).addClass("ac-fail");
                } else if (ui.content.length > 15) {
                    ac_status.val('Показаны не все варианты! Уточните критерий')
                        .removeClass("ac-act").addClass("ac-warn");
                } else {
                    //console.log(ui.content);
                    ac_status.hide().val("");
                }
            }
        })
            .on('focus', function (event) {
                $(this).select();
            })
            .on('blur', function (event) {
                ac_id = $(this).parent().find('.ac_id');
                ac_status = $(this).parent().find('.ac_status');
                if ($(this).val().length == 0) {
                    ac_id.val('');
                    $(this).removeClass("ac-act").addClass("ac-fail");

                    ac_status.val('Укажите заказчика!').show()
                        .removeClass("ac-act").addClass("ac-fail");
                } else
                    ac_status.hide().val("");

                ac_id.change();  //для срабатывания слушателей за изменением этого поля
            })
            .data('ui-autocomplete')._renderItem = function (ul, item) {
            //thanks to Salman Arshad for icon and match highlighting code
            //http://salman-w.blogspot.ca/2013/12/jquery-ui-autocomplete-examples.html
            //!подсвечивает только если поиск производится по одному слову.
            var $div = $("<div></div>");
            if (item.icon) {
                $("<img class='m-icon'>").attr("src", "/images/" + item.icon).appendTo($div);
            } else {
                $("<span class='x-icon'></span>").appendTo($div);
            }
            var mName = $("<span class='m-name'></span>").html(item.label).appendTo($div),
                searchText = $.trim(this.term).toLowerCase(),
                currentNode = mName.get(0).firstChild,
                matchIndex, newTextNode, newSpanNode;

            while ((matchIndex = currentNode.data.toLowerCase().indexOf(searchText)) >= 0) {
                newTextNode = currentNode.splitText(matchIndex);
                currentNode = newTextNode.splitText(searchText.length);
                newSpanNode = document.createElement("span");
                newSpanNode.className = "highlight";
                currentNode.parentNode.insertBefore(newSpanNode, currentNode);
                newSpanNode.appendChild(newTextNode);
            }
            return $("<li></li>").append($div).appendTo(ul);
        };
    }

    if (1 == 0) {
        $("#orgname111").autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: "/orgs/autocomplete/search",
                    dataType: "json",
                    data: {
                        q: request.term,
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (data) {
                        //console.log(data);
                        response($.map(data, function (item, index) {
                            if (index == 16) {
                                var n = data.length - 16;
                                return {
                                    // label: "- Показаны не все варианты (есть еще " + n + " записей), уточните критерий поиска!"
                                    label: " ... Показаны не все варианты! Уточните критерий поиска!"
                                }
                            }

                            if (index > 16) return null;

                            var lbl = item.name + " ( ИНН: " + (item.inn ? item.inn : '-') + ", КПП: " + (item.kpp ? item.kpp : '-') + ")";
                            return {
                                label: lbl,
                                value: item.name,
                                id: item.id
                            }
                        }));
                    }
                });
            },
            delay: 250,
            minLength: 1,
            autoFill: true,
            cacheLength: 1,
            // autoFocus: true,

            select: function (event, ui) {
                if (ui.item.id) {
                    $('#orgid').val(ui.item.id);
                    // $(this).val(ui.item.value);
                    $(this).val(ui.item.label);

                    // $("#ac_orgid").hide().val("ok").removeClass("ac-fail");
                    $("#ac_orgid").hide().removeClass("ac-fail");
                    $(this).addClass("ac-act");

                    $("#orgid").change();  //для срабатывания заполнения договоров
                }
                event.preventDefault();
            },
            search: function () {
                $('#orgid').val('');
                $(this).removeClass("ac-fail").removeClass("ac-warn").addClass("ac-act");
                $("#ac_orgid").val("поиск...")
                    .removeClass("ac-fail").removeClass("ac-warn")
                    .addClass("ac-act").show();
            },
            response: function (event, ui) {
                $(this).removeClass("ac-act");
                if (ui.content.length == 0) {
                    $('#ac_orgid').val('Варианты не найдены.')
                        .removeClass("ac-act").addClass("ac-fail");
                    $(this).addClass("ac-fail");
                } else if (ui.content.length > 15) {
                    $('#ac_orgid').val('Показаны не все варианты! Уточните критерий')
                        .removeClass("ac-act").addClass("ac-warn");
                } else {
                    //console.log(ui.content);
                    $("#ac_orgid").hide().val("");
                }
            }
        })
            .on('focus', function (event) {
                $(this).select();
            })
            .on('blur', function (event) {
                if ($(this).val().length == 0) {
                    $('#orgid').val('');
                    $('#orgname').removeClass("ac-act").addClass("ac-fail");
                    $('#ac_orgid').val('Укажите организацию!').show()
                        .removeClass("ac-act").addClass("ac-fail");
                } else
                    $('#ac_orgid').hide().val("");
            })
            .data('ui-autocomplete')._renderItem = function (ul, item) {
            //thanks to Salman Arshad for icon and match highlighting code
            //http://salman-w.blogspot.ca/2013/12/jquery-ui-autocomplete-examples.html
            //!подсвечивает только если поиск производится по одному слову.
            var $div = $("<div></div>");
            if (item.icon) {
                $("<img class='m-icon'>").attr("src", "/images/" + item.icon).appendTo($div);
            } else {
                $("<span class='x-icon'></span>").appendTo($div);
            }
            var mName = $("<span class='m-name'></span>").html(item.label).appendTo($div),
                searchText = $.trim(this.term).toLowerCase(),
                currentNode = mName.get(0).firstChild,
                matchIndex, newTextNode, newSpanNode;

            while ((matchIndex = currentNode.data.toLowerCase().indexOf(searchText)) >= 0) {
                newTextNode = currentNode.splitText(matchIndex);
                currentNode = newTextNode.splitText(searchText.length);
                newSpanNode = document.createElement("span");
                newSpanNode.className = "highlight";
                currentNode.parentNode.insertBefore(newSpanNode, currentNode);
                newSpanNode.appendChild(newTextNode);
            }
            return $("<li></li>").append($div).appendTo(ul);
        };
    }

    function contracts_rfr() {
        //console.log($("#ownorgid").val(), $("#orgid").val());

        var selector = "#contractid";
        var save_ID = $(selector).val();
        //console.log('save_ID='+save_ID)

        $(selector + " > option").remove();

        if ($("#ownorgid").val() && $("#orgid").val()) {

            $.get("/api/contracts/for_", {
                    between_orgs: [$("#ownorgid").val(), $("#orgid").val()]
                    //, buildobjid: $("#buildobjid").val()
                },
                function (data) {
                    //console.log(data);

                    $(selector).append($("<option>"));
                    $.each(data.contracts, function (index, value) {
                        $(selector).append($("<option>").attr("value", index).append(value))
                    });

                    $(selector).val(save_ID);
                    //console.log('restored save_ID=',$(selector).val())
                }
            );
        }
    }


    $("#ownorgid, #orgid").change(function () {
        contracts_rfr();
    });


    $("input[name=docdate]").change(function () {
        //Если дата оплаты еще не определена, то установим ее в соответствии с датой документа
        if (!$("#paydate").val())
            $("#paydate").val($(this).val());

        if ($(this).val())
            $("#paydate").attr('min', $(this).val());
        else
            $("#paydate").attr('min', $(this).attr('min'));

    });


    //--- при загрузке ------------------------------------------------------------------------------------------------

    //покраска в зеленый всех автозаполняемых названий с установленными id в соответств. полях ---
    $.each($(".ac_name"), function (key, value) {
        //console.log( key + ": " + $(value).val() );
        if ($(this).parent().find('.ac_id').val()) {
            $(value).addClass("ac-act");

            //отобразить ссылку на карточку редактирования объекта справочника
            const id_lnk = $(this).parent().find('.id_lnk');
            id_lnk.hide()
            if (id_lnk && id_lnk.data('id') && id_lnk.data('obj'))
                id_lnk.show() //отобразить ссылку на карточку редактирования объекта справочника

        } else {
            $(this).parent().find('.id_lnk').hide()
        }
    });

    //-----------------------------------------------------------------------------------------------------------------

});
