$(document).ready(function () {

    $("#rqsttypeid").change(function () {

        var rqsttypeid = $("#rqsttypeid").val();

        if (rqsttypeid == 1) {
            $(".rt1_hide").hide();
            $(".rt1_show").show();
        }
        if (rqsttypeid == 2) {
            $(".rt2_hide").hide();
            $(".rt2_show").show();
        }
        if (rqsttypeid == 3) {
            $(".rt3_hide").hide();
            $(".rt3_show").show();
        }
        // var refitmid = $("#refitmid").val();
        // var qty = $("#qty").val();
        // // console.log('qty=' + qty);
        // $.get("/refitems/getprice/", {refitmid: refitmid, orgid: orgid, qty: qty},
        //     function (data) {
        //         // console.log(data);
        //         // console.log(data.price);
        //         $("#price").val(data.price);
        //         $("#itmsum").val(data.price*qty);
        //
        //     }
        // )
    });

    $("#car_orgid").change(function () {

        // var values = Array.from(document.querySelectorAll('select[name="car_orgid"] > optgroup')).map(el => el.getAttribute('label'));
        // alert(values);
        //
        //  alert($('#car_orgid').val());
        //  alert($('#car_orgid').closest('optgroup'));
        // alert($('#car_orgid').closest('optgroup').prop('label'));

        var car_orgid = $("#car_orgid").val();

        if (car_orgid == 6) {
            //УМТС
            $(".asgn_ext").hide();
            $(".asgn_own").show();
        } else {
            $(".asgn_ext").show();
            $(".asgn_own").hide();
        }
    });

    $("#doctypeid").change(function () {
        $.get("/stock/wrhdoctypes/params", {doctypeid: $("#doctypeid").val()},
            function (data) {
                //console.log(data);
                // $("#need_relwrh").val(data.need_relwrh);
                // $("#need_predoc").val(data.need_predoc);

                if (data.need_relwrh == "1") $("#relwrh").show()
                else $("#relwrh").hide();

                if (data.need_predoc == "1") $("#predoc").show()
                else $("#predoc").hide();
            }
        )
    });

    $("#buildobjid").change(function () {
        //alert($("#buildobjid").val());
        $("#buildopertypeid > option").remove()
        $.get("/buildobjs/buildopertypes/params", {buildobjid: $("#buildobjid").val()},
            function (data) {
                //console.log(data);
                $("#tgt_addr").val(data.address);

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
    });

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

    $("#buildopertypeid").change(function () {
        buildopertypeid_rfr();
    });


    $("#src_orgid").change(function () {
        $.get("/orgs/info/params", {orgid: $("#src_orgid").val()},
            function (data) {
                //console.log(data);
                $("#src_addr").val(data.address);
            }
        )

        $.get("/orgs/addrs/params", {orgid: $("#src_orgid").val()},
            function (data) {
                console.log(data);
                $("#src_addr").val("");
                $("#orgaddrs > option").remove()
                $("#orgaddrs").append($("<option>"))
                $.each(data.addresses, function (index, value) {
                    $("#orgaddrs").append($("<option>").attr("value", value))
                });
            }
        )

    });

    $("#fctbegdt").change(function () {
        var fctbegdt = moment($("#fctbegdt").val());
        var fctenddt = fctbegdt.add(3, 'hours');

        $("#fctenddt").val(fctenddt.format('YYYY-MM-DD[T]HH:mm'));

        // console.log(moment.duration(fctenddt.diff(fctbegdt, 'hours', true)));
        //console.log(fctenddt.diff(fctbegdt, 'hours', true));
        //console.log(moment.utc(moment.duration(fctenddt) - moment.duration(fctbegdt)).format('HH:mm'));
    });


    $("#driver_orgid").change(function () {
        $.get("/orgstaff/fio_name/params", {orgid: $("#driver_orgid").val()},
            function (data) {
                $("#drivername").val("");
                $("#driverorgstaff > option").remove()
                $("#driverorgstaff").append($("<option>"))
                $.each(data.staff, function (index, value) {
                    //$("#driverorgstaff").append($("<option>").attr("value", index).append(value))
                    $("#driverorgstaff").append($("<option>").attr("value", index))
                });
            }
        )
    });


    if ($("#src_orgname").length > 0) {
        //    if (!$('#src_orgname').prop('readonly')) {

        $("#src_orgname").autocomplete({

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
            minLength: 2,
            autoFill: true,
            cacheLength: 2,
            // autoFocus: true,

            select: function (event, ui) {
                if (ui.item.id) {
                    $('#src_orgid').val(ui.item.id);
                    // $(this).val(ui.item.value);
                    $(this).val(ui.item.label);

                    // $("#ac_orgid").hide().val("ok").removeClass("ac-fail");
                    $("#ac_orgid").hide().removeClass("ac-fail");
                    $(this).addClass("ac-act");

                    $("#src_orgid").change();  //для срабатывания заполнения договоров
                }
                event.preventDefault();
            },
            search: function () {
                $('#src_orgid').val('');
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
                    $('#src_orgid').val('');
                    $('#src_orgname').removeClass("ac-act").addClass("ac-fail");
                    $('#ac_orgid').val('Укажите организацию!').show()
                        .removeClass("ac-act").addClass("ac-fail");
                } else
                    $('#ac_orgid').hide().val("");
            });
    }

    $("#fcthrs,#fct_price").change(function () {


        //console.log($("#paytypeid").val() );

        if ($("#paytypeid").val() == 2) {
            var price = parseFloat($("#fct_price").val());
            var qty = parseFloat($("#fcthrs").val());
            var s = Math.round(100 * (price * qty)) / 100;
            $("#fct_sum").val(s);

        }
    });

    if ($("#orgname").length > 0) {

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
    }

    $("#rqsttypeid").change();
    $("#car_orgid").change();
    var fctenddt_setbyuser = false;

});
