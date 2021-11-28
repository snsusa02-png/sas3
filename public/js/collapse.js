$(document).ready(function () {

    $('.collapse').on('shown.bs.collapse', function (evnt) {
        //console.log(evnt);
        // console.log($(this).attr('id'));
        // console.log(this.id);
        // console.log(evnt.target.id);

        //sessionStorage.SessionName = "SessionData";

        sessionStorage.setItem(this.id + '_collapse', "show");
        //console.log('collapse: ' + this.id + ': ' + sessionStorage.getItem(this.id));
    });

    $('.collapse').on('hidden.bs.collapse', function () {
        //alert('hidden:'+this.id);
        sessionStorage.setItem(this.id + '_collapse', "hide");
        //console.log('collapse: ' + this.id + ': ' + sessionStorage.getItem(this.id));
    })


    //$.each( $('.collapse'), function( key, value ) {
    $('.collapse').each(function (key, value) {
        //alert( key + ": " + value );
        state = sessionStorage.getItem(this.id + '_collapse');
        //console.log(this.id + '_collapse' + ': ' + state);
        if (state == 'show')
            $(this).collapse('show');
        else
            $(this).collapse('hide');

    });

});
