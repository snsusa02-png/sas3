$(document).ready(function () {

    $("#opertypeid").focus(function () {
        $(this).data('val', $(this).val());
    });


    $("#cardid").change(function () {

        if ($("#cardid").val()) {

            //получить данные по топливной карте--------------------------------
            $.get("/api/fuelcards/data_for_card",
                {
                    cardid: $("#cardid").val()
                },
                function (data) {
                    // console.log(data);
                    // console.log(data.data.ref_machineid);
                    $("#machineid").val(data.data.ref_machineid);
                    $("#machine_name").val(data.data.ref_machine_name);
                    $("#suporgid").val(data.data.suporgid);
                    $("#suporgid").change();  //для срабатывания слушателей за изменением этого поля
                }
            )
            //-----------------------------------------------------------------
        }
    });

    $("#fuel_qty").change(function () {
        // console.log(this.value)

        const qty = parseFloat(this.value);
        const price = parseFloat($("#fuel_price").val());

        if (!(isNaN(qty) || isNaN(price))) {
            sum = Math.round(100 * qty * price) / 100;
        }
        $("#paysum").val(sum);
    });

    $("#fuel_price").change(function () {
        // console.log(this.value)
        //$("#unload_price").val(this.value)

        const price = parseFloat(this.value);
        const qty = parseFloat($("#fuel_qty").val());

        if (!(isNaN(qty) || isNaN(price))) {
            sum = Math.round(100 * qty * price) / 100;
        }
        $("#paysum").val(sum);
    });

    $("#paysum").change(function () {
        // console.log(this.value)

        const sum = parseFloat(this.value);
        const qty = parseFloat($("#fuel_qty").val());

        if (!(isNaN(sum) || isNaN(qty)) && qty > 0) {
            price = Math.round(1000000 * sum / qty) / 1000000;
        }
        $("#fuel_price").val(price);
    });


    //на изменение ID заполняемого по автокомплиту
    $(".ac_id").change(function () {
        //отработаем скрытие/открытие кнопки со ссылкой на выбранный элемент спр-ка в зависимости от наличия значения в id
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


    if ($(".driver_name").length > 0) {
        $(".driver_name").autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: "/orgstaff/autocomplete/search",
                    dataType: "json",
                    data: {
                        q: request.term,
                        flagid: 187,    //признак водителя
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

                            var lbl = item.name + " (" + item.orgname + ": " + item.postname + ")";
                            return {
                                label: lbl,
                                value: item.name,
                                id: item.id,
                                orgid: item.orgid,
                                orgname: item.orgname
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
                    // console.log($(this).parent().find('.staffid').val());
                    var ac_id = $(this).parent().find('.ac_id');
                    ac_id.val(ui.item.id);
                    // var orgid = $(this).parent().find('.orgid');
                    // orgid.val(ui.item.orgid);
                    $(this).val(ui.item.label);

                    var ac_status = $(this).parent().find('.ac_status');
                    ac_status.hide().removeClass("ac-fail");
                    $(this).addClass("ac-act");

                    ac_id.change();  //для срабатывания слушателей за изменением этого поля
                }
                event.preventDefault();

            },
            search: function () {
                $(this).parent().find('.staffid').val('');

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
                ac_id = $(this).parent().find('.staffid');
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
    }

    if ($(".machine_name").length > 0) {
        $(".machine_name").autocomplete({
            source: function (request, response) {

                $.ajax({
                    url: "/api/machines/for_ac",
                    dataType: "json",
                    data: {
                        //q: request.term,
                        s_name: request.term,
                        opertypeid: $("#opertypeid").val(),
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

                            var lbl = item.name + " (" + item.regnum + ". " + item.orgname + ")";
                            return {
                                label: lbl,
                                value: item.name,
                                id: item.id,
                                orgid: item.orgid,
                                orgname: item.orgname,
                                in_gk: item.in_gk
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
                    var ac_id = $(this).parent().find('.ac_id');
                    ac_id.val(ui.item.id);

                    var orgid = $(this).parent().find('.orgid');
                    orgid.val(ui.item.orgid);
                    $(this).val(ui.item.label);

                    // $("#ac_orgid").hide().val("ok").removeClass("ac-fail");
                    var ac_status = $(this).parent().find('.ac_status');
                    ac_status.hide().removeClass("ac-fail");
                    $(this).addClass("ac-act");

                    //console.log(ui.item.in_gk);
                    $("#in_gk").val(ui.item.in_gk);
                    //if (ui.item.in_gk == 1) {
                    if ($("#in_gk").val() == 1) {
                        $(".driver_info").show();
                        $(".salary_info").show();
                        $("#driverid").val('');     //зачищаем 160

                    } else {
                        $(".driver_info").hide();
                        $(".salary_info").hide();
                        $("#driverid").val(160);    //160 - "-неизвестный--водитель-"
                        $("#raid_salary").val(0);
                    }

                    ac_id.change();  //для срабатывания слушателей за изменением этого поля

                }
                event.preventDefault();
            },
            search: function () {
                $(this).parent().find('.machineid').val('');

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

                    $(this).parent().find('.ac_name')
                        .removeClass("ac-act")
                        .addClass("ac-fail");

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

    //2026-03-15
    $("#paydate").change(function () {
        $("#suporgid").change();
    });

    $("#suporgid").change(function () {
        //alert($("#suporgid").val());

        $("#ri_sup_priceid > option").remove();
        //console.log($("#suporgid").val(), $("#paydate").val())
        $.get("/api/ri_sup_prices/items", {orgid: $("#suporgid").val(), paydate: $("#paydate").val()},
            function (data) {
                // console.log(data);

                $("#ri_sup_priceid > option").remove()
                $("#ri_sup_priceid").append($("<option>"))
                $.each(data.items, function (index, value) {
                        $("#ri_sup_priceid").append($("<option>").attr("value", index).append(value))
                    }
                );
                 console.log($('#ri_sup_priceid option').length);
                if ($('#ri_sup_priceid option').length > 1) {
                    $('#ri_sup_priceid').prop('required', true);
                    $('#ri_sup_priceid').addClass('required');
                    $('#ri_sup_price_lbl').addClass('required');
                    $('#ri_sup_price_div').show();
                    $('#refitem_div').hide();
                } else {
                    $('#ri_sup_priceid').val(null);
                    $('#ri_sup_priceid').prop('required', false);
                    $('#ri_sup_priceid').removeClass('required');
                    $('#ri_sup_price_lbl').removeClass('required');
                    $('#ri_sup_price_div').hide();
                    $('#refitem_div').show();
                    $('#fuel_price').prop('readonly', false);

                }
            }
        )

        //alert($('#ri_sup_priceid option').length, cnt);
        // console.log(cnt);
        // // if ($('#ri_sup_priceid option').length >= 1) {
        // if (cnt >= 1) {
        //     $('#ri_sup_priceid').prop('required', true);
        //     $('#ri_sup_priceid').addClass('required');
        // } else {
        //     $('#ri_sup_priceid').prop('required', false);
        //     $('#ri_sup_priceid').removeClass('required');
        //
        // }
    });

    $("#ri_sup_priceid").change(function () {
        // alert($("#ri_sup_priceid").val());

        //console.log($("#suporgid").val(), $("#paydate").val())
        $id = $("#ri_sup_priceid").val();
        if ($id == '')
            // Remove readonly
            $('#fuel_price').prop('readonly', false);

        else {
            $.get("/api/ri_sup_prices/item", {id: $id},
                function (data) {
                    //console.log(data);
                    console.log(data.price);

                    $("#refitmid").val(data.refitmid);
                    $("#fuel_price").val(data.price);
                    $("#fuel_price").change();  //для срабатывания слушателей за изменением этого поля
                }
            )
            // Set readonly
            $('#fuel_price').prop('readonly', true);
        }

    });


    if ($(".ac_suporg_name").length > 0) {

        //Поиск контрагента
        $(".ac_suporg_name").autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: "/api/orgs/for_ac",
                    dataType: "json",
                    data: {
                        name_inn: request.term,
                        //flagtypeid: 13, //13 - признак поставщика
                        active: 1,
                        in_ri_sup_prices: 1,    //есть записи о товарах/ценах
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

    if ($("#load_cargo_name").length > 0) {
        $("#load_cargo_name").autocomplete({
            source: function (request, response) {
                $.ajax({
                    //url: "/refitems/autocomplete/search",
                    url: "/api/refitems/for_ac",
                    dataType: "json",
                    data: {
                        name: request.term,
                        suporgid: $("#suporgid").val(),
                        load_placeid: $("#load_placeid").val(),
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

                    $('#load_price').val(ui.item.price).prop('readonly', true);

                    //установим допустимую ЕИ ---------------------------------------------
                    //$('#qty_unittypeid').val(ui.item.unittypeid).prop('readonly', true);
                    $("#qty_unittypeid > option").remove();
                    $("#qty_unittypeid").append($("<option>").attr("value", ui.item.unittypeid).append(ui.item.unit))
                    $("#load_qty_unit").html(ui.item.unit)
                    //$("#unload_qty_unit").html(ui.item.unit)
                    //---------------------------------------------------------------------

                    //$('#code1s').val(ui.item.code1s);
                    //$('#code').val(ui.item.id);
                    //$('#unit').html(ui.item.unit);
                    //$('#unit_html').html(ui.item.unit);
                    //$('#specinfo').html(ui.item.specinfo);

                    $(this).val(ui.item.value);

                    ac_status = $(this).parent().find('.ac_status');
                    ac_status.hide().val("").removeClass("ac-fail");

                    ac_id.change();  //для срабатывания слушателей за изменением этого поля
                    $('#load_price').change();

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

                    $('#load_price').prop('readonly', false);

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
                    $('#load_price').prop('readonly', false);
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
    }

    if ($("#unload_cargo_name").length > 0) {

        $("#unload_cargo_name").autocomplete({
            source: function (request, response) {
                $.ajax({
                    //url: "/refitems/autocomplete/search",
                    url: "/api/refitems/for_ac",
                    dataType: "json",
                    data: {
                        name: request.term,
                        // suporgid: $("#suporgid").val(),
                        // load_placeid: $("#load_placeid").val(),
                        // price_on_date: $("#wrkdate").val(),
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

                    $('#unload_price').val(ui.item.price)   //.prop('readonly', true);

                    //установим допустимую ЕИ ---------------------------------------------
                    //$("#qty_unittypeid > option").remove();
                    //$("#qty_unittypeid").append($("<option>").attr("value", ui.item.unittypeid).append(ui.item.unit))
                    $("#unload_qty_unit").html(ui.item.unit)
                    //---------------------------------------------------------------------

                    //$('#code1s').val(ui.item.code1s);
                    //$('#code').val(ui.item.id);
                    //$('#unit').html(ui.item.unit);
                    //$('#unit_html').html(ui.item.unit);
                    //$('#specinfo').html(ui.item.specinfo);

                    $(this).val(ui.item.value);

                    ac_status = $(this).parent().find('.ac_status');
                    ac_status.hide().val("").removeClass("ac-fail");

                    ac_id.change();  //для срабатывания слушателей за изменением этого поля
                    $('#unload_price').change();

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

                    $('#load_price').prop('readonly', false);

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
                    $('#load_price').prop('readonly', false);
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
    }

    if ($(".load_placename").length > 0) {
        $(".load_placename").autocomplete({
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

    if ($("#unload_placename").length > 0) {
        $(".unload_placename").autocomplete({
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


    //при загрузке -------------------------------------------------


    //покраска в зеленый всех автозаполняемых названий с установленными id в соответств. полях
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
