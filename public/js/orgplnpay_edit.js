$(document).ready(function () {

    //чекбокс для скрытия/показа строк без согласования руководителя
    function toggleAgr1() {
        var rows = document.getElementsByClassName('agr1_no');
        for (var i = 0; i < rows.length; i++) {
            if (this.checked)
                rows[i].style.display = 'none'
            else
                rows[i].style.display = ''
        }
    }

    if (document.getElementById('tglAgr1')) {
        document.getElementById('tglAgr1').onchange = toggleAgr1;
        //document.getElementById('tglAgr1').onchange = filtBy();
    }

    //чекбокс для скрытия/показа строк без согласования фин.директора
    function toggleAgr2() {
        var rows = document.getElementsByClassName('agr2_no');
        for (var i = 0; i < rows.length; i++) {
            if (this.checked)
                rows[i].style.display = 'none'
            else
                rows[i].style.display = ''
        }
    }

    if (document.getElementById('tglAgr2'))
        document.getElementById('tglAgr2').onchange = toggleAgr2;

    function filtBy() {
        //скрытие рядов таблицы состава заявки, которые в названии клиента не содержат нужный текст
        //скрытие рядов таблицы состава заявки, которые в названии материала не содежрэат нужный текст

        var input, filter, table, tr, td, i, txtValue;

        const filter1 = document.getElementById("filtByOrgName").value.toUpperCase();
        const filter2 = document.getElementById("filtByItmName").value.toUpperCase();
        //const checked3 = document.getElementById('tglAgr1').checked;
        //console.log(checked3)

        //сохраним в сессии
        set_session('filtByOrgName', filter1);
        set_session('filtByItmName', filter2);

        table = document.getElementById("items");
        //tr = table.getElementsByTagName("tr.item");
        tr = table.querySelectorAll("tr.item");

        const tgt_cnt = 2;
        for (i = 0; i < tr.length; i++) {

            var cnt = 0;

            td = tr[i].getElementsByTagName("td")[2];

            if (td) {
                txtValue = td.textContent || td.innerText;
                // console.log(txtValue)
                if (txtValue.toUpperCase().indexOf(filter1) > -1) {
                    cnt++;
                }
            } else
                cnt++;

            td = tr[i].getElementsByTagName("td")[3];
            if (td) {
                txtValue = td.textContent || td.innerText;
                // console.log(txtValue)
                if (txtValue.toUpperCase().indexOf(filter2) > -1) {
                    cnt++;
                }
            } else
                cnt++;

            // if (checked3 && tr[i].getElementsByClassName('agr1'))
            //     cnt++;
            // else if (!checked3 && tr[i].getElementsByClassName('agr1_no'))
            //     cnt++;
            // else
            //     null;


            //если кол-во совпадений соответствует целевому, то отображаем, если нет - скрываем строку
            if (cnt == tgt_cnt) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }

    if (document.getElementById('filtByOrgName'))
        document.getElementById('filtByOrgName').onkeyup = filtBy;


    if (document.getElementById('filtByItmName'))
        document.getElementById('filtByItmName').onkeyup = filtBy;

    function clearFilt() {
        document.getElementById("filtByOrgName").value = ''
        document.getElementById("filtByItmName").value = '';
        document.getElementById('tglAgr1').checked = false;
        document.getElementById('tglAgr2').checked = false;
        filtBy();
    }

    if (document.getElementById('clear_filt'))
        document.getElementById('clear_filt').onclick = clearFilt;


//при открытии - восстановить фильтр
    document.getElementById("filtByOrgName").value = get_session('filtByOrgName');
    document.getElementById("filtByItmName").value = get_session('filtByItmName');
    filtBy();

})
;
