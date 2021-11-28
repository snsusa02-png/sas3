function hitStaff(id) {
    //обрабочик выбора
    $.ajax({
        url: "/orgstaffs/getshortinfo",
        type: 'post',
        data: {'id': id},
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (data) {
            //Используем getElementById т.к. на родительском окне
            //может не быть jquery
            var elm_id = opener.document.getElementById("staffid"),
                elm_name = opener.document.getElementById("stfname");

            if (elm_id !== undefined && elm_name !== undefined) {
                elm_id.value = data.id;
                // elm_name.value=data.name;
                elm_name.value = data.name;
            }

            // если нужно - перенос id организации
            elm = opener.document.getElementById("orgid");
            if (elm !== undefined)
                elm.value = data.orgid;

            self.close();
        }
        ,
        error: function (msg) {
            console.log(msg);
            console.log("Ошибка получения данных об сотруднике!")
        }
    });
}
