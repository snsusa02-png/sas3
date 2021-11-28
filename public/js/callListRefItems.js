function callListRefItems(orgid) {
    //открытие нового окна

    var w = Math.max(870, window.innerWidth - 480);
    var h = Math.max(780, window.innerHeight - 80);

    var winl = (screen.width - w) / 2;
    var wint = (screen.height - h) / 2;
    var settings = 'height=' + h + ',';
    settings += 'width=' + w + ',';
    settings += 'top=' + wint + ',';
    settings += 'left=' + winl + ',';
    settings += "copyhistory=no,directories=no,location=1,menubar=no,resizable=yes,scrollbars=yes,status=no,toolbar=0,dependent";
    DispWin = window.open("", "_NSI", settings);
    DispWin.document.Title = "Выбор из прайслиста";
    DispWin.document.location = "/refitems/list?svc=*&orgid=" + orgid;
    if (parseInt(navigator.appVersion, 10) >= 4) {
        DispWin.window.focus();
    }
}
