$(document).ready(function () {

    function contracts_rfr() {
        var selector = "#contractid";
        var save_ID = $(selector).val();
        //console.log(save_ID);
        $(selector + " > option").remove()
        $.get("/api/contracts/for_", {
                ownorgid: $("#ownorgid").val()
                , orgid: $("#orgid").val()
                , categoryid: 2
                , for_userid: null
            },
            function (data) {
                //console.log(data);

                $(selector + " > option").remove()
                $(selector).append($("<option>"))
                $.each(data.contracts, function (index, value) {
                    $(selector).append($("<option>").attr("value", index).append(value))
                });
            }
        )
        $(selector).val(save_ID);
    }


    $("#orgid").change(function () {
        contracts_rfr();
    });

    $("#contractid").change(function () {
        let contractid = $("#contractid").val();
        if (contractid)
            $("#contract_link").attr('href', '/contracts/' + contractid + '/edit').show();
        else
            $("#contract_link").attr('href', '#').hide();
    });


    $("#set_alreadypay").click(function () {
        if ($(this).prop('checked')) {
            $("#alreadypay_div").css('display', '')
        } else {
            $("#alreadypay_div").css('display', 'none')
        }
    });


    $("input[name='plnpaysum']").change(function () {
        //для старых браузеров
        var val = parseFloat($(this).val().replace(',', '.').replace(' ', '')).toFixed(2);
        val = (isNaN(val) || val < 0) ? 0 : val;

        $(this).val(val);
    });

    $("#btnFullPay").click(function () {
        //sum = $("#plnpaysum").val();
        sum = $("#lim_sum").val();
        $("#agr_sum").val(sum);
    });

    $("#btnSamePay").click(function () {
        limsum = $("#lim_sum").val();
        sum = $("#op_agr_sum").val();

        $("#agr_sum").val(Math.min(limsum, sum));
    });

    $("#btnNoPay").click(function () {
        $("#agr_sum").val(0);
    });

    $("#btnAgrSum2PaySum").click(function () {
        sum = $("#agr_sum").val();
        //alert(sum);
        $("#fctpaysum").val(sum);
    });


    function chkCategoryid() {

        //для платежей связанных с материалами
        if ($("#categoryid").val() == 7) {

            var equiprqst_id = $("#equiprqst_id").val();
            console.log(equiprqst_id);

            $("#equiprqst_id > option").remove();
            //$.get("/buildobjs/contracts/params", {orgid: $("#orgid").val(), ownorgid: $("#ownorgid").val()},
            $.get("/equiprqsts/orgplnpay_items/params", {orgid: $("#orgid").val()},
                function (data) {
                    //console.log(data);
                    //$("#tgt_addr").val(data.address);

                    $("#equiprqst_id > option").remove()
                    $("#equiprqst_id").append($("<option>"))
                    $.each(data.equiprqsts, function (index, value) {
                        $("#equiprqst_id").append($("<option>").attr("value", index).append(value))
                    });
                }
            )
//                alert(equiprqst_id);
            console.log(equiprqst_id);
            $("#equiprqst_id").val(equiprqst_id);
            console.log($("#equiprqst_id").val());

            $(".equiprqst").show();
        } else
            $(".equiprqst").hide();
    }


    $("#orgid,#categoryid").change(function () {
        //alert($("#orgid").val());
        chkCategoryid();
    });

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


    //при открытии страницы
//    chkCategoryid();

});
