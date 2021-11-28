$(document).ready(function () {

    $(".form-control").change(function () {
        //console.log($(this).val())
        if ($(this).val())
            $(this).addClass('searchby');
        else
            $(this).removeClass('searchby');
    });

    $('.form-control').each(function (index, value) {
        //console.log(`${index}: ${this.id}`);
        if ($(this).val())
            $(this).addClass('searchby');
        else
            $(this).removeClass('searchby');
    });

    //инициализация подсказок
    $('[data-toggle="tooltip"]').tooltip();

    $(window).scroll(function () {

        if ($(this).scrollTop() + $(window).height() < $(".container-fluid").height()) {
            $('.scrolldown').fadeIn('slow', 'linear');
        } else {
            $('.scrolldown').fadeOut('fast', 'swing');
        }

        if ($(this).scrollTop() >= 100) {
            // длительность анимации - 'slow'
            // тип анимации -  'linear'
            $('.scrollup').fadeIn('slow', 'linear');
        } else {
            // длительность анимации - 'fast'
            // тип анимации -  'swing'
            $('.scrollup').fadeOut('fast', 'swing');
        }
    });


    function s_ocl_itmid_rfr() {
        //console.log($("#s_ownorgid").val());

        var selector = "#s_ocl_itmid";
        var save_ID = $(selector).val();

        $(selector + " > option").remove();

        if ($("#s_ownorgid").val()) {
            $.get("/api/ocl_items/params", {
                    orgid: $("#s_ownorgid").val(),
                    in_documents: 1,
                },

                function (data) {
                    //console.log(data);
                    $(selector + " > option").remove();
                    $(selector).append($("<option>"));
                    $.each(data.ocl_items, function (index, value) {
                        $(selector).append($("<option>").attr("value", index).append(value))
                    });
                    $(selector).val(save_ID);
                }
            )
        }
    }

    function s_ocl_depid_rfr() {
        //console.log($("#s_ownorgid").val());

        var selector = "#s_ocl_depid";
        var save_ID = $(selector).val();

        $(selector + " > option").remove();

        if ($("#s_ownorgid").val()) {
            $.get("/api/orgdeps/for_", {
                    orgid: $("#s_ownorgid").val(),
                    in_ocl_items_with_documents: 1,
                },

                function (data) {
                    //console.log(data);
                    $(selector + " > option").remove();
                    $(selector).append($("<option>"));
                    $.each(data.orgdeps, function (index, value) {
                        $(selector).append($("<option>").attr("value", index).append(value))
                    });
                    $(selector).val(save_ID);
                    //console.log(save_ID)
                }
            )
        }
    }

    $("#s_ownorgid").change(function () {
        s_ocl_itmid_rfr();
        s_ocl_depid_rfr();
    });


    // При запуске ----------------------------
    //-----------------------------------------
});
