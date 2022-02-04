$(document).ready(function () {

    //на изменение ID заполняемого по автокомплиту
    $(".ac_id").change(function () {
        //отработаем скрытие/открытие кнопки со ссылкой на выбраный элмент спр-ка в зависимости от наличия значения в id
        if ($(this).val()) {
            $(this).parent().find('.id_lnk').hide()
            const id_lnk = $(this).parent().find('.id_lnk');
            if (id_lnk && id_lnk.data('id') && id_lnk.data('obj'))
                $(this).parent().find('.id_lnk').show() //отобразить ссылку на карточку редактирования объекта справочника
        } else {
            $(this).parent().find('.id_lnk').hide()
        }
    });

    $('.id_lnk').click(function (e) {
        //переход в элемент справочника

        e.preventDefault();

        //console.log($(this).data('obj'));
        const chkfld_id = $(this).data('id');
        if (chkfld_id) {
            const id = $("#" + chkfld_id).val();
            const ref = $(this).data('obj');
            if (id && ref) {
                var url = "/" + ref + "/" + id + "/edit";
                window.open(url, '_blank');
            }
        }
    });

    //все остальные - скрываем / показываем от обратного!
    // $('.id_lnk').each( (key, value) => {
    //$.each($(".id_lnk"), function (key, value) {
    $(".id_lnk").each( function (key, value) {
        // console.log(key)
        // console.log(value)
        // console.log($(this).parent().find('.ac_id').val())
        // console.log($(value).parent().find('.ac_id').val())
        // console.log($(this).data('id'))
        // console.log($('#' + $(this).data('id')).val())


        const id_lnk = $(this);
        id_lnk.hide()

        //если в связанном поле есть значение, то показываем кнопку-ссылку на карточку редактирования объекта справочника
        if (id_lnk && id_lnk.data('id') && id_lnk.data('obj') && $('#' + id_lnk.data('id')).val())
            id_lnk.show() //отобразить ссылку на карточку редактирования объекта справочника
    });

});

