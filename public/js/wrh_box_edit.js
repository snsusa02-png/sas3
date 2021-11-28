$(document).ready(function () {

    $("*").change(function () {
        //скрываем передачу на оплату при любом изменении данных. Пусть сначала сохранят изменения
        $("#send2pay").hide();
    });

    $('[data-toggle="tooltip"]').tooltip()  //инициализация подсказок


    $("#ownorgid").change(function () {
        let ownorgid = $("#ownorgid").val();
        if (ownorgid)
            $("#ownorg_link").attr('href', '/orgs/' + ownorgid + '/edit').show();
        else
            $("#ownorg_link").attr('href', '#').hide();
    });


    function contracts_rfr() {
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
        contracts_rfr();
    });


    $("#ownorgid, #orgid").change(function () {
        contracts_rfr();
    });

    $("#contractid").change(function () {
        let contractid = $("#contractid").val();
        if (contractid)
            $("#contract_link").attr('href', '/contracts/' + contractid + '/edit').show();
        else
            $("#contract_link").attr('href', '#').hide();
    });


    //при открытии страницы
//    chkCategoryid();


});
