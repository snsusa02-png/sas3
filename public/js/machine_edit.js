$(document).ready(function () {

    $("#opertypeid").focus(function () {
        $(this).data('val', $(this).val());
    });

    $("#opertypeid").change(function () {

        const opertypeid = $(this).val();
        const pre_opertypeid = $(this).data("val");

        $(".ots_" + opertypeid).show()
        $(".oth_" + pre_opertypeid).show()
        $(".oth_" + opertypeid).hide()

        alert(pre_opertypeid + ' ' + opertypeid);
    });


    $("#qty_unittypeid").change(function () {
        //$(this).find('option:selected').text();
        $("#unload_qty_unit").html($(this).find('option:selected').text());
    });

    $("#itm_price").change(function () {
        //console.log(this.value)
        // $("#unload_price").val(this.value)
        // recalc_unloadsum()
    });

    $("#orgid").change(function () {
        $("#org_placeid").val('')
        $("#org_placename").val('')
    });


    //на изменение ID заполняемого по автокомплиту
    $(".ac_id").change(function () {
        //отработаем скрытие/открытие кнопки со ссылкой на выбраный элмент спр-ка в зависимости от наличия значения в id
        if ($(this).val()) {
            $(this).parent().find('.id_lnk').hide()
            const id_lnk = $(this).parent().find('.id_lnk');
            if (id_lnk && id_lnk.data('id') && id_lnk.data('obj'))
                $(this).parent().find('.id_lnk').show() //отобразить ссылку на карточку редактирования объекта справочника
        } else {
            $(this).parent().find('.id_lnk').hide()
        }
    });


    $('.id_lnk').click(function (e) {
        //переход в элемент справочника
        e.preventDefault();

        //console.log($(this).data('obj'));
        const chkfld_id = $(this).data('id');
        if (chkfld_id) {
            const id = $("#" + chkfld_id).val();
            const ref = $(this).data('obj');
            if (id && ref) {
                var url = "/" + ref + "/" + id + "/edit";
                window.open(url, '_blank');
            }
        }
    });


    if ($(".ac_org_name").length > 0) {

        //Поиск контрагента
        $(".ac_org_name").autocomplete({


            source: function (request, response) {
                var ft = null;  //flagtypeid

                $.ajax({
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
            autoFocus: true,

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

    function rfr_labels() {
        const sale_dir = $('#sale_dir').val();
        const producttypeid = $('#producttypeid').val();
//        console.log('sale_dir =' + sale_dir);
//        console.log('producttypeid =' + producttypeid);

        if (sale_dir == -1) {
            //Покупка
            $("#lbl_sup").html('Поставщик')
            $("#lbl_org").html('Покупатель')
            $('#itm_price').prop('readonly', true);

            $('.raid_info').hide();
            $('#raid_qty').prop('required', false);
            $('#lbl_raid_qty').removeClass('required');

            if (producttypeid == 1) {
                //Услуга
                $("#lbl_refitm").html('Услуга');
                $("#lbl_sup_place").html('Место');
                $("#lbl_sup_place").parent().removeClass('required')
                $("#lbl_org_place").html('Место');
            } else {
                //Товар
                $("#lbl_refitm").html('Товар');
                $("#lbl_sup_place").html('Место загрузки');
                $("#lbl_sup_place").parent().addClass('required')
                $("#lbl_org_place").html('Место выгрузки (не обязательно)');
            }

        } else if (sale_dir == 0) {
            //внутр. операция
            $("#lbl_sup").html('Отправитель')
            $("#lbl_org").html('Получатель')
            $("#paytypeid").val(2)

            $('.raid_info').hide();
            $('#raid_qty').prop('required', false);
            $('#lbl_raid_qty').removeClass('required');

            $("#lbl_refitm").html('Товар / Услуга');
            $("#lbl_sup_place").html('Место');
            $("#lbl_sup_place").parent().removeClass('required')
            $("#lbl_org_place").html('Место');
            $("#lbl_org_place").parent().removeClass('required')

        } else if (sale_dir == +1) {
            //Продажа

            $("#lbl_sup").html('Исполнитель')
            $("#lbl_org").html('Заказчик')
            $('#itm_price').prop('readonly', false);

            $('.raid_info').show();
            $('#raid_qty').prop('required', true);
            $('#lbl_raid_qty').addClass('required');

            if (producttypeid == 1) {
                //Услуга
                $("#lbl_refitm").html('Услуга');
                $("#lbl_sup_place").html('Место (не обязательно)');
                $("#lbl_sup_place").parent().removeClass('required')
                $("#lbl_org_place").html('Место предоставления услуги');
            } else {
                //Товар
                $("#lbl_refitm").html('Товар');
                $("#lbl_sup_place").html('Место загрузки');
                $("#lbl_sup_place").parent().removeClass('required')
                $("#lbl_org_place").html('Место выгрузки');
            }
        }



        if (sale_dir == 1) {

        } else if (sale_dir == -1) {
        } else {

        }
    }


    //при загрузке --------------------------------------------------------------------------

    rfr_labels();

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

});
