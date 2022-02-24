$(document).ready(function () {

    function sale_dir_change() {

        const sale_dir = $("#sale_dir").val();

        if (sale_dir == -1) {
            //Покупка
            $("#lbl_sup").html('Поставщик')
            $("#lbl_org").html('Покупатель')
            $('#itm_price').prop('readonly', true);

            $('.raid_info').hide();
            $('#raid_qty').prop('required', false);
            $('#lbl_raid_qty').removeClass('required');

        } else if (sale_dir == 0) {
            //внутр. операция
            $("#lbl_sup").html('Отправитель')
            $("#lbl_org").html('Получатель')
            $("#paytypeid").val(2)

            $('.raid_info').hide();
            $('#raid_qty').prop('required', false);
            $('#lbl_raid_qty').removeClass('required');

        } else if (sale_dir == +1) {
            $("#lbl_sup").html('Исполнитель')
            $("#lbl_org").html('Заказчик')
            $('#itm_price').prop('readonly', false);

            $('.raid_info').show();
            $('#raid_qty').prop('required', true);
            $('#lbl_raid_qty').addClass('required');

            // console.log($("#suporgid").data('gk'));
            // if ($("#suporgid").data('gk') == 0) {
            //     $("#suporgid").val('');
            //     $("#suporg_name").val('');
            // }

        }
    }

    $("#sale_dir").change(function () {
        sale_dir_change()
    });

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


    function recalc_itmsum() {
        const qty = parseFloat($("#itm_qty").val()) ?? 0;
        const price = parseFloat($("#itm_price").val()) ?? 0;
        const sum = Math.round(100 * qty * price) / 100;
        $("#itm_sum").val(sum);
        //console.log(sum)
    }

    $("#itm_qty, #itm_price").change(function () {
        recalc_itmsum();
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


    if ($(".ac_suporg_name").length > 0) {

        //Поиск контрагента
        $(".ac_suporg_name").autocomplete({
            source: function (request, response) {
                var in_ri_sup_prices = null
                var ft = null
                if ($("#sale_dir").val() == 0 || $("#sale_dir").val() == 1)
                    ft = 12
                else
                    in_ri_sup_prices = 1


                $.ajax({
                    url: "/api/orgs/for_ac",
                    dataType: "json",
                    data: {
                        name_inn: request.term,
                        //flagtypeid: 13, //13 - признак поставщика
                        flagtypeid: ft,
                        active: 1,
                        in_ri_sup_prices: in_ri_sup_prices,    //есть записи о товарах/ценах
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

                            //var lbl = item.code  + " - " +item.name;
                            var lbl = item.name + " (" + (item.inn ?? '-') + ")";
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
                    .removeClass("ac-fail")
                    .removeClass("ac-warn")
                    .addClass("ac-act")
                    .show();
            },
            response: function (event, ui) {
                $(this).removeClass("ac-act");
                ac_status = $(this).parent().find('.ac_status');
                if (ui.content.length == 0) {
                    ac_status.val('Варианты не найдены.')
                        .removeClass("ac-act")
                        .addClass("ac-fail")
                        .attr('title', 'Укажите поставщика!');
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

                    ac_status.val('Укажите поставщика!')
                        .attr('title', 'Укажите поставщика!')
                        .show()
                        .removeClass("ac-act").addClass("ac-fail").hide(1000);
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


    $("#itm_name").autocomplete({
        source: function (request, response) {
            $.ajax({
                //url: "/refitems/autocomplete/search",
                url: "/api/refitems/for_ac",
                dataType: "json",
                data: {
                    name: request.term,
                    suporgid: $("#suporgid").val(),
                    load_placeid: $("#sup_placeid").val(),
                    price_on_date: $("#wrkdate").val(),
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (data) {
                    //
                    // console.log(n);
                    response($.map(data, function (item, index) {
                        if (index == 16) {
                            var n = data.length - 16;
                            return {
                                // label: "- Показаны не все варианты (есть еще " + n + " записей), уточните критерий поиска!"
                                label: " ... Показаны не все варианты! Уточните критерий поиска!"
                            }
                        }

                        if (index > 16) return null;

                        //var lbl = item.code + " | " + item.name;
                        var lbl = item.name;
                        //lbl = lbl + " (категория: " + item.itname;
                        //if (item.specinfo) lbl = lbl + "; " + item.specinfo;
                        lbl = lbl + "(цена: " + item.price + " &#x20bd; / " + item.unit + ")";
                        return {
                            label: lbl,
                            value: item.name,
                            price: item.price,
                            code1s: item.code,
                            icon: item.photourl,
                            specinfo: item.specinfo,
                            unit: item.unit,
                            unittypeid: item.unittypeid,
                            decimal_dgts: item.decimal_dgts,
                            id: item.id
                        }
                    }));
                }
            });
        },
        delay: 300,
        minLength: 2,
        autoFill: true,
        cacheLength: 1,
        autoFocus: true,

        select: function (event, ui) {
            if (ui.item.id) {
                ac_id = $(this).parent().find('.ac_id')
                ac_id.val(ui.item.id);

                if (ui.item.price)
                    $('#itm_price').val(ui.item.price).prop('readonly', true);
                else
                    $('#itm_price').val(ui.item.price).prop('readonly', false);

                //установим допустимую ЕИ ---------------------------------------------
                //$('#qty_unittypeid').val(ui.item.unittypeid).prop('readonly', true);
                $("#qty_unittypeid > option").remove();
                $("#qty_unittypeid").append($("<option>").attr("value", ui.item.unittypeid).append(ui.item.unit))
                $("#load_qty_unit").html(ui.item.unit)
                //$("#unload_qty_unit").html(ui.item.unit)
                //---------------------------------------------------------------------

                $('#itm_qty').prop('step', 1 / 10 ** ui.item.decimal_dgts);
                //console.log('11111' + ui.item.decimal_dgts);


                //$('#code1s').val(ui.item.code1s);
                //$('#code').val(ui.item.id);
                //$('#unit').html(ui.item.unit);
                //$('#unit_html').html(ui.item.unit);
                //$('#specinfo').html(ui.item.specinfo);

                $(this).val(ui.item.value);

                ac_status = $(this).parent().find('.ac_status');
                ac_status.hide().val("").removeClass("ac-fail");

                ac_id.change();  //для срабатывания слушателей за изменением этого поля
                $('#itm_price').change();

            } else
                event.preventDefault();
        },
        search: function () {
            $(this).removeClass("ac-fail").removeClass("ac-warn").addClass("ac-act");
            ac_status = $(this).parent().find('.ac_status');
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

                $('#itm_price').prop('readonly', false);

            } else if (ui.content.length > 15) {
                ac_status.val('Показаны не все варианты! Уточните критерий')
                    .removeClass("ac-act").addClass("ac-fail");
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
            console.log($(this).val().length);

            if ($(this).val().length == 0) {
                ac_id.val('');
                $(this).removeClass("ac-act").addClass("ac-fail");

                ac_status.val('Укажите товар!').show()
                    .removeClass("ac-act").addClass("ac-fail");

                //specifics -----------------
                $('#itm_price').prop('readonly', false);
                $('#unittypeid').prop('readonly', false);

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


    if ($(".sup_placename").length > 0) {
        $(".sup_placename").autocomplete({
            source: function (request, response) {

                $.ajax({
                    //url: "/api/places/for_ac",
                    url: "/api/org_places/for_ac",
                    dataType: "json",
                    data: {
                        name_address: request.term,
                        orgid: $("#suporid").val(),
                        placetypeid: 2,
                        active: 1,
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

                            //var lbl = item.name + " (" + item.code + ")";

                            //var lbl = item.name + " (" + (item.address ?? '-') + ")";
                            var lbl = item.name;
                            if (item.address)
                                lbl += " (" + item.address + ")";

                            return {
                                label: lbl,
                                value: item.name,
                                id: item.id,
                                //orgid: item.orgid,
                                //orgname: item.orgname
                            }
                        }));
                    }
                });
            },
            delay: 250,
            minLength: 1,
            autoFill: true,
            cacheLength: 10,
            autoFocus: true,

            select: function (event, ui) {
                if (ui.item.id) {
                    const ac_id = $(this).parent().find('.ac_id');
                    ac_id.val(ui.item.id);
                    $(this).val(ui.item.label);

                    var ac_status = $(this).parent().find('.ac_status');
                    ac_status.hide().removeClass("ac-fail");
                    $(this).addClass("ac-act");

                    ac_id.change();  //для срабатывания слушателей за изменением этого поля
                }
                event.preventDefault();
            },
            search: function () {
                $(this).parent().find('.ac_id').val('');

                $(this).removeClass("ac-fail").removeClass("ac-warn").addClass("ac-act");

                var ac_status = $(this).parent().find('.ac_status');
                ac_status.val("поиск...")
                    .removeClass("ac-fail")
                    .removeClass("ac-warn")
                    .addClass("ac-act")
                    .show();
            },
            response: function (event, ui) {
                var ac_status = $(this).parent().find('.ac_status');
                $(this).removeClass("ac-act");
                if (ui.content.length == 0) {
                    ac_status.val("Варианты не найдены.")
                        .removeClass("ac-act")
                        .addClass("ac-fail");
                    $(this).addClass("ac-fail");
                } else if (ui.content.length > 15) {
                    ac_status.val('Показаны не все варианты! Уточните критерий')
                        .removeClass("ac-act")
                        .addClass("ac-warn");
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
                if ($(this).val().length == 0) {
                    ac_id.val('');

                    //$(this).parent().find('.machine_name')
                    $(this).removeClass("ac-act").addClass("ac-fail");

                    var ac_status = $(this).parent().find('.ac_status');
                    ac_status.val('Укажите рег. номер техники!')
                        .show()
                        .removeClass("ac-act")
                        .addClass("ac-fail");
                } else
                    $(this).parent().find('.ac_status').hide().val("");

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

    if ($(".org_placename").length > 0) {
        $(".org_placename").autocomplete({
            source: function (request, response) {

                $.ajax({
                    //url: "/api/places/for_ac",
                    url: "/api/org_places/for_ac",
                    dataType: "json",
                    data: {
                        s_name: request.term,
                        orgid: $("#orgid").val(),
                        s_for_unload: 1,
                        s_active: 1,
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

                            var lbl = item.name;
                            if (item.address)
                                lbl += " (" + item.address + ")";

                            //var lbl = item.name;
                            return {
                                label: lbl,
                                value: item.name,
                                id: item.id,
                            }
                        }));
                    }
                });
            },
            delay: 250,
            minLength: 1,
            autoFill: true,
            cacheLength: 10,
            autoFocus: true,

            select: function (event, ui) {
                if (ui.item.id) {
                    $(this).val(ui.item.label);
                    const ac_id = $(this).parent().find('.ac_id');
                    ac_id.val(ui.item.id);

                    var ac_status = $(this).parent().find('.ac_status');
                    ac_status.hide().removeClass("ac-fail");
                    $(this).addClass("ac-act");

                    ac_id.change();  //для срабатывания слушателей за изменением этого поля
                }
                event.preventDefault();
            },
            search: function () {
                $(this).parent().find('.ac_id').val('');

                $(this).removeClass("ac-fail").removeClass("ac-warn").addClass("ac-act");

                var ac_status = $(this).parent().find('.ac_status');
                ac_status.val("поиск...")
                    .removeClass("ac-fail")
                    .removeClass("ac-warn")
                    .addClass("ac-act")
                    .show();
            },
            response: function (event, ui) {
                var ac_status = $(this).parent().find('.ac_status');
                $(this).removeClass("ac-act");
                if (ui.content.length == 0) {
                    ac_status.val("Варианты не найдены.")
                        .removeClass("ac-act")
                        .addClass("ac-fail");
                    $(this).addClass("ac-fail");
                } else if (ui.content.length > 15) {
                    ac_status.val('Показаны не все варианты! Уточните критерий')
                        .removeClass("ac-act")
                        .addClass("ac-warn");
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
                if ($(this).val().length == 0) {
                    $(this).parent().find('.ac_id').val('');

                    var ac_status = $(this).parent().find('.ac_status');
                    ac_status.val('Укажите рег. номер техники!')
                        .show()
                        .removeClass("ac-act")
                        .addClass("ac-fail");
                } else
                    $(this).parent().find('.ac_status').hide().val("");

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

    function sup_places_rfr() {
        //console.log($("#suporgid").val());

        var selector = "#sup_placeid";
        var save_ID = $(selector).val();
        //console.log('save_ID='+save_ID)

        $(selector + " > option").remove();

        if ($("#suporgid").val()) {

            $.get("/api/org_places/for_", {
                    s_orgid: $("#suporgid").val()
                },
                function (data) {
                    //console.log(data);

                    $(selector).append($("<option>"));
                    $.each(data.org_places, function (index, value) {
                        $(selector).append($("<option>").attr("value", index).append(value))
                    });

                    $(selector).val(save_ID);
                    //console.log('restored save_ID=',$(selector).val())
                    //Если вариант всего один - попробуем сразу его выбрать. 2 - потому что есть еще placeholder
                    if ($(selector + ' option').length == 2) {
                        $(selector).prop('selectedIndex', 1);
                    }
                }
            );
        }
    }

    $("#suporgid").change(function () {
        sup_places_rfr();
    });


    function unload_places_rfr() {
        //console.log($("#suporgid").val());

        var selector = "#org_placeid";
        var save_ID = $(selector).val();
        //console.log('save_ID='+save_ID)

        $(selector + " > option").remove();

        if ($("#orgid").val()) {

            $.get("/api/org_places/for_", {
                    s_orgid: $("#orgid").val()
                },
                function (data) {
                    //console.log(data);

                    $(selector).append($("<option>"));
                    $.each(data.org_places, function (index, value) {
                        $(selector).append($("<option>").attr("value", index).append(value))
                    });

                    $(selector).val(save_ID);
                    //console.log('restored save_ID=',$(selector).val())
                    //Если вариант всего один - попробуем сразу его выбрать. 2 - потому что есть еще placeholder
                    if ($(selector + ' option').length == 2) {
                        $(selector).prop('selectedIndex', 1);
                    }
                }
            );
        }
    }

    $("#orgid").change(function () {
        unload_places_rfr();
    });

    if ($("#org_placename").length > 0) {
        $(".org_placename").autocomplete({
            source: function (request, response) {

                $.ajax({
                    //url: "/api/places/for_ac",
                    url: "/api/org_places/for_ac",
                    dataType: "json",
                    data: {
                        s_name: request.term,
                        orgid: $("#orgid").val(),
                        s_for_unload: 1,
                        s_active: 1,
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

                            var lbl = item.name;
                            if (item.address)
                                lbl += " (" + item.address + ")";

                            //var lbl = item.name;
                            return {
                                label: lbl,
                                value: item.name,
                                id: item.id,
                            }
                        }));
                    }
                });
            },
            delay: 250,
            minLength: 1,
            autoFill: true,
            cacheLength: 10,
            autoFocus: true,

            select: function (event, ui) {
                if (ui.item.id) {
                    $(this).val(ui.item.label);
                    const ac_id = $(this).parent().find('.ac_id');
                    ac_id.val(ui.item.id);

                    var ac_status = $(this).parent().find('.ac_status');
                    ac_status.hide().removeClass("ac-fail");
                    $(this).addClass("ac-act");

                    ac_id.change();  //для срабатывания слушателей за изменением этого поля
                }
                event.preventDefault();
            },
            search: function () {
                $(this).parent().find('.ac_id').val('');

                $(this).removeClass("ac-fail").removeClass("ac-warn").addClass("ac-act");

                var ac_status = $(this).parent().find('.ac_status');
                ac_status.val("поиск...")
                    .removeClass("ac-fail")
                    .removeClass("ac-warn")
                    .addClass("ac-act")
                    .show();
            },
            response: function (event, ui) {
                var ac_status = $(this).parent().find('.ac_status');
                $(this).removeClass("ac-act");
                if (ui.content.length == 0) {
                    ac_status.val("Варианты не найдены.")
                        .removeClass("ac-act")
                        .addClass("ac-fail");
                    $(this).addClass("ac-fail");
                } else if (ui.content.length > 15) {
                    ac_status.val('Показаны не все варианты! Уточните критерий')
                        .removeClass("ac-act")
                        .addClass("ac-warn");
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
                if ($(this).val().length == 0) {
                    $(this).parent().find('.ac_id').val('');

                    var ac_status = $(this).parent().find('.ac_status');
                    ac_status.val('Укажите данные!')
                        .show()
                        .removeClass("ac-act")
                        .addClass("ac-fail");
                } else
                    $(this).parent().find('.ac_status').hide().val("");

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


    function contracts_rfr() {
        console.log($("#suporgid").val(), $("#orgid").val());

        var selector = "#contractid";
        var save_ID = $(selector).val();
        //console.log('save_ID='+save_ID)

        $(selector + " > option").remove();

        if ($("#suporgid").val() && $("#orgid").val()) {

            $.get("/api/contracts/for_", {
                    between_orgs: [$("#suporgid").val(), $("#orgid").val()]
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


    $("#suporgid, #orgid").change(function () {
        contracts_rfr();
    });



    $(".disp_name").autocomplete({
        source: function (request, response) {
            $.ajax({
                //url: "/users/autocomplete/search",
                url: "/orgstaff/autocomplete/search",
                dataType: "json",
                data: {
                    q: request.term,
                    flagid: 188,    //Признак Диспетчера
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

                        var lbl = item.name;    // + " (" + item.orgname + ": " + item.postname + ")";
                        return {
                            label: lbl,
                            value: item.name,
                            id: item.id
                            //orgid: item.orgid,
                            //orgname: item.orgname
                        }
                    }));
                }
            });
        },
        delay: 250,
        minLength: 2,
        autoFill: true,
        cacheLength: 10,
        autoFocus: true,

        select: function (event, ui) {
            if (ui.item.id) {
                // console.log($(this).parent().find('.userid').val());
                var ac_id = $(this).parent().find('.ac_id');
                ac_id.val(ui.item.id);
                //var orgid = $(this).parent().find('.orgid');
                //orgid.val(ui.item.orgid);
                $(this).val(ui.item.label);

                // $("#ac_orgid").hide().val("ok").removeClass("ac-fail");
                var ac_status = $(this).parent().find('.ac_status');
                ac_status.hide().removeClass("ac-fail");
                $(this).addClass("ac-act");

                ac_id.change();  //для срабатывания слушателей за изменением этого поля
            }
            event.preventDefault();
        },
        search: function () {
            //$(this).parent().find('.staffid').val('');
            $(this).parent().find('.ac_id').val('');

            $(this).removeClass("ac-fail").removeClass("ac-warn").addClass("ac-act");

            var ac_status = $(this).parent().find('.ac_status');
            ac_status.val("поиск...")
                .removeClass("ac-fail")
                .removeClass("ac-warn")
                .addClass("ac-act")
                .show();
        },
        response: function (event, ui) {
            var ac_status = $(this).parent().find('.ac_status');
            $(this).removeClass("ac-act");
            if (ui.content.length == 0) {
                ac_status.val("Варианты не найдены.")
                    .removeClass("ac-act")
                    .addClass("ac-fail");
                $(this).addClass("ac-fail");
            } else if (ui.content.length > 15) {
                ac_status.val('Показаны не все варианты! Уточните критерий')
                    .removeClass("ac-act")
                    .addClass("ac-warn");
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

            if ($(this).val().length == 0) {
                ac_id.val('');

                $(this).parent().find('.stfname')
                    .removeClass("ac-act")
                    .addClass("ac-fail");

                var ac_status = $(this).parent().find('.ac_status');
                ac_status.val('Укажите сотрудника!')
                    .show()
                    .removeClass("ac-act")
                    .addClass("ac-fail");
            } else
                $(this).parent().find('.ac_status').hide().val("");

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


    //при загрузке --------------------------------------------------------------------------

    sale_dir_change();


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
