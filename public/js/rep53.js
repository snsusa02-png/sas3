$(document).ready(function () {

    $("[data-num]").click(function () {
        if ($(this).hasClass('clicked'))
            $(this).removeClass('bg-warning clicked');
        else
            $(this).addClass('bg-warning clicked');

        cnt = totSum = 0;
        $('.clicked').each(function (i, obj) {
            cnt++
            totSum += parseFloat($(obj).data('num'));
        });
        if (cnt > 0) {
            $("#_calc_selected_sum").html('sum: ' + totSum)
            $("#_calc_selected_sum").show()
        } else {
            $("#_calc_selected_sum").hide()
        }
    });
});


function convertDate(d) {
    var p = d.split(".");
    //console.log(parseInt(p[2])*10000+ parseInt(p[1])*100 + parseInt(p[0]))
    //return +(p[2] + p[1] + p[0]);
    return +(parseInt(p[2]) * 10000 + parseInt(p[1]) * 100 + parseInt(p[0]));
}

function sortByDate() {
    var tbody = document.querySelector("#results tbody");
    // get trs as array for ease of use
    var rows = [].slice.call(tbody.querySelectorAll("tr"));

    rows.sort(function (a, b) {
        console.log(convertDate(a.cells[0].innerHTML))
        console.log(convertDate(b.cells[0].innerHTML))
        return -(convertDate(a.cells[0].innerHTML) - convertDate(b.cells[0].innerHTML));
    });

    rows.forEach(function (v) {
        tbody.appendChild(v); // note that .appendChild() *moves* elements
    });
}

function sortByNPP() {
    var tbody = document.querySelector("#results tbody");
    alert('tbody')
    // получим все строки таблицы как массив
    var rows = [].slice.call(tbody.querySelectorAll("tr"));
    alert('rows')
    var sort_btn = document.querySelector("#sort_1");
    alert('sort_btn')
    var sort_dir = -1*sort_btn.dataset.dir;
    alert('sort_dir')
    if (sort_dir == 1) {
        sort_btn.innerHTML = '<i class="fa fa-sort-desc" aria-hidden="true"></i>';
    } else{
        sort_btn.innerHTML = '<i class="fa fa-sort-asc" aria-hidden="true"></i>';
    }
    sort_btn.dataset.dir = sort_dir;

    rows.sort(function (a, b) {
        return sort_dir * ((a.cells[0].dataset.npp) - (b.cells[0].dataset.npp));
    });
    alert('after sort')

    rows.forEach(function (v) {
        tbody.appendChild(v); // note that .appendChild() *moves* elements
    });
    alert('after appendChild')
}

//document.querySelector("#sort_1").addEventListener("click", sortByDate);
document.querySelector("#sort_1").addEventListener("click", sortByNPP);
document.querySelector("#sort_1").addEventListener("touchend", sortByNPP);
sortByNPP();
