function hitOrg(id) {
    // hitfield_id, hitfield_name должны быть определены в родительском скрипте

    // console.log(hitfield_id);
    // console.log(hitfield_name);
    // alert(hitfield_id)

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

            var elm_id = opener.document.getElementById(hitfield_id),
                elm_name = opener.document.getElementById(hitfield_name);

            if (elm_id !== undefined && elm_name !== undefined) {
                elm_id.value = data.id;
                // elm_name.value=data.name;
                elm_name.value = data.name
                    + " ( ИНН: " + (data.inn ? data.inn : '-')
                    + ", КПП: " + (data.kpp ? data.kpp : '-') + ")";
            }
            self.close();
        },
        error: function (msg) {
            console.log("Ошибка получения данных об организации!")
        }
    });
}
