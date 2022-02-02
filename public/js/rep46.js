$(document).ready(function () {

    $("#s_period_type").change(function () {

            var code = $("#s_period_type").val();
            //console.log(code);

            if (code == 1) {
                //day
                $(".dpt_2").hide();
                $(".dpt_3").hide();
                $(".dpt_4").hide();
                $(".dpt_9").hide();

                $(".dpt_1").show();

                $("#s_month").prop('required',false);
                $("#s_quarter").prop('required',false);
                $("#s_year").prop('required',false);
                $("#s_begdate").prop('required',true);
                $("#s_enddate").prop('required',false);

                // $("label[for='s_month']").addClass('required');
                // $("label[for='s_year']").addClass('required');
                $("label[for='s_begdate']").html('Дата:');

            } else if (code == 2) {
                //month
                $(".dpt_2").show();
                $(".dpt_3").hide();
                $(".dpt_4").show();
                $(".dpt_9").hide();

                $("#s_month").prop('required',true);
                $("#s_quarter").prop('required',false);
                $("#s_year").prop('required',true);
                $("#s_begdate").prop('required',false);
                $("#s_enddate").prop('required',false);

                // $("label[for='s_month']").addClass('required');
                // $("label[for='s_year']").addClass('required');

            } else if (code == 3) {
                //quarter
                $(".dpt_1").hide();
                $(".dpt_2").hide();
                $(".dpt_3").show();
                $(".dpt_4").show();
                $(".dpt_9").hide();

                $("#s_month").prop('required',false);
                $("#s_quarter").prop('required',true);
                $("#s_year").prop('required',true);
                $("#s_begdate").prop('required',false);
                $("#s_enddate").prop('required',false);

            } else if (code == 4) {
                //year
                $(".dpt_1").hide();
                $(".dpt_2").hide();
                $(".dpt_3").hide();
                $(".dpt_4").show();
                $(".dpt_9").hide();

                $("#s_month").prop('required',false);
                $("#s_quarter").prop('required',false);
                $("#s_year").prop('required',true);
                $("#s_begdate").prop('required',false);
                $("#s_enddate").prop('required',false);

            } else if (code == 9) {
                //calendar
                $(".dpt_1").hide();
                $(".dpt_2").hide();
                $(".dpt_3").hide();
                $(".dpt_4").hide();
                $(".dpt_9").show();

                $("#s_month").prop('required',false);
                $("#s_quarter").prop('required',false);
                $("#s_year").prop('required',false);
                $("#s_begdate").prop('required',true);
                $("#s_enddate").prop('required',false);

                $("label[for='s_begdate']").html('Начало периода:');

            } else {

                $(".dpt_1").hide();
                $(".dpt_2").hide();
                $(".dpt_3").hide();
                $(".dpt_9").show();

                $("#s_month").prop('required',false);
                $("#s_quarter").prop('required',false);
                $("#s_year").prop('required',false);
                $("#s_begdate").prop('required',true);
                $("#s_enddate").prop('required',false);
            }
        }
    );


    $("#s_period_type").change();

});
