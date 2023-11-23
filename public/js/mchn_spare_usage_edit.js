$(document).ready(function () {

    function calc_spare_sum() {
        var qty = parseFloat($("#qty").val());
        if (isNaN(qty)) {
            qty = 1;
            $("#qty").val(qty);
        }

        var price = parseFloat($("#price").val());
        if (isNaN(price)) {
            sum = parseFloat($("#spare_sum").val());
            if (isNaN(sum))
                sum = 0

            price = sum / qty;
            $("#price").val(price);
        }

        $("#spare_sum").val(qty * price);
    }

    function calc_price_qty() {

        var qty = parseFloat($("#qty").val());
        if (isNaN(qty)) {
            qty = 1;
            $("#qty").val(qty);
        }

        var price = parseFloat($("#price").val());
        if (isNaN(price))
            price = 0;

        var sum = parseFloat($("#spare_sum").val());
        if (isNaN(sum)) {
            sum = qty * price;
            $("#spare_sum").val(sum);
        }

        price = Math.round(100 * sum / qty) / 100;
        $("#price").val(price);
    }

    $("#qty").change(function () {
        //$(this).data('val', $(this).val());
        calc_spare_sum();
    });

    $("#price").change(function () {
        calc_spare_sum();
    });
    $("#spare_sum").change(function () {
        calc_price_qty();
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
