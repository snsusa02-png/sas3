$(document).ready(function () {

    $("*").change(function () {
        //скрываем передачу на оплату при любом изменении данных. Пусть сначала сохранят изменения
        $("#send2pay").hide();
    });

    $('[data-toggle="tooltip"]').tooltip()  //инициализация подсказок


    $("#ownorgid").change(function () {
        let ownorgid = $("#ownorgid").val();
        if (ownorgid)
            $("#ownorg_link").attr('href', '/orgs/' + ownorgid + '/edit').show();
        else
            $("#ownorg_link").attr('href', '#').hide();

    });

    $(".ri_name").autocomplete({
        source: function (request, response) {
            $.ajax({
                url: "/api/refitems/for_ac",
                dataType: "json",
                data: {
                    name: request.term,
                    active: 1,
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
                        //lbl = lbl + "(цена: " + item.price + " &#x20bd; / " + item.unit + ")";
                        lbl = lbl + "(ЕИ = "+ item.unit + ")";
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

                //$('#load_price').val(ui.item.price).prop('readonly', true);
                //$('#qty_unittypeid').val(ui.item.unittypeid).prop('readonly', true);
                //$('#code1s').val(ui.item.code1s);
                //$('#code').val(ui.item.id);
                $('#unit').html(ui.item.unit);
                //$('#unit_html').html(ui.item.unit);
                //$('#specinfo').html(ui.item.specinfo);

                $(this).val(ui.item.value);

                ac_status = $(this).parent().find('.ac_status');
                ac_status.hide().val("").removeClass("ac-fail");

                ac_id.change();  //для срабатывания слушателей за изменением этого поля
                //$('#load_price').change();

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


    if (!$('#orgname').prop('readonly')) {
        $("#orgname").autocomplete({
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

                    $("#new_org_link").hide();
                    $("#org_search_btn").hide();
                    $("#org_link").attr('href', '/orgs/' + ui.item.id + '/edit').show();

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
                //$("#org_link").attr('href', '#').hide();
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
                $("#org_search_btn").show();
                //$("#org_link").attr('href', '#').hide();
            })
            .on('blur', function (event) {
                if ($(this).val().length == 0) {
                    $('#orgid').val('');
                    $('#orgname').removeClass("ac-act").addClass("ac-fail");
                    $('#ac_orgid').val('Укажите организацию!').show()
                        .removeClass("ac-act").addClass("ac-fail");
                } else {
                    $('#ac_orgid').hide().val("");
                }
                if ($("#orgid").val()) {
                    $("#org_link").attr('href', '/orgs/' + $("#orgid").val() + '/edit').show();
                    $("#new_org_link").hide();
                } else {
                    $("#org_link").attr('href', '#').hide();
                    $("#new_org_link").show();
                }

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
        }
    }


    $(".doc_qty").change(function () {
        var par = $(this).parent();

        var k = parseFloat($(this).attr('k_d2r'));
        var doc_qty = parseFloat($(this).val());
        var get_qty = '';
        if (!isNaN(doc_qty)) {
            var decdgts = parseFloat($(this).attr('decdgts'));
            decdgts = (isNaN(decdgts)) ? 3 : decdgts;
            get_qty = (doc_qty / k).toFixed(decdgts);
        }
        $(".get_qty", $(par)).val(get_qty);
        recalc();
    });

    $(".get_qty").change(function () {
        var par = $(this).parent();
        var o_doc_qty = $(".doc_qty", $(par));

        const k = parseFloat($(o_doc_qty).attr('k_d2r'));
        var get_qty = parseFloat($(this).val());
        var doc_qty = '';
        if (!isNaN(get_qty)) {
            var decdgts = parseFloat($(this).attr('decdgts')) ?? 3;
            doc_qty = (get_qty * k).toFixed(decdgts);
        }
        $(o_doc_qty).val(doc_qty);
        recalc();
    });


    function recalc() {
        var par;
        var cnt, price;
        var totsum = 0, sum, j = 0;
        $(".get_qty").each(function (i, val) {
            price = parseFloat($(val).attr("ord_price"));
            qty = parseFloat($(val).val());
            sum = price * qty;
            if (!isNaN(sum))
                totsum += sum;

        });
        $("#totgetsum").html((totsum).toFixed(2))
        totsum += parseFloat($("#totextraexpsum").html());
        //console.log(totsum)

        $("#usedsum").val((totsum).toFixed(2))
        $("#totsum").html((totsum).toFixed(2))
    }


    function chkCategoryid() {
        //Скрытие / Открытие частей формы в зависимости от выбранной категории платежа
        // Тек. список категорий (!) Обновлять при добавлении
        var ctgs = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13];

        const ctg = $("#categoryid").val(); //выбранная категория

        //все остальные - скрываем / показываем от обратного!
        ctgs.forEach((x, i) => {
            if (x != ctg) {
                //console.log(x);
                $(".c" + x + "_show").hide();
                $(".c" + x + "_hide").show();
            }
        });

        $(".c" + ctg + "_show").show();
        $(".c" + ctg + "_hide").hide();

    }

    $("#categoryid").change(function () {
        chkCategoryid();
    });


    //при открытии страницы
    chkCategoryid();
//    buildopertypes_rfr();


});
