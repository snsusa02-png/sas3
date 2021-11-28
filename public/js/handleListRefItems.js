function hLRI(id) {
    //обработчик выбора
    var orgid = $("#orgid").val();
    $.ajax({
        url: "getshortinfo",
        type: 'post',
        data: {'id': id, 'orgid': orgid},
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (data) {
            //Используем getElementById т.к. на родительском окне
            //может не быть jquery

            //console.log(data);
            var elm_id = opener.document.getElementById("refitmid"),
                elm_name = opener.document.getElementById("itmname") ?? opener.document.getElementById("refitmname"),
                elm_price = opener.document.getElementById("est_price"),
                elm_code = opener.document.getElementById("refitmcode") ?? opener.document.getElementById("code")


            if (elm_id !== null && elm_name !== null) {
                elm_id.value = data.id;
                elm_name.value = data.name;
                elm_name.classList.add("ac-success");

                if (elm_price !== null)
                    elm_price.value = data.price;
                if (elm_code !== null)
                    elm_code.value = data.id;
            }

            var elm_unit = opener.document.getElementById("unit");
            if (elm_unit !== null) {
                elm_unit.value = data.unit;
                elm_unit.readOnly = true;
            }
            var elm_unittypeid = opener.document.getElementById("unittypeid");
            if (elm_unittypeid) {
                elm_unittypeid.value = data.unittypeid;

                elm_unittypeid_aux = opener.document.getElementById("unittypeid_aux");
                if (elm_unittypeid_aux) {
                    elm_unittypeid_aux.value = data.unittypeid;
                    elm_unittypeid.disabled = true;
                    elm_unittypeid_aux.disabled = false;
                }
            }
            var elm_unit = opener.document.getElementById("unit_html");
            if (elm_unit !== null) {
                elm_unit.innerHTML = data.unit;
                elm_unit.readOnly = true;
            }

            elm_qty = opener.document.getElementById("rqst_qty") ?? opener.document.getElementById("qty")
            if (elm_qty !== null)
                elm_qty.focus();


            self.close();
        },
        error: function (msg) {
            console.log("Ошибка получения данных о товаре")
        }
    });
}
