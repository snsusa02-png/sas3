function setRangeByInput(sObj, rangeID) {
    if (sObj) {
        var val = sObj.value.replace(",",".");
        if (sObj.max) val = Math.min(val, sObj.max);
        if (sObj.min) val = Math.max(val, sObj.min);
        sObj.value = val;

        var slider = document.getElementById(rangeID);
        if (slider) slider.value = sObj.value;
    }
}

function setInputByRange(sObj, inputID ) {
    var input = document.getElementById(inputID);
    if (input) input.value = sObj.value;
}
