$(document).ready(function () {


    $("#wrkbegdate").change(function () {

        var begdt = moment($("#wrkbegdate").val());
        var enddt = begdt.clone();
        enddt = enddt.endOf('month');

        //$("#wrkbegdt").val(enddt.format('YYYY-MM-DD[T]HH:mm'));
        $("#wrkenddate").val(enddt.format('YYYY-MM-DD'));
        $("#wrkenddate").attr("min", begdt.format('YYYY-MM-DD') );
        $("#wrkenddate").attr("max", enddt.format('YYYY-MM-DD') );

    });


    function formatDate(date) {
        var d = new Date(date),
            month = '' + (d.getMonth() + 1),
            day = '' + d.getDate(),
            year = d.getFullYear();

        if (month.length < 2)
            month = '0' + month;
        if (day.length < 2)
            day = '0' + day;

        return [year, month, day].join('-');
    }



    //при открытии --------------------------------------------

});
