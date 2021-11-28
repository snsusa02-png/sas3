$(document).ready(function () {


    function bindAutoComplete(prnt) {
        //Поиск типа документов
        prnt.find(".ac_doctypename").autocomplete({

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
});
