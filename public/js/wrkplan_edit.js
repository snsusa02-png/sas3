$(document).ready(function () {

    $("#begdate").change(function () {
        var begdate = moment($("#begdate").val());
        var enddate = begdate.add(14, 'days');

        $("#enddate").val(enddate.format('YYYY-MM-DD'));

        //console.log(moment.duration(fctenddt.diff(fctbegdt, 'hours', true)));
        //console.log(fctenddt.diff(fctbegdt, 'hours', true));
        //console.log(moment.utc(moment.duration(fctenddt) - moment.duration(fctbegdt)).format('HH:mm'));
    });

});
