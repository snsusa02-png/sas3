$(document).ready(function () {

    $(window).scroll(function () {

// console.log($(this).scrollTop())
// console.log($(window).height())
// console.log($(".container").height())

        if ($(this).scrollTop()+$(window).height() < $(".container").height()) {
            // длительность анимации - 'slow'
            // тип анимации -  'linear'
            $('.scrolldown').fadeIn('slow', 'linear');
        } else {
            // длительность анимации - 'fast'
            // тип анимации -  'swing'
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
