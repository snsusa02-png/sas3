function clrPeriod2() {
    $("#vBegDate2").val('');
    $("#vEndDate2").val('');
}

function checkForm(mainForm, cAct) {
    if (ValidFormData(mainForm)) {
        mainForm.Action.value = cAct;
        mainForm.submit();
    }
}

function checkStr(oStr, cMsg) {
    if (oStr && oStr.value == "") {
        alert(cMsg);
        oStr.focus();
        return (false);
    }
    return (true);
}

function ValidFormData(form) {
    //alert(form.vTimeSelType.value);
    if (form.vTimeSelType.value == "2") {

        if (!checkStr(form.vBegDate1, "Укажите начало основного периода!")) return (false);
        if (!checkStr(form.vEndDate1, "Укажите окончание основного периода!")) return (false);

        if (form.vBegDate2.value != "") {
            if (!checkStr(form.vEndDate2, "Укажите окончание сравниваемого периода!")) return (false);
        }

    }

    //return false;
    return true;
}


var NS = (navigator.appName.indexOf("Netscape") != -1);
var MS = (navigator.appName.indexOf("Microsoft") != -1) || (navigator.appName.indexOf("Opera") != -1);
var VER = parseInt(navigator.appVersion);
var CSS = ((MS && VER >= 4) || (NS && VER >= 5));
var NS4 = (NS && VER == 4);
var NS6 = (NS && VER == 5);
var IE4 = (MS && VER == 4);
var IE5 = (MS && VER == 5);
var ver4 = (VER >= 4 && (MS || NS));
var ver5 = ((MS && VER >= 5) || (NS && VER >= 5));

// if (ver4) {
//     with (document) {
//         write("<style type='text/css'>");
//         if (!CSS) {
//             write(".msghead { font-size: 10pt; font-family: Arial,Helvetica; margin-top: 2pt; margin-bottom: 2pt; }");
//             write(".msgbody { margin-left:30px; background-color:#FFF2CB; font-size: 9pt; font-family: Arial,Helvetica; margin-top: 2pt; margin-bottom: 2pt; }");
//         } else {
//             write(".msghead { font-size: 10pt; font-family: Arial,Helvetica; margin-top: 2pt; margin-bottom: 2pt; }");
//             write(".msgbody { margin-left:30px; background-color:#FFF2CB; font-size: 9pt; font-family: Arial,Helvetica; margin-top: 2pt; margin-bottom: 2pt; display:none; }");
//             write(".shw_hid { display:none; }");
//         }
//         write("</style>");
//     }
// }

function expandEl1(el) {
    if (!CSS) return;
    if (IE4) {
        whichEl = eval("b" + el);
    } else {
        whichEl = document.getElementById("b" + el);
    }
    if (whichEl) {
        whichEl.style.display = "inline-block";
        if (!MS && whichEl.tagName && whichEl.tagName.toLowerCase() == 'tr')
            whichEl.style.display = "table-row";
    }
}

function splitEl1(el) {
    if (!CSS) return;

    if (IE4) {
        whichEl = eval("b" + el);
    } else {
        whichEl = document.getElementById("b" + el);
    }
    if (whichEl) whichEl.style.display = "none";
}

//-----------------------------------------------------------------------------------------------------------------------------

function vTimeSelType_change(obj) {
    // var v = document.form.vTimeSelType.value;
    var v = obj.value;
    //console.log(v);
    if (v == 2) {
        $("#bTimeSelType_2").show();
        $("#bTimeSelType_1").hide();
    } else {
        $("#bTimeSelType_2").hide();
        $("#bTimeSelType_1").show();
    }
}

function vYr1_change() {
    var v = $("#vYr1").val();

    if (v == 0) {
        $("#bMn1").hide();
        $("#bDc1").hide();
        $("#bWk1").hide();
    } else {
        $("#bMn1").show();
        $("#bDc1").hide();
        $("#bWk1").show();

        $("#vWk1").val(0);
    }
}

function vMn1_change() {
    var v = $("#vMn1").val();

    if (v == 0) {
        $("#vDc1").val(0);
        $("#bWk1").show();
        $("#bDc1").hide();
    } else {
        $("#vWk1").val(0);
        // $("#bDc1").show();
        $("#bWk1").hide();
    }
}

function vWk1_change() {
    var v = $("#vWk1").val();

    if (v == 0) {
        $("#bMn1").show();
    } else {
        $("#vMn1").val(0);
        $("#vDc1").val(0);
        $("#bMn1").hide();
        $("#bDc1").hide();
    }
}


function vYr2_change() {
    var v = $("#vYr2").val();
    if (v == 0) {
        $("#vMn2").val(0);
        $("#vDc2").val(0);
        $("#vWk2").val(0);
        $("#bMn2").hide();
        $("#bDc2").hide();
        $("#bWk2").hide();
    } else {
        var v = $("#vMn2").val();
//console.log('vMn2='+v);
        if (v != 0) {
            $("#bMn2").show();
            // $("#bDc2").show();
            $("#bWk2").hide();
        } else if ($("#vWk2").val() != 0) {
            $("#bMn2").hide();
            $("#bDc2").hide();
            $("#bWk2").show();
        } else {
            $("#bMn2").show();
            $("#bWk2").show();
        }
    }
    ShowHideAuxElms();
}

function vMn2_change() {
    var v = $("#vMn2").val();

    if (v == 0) {
        $("#vDc2").val(0);
        $("#bWk2").show();
        $("#bDc2").hide();
    } else {
        $("#vWk2").val(0);
        // $("#bDc2").show();
        $("#bWk2").hide();
    }
}

function vWk2_change() {
    var v = $("#vWk2").val();

    if (v == 0) {
        expandEl1("Mn2").show();
    } else {
        $("#vMn2").val(0);
        $("#vDc2").val(0);
        $("#bMn2").hide();
        $("#bDc2").hide();
    }
}


function vBuyerType_change() {
    var v = document.form.vBuyerType.value;
    //console.log(v);
    if (v == "-OrgID-") {
        $("#lblBuyer").html("Покупатель");

        splitEl1("OrgGrpID");
        splitEl1("PrsnGrpID");

        expandEl1("OrgID").show();
    } else if (v == "-OrgGrpID-") {
        $("#lblBuyer").html("Группа ю/л");

        splitEl1("OrgID");
        splitEl1("PrsnGrpID");

        expandEl1("OrgGrpID");
    } else if (v == "-PrsnGrpID-") {
        $("#lblBuyer").html("Группа персон");

        splitEl1("OrgID");
        splitEl1("OrgGrpID");

        expandEl1("PrsnGrpID")
        show();
    } else {
        document.form.OrgID.value = "";
//		document.form.OrgGrpID.value = "";
        splitEl1("OrgID");
        splitEl1("OrgGrpID");
        splitEl1("PrsnGrpID");

        $("#lblBuyer").html("");
    }
}


function ParamRecName_blur() {
    var v = document.form.ParamRecName.value;
    console.log(v);
    if (v != "") {
        expandEl1("SaveUsrParamSet");
    } else {
        splitEl1("SaveUsrParamSet");
    }
}

function vUsrParamSetID_change() {
    var v = document.form.vUsrParamSetID.value;
    console.log(v);
    if (v != "") {
        expandEl1("LoadUsrParamSet");
    } else {
        splitEl1("LoadUsrParamSet");
    }
}

function ShowHideAuxElms() {
    var cnt = $('#list2 option').length;
    console.log(cnt);
    if (cnt == 1) {

        if ($('#vYr2').val() == '') {
            $('#bOrdbyItmSum').show();
            $('#bOrdbyDocQty').show();
        }

    } else if (cnt > 1) {
        //показать чекбокс с подитогами
        $('#bShowGrpSum').show();
        $('#bOrdbyItmSum').hide();
        $('#bOrdbyDocQty').hide();
    } else {
        $('#bShowGrpSum').hide();
        $('#bOrdbyItmSum').hide();
        $('#bOrdbyDocQty').hide();
    }
}


$(document).ready(function () {

    ShowHideAuxElms();

    $('#list1').dblclick(function () {
        $("#GrpLst").val($("#GrpLst").val() + ',' + $('#list1 option:selected').val());
        // return !$('#list1 option:selected').remove().appendTo('#list2');
        $('#list1 option:selected').remove().appendTo('#list2');

        ShowHideAuxElms();
        return;
    });

    $('#list2').dblclick(function () {
        $("#GrpLst").val($("#GrpLst").val().replace(',' + $('#list2 option:selected').val(), ''));
        // return !$('#list2 option:selected').remove().appendTo('#list1');

        $('#list2 option:selected').remove().appendTo('#list1')

        ShowHideAuxElms();
        return;
    });

    $('#add').click(function () {
        $("#GrpLst").val($("#GrpLst").val() + ',' + $('#list1 option:selected').val());
        $('#list1 option:selected').remove().appendTo('#list2');

        ShowHideAuxElms();
        return;
    });
    $('#remove').click(function () {
        $("#GrpLst").val($("#GrpLst").val().replace(',' + $('#list2 option:selected').val(), ''));
        $('#list2 option:selected').remove().appendTo('#list1');

        ShowHideAuxElms();
        return;
    });
    $('#allremove').click(function () {
        $("#GrpLst").val('');
        $('#list2 option').remove().appendTo('#list1');

        ShowHideAuxElms();
        return;
    });


    // $("#vBegDate1").datepicker({
    //     changeMonth: true,
    //     changeYear: true,
    //     showOn: "button",
    //     buttonImage: "images/calendar.gif",
    //     buttonImageOnly: true,
    //     buttonText: "Укажите начало основного периода",
    //     onSelect: function (selected, evnt) {
    //         var o = $("#vEndDate1");
    //         if (o.val() == "") o.val(selected);
    //     }
    // });
    //
    // $("#vEndDate1").datepicker({
    //     changeMonth: true,
    //     changeYear: true,
    //     showOn: "button",
    //     buttonImage: "images/calendar.gif",
    //     buttonImageOnly: true,
    //     buttonText: "Укажите окончание основного периода"
    // });
    //
    // $("#vBegDate2").datepicker({
    //     changeMonth: true,
    //     changeYear: true,
    //     showOn: "button",
    //     buttonImage: "images/calendar.gif",
    //     buttonImageOnly: true,
    //     buttonText: "Укажите начало сравниваемого периода",
    //     onSelect: function (selected, evnt) {
    //         if ($("#vEndDate2").val() == "") $("#vEndDate2").val(selected);
    //     }
    // });
    //
    // $("#vEndDate2").datepicker({
    //     changeMonth: true,
    //     changeYear: true,
    //     showOn: "button",
    //     buttonImage: "images/calendar.gif",
    //     buttonImageOnly: true,
    //     buttonText: "Укажите окончание сравниваемого периода"
    // });



    $("#orgname").autocomplete({
        source: function (request, response) {
            $.ajax({
                url: "/orgs/autocomplete/search",
                //url: "/api/orgs/for_",
                dataType: "json",
                data: {
                    q: request.term,
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (data) {
                    //console.log(data);
                    response($.map(data, function (item, index) {
                        if (index == 16) {
                            var n = data.length - 16;
                            return {
                                // label: "- Показаны не все варианты (есть еще " + n + " записей), уточните критерий поиска!"
                                label: " ... Показаны не все варианты! Уточните критерий поиска!"
                            }
                        }

                        if (index > 16) return null;

                        var lbl = item.name + " ( ИНН: " + (item.inn ? item.inn : '-') + ", КПП: " + (item.kpp ? item.kpp : '-') + ")";
                        return {
                            label: lbl,
                            value: item.name,
                            id: item.id
                        }
                    }));
                }
            });
        },
        delay: 250,
        minLength: 1,
        autoFill: true,
        cacheLength: 1,
        // autoFocus: true,

        select: function (event, ui) {
            if (ui.item.id) {
                $('#orgid').val(ui.item.id);
                // $(this).val(ui.item.value);
                $(this).val(ui.item.label);

                // $("#ac_orgid").hide().val("ok").removeClass("ac-fail");
                $("#ac_orgid").hide().removeClass("ac-fail");
                $(this).addClass("ac-act");
            }
            event.preventDefault();
        },
        search: function () {
            $('#orgid').val('');
            $(this).removeClass("ac-fail").removeClass("ac-warn").addClass("ac-act");
            $("#ac_orgid").val("поиск...")
                .removeClass("ac-fail").removeClass("ac-warn")
                .addClass("ac-act").show();
        },
        response: function (event, ui) {
            $(this).removeClass("ac-act");
            if (ui.content.length == 0) {
                $('#ac_orgid').val('Варианты не найдены.')
                    .removeClass("ac-act").addClass("ac-fail");
                $(this).addClass("ac-fail");
            } else if (ui.content.length > 15) {
                $('#ac_orgid').val('Показаны не все варианты! Уточните критерий')
                    .removeClass("ac-act").addClass("ac-warn");
            } else {
                //console.log(ui.content);
                $("#ac_orgid").hide().val("");
            }
        }
    })
        .on('focus', function (event) {
            $(this).select();
        })
        .on('blur', function (event) {
            if ($(this).val().length == 0) {
                $('#orgid').val('');
                $('#orgname').removeClass("ac-act").addClass("ac-fail");
                $('#ac_orgid').val('Укажите организацию!').show()
                    .removeClass("ac-act").addClass("ac-fail");
            } else
                $('#ac_orgid').hide().val("");
        })
        .data('ui-autocomplete')._renderItem = function (ul, item) {
        //thanks to Salman Arshad for icon and match highlighting code
        //http://salman-w.blogspot.ca/2013/12/jquery-ui-autocomplete-examples.html
        //!подсвечивает только если поиск производится по одному слову.
        var $div = $("<div></div>");
        if (item.icon) {
            $("<img class='m-icon'>").attr("src", "/images/" + item.icon).appendTo($div);
        } else {
            $("<span class='x-icon'></span>").appendTo($div);
        }
        var mName = $("<span class='m-name'></span>").html(item.label).appendTo($div),
            searchText = $.trim(this.term).toLowerCase(),
            currentNode = mName.get(0).firstChild,
            matchIndex, newTextNode, newSpanNode;

        while ((matchIndex = currentNode.data.toLowerCase().indexOf(searchText)) >= 0) {
            newTextNode = currentNode.splitText(matchIndex);
            currentNode = newTextNode.splitText(searchText.length);
            newSpanNode = document.createElement("span");
            newSpanNode.className = "highlight";
            currentNode.parentNode.insertBefore(newSpanNode, currentNode);
            newSpanNode.appendChild(newTextNode);
        }
        return $("<li></li>").append($div).appendTo(ul);
    };


});
