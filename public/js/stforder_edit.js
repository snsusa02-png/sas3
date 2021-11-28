$(document).ready(function () {

    function rfr_depid_lnk() {
        if ($("#depid").val())
            $("#depid_lnk").show()
        else
            $("#depid_lnk").hide()

    }


    function chkOrdTypeID() {
        //Скрытие / Открытие частей формы в зависимости от выбранного типа приказа
        // Тек. список типов приказов (!) Обновлять при добавлении
        var ctgs = [10, 20, 30, 40, 50, 60, 70, 80, 90];

        const ctg = $("#ordtypeid").val(); //выбранный тип приказа
        //console.log(ctg);
        //все остальные - скрываем / показываем от обратного!
        ctgs.forEach((x, i) => {
            if (x != ctg) {
                //console.log(x,i);
                $(".t" + x + "_show").hide();
                $(".t" + x + "_hide").show();
            }
        });

        $(".t" + ctg + "_show").show();
        $(".t" + ctg + "_hide").hide();

    }

    $("#ordtypeid").change(function () {
        chkOrdTypeID()
    })

    function chkVacTypeID() {
        //Скрытие / Открытие частей формы в зависимости от выбранного типа отпуска
        // Тек. список типов отпуска (!) Обновлять при добавлении
        var ctgs = [1, 2, 3, 4];

        const ctg = $("#vactypeid").val(); //выбранный тип отпуска

        //все остальные - скрываем / показываем от обратного!
        ctgs.forEach((x, i) => {
            if (x != ctg) {
                //console.log(x);
                $(".vt" + x + "_show").hide();
                $(".vt" + x + "_hide").show();
            }
        });

        $(".vt" + ctg + "_show").show();
        $(".vt" + ctg + "_hide").hide();

    }

    $("#vactypeid").change(function () {
        chkVacTypeID()
    })


    $("#depid").change(function () {
        //alert($("#depid").val());
        const save_id = $("#postid").val();

        $("#postid > option").remove()
        $.get("/api/orgposts/dep_posts", {depid: $("#depid").val(), staffid: $("#id").val(),},
            function (data) {
                $("#postid").append($("<option>"))
                $.each(data.list, function (index, value) {
                    $("#postid").append($("<option>").attr("value", index).append(value))
                });
                $("#postid").val(save_id);
            }
        )

        rfr_depid_lnk()
    });


    $("#postid").change(function () {
        //alert($("#postid").val());

        $.get("/api/orgposts/stdlimunits", {postid: $("#postid").val(), staffid: $("#id").val()},
            function (data) {
                //console.log(data);
                var max = parseFloat(data);
                max = (max > 1.5) ? 1.5 : max;
                $("#stdpostunit").attr('max', max);
                if ($("#stdpostunit").val() > max)
                    $("#stdpostunit").val(max);
                else if ($("#stdpostunit").val() === '')
                    $("#stdpostunit").val((max >= 1) ? 1 : max);
            }
        )
    });


    //при открытии ---------------------
    chkVacTypeID()  //порядок важен - сначала по частным признакам
    chkOrdTypeID()  //  - затем по общим

    $('#depid_lnk').click(function (e) {
        //console.log($(this).attr('id'));
        e.preventDefault();
        if ($("#depid").val()) {
            var url = '/orgdeps/' + $("#depid").val() + '/edit';
            window.open(url, '_blank');
        }
    });

    $('#postid_lnk').click(function (e) {
        //console.log($(this).attr('id'));
        e.preventDefault();
        if ($("#postid").val()) {
            var url = '/orgposts/' + $("#postid").val() + '/edit';
            window.open(url, '_blank');
        }
    });


    if ($(".ac_orgstaff_name").length) {
        $(".ac_orgstaff_name").autocomplete({
            source: function (request, response) {

                $.ajax({
                    //url: "/users/autocomplete/search",
                    url: "/api/orgstaff/ac_",
                    dataType: "json",
                    data: {
                        q: request.term,
                        orgid: $("#orgid").val(),
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
                    $(this).val(ui.item.label);

                    var set_id = $(this).parent().find('.ac_orgstaff_id');
                    set_id.val(ui.item.id);

                    // $("#ac_orgid").hide().val("ok").removeClass("ac-fail");
                    var ac_status = $(this).parent().find('.ac_status');
                    ac_status.hide().removeClass("ac-fail");
                    $(this).addClass("ac-act");
                }
                event.preventDefault();
            },
            search: function () {
                $(this).parent().find('.ac_orgstaff_id').val('');

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
                    $(this).parent().find('.ac_orgstaff_id').val('');

                    $(this).parent().find('.ac_orgstaff_name')
                        .removeClass("ac-act")
                        .addClass("ac-fail");

                    var ac_status = $(this).parent().find('.ac_status');
                    ac_status.val('Укажите сотрудника!')
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
