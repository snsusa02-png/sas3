$(document).ready(function () {

    //добавление подсветки для непустых полей
    $(".form-control").change(function () {
        //console.log($(this).val())
        if ($(this).val())
            $(this).addClass('searchby');
        else
            $(this).removeClass('searchby');
    });

    $('.form-control').each(function(index, value) {
        //console.log(`${index}: ${this.id}`);
        if ($(this).val())
            $(this).addClass('searchby');
        else
            $(this).removeClass('searchby');
    });

    //инициализация подсказок
    $('[data-toggle="tooltip"]').tooltip();

    $(window).scroll(function () {

        if ($(this).scrollTop()+$(window).height() < $(".container-fluid").height()) {
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

});
