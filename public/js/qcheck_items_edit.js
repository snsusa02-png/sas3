$(document).ready(function () {

    $("#resporgid").change(function () {
        //alert($("#orgid").val());
        // alert($("#buildobjid").val());
        $("#respstaffid > option").remove()
        $.get("/buildobjs/buildobjstaff/params", {orgid: $("#resporgid").val(),buildobjid: $("#buildobjid").val()},
            function (data) {
                $("#respstaffid > option").remove()
                $("#respstaffid").append( $("<option>") )
                $.each(data.staff , function (index, value){
                   $("#respstaffid").append( $("<option>").attr("value", index).append(value) )
                });
            }
        )
    });

});
