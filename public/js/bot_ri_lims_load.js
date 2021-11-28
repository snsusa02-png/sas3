$(document).ready(function () {


    $('#forEdit').submit(function () {

        $(".btn-close").hide();
        $(".btn-submit").prop('disabled',true).html('<i class="fa fa-floppy-o fa-spin" aria-hidden="true"></i>');

    });


});
