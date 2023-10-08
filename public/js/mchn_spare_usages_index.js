$(document).ready(function () {

    $("#s_timestatuscode").change(function (e) {

        if ($("#s_timestatuscode").val() == 5) {
            $("#s_operdate").show();
        } else {
            $("#s_operdate").hide();
        }
    });

    //$("#s_wrkdate").change(function (e) {
    $("#s_operdate").focusout(function (e) {
        $(this).closest("form").submit();
        //console.log('focusout')
    });

    if ($("#s_timestatuscode").val() == 5)
        $("#s_operdate").show();
    else
        $("#s_operdate").hide();

    //добавление подсветки для непустых полей
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

    // настройки для DataList
    $('[list]').focusin(function(){
        //выделение всего текста при входе в input
        $(this).css("background-color", "#FFFFCC").select();
    })
    $('[list]').change(function(){
        // отправка формы при изменении данных
        $(this).closest("form").submit();
    })

});
