$(document).ready(function () {

    $(document).ready(function () {
        $('.itmtypeid').click(function () {
            $('#itmtypeid').val($(this).attr("itmtypeid"));

            // $('.itmtypeid').each(function (index) {
            //     $(this).removeClass('text-success font-weight-bold')
            // });
            $('.itmtypeid').removeClass('text-success font-weight-bold');

            $(this).addClass('text-success font-weight-bold');
        });
    });

});
