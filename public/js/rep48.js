$(document).ready(function () {

    $("[data-num]").click(function () {
        if ($(this).hasClass('clicked'))
            $(this).removeClass('bg-warning clicked');
        else
            $(this).addClass('bg-warning clicked');

        let cnt = 0;
        let totSum = 0;
        $('.clicked').each(function (i, obj) {
            cnt++
            totSum += parseFloat($(obj).data('num'));
        });
        if (cnt > 0) {
            $("#_calc_selected_sum").html('sum: ' + totSum).show()
            //$("#_calc_selected_sum").show()
        } else {
            $("#_calc_selected_sum").hide()
        }
    });

});

function sortByNPP() {
    let tbody = document.querySelector("#results tbody");
    // получим все строки таблицы как массив
    let rows = [].slice.call(tbody.querySelectorAll("tr"));
    let sort_btn = document.querySelector("#sort_1");
    let sort_dir = -1*parseInt(sort_btn.dataset.dir);
    if (sort_dir === 1) {
        sort_btn.innerHTML = '<i class="fa fa-sort-desc" aria-hidden="true"></i>';
        $(".day_sums").show();
    } else{
        sort_btn.innerHTML = '<i class="fa fa-sort-asc" aria-hidden="true"></i>';
        $(".day_sums").hide();
    }
    sort_btn.dataset.dir = sort_dir.toString();

    rows.sort(function (a, b) {
        return sort_dir * ((a.cells[0].dataset.npp) - (b.cells[0].dataset.npp));
    });

    rows.forEach(function (v) {
        tbody.appendChild(v); // note that .appendChild() *moves* elements
    });
}

document.querySelector("#sort_1").addEventListener("click", sortByNPP);
//2025-03-30 Временно отменим сортировку - на больших объемах не работает!
// пока оставил как есть
sortByNPP();
