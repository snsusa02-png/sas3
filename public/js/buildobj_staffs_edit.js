$(document).ready(function () {

    $("#orgid").change(function () {
        //alert($("#orgid").val());
        $("#staffid > option").remove()
        $.get("/buildobjs/staff/params", {orgid: $("#orgid").val()},
            function (data) {
                console.log(data);
                //$("#tgt_addr").val(data.address);

                $("#staffid > option").remove()
                $("#staffid").append( $("<option>") )
                $.each(data.staff , function (index, value){
                   $("#staffid").append( $("<option>").attr("value", index).append(value) )
                });
            }
        )
    });

    //$("#buildobjid").change();

});
