$(document).ready(function () {

    function buildopertypeid_rfr() {
        var selector = "#orgcontractid";
        var save_ID = $(selector).val();
        //console.log(save_ID);
        $(selector + " > option").remove()
        $.get("/api/contracts/buildopertypeid", {buildopertypeid: $("#buildopertypeid").val()},
            function (data) {
                //console.log(data);

                $(selector + " > option").remove()
                $(selector).append($("<option>"))
                $.each(data.orgcontracts, function (index, value) {
                    $(selector).append($("<option>").attr("value", index).append(value))
                });
            }
        )
        $(selector).val(save_ID);
    }


    $("#buildopertypeid").change(function () {
        buildopertypeid_rfr();
    });

    // $("#machine_name").change(function (e) {
    //     console.log($("#machine_name").val());
    // });

    $(".machine_name").autocomplete({
        source: function (request, response) {
            //$.get("/api/budgets/for_", {buildobjid: s_buildobjid, in_equiprsts: 1},

                $.ajax({
                url: "/api/machines/for_ac",
                dataType: "json",
                data: {
                    //q: request.term,
                    s_name: request.term,
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
        // autoFocus: true,

        select: function (event, ui) {
            if (ui.item.id) {
                // console.log($(this).parent().find('.machineid').val());
                var machineid = $(this).parent().find('.machineid');
                machineid.val(ui.item.id);
                var orgid = $(this).parent().find('.orgid');
                orgid.val(ui.item.orgid);
                $(this).val(ui.item.label);

                // $("#ac_orgid").hide().val("ok").removeClass("ac-fail");
                var ac_status = $(this).parent().find('.ac_status');
                ac_status.hide().removeClass("ac-fail");
                $(this).addClass("ac-act");
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
            if ($(this).val().length == 0) {
                $(this).parent().find('.machineid').val('');

                $(this).parent().find('.machine_name')
                    .removeClass("ac-act")
                    .addClass("ac-fail");

                var ac_status = $(this).parent().find('.ac_status');
                ac_status.val('Укажите рег. номер техники!')
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
});
