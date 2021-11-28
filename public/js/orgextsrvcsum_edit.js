$(document).ready(function () {

    function chkOrgid() {

        var orgid = $("#orgid").val();
        //console.log(orgid);

        $("#acntid > option").remove();
        // $.get("/equiprqsts/orgplnpay_items/params", {orgid: $("#orgid").val()},
        $.get("/org/acnts/params", {orgid: $("#orgid").val()},
            function (data) {
                //console.log('data: ');
                // console.log(data);
                //$("#tgt_addr").val(data.address);

                $("#acntid > option").remove()
                $("#acntid").append($("<option>"))
                $.each(data.acnts, function (index, value) {
                    $("#acntid").append($("<option>").attr("value", index).append(value))
                });
                //Если р/сч всего один - попробуем сразу его выбрать. 2- потому что есть еще placeholder
                if ($('#acntid option').length == 2) {
                    $("select#acntid").prop('selectedIndex', 1);

                }

            }
        )
        // но $('#acntid option').length почему-то дает всегда 0
        // console.log('--- length ---');
        //  console.log($('#acntid option').length);
        //  console.log($('#acntid').length);
        // console.log($('#acntid option').length);
        // console.log($("#acntid").val());

        //$(".equiprqst").show();
    }


    $("#orgid").change(function () {
        //alert($("#orgid").val());
        chkOrgid();
    });

    $("input[name='restsum']").change(function () {
        //для старых браузеров
        var nval = parseFloat($(this).val().replace(',', '.').replace(' ', ''));
        var val = nval.toFixed(2);
        val = (isNaN(val) || val < 0) ? 0 : val;
        $(this).val(val);

//        var sum = nval + parseFloat($("#inpsum").val().replace(',', '.').replace(' ', ''));
        //console.log(sum);

//        $("#cursum").val(sum.toFixed(2));
    });

    $("input[name='inpsum']").change(function () {
        //для старых браузеров
        var nval = parseFloat($(this).val().replace(',', '.').replace(' ', ''));
        var val = nval.toFixed(2);
        val = (isNaN(val) || val < 0) ? 0 : val;
        $(this).val(val);

        var sum = nval + parseFloat($("#restsum").val().replace(',', '.').replace(' ', ''));
        //console.log(sum);

        $("#cursum").val(sum.toFixed(2));
    });

    //при открытии страницы
//    chkCategoryid();

});
