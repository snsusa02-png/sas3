$(document).ready(function () {


    $("#refitmname").autocomplete({

        source: function (request, response) {
            var search_params = {
                q: request.term,
                svc: 0,
                wdid: $("#docid").val(),    //ID документа склада
                in_compounds:  $("#ri_produced").val(),
                cmpnd_ownorgid:  $("#cmpnd_ownorgid").val(),
                cmpnd_on_date:  $("#cmpnd_on_date").val()
            };
            //console.log(search_params)
            if (1 == 0) {
                search_params.in_compounds = 1;
                search_params.cmpnd_ownorgid = $("#ownorgid").val();
            }
            console.log(search_params)

            $.ajax({
                /*url: "/wrhdoclst/autocomplete/refitem",*/
                /*url: "/refitems/autocomplete/search",*/
                url: "/refitems/ac/wrhdoclst",
                dataType: "json",
                data: search_params
                // {
                //     q: request.term,
                //     svc: 0,
                //     wdid: $("#docid").val(),    //ID документа склада
                // }
                ,
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

                        var lbl = item.code + " | " + item.name;
                        lbl = lbl + " (категория: " + item.itname;
                        if (item.specinfo) lbl = lbl + "; " + item.specinfo;
                        lbl = lbl + "; цена: " + item.price + " &#x20bd;)";
                        return {
                            label: lbl,
                            value: item.name,
                            price: item.price,
                            code1s: item.code,
                            icon: item.photourl,
                            specinfo: item.specinfo,
                            unit: item.unittypename,
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
                $('#refitmid').val(ui.item.id);
                $('#price').val(ui.item.price);
                $('#code1s').val(ui.item.code1s);
                $('#code').val(ui.item.id);
                $('#unit').html(ui.item.unit);
                $('#unit_html').html(ui.item.unit);
                $('#specinfo').html(ui.item.specinfo);
                $(this).val(ui.item.value);
                $("#ac_refitmid").hide().val("").removeClass("ac-fail");
            } else
                event.preventDefault();
        },
        search: function () {
            $(this).removeClass("ac-fail").removeClass("ac-warn").addClass("ac-act");
            $("#ac_refitmid").val("поиск...")
                .removeClass("ac-fail").removeClass("ac-warn")
                .addClass("ac-act").show();
        },
        response: function (event, ui) {
            $(this).removeClass("ac-act");
            if (ui.content.length == 0) {
                $('#ac_refitmid').val('Варианты не найдены.')
                    .removeClass("ac-act").addClass("ac-fail");
                $(this).addClass("ac-fail");
            } else if (ui.content.length > 15) {
                $('#ac_refitmid').val('Показаны не все варианты! Уточните критерий')
                    .removeClass("ac-act").addClass("ac-fail");
            } else {
                //console.log(ui.content);
                $("#ac_refitmid").hide().val("");
            }
        }
    })
        .on('focus', function (event) {
            $(this).select();
        })
        .on('blur', function (event) {
            $('#ac_refitmid').hide().val("");
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

    if ($(".ac_cmpnd_name").length > 0) {

        //Поиск рецепта изготовления
        $(".ac_cmpnd_name").autocomplete({

            source: function (request, response) {
                //var ft = ($("#sale_dir").val() == -1 || $("#sale_dir").val() == 0) ? 12 : null;

                $.ajax({
                    //url: "/orgs/autocomplete/search",
                    url: "/api/ri_compounds/for_ac",
                    dataType: "json",
                    data: {
                        name: request.term,
                        ownorgid: $("#ownorgid").val(),
                        refitmid: $("#refitmid").val(),
                        signed: 1,
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

                            var lbl = item.notes + " / " + item.name;
                            //var lbl = item.name;
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


});
