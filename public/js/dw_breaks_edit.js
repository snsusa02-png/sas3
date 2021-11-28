$(document).ready(function () {

    function recalc_hrs() {
        const begtime = $("#begtime").val();
        const endtime = $("#endtime").val();

        var hrs = '-';
        if (begtime && endtime) {
            const t1 = begtime.split(':'), t2 = endtime.split(':');
            const d1 = new Date(0, 0, 0, t1[0], t1[1]),
                d2 = new Date(0, 0, 0, t2[0], t2[1]);
            hrs = Math.round(10 * ((d2 - d1) / 3600000)) / 10;
        }
        $("#breakhrs").val(hrs);
        //console.log(hrs)
    }

    $("#begtime, #endtime").change(function () {
        recalc_hrs();
    });
});
