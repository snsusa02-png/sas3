$(document).ready(function () {

    $("#itmname").autocomplete({
        source: function (request, response) {
            $.ajax({
                url: "/bot_ri_lims/autocomplete/refitems",
                dataType: "json",
                data: {
                    q: request.term,
                    //svc: 0, //И товары, и услуги
                    it: $('#itmtypeid').val(),
                    cwp: $('#cwp_id').val(),
                    bot: $('#buildopertypeid').val(),
                    rid: $('#id').val(),
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (data) {
                    //
                    // console.log(n);
                    response($.map(data, function (item, index) {
                        if (index == 16) {
                            var n = data.length - 16;
                            return {
                                // label: "- Показаны не все варианты (есть еще " + n + " записей), уточните критерий поиска!"
                                label: " ... Показаны не все варианты! Уточните критерий поиска!"
                            }
                        }

                        if (index > 16) return null;

                        // var lbl = item.code + " | " + item.name;
                        // lbl = lbl + " (категория: " + item.itname;
                        // if (item.specinfo) lbl = lbl + "; " + item.specinfo;
                        // lbl = lbl + "; цена: " + item.price + " &#x20bd;)";

                        var lbl = item.name;
                        //lbl += " / " + item.unittypename;
                        if (item.itname) lbl += " / категория: " + item.itname;
                        //if (item.specinfo) lbl += "/ " + item.specinfo;
                        if (item.qty) lbl += "/ лимит: " + item.qty + item.unittypename;
                        if (item.price) lbl += "/ цена: " + item.price + " &#x20bd;";

                        return {
                            label: lbl,
                            value: item.name,
                            price: item.price,
                            code: item.code,
                            icon: item.photourl,
                            specinfo: item.specinfo,
                            unit: item.unittypename,
                            unittypeid: item.unittypeid,
                            decimal_dgts: item.decimal_dgts,
                            qty: item.qty,
                            ri_limid: item.ri_limid,
                            id: item.id
                        }
                    }));
                }
            });
        },
        delay: 300,
        minLength: 2,
        autoFill: true,
        cacheLength: 1,
        autoFocus: true,

        select: function (event, ui) {
            if (ui.item.id) {
                $('#refitmid').val(ui.item.id);
                //console.log(ui.item.price);
                $('#est_price').val(ui.item.price).change();
                $('#code').val(ui.item.code);

                $('#unittypeid').val(ui.item.unittypeid).prop('disabled', true);
                $('#unittypeid_aux').val(ui.item.unittypeid).prop('disabled', false);

                $('#unit').val(ui.item.unit).prop('readonly', 'readonly');

                $('#qty').val(ui.item.qty)
                        .prop('max', ui.item.qty)
                        .prop('step', 1/10**ui.item.decimal_dgts);

                $('#ri_limid').val(ui.item.ri_limid);

                $('#specinfo').html(ui.item.specinfo);
                $(this).val(ui.item.value);
                $("#ac_refitmid").hide().val("").removeClass("ac-fail");
            }
            event.preventDefault();
        },
        search: function () {
            $('#refitmid').val('');
            $(this).removeClass("ac-fail").removeClass("ac-warn").addClass("ac-act");
            $("#ac_refitmid").val("поиск...")
                .removeClass("ac-fail").removeClass("ac-warn")
                .addClass("ac-act").show();
        },
        response: function (event, ui) {
            $(this).removeClass("ac-act");
            if (ui.content.length == 0) {
                $('#ac_refitmid').val('Варианты не найдены.')
                    .removeClass("ac-act").addClass("ac-fail");
                $(this).addClass("ac-fail");
                //$('#unit').prop('readonly', '');
                $('#unittypeid').prop('disabled', false);
                $('#unittypeid_aux').val('').prop('disabled', true);
            } else if (ui.content.length > 15) {
                $('#ac_refitmid').val('Показаны не все варианты! Уточните критерий')
                    .removeClass("ac-act").addClass("ac-warn");
            } else {
                //console.log(ui.content);
                $("#ac_refitmid").hide().val("");
            }
        }
    })
        .on('focus', function (event) {
            $(this).select();
        })
        .on('blur', function (event) {
            $('#ac_refitmid').hide().val("");
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
