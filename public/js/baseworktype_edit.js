$(document).ready(function () {

    $(".smr_pcnt").change(function () {
        //alert($(this).val());

        var totpcnt = parseFloat($("input[name=material_smr_pcnt]").val())
            + parseFloat($("input[name=machine_smr_pcnt]").val())
            + parseFloat($("input[name=fot_smr_pcnt]").val())
            + parseFloat($("input[name=nakl_sp_smr_pcnt]").val());

        if (totpcnt > 100) {
            $(this).val(($(this).val() - (totpcnt - 100)).toFixed(2));
            totpcnt = 100;
        }

        $("#tot_pcnt").val(totpcnt.toFixed(2));

        if (totpcnt == 100)
            $("#tot_pcnt").addClass('text-success font-weight-bold')
        else
            $("#tot_pcnt").removeClass('text-success font-weight-bold');

    });

});
