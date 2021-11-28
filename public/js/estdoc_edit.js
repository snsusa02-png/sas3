$(document).ready(function () {

    $("#buildobjid").change(function () {
        //alert($("#buildobjid").val());
        $("#buildopertypeid > option").remove()
        $.get("/buildobjs/buildopertypes/params", {buildobjid: $("#buildobjid").val()},
            function (data) {
                //console.log(data);
                $("#tgt_addr").val(data.address);

                //var validOpers = validOpers.split(',');
                $("#buildopertypeid > option").remove()
                $("#buildopertypeid").append( $("<option>") )
                $.each(data.opertypes , function (index, value){
                   $("#buildopertypeid").append( $("<option>").attr("value", index).append(value) )
                });

                // $("#need_relwrh").val(data.need_relwrh);
                // $("#need_predoc").val(data.need_predoc);

                // if (data.need_relwrh == "1") $("#relwrh").show()
                // else $("#relwrh").hide();
                //
                // if (data.need_predoc == "1") $("#predoc").show()
                // else $("#predoc").hide();
            }
        )
    });





    //$("#buildobjid").change();

});
