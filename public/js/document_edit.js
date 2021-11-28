$(document).ready(function () {


    $("#dirtypeid").change(function () {

        const dirtypeid = $(this).val()
        if (dirtypeid == 1) {
            $("#org_regtype_name").html('исходящий')
            $(".org_info").show()

        } else if (dirtypeid == 2) {
            $("#org_regtype_name").html('входящий')
            $(".org_info").show()

        } else {
            $("#org_regtype_name").html('-')
            $(".org_info").hide()

        }

    });


    $("#buildobjid").change(function () {

        buildopertypes_rfr();

    });

    function buildopertypes_rfr() {

        var selector = "#buildopertypeid";

        if ($("#buildobjid").val()) {
            var save_ID = $(selector).val();
            //console.log(save_ID);

            $(selector + " > option").remove()
            $.get("/buildobjs/buildopertypes/params", {
                    buildobjid: $("#buildobjid").val(),
                    budget_orgid: $("#ownorgid").val()
                },
                function (data) {
                    //console.log(data);

                    $(selector + " > option").remove()
                    $(selector).append($("<option>"))
                    $.each(data.opertypes, function (index, value) {
                        $(selector).append($("<option>").attr("value", index).append(value))
                    });
                }
            )
            $(selector).val(save_ID);
        } else {
            $(selector + " > option").remove()
        }
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
                    , buildobjid: $("#buildobjid").val()
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

    $("#rfr_contracts").click(function () {
        contracts_rfr();
    });

    //$("#src_orgid, #tgt_orgid, #buildobjid").change(function () {
    $("#ownorgid, #orgid, #buildobjid").change(function () {
        contracts_rfr();
    });


    //Поиск контрагента
    $(".ac_orgname").autocomplete({
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
                var orgid = $(this).parent().find('.ac_orgid');

                //$('#orgid').val(ui.item.id);
                orgid.val(ui.item.id);
                $(this).val(ui.item.label);

                // $("#ac_orgid").hide().val("ok").removeClass("ac-fail");
                $("#ac_orgid").hide().removeClass("ac-fail");
                var ac_status = $(this).parent().find('.ac_status');
                ac_status.hide().removeClass("ac-fail");
                $(this).addClass("ac-act");

                orgid.change();  //для срабатывания заполнения договоров
            }
            event.preventDefault();
        },
        search: function () {
            var orgid = $(this).parent().find('.ac_orgid');
            orgid.val('');
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
                const orgid = $(this).parent().find('.ac_orgid');
                orgid.val('');
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


    //$("#buildobjid").change();

    $("#categoryid,#ownorgid").change(function () {
        //скрываем кнопку загрузки рег. номера при любом изменении данных. Пусть сначала сохранят изменения
        $("#getregnum").hide();
    });

    $("#getregnum").click(function () {
        if (!$("#regnum").val()) {

            $.get("/contracts/fill_regnum/params", {contractid: $("#id").val()},
            )
                .done(function (data) {
                    //alert("Data Loaded: " + data);
                    //console.log(data);
                    $("#regnum").val(data.regnum);
                    $("#regnum").attr('readonly', 'readonly');
                    $("#getregnum").hide();
                });
        }
    });


    $("#setregnum").click(function () {
        //alert($(this).checked());
        if ($('#setregnum').prop('checked')) {
            $("#set_regnum").val(1);
        } else {
            $("#set_regnum").val(0);
        }
    });


    $("#contracttypeid").change(function () {

        //alert($("#contracttypeid").val());
        var save_ID1 = $("#ownorgroletypeid").val();
        var save_ID2 = $("#orgroletypeid").val();
        //console.log('buildobjid=' + save_ID);

        $("#ownorgroletypeid > option").remove();
        $("#orgroletypeid > option").remove();

        //получим список ролей для выбранного типа контракта
        $.get("/api/contractroles/typeid", {
                typeid: $("#contracttypeid").val(),
            },
            function (data) {
                //console.log(data);

                //var validOpers = validOpers.split(',');
                //$("#buildobjid > option").remove()
                $("#ownorgroletypeid").append($("<option>"))
                $("#orgroletypeid").append($("<option>"))
                $.each(data.roles, function (index, value) {
                    $("#ownorgroletypeid").append($("<option>").attr("value", index).append(value))
                    $("#orgroletypeid").append($("<option>").attr("value", index).append(value))
                });
            }
        )
        $("#ownorgroletypeid").val(save_ID1);
        $("#orgroletypeid").val(save_ID2);

    });


    function docsubtypes_rfr() {
        var selector = "#docsubtypeid";

        $(selector + " > option").remove()

        if ($("#doctypeid").val()) {
            var save_ID = $(selector).val();
            //console.log(save_ID);

            //$(selector + " > option").remove()
            $.get("/api/doctypes/params", {parent_id: $("#doctypeid").val(),},
                function (data) {
                    //console.log(data);

                    $(selector).append($("<option>"))
                    $.each(data.doctypes, function (index, value) {
                        $(selector).append($("<option>").attr("value", index).append(value))
                    });

                    $(selector).val(save_ID);
                }
            )
            // } else {
            //     $(selector + " > option").remove()
        }
    }

    $("#doctypeid").change(function () {

        docsubtypes_rfr();

    });


    function ocl_items_rfr() {
        var selector = "#ocl_itmid";
        //console.log('ownorgid=' + $("#ownorgid").val(), );

        $(selector + " > option").remove()

        if ($("#ownorgid").val()) {
            var save_ID = $(selector).val();
            //console.log(save_ID);

            //$(selector + " > option").remove()
            $.get("/api/ocl_items/params", {orgid: $("#ownorgid").val(),},
                function (data) {
                    //console.log(data);

                    $(selector).append($("<option>"))
                    $.each(data.ocl_items, function (index, value) {
                        $(selector).append($("<option>").attr("value", index).append(value))
                    });

                    $(selector).val(save_ID);
                }
            )
            // } else {
            //     $(selector + " > option").remove()
        }
    }

    $("#ownorgid").change(function () {


        ocl_items_rfr();
        orgdeps_rfr();

    });

    function orgdeps_rfr() {

        var selector = "#depid";
        console.log('ownorgid=' + $("#ownorgid").val(),);

        $(selector + " > option").remove()

        if ($("#ownorgid").val()) {
            var save_ID = $(selector).val();
            //console.log(save_ID);

            $.get("/api/orgdeps/for_", {
                    orgid: $("#ownorgid").val(),
                    active_or_current: $("#orgdepid").val()
                },
                function (data) {
                    //console.log(data);

                    $(selector).append($("<option>"))
                    $.each(data.orgdeps, function (index, value) {
                        $(selector).append($("<option>").attr("value", index).append(value))
                    });

                    $(selector).val(save_ID);
                }
            )
        }
    }

    function rfr_depid_lnk() {
        if ($("#depid").val())
            $("#depid_lnk").show()
        else
            $("#depid_lnk").hide()

    }

    $("#orgdepid").change(function () {
        alert($("#depid").val());

        rfr_depid_lnk()
    });



    function bindAutoComplete($prnt) {

        //Поиск типа документов
        $prnt.find(".ac_doctypename").autocomplete({
            source: function (request, response) {
                //console.log(request)
                $.ajax({
                    url: "/api/doctypes/ac_",
                    dataType: "json",
                    data: {
                        name: request.term,
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

                    var set_id = $(this).parent().find('.ac_doctypeid');

                    //$('#set_id').val(ui.item.id);
                    $(set_id).val(ui.item.id);
                    $(this).val(ui.item.label);

                    var ac_status = $(this).parent().find('.ac_status');
                    $(ac_status).hide().removeClass("ac-fail");
                    $(this).addClass("ac-act");

                    set_id.change();  //для срабатывания заполнения подтипов
                }
                event.preventDefault();
            },
            search: function () {

                var set_id = $(this).parent().find('.ac_doctypeid')
                var ac_status = $(this).parent().find('.ac_status');

                $(set_id).val('');
                $(this).removeClass("ac-fail").removeClass("ac-warn").addClass("ac-act");
                ac_status.val("поиск...")
                    .removeClass("ac-fail").removeClass("ac-warn")
                    .addClass("ac-act").show();
            },
            response: function (event, ui) {
                $(this).removeClass("ac-act");
                var ac_status = $(this).parent().find('.ac_status');
                if (ui.content.length == 0) {
                    $(ac_status).val('Варианты не найдены.')
                        .removeClass("ac-act").addClass("ac-fail");
                    $(this).addClass("ac-fail");
                } else if (ui.content.length > 15) {
                    $(ac_status).val('Показаны не все варианты! Уточните критерий')
                        .removeClass("ac-act").addClass("ac-warn");
                } else {
                    //console.log(ui.content);
                    $(ac_status).hide().val("");
                }
            }
        })
            .on('focus', function (event) {
                $(this).select();
            })
            .on('blur', function (event) {
                //alert($(this).parent().find('.ac_doctypeid').val())

                if ($(this).val().length == 0) {
                    $(this).parent().find('.ac_doctypeid').val('');
                }

                //если значение не установлено, то сообщим об этом
                var set_id = $(this).parent().find('.ac_doctypeid')
                if ($(set_id).val().length == 0) {
                    $(this).parent().find('.ac_doctypename').removeClass("ac-act").addClass("ac-fail");
                    $(this).parent().find('.ac_status').val('Укажите тип!').show()
                        .removeClass("ac-act").addClass("ac-fail");
                } else
                    $(this).parent().find('.ac_status').hide().val("");

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
                //searchText = $.trim(this.term).toLowerCase(),
                searchText = $.trim(this.term).toLowerCase(),
                currentNode = mName.get(0).firstChild,
                matchIndex, newTextNode, newSpanNode;

            //while ((matchIndex = currentNode.data.toLowerCase().indexOf(searchText)) >= 0) {
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


//при запуске -----
    bindAutoComplete($(".doctype"));

    contracts_rfr();

})
;
