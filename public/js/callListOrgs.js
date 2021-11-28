function callListOrgs() {
    //открытие нового окна
	var w = 870;
	var h = 600;
	var winl = (screen.width - w) / 2;
	var wint = (screen.height - h) / 2;
	var settings = 'height=' + h + ',';
	settings += 'width=' + w + ',';
	settings += 'top=' + wint + ',';
	settings += 'left=' + winl + ',';
	settings += "copyhistory=no,directories=no,location=1,menubar=no,resizable=yes,scrollbars=yes,status=no,toolbar=0,dependent";
	DispWin = window.open("", "_NSI", settings);
	DispWin.document.Title = "Выбор организации";
	DispWin.document.location = "/orgs/list";
    if (parseInt(navigator.appVersion, 10) >= 4) { DispWin.window.focus(); }
}
