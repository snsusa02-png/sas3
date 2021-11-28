$(document).ready(function () {

    function rfr_depid_lnk() {
        if ($("#orgdepid").val())
            $("#depid_lnk").show()
        else
            $("#depid_lnk").hide()

    }

    $("#orgdepid").change(function () {
        rfr_depid_lnk()
    });


    $('#depid_lnk').click(function (e) {
        //console.log($(this).attr('id'));
        e.preventDefault();
        if ($("#orgdepid").val()) {
            var url = '/orgdeps/' + $("#orgdepid").val() + '/edit';
            window.open(url, '_blank');
        }
    });



    //при запуске -----

    rfr_depid_lnk()

});
