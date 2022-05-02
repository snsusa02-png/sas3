$(document).ready(function () {


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
sortByNPP();
