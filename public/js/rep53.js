$(document).ready(function () {

    $("[data-num]").click(function () {
        if ($(this).hasClass('clicked'))
            $(this).removeClass('bg-warning clicked');
        else
            $(this).addClass('bg-warning clicked');

        cnt = totSum = 0;
        $('.clicked').each(function (i, obj) {
            cnt++
            totSum += parseFloat($(obj).data('num'));
        });
        if (cnt > 0) {
            $("#_calc_selected_sum").html('sum: ' + totSum)
            $("#_calc_selected_sum").show()
        } else {
            $("#_calc_selected_sum").hide()
        }
    });
});
