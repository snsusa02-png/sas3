$(document).ready(function () {

    if (1 == 0) {
        $("#orgname").autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: "/orgs/autocomplete/search/",
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
    }


    $("#initorgid").change(function () {

        //alert($("#s_orgid").val());
        var save_ID = $("#buildobjid").val();
        //console.log('buildobjid=' + save_ID);

        $("#buildobjid > option").remove();
        $("#s_buildopertypeid > option").remove();

        //$("#buildopertypeid").css("background-color","#ffe1e1");

        //получим объекты, участвующие в заявках где есть передача давальческих материалов между s_ownorgid и s_orgid
        $.get("/api/buildobjs/budget_owner", {
                orgid: $("#initorgid").val(),
            },
            function (data) {
                //console.log(data);

                //var validOpers = validOpers.split(',');
                $("#buildobjid > option").remove()
                $("#buildobjid").append($("<option>"))
                $.each(data.buildobjs, function (index, value) {
                    $("#buildobjid").append($("<option>").attr("value", index).append(value))
                });
            }
        )

        $("#buildobjid").val(save_ID);
    });


    function buildobjid_rfr() {
        var save_ID = $("#buildopertypeid").val();
        console.log(save_ID);
        $("#buildopertypeid > option").remove();
        //$("#buildopertypeid").css("background-color","#ffe1e1");

        //$.get("/buildobjs/buildopertypes/params", {buildobjid: $("#buildobjid").val()},
        $.get("/api/buildopertypes/for_", {
                buildobjid: $("#buildobjid").val(),
                budget_orgid: $("#initorgid").val()
            },
            function (data) {
                //console.log(data);
                $("#deli_address").val(data.address);

                //var validOpers = validOpers.split(',');
                $("#buildopertypeid > option").remove()
                $("#buildopertypeid").append($("<option>"))
                $.each(data.opertypes, function (index, value) {
                    $("#buildopertypeid").append($("<option>").attr("value", index).append(value))
                });

                // $("#need_relwrh").val(data.need_relwrh);
                // $("#need_predoc").val(data.need_predoc);

                // if (data.need_relwrh == "1") $("#relwrh").show()
                // else $("#relwrh").hide();
                //
                // if (data.need_predoc == "1") $("#predoc").show()
                // else $("#predoc").hide();
            }
        )
        $("#buildopertypeid").val(save_ID);
        //$("#buildopertypeid").css("background-color","");
    }

    function buildopertypeid_rfr() {
        var selector = "#orgcontractid";
        var save_ID = $(selector).val();
        console.log(save_ID);
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

    // function budgetitms_rfr() {
    //     $("#budgetitmid > option").remove();
    //
    //     if ($("#initorgid").val() && $("#buildobjid").val()) {
    //         $.get("/api/budgetitms/org/buildobj"
    //             , {orgid: $("#initorgid").val(), buildobjid: $("#buildobjid").val()},
    //             function (data) {
    //                 console.log(data);
    //
    //                 //var validOpers = validOpers.split(',');
    //                 $("#budgetitmid > option").remove()
    //                 $("#budgetitmid").append($("<option>"))
    //                 $.each(data.budgetitms, function (index, value) {
    //                     $("#budgetitmid").append($("<option>").attr("value", index).append(value))
    //                 });
    //             }
    //         )
    //     }
    // }

    $("#initorgid").change(function () {
        //alert($("#initorgid").val());
        //budgetitms_rfr();
    });

    $("#buildobjid").change(function () {
        //alert($("#buildobjid").val());
        buildobjid_rfr();

        //budgetitms_rfr();
    });

    $("#buildopertypeid").change(function () {
        buildopertypeid_rfr();
    });


    // $("#src_orgid").change(function () {
    //     $.get("/orgs/info/params", {orgid: $("#src_orgid").val()},
    //         function (data) {
    //             console.log(data);
    //             $("#src_addr").val(data.address);
    //         }
    //     )
    // });


    var form_act = function (options) {

        var oForm = {
            elem: $('<div>')
                .attr({'id': 'form_act_doc'})
                .css({
                    'background-color': '#EFC377',
                    'width': '100%',
                    'height': '47px',
                    'position': 'fixed',
                    'transition': 'bottom 0.5s ease-out 0.5s',
                    'box-shadow': '0px -3px 10px 0px rgba(50, 50, 50, 0.59)',
                    'bottom': '-70px'
                })
            ,
            add_count_info: function () {
                //добавление информации о выделенных док-ах
                var d = this;
                console.log('add_count_info');
                $(this.elem).append(
                    // $('#bottom-menu').append(
                    $('<div>').addClass('count_info inline')
                        .append(
                            $('<div>').addClass('f14 inline')
                                .append('Выбрано позиций:')
                                .append($('<span>').attr({'id': 'act_count'}))
                        )
                        .append(
                            $('<input>').click(function () {
                                d.down();
                            })
                                .attr({'type': 'button', 'value': 'Снять выделение'})
                        )
                )

            },
            add_action: function () {
                $(this.elem).find(".act_info").remove();
                var d = this;
                if (document.may_change === undefined) document.may_change = 0;
                var s_method = $('<select>').attr({'id': 'method', 'style': 'width:40%;'})
                    .append('<option value=""></option>')
                    .change(function () {
                        //обновляем метод
                        d.method = $(this).find('option:selected').val();
                        $(this).css({'border': 'none'})
                    });
                $.ajax({
                    // url: "/internal/Ferret/i/form_act/form_act_ajax.asp",
                    // data: {t: options.get_type, rep_changeble: document.may_change, issubagent: document.issubagent},
                    url: "/equiprqsts/api/group_actions",
                    data: {t: options.get_type, rep_changeble: document.may_change, issubagent: document.issubagent},
                    async: false,
                    success: function (data) {
                        $.each(eval(data), function (k, v) {
                            s_method.append(
                                $("<option>").append(v.name)
                                    .attr({'value': v.code})
                                    .click(function () {
                                        $('.descr .content').attr({'title': v.description})
                                            .find('img').css({'border': '1px solid #fff'});
                                    })
                            )
                        })
                    }
                });
                $(this.elem).append(
                    $('<div>').addClass('act_info')
                        .append(
                            $('<div>').addClass('f14 inline').append('Действие:')
                        )
                        .append(s_method)
                        .append(
                            $('<input>').attr({'id': 'go_act', 'type': 'button', 'value': 'Применить'})
                                .css({'margin-left': '10px'})
                                .click(function () {
                                    d.send_values();
                                })
                        )
                )
            },
            add_description: function () {
                $(this.elem).append(
                    $('<div>').addClass('descr').css({'display': 'inline-block'})
                        .append(
                            //$("<div>").addClass('content').append('<img width="20px" height="20px" src="/images/icons/app/info.png">')
                        )
                )
            },
            init: function () {
                console.log('init');
                //добавляем блоки
                this.add_count_info();
                $('#bottom-menu').add_count_info();
                //this.add_action();
                //this.add_description();

                if (this.cnt_chk() > 0) {
                    this.upd_cnt_chk();
                    this.up();
                }
                $('body').append(this.elem);
                var w = this;
                //повешаем обработчики на чекбоксы
                $('input[name="act_doc"]').click(function () {
                    w.UpDownToggle();
                });
            },
            UpDownToggle: function () {
                var w = this;

                $(w.elem).find('#act_count').html(w.cnt_chk());
                if ($('input[name="act_doc"]:checked').length > 0) {
                    w.up(); //показать
                } else {
                    w.down(); //скрыть
                }

            },
            up: function () {
                console.log('up')
                $(this.elem).css({'bottom': '0px'});
                $('#bottom-menu').css('bottom', '0px')
                    .css("background-color", "snow");
                this.upd_cnt_chk();
                console.log($('#bottom-menu').css("background-color"))

            },
            down: function () {
                console.log('down')
                $('input[name="act_doc"]:checked').removeAttr('checked');
                $(this.elem).css('bottom', '-70px');
                this.upd_cnt_chk();
                $('#bottom-menu').css('bottom', '-180px');
            },
            cnt_chk: function () {
                console.log($('input[name="act_doc"]:checked').length);
                return $('input[name="act_doc"]:checked').length;
            },
            upd_cnt_chk: function () {
                $(this.elem).find('#act_count').html(this.cnt_chk());
                console.log('upd_cnt_chk');
                $("#bottom-menu").find('#act_count').html(this.cnt_chk());
            },
            send_values: function () {
                console.log(this.method);
                if (this.method != '' && this.method != undefined) {
                    var items = [];
                    //debugger;
                    $('input[name="act_doc"]:checked').each(function (i, elem) {
                        var v = $(elem).val();
                        items.push(v);
                    });
                    var need_confirm = this.method.substring(0, 1);
                    this.method = this.method.substring(2);
                    if (need_confirm == 0) {
                        this.ajax_post(items);
                    } else {
                        if (confirm('Применить к выбранным документам "' + this.elem.find('#method option:selected').text() + '"?')) {
                            this.ajax_post(items);
                        }
                    }
                } else {
                    $("#method").css({'border': '1px solid red'})
                }
            }
            //,            ajax_post: options.ajax_post

        }

        return oForm;
    };

    var w;
    var options = {
        get_type: 'acts',
        ajax_post: function (items) {
            $.ajax({
                url: "ajax_doc_actions.asp",
                type: "POST",
                data: {t: this.method, rpt_id: $('#Id').val(), items: items.join(",")},
                beforeSend: function () {
                    $('#go_act').addClass("act")
                },
                success: function (data) {
                    //debugger;
                    var jdata = JSON.parse(data);
                    if (jdata.act !== undefined) {
                        var swh = jdata.act;
                        if (swh == "dialog") {
                            alert(jdata.label);
                        } else if (swh == "window" && jdata.url !== undefined) {
                            var execute = "window.open(" + jdata.url + ")";
                            eval(execute);
                        }
                    }
                    if (jdata.stay_cheched !== undefined && jdata.stay_cheched == false) $('input[name="act_doc"]:checked').attr('checked', false);
                    //if(jdata.reload!==undefined && jdata.reload==true) location.reload();
                    location.reload();
                }
            })
        }
    }
    w = new form_act(options);
    //w.init(); //инициализируем форму с действиями для выбранных документов

    $(".checkAll").click(function () {
        var $this = $(this), isChekedAll = $this.hasClass("checkedAll");
        $(".partTable input[type='checkbox']").each(function () {
            $(this).attr("checked", false);
            $(this).parents(".partTable").find(".checkAll").removeClass("checkedAll");
        });

        var elLst = $(this).parents(".partTable").find("input[type='checkbox']");

        elLst.each(function () {
            if (!isChekedAll) {
                $(this).attr("checked", true);
            } else {
                $(this).attr("checked", false);

            }
        });

        if (!isChekedAll) {
            $this.addClass("checkedAll");
        } else {
            $this.removeClass("checkedAll");
        }

        if (typeof (w) != "undefined") {
            w.UpDownToggle();
        }

        // up: function() {
        //$('#footer-menu').css({'bottom': '0px'});
        // },
        // down: function() {
        //     $('input[name="act_doc"]:checked').removeAttr('checked');
        //     $(this.elem).css('bottom','-70px');
        // },

    });

    $('input[name="act_doc"]').click(function () {

        // if ($(this).attr("checked"))
        //     $(this).attr("checked", false);
        // else
        //     $(this).attr("checked", true);

        w.UpDownToggle();

    });

    $('#go_act').click(function () {
        alert(123);
        w.send_values();
    });

    //buildobjid_rfr();
    //$("#car_orgid").change();
    var fctenddt_setbyuser = false;


    //чекбокс для скрытия/показа строк, связанных со справ-ком номенклатуры
    function toggleRefitem() {
        var rows = document.getElementsByClassName('refitem');
        //console.log(this.checked);
        for (var i = 0; i < rows.length; i++) {
            if (this.checked)
                rows[i].style.display = 'none'
            else
                rows[i].style.display = '';
        }
    }

    if (document.getElementById('tglRefitem'))
        document.getElementById('tglRefitem').onchange = toggleRefitem;

    function filtByItmName() {
        //скрытие рядов таблицы состава заявки, которые в названии материала не содежрэат нужный текст
        var input, filter, table, tr, td, i, txtValue;
        input = document.getElementById("srchByItmName");
        filter = input.value.toUpperCase();
        table = document.getElementById("items");
        //tr = table.getElementsByTagName("tr.item");
        tr = table.querySelectorAll("tr.item");
        //console.log(tr);

        for (i = 0; i < tr.length; i++) {
            td = tr[i].getElementsByTagName("td")[1];
            if (td) {
                txtValue = td.textContent || td.innerText;
                //console.log(txtValue)
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    }

    if (document.getElementById('srchByItmName'))
        document.getElementById('srchByItmName').onkeyup = filtByItmName;

});
