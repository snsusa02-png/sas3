window.onload = function () {

    var unsaved = false;

    $(":input").change(function () { //triggers change in all input fields including text type
        //console.log('changed!');
        unsaved = true;
    });

    function unloadPage() {
        if (unsaved) {
            return "You have unsaved changes on this page. Do you want to leave this page and discard your changes or stay on this page?";
        }

    }

    window.onbeforeunload = unloadPage;

    $(document).on("submit", "form", function(event){
        window.onbeforeunload = null;
    });

}