function hitOrg(id) {
    //обрабочик выбора
    $.ajax({
        url: "getshortinfo",
        type: 'post',
        data: {'id': id},
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (data) {
            //Используем getElementById т.к. на родительсом окне
            //может не быть jquery

            var elm_id = opener.document.getElementById("orgid"),
                elm_name = opener.document.getElementById("orgname");

            if (elm_id !== undefined && elm_name !== undefined) {
                elm_id.value = data.id;
                // elm_name.value=data.name;
                elm_name.value = data.name
                    + " ( ИНН: " + (data.inn ? data.inn : '-')
                    + ", КПП*: " + (data.kpp ? data.kpp : '-') + ")";

                //для того чтобы сработал зависимый код при изменении поля
                var event = new Event('change');
                elm_id.dispatchEvent(event);
            }
            self.close();
        },
        error: function (msg) {
            console.log("Ошибка получения данных об организации!")
        }
    });
}
