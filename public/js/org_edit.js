$(document).ready(function () {

    function chk_kindid() {
        var kindid = $("#kindid").val();

        $(".hk1").show();
        $(".hk2").show();
        $(".hk3").show();
        if (kindid === '1') {
            $(".hk1").hide();
            //$(".hk2").show(100);
            $("#lbl_name").html('Название:');
            $("#lbl_address").html('Адрес:');

        } else if (kindid === '2') {
            //$(".hk1").show(100);
            $(".hk2").hide();
            $("#lbl_name").html('ФИО:');
            $("#lbl_address").html('Адрес регистрации:');

        } else if (kindid === '3') {
            //$(".hk1").show(100);
            //$(".hk2").hide();
            $(".hk3").hide();
            $("#lbl_name").html('ФИО:');
            $("#lbl_address").html('Адрес регистрации:');
        }

    }

    $("#kindid").change(function () {
        //alert($("#kindid").val());
        chk_kindid();
    });


    function formatDate(date) {
        var d = new Date(date),
            month = '' + (d.getMonth() + 1),
            day = '' + d.getDate(),
            year = d.getFullYear();

        if (month.length < 2)
            month = '0' + month;
        if (day.length < 2)
            day = '0' + day;

        return [year, month, day].join('-');
    }

    if ($("input[name=id]").val() == -1) {
        //api-key for shevchenko.s@basko.su
        $("#name").suggestions({
            token: "6b30c27be1bc72ecf8f904b0ef1fe2baca9b0756",
            type: "PARTY",
            /* Вызывается, когда пользователь выбирает одну из подсказок */
            onSelect: function (suggestion) {
                //console.log(suggestion);

                $(this).val(suggestion.data.name.full)
                $("input[name=inn]").val(suggestion.data.inn)
                $("input[name=kpp]").val(suggestion.data.kpp)
                $("input[name=okpo]").val(suggestion.data.okpo)
                $("textarea[name=address]").val(suggestion.data.address.unrestricted_value)
                $("textarea[name=fullname]").val(suggestion.data.name.full_with_opf)

                if (suggestion.data.type == 'LEGAL') {
                    $("input[name=boss_fullname]").val(suggestion.data.management.name)
                    $("input[name=boss_postname]").val(suggestion.data.management.post)
                }

                if (suggestion.data.type == 'LEGAL') {
                    $("#kindid").val(1)
                    $("input[name=ogrn]").val(suggestion.data.ogrn)
                } else if (suggestion.data.type == 'INDIVIDUAL') {
                    $("#kindid").val(2)
                    $("input[name=ogrnip]").val(suggestion.data.ogrn)
                }

                var begdate = '';
                if (suggestion.data.ogrn_date)
                    begdate = formatDate(new Date(suggestion.data.ogrn_date))
                $("input[name=begdate]").val(begdate)
                //console.log(begdate);

                var enddate = '';
                if (suggestion.data.state.liquidation_date)
                    enddate = formatDate(new Date(suggestion.data.state.liquidation_date))
                $("input[name=enddate]").val(enddate)
                //console.log(enddate);

                // var hours = date.getHours();
                // var minutes = "0" + date.getMinutes();
                // var seconds = "0" + date.getSeconds();
                // var formattedTime = hours + ':' + minutes.substr(-2) + ':' + seconds.substr(-2);
                // console.log(formattedTime);

                chk_kindid()
            }
        });
    }

    // Функция для проверки правильности ИНН
    function is_valid_inn(i)
    {
        if ( i.match(/\D/) ) return false;

        var inn = i.match(/(\d)/g);

        if ( inn.length == 10 )
        {
            return inn[9] == String(((
                2*inn[0] + 4*inn[1] + 10*inn[2] +
                3*inn[3] + 5*inn[4] +  9*inn[5] +
                4*inn[6] + 6*inn[7] +  8*inn[8]
            ) % 11) % 10);
        }
        else if ( inn.length == 12 )
        {
            return inn[10] == String(((
                7*inn[0] + 2*inn[1] + 4*inn[2] +
                10*inn[3] + 3*inn[4] + 5*inn[5] +
                9*inn[6] + 4*inn[7] + 6*inn[8] +
                8*inn[9]
            ) % 11) % 10) && inn[11] == String(((
                3*inn[0] +  7*inn[1] + 2*inn[2] +
                4*inn[3] + 10*inn[4] + 3*inn[5] +
                5*inn[6] +  9*inn[7] + 4*inn[8] +
                6*inn[9] +  8*inn[10]
            ) % 11) % 10);
        }

        return false;
    }


    //при открытии --------------------------------------------
    chk_kindid();

});
