$(document).ready(function () {


    var pre_docdate = $("#docdate").val();
    console.log('------------------', pre_docdate)

    $("#paydir").change(function () {
        //Меняем подписи к контрагентам в зависимости от направления платежа
        if ($(this).val() == -1) {
            //Расход
            $("#ownorg_aux_lbl").html('(Плательщик)')
            $("#org_aux_lbl").html('(Получатель)')
        } else if ($(this).val() == +1) {
            $("#ownorg_aux_lbl").html('(Получатель)')
            $("#org_aux_lbl").html('(Плательщик)')
        } else {
            $("#ownorg_aux_lbl").html('')
            $("#org_aux_lbl").html('')
        }
    });

    $("#docdate").change(function () {
        const docdate = $("#docdate").val()
        const paydate = $("#paydate").val()
        //console.log(paydate, pre_docdate, paydate == pre_docdate)
        if (!paydate || paydate == pre_docdate) {
            $("#paydate").val(docdate);
        }
        pre_docdate = $("#docdate").val();
    })


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
                $('#orgid').val('');
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

        //обновляем источники номеров регистрации
        refresh_regnum_srcid();
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


    $("input[name=docdate]").change(function () {
        //Если дата начала действия еще не определена, то установим ее в соответствии с датой договора
        if (!$("#begdate").val())
            $("#begdate").val($(this).val());
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

    function refresh_regnum_srcid() {

        const selector = '#regnum_srcid';
        var save_ID1 = $(selector).val();
        //console.log('save_ID1=' + save_ID1);

        $(selector + " > option").remove();

        // обязательно должен быть определен ownorgid
        //   иначе список нумераторов должен быть пуст

        if ($("#ownorgid").val()) {
            //получим список доступных нумер для выбранного типа контракта
            //$.get("/api/contractroles/typeid", {
            $.get("/api/regnum_srcs", {
                    ownorgid: $("#ownorgid").val(),
                    categoryid: $("#categoryid").val(),
                    active_or_selected: 1,
                },
                function (data) {
                    //console.log(data);

                    //var validOpers = validOpers.split(',');
                    //$("#buildobjid > option").remove()
                    $(selector).append($("<option>"))
                    $.each(data.regnum_srcs, function (index, value) {
                        $("#regnum_srcid").append($("<option>").attr("value", index).append(value))
                    });

                    $(selector).val(save_ID1);

                    //Если вариант всего один - попробуем сразу его выбрать. 2 - потому что есть еще placeholder
                    if ($(selector + ' option').length == 2) {
                        $(selector).prop('selectedIndex', 1);
                    }

                }
            );
        }

    }

});
