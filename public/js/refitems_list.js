$(document).ready(function () {

    $(document).ready(function () {

        //зачистить предыдущее значение при открытии списка категорий
        $('#btn_itmtype_tree').click(function () {
            $('#itmtypeid').val('');
            $('#itmtypename').val('');

        });

        //выбор категории
        $('.itmtypeid').click(function () {
            $('#itmtypeid').val($(this).attr("data-id"));
            $('#itmtypename').val($(this).html());

            // $('.itmtypeid').each(function (index) {
            //     $(this).removeClass('text-success font-weight-bold')
            // });
            $('.itmtypeid').removeClass('font-weight-bold pl-1 pr-3 bg-warning');

            $(this).addClass('font-weight-bold pl-1 pr-3 bg-warning');

            $("#forIndex").submit();    //Отправим запрос формы
        });
    })

});
