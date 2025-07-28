$(document).ready(function () {

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


    $(".stfname").autocomplete({
        source: function (request, response) {
            $.ajax({
                url: "/orgstaff/autocomplete/search",
                dataType: "json",
                data: {
                    q: request.term,
                    //aux:"hrs_salary"
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
                            orgname: item.orgname,
                            // opertypeid: item.opertypeid
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
                //var staffid = $(this).parent().find('.staffid');
                var staffid = $(this).parent().find('.ac_id');
                staffid.val(ui.item.id);
                var orgid = $(this).parent().find('.orgid');
                orgid.val(ui.item.orgid);
                // var opertypeid = $(this).parent().parent().find('.opertypeid');
                // переделать на относительный поиск по классу?
                // var opertypeid = $('#opertypeid');
                // opertypeid.val(ui.item.opertypeid);
                $(this).val(ui.item.label);

                // $("#ac_orgid").hide().val("ok").removeClass("ac-fail");
                var ac_status = $(this).parent().find('.ac_status');
                ac_status.hide().removeClass("ac-fail");
                $(this).addClass("ac-act");


                //получить данные по рейсам выбранного авто за указанный день -----
                //raid_info_rfr()
                //-----------------------------------------------------------------

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
            if ($(this).val().length == 0) {
                $(this).parent().find('.staffid').val('');

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
