$(document).ready(function () {


    //$("#buildobjid").change();

    function chk_status() {
        var progress = $("#statusid").val();
        // if (progress == 2 || progress == 3 || progress == 5)
        //     $("#progress_div").show();
        // else
        //     $("#progress_div").hide();
    }

    chk_status();

    $("#statusid").change(function () {
        chk_status();
    });


    function chk_plnbegdt() {
        var chk_val = $("#plnbegdt").val();
        if (chk_val)
            $("#notify_div").show();
        else
            $("#notify_div").hide();
    }

    chk_plnbegdt();

    $("#plnbegdt").change(function () {
        chk_plnbegdt();
    });

    $("#categoryid,#ownorgid").change(function () {
        //скрываем кнопку загрузки рег. номера при любом изменении данных. Пусть сначала сохранят изменения
        $("#getregnum").hide();
    });

    $("#name").change(function () {
        if ($(this).val() != '')
            $(this).attr('hand', 1);
        else
            $(this).attr('hand', 0);
    });

    $("#descript").change(function () {
        if ($("#name").attr('hand') != 1)
            $("#name").val($("#descript").val().substring(1, 160));
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


    if ($(".ac_username")) {
        $(".ac_username").autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: "/users/autocomplete/search",
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
            // autoFocus: true,

            select: function (event, ui) {
                if (ui.item.id) {
                    // console.log($(this).parent().find('.userid').val());
                    var set_id = $(this).parent().find('.ac_userid');
                    set_id.val(ui.item.id);
                    //var orgid = $(this).parent().find('.orgid');
                    //orgid.val(ui.item.orgid);
                    $(this).val(ui.item.label);

                    // $("#ac_orgid").hide().val("ok").removeClass("ac-fail");
                    var ac_status = $(this).parent().find('.ac_status');
                    ac_status.hide().removeClass("ac-fail");
                    $(this).addClass("ac-act");
                }
                event.preventDefault();
            },
            search: function () {
                $(this).parent().find('.ac_userid').val('');

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
                if ($(this).val().length == 0) {
                    $(this).parent().find('.ac_userid').val('');

                    $(this).parent().find('.ac_username')
                        .removeClass("ac-act")
                        .addClass("ac-fail");

                    var ac_status = $(this).parent().find('.ac_status');
                    ac_status.val('Укажите пользователя!')
                        .show()
                        .removeClass("ac-act")
                        .addClass("ac-fail");
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


});
