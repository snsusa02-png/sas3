$(document).ready(function () {

    $("#salary,#bns1pcnt,#bns2pcnt").change(function () {
        //alert($("#depid").val());

        base = parseFloat($("#salary").val())
        totsum = base;

        pcnt = parseFloat($("#bns1pcnt").val())
        pcnt = (isNaN(pcnt)) ? 0 : pcnt
        $("#bns1pcnt").val(pcnt);
        bns = Math.round(base * pcnt) / 100;
        $("#bns1sum").val(bns);
        totsum += bns;

        pcnt = parseFloat($("#bns2pcnt").val())
        pcnt = (isNaN(pcnt)) ? 0 : pcnt
        $("#bns2pcnt").val(pcnt);
        bns = Math.round(base * pcnt) / 100;
        $("#bns2sum").val(bns);
        totsum += bns;

        $("#totsalary").val(totsum);

    });

});

