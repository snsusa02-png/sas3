$(document).ready(function () {

    var inclineSlider = document.getElementById("inclineRange");
    var inclineOutput = document.getElementById("c_incline");
    inclineOutput.value = inclineSlider.value; // Display the default slider value

// Update the current slider value (each time you drag the slider handle)
    inclineSlider.oninput = function() {
        //alert(this.value)
        //output.innerHTML = this.value;
        inclineOutput.value = this.value;
    }

    inclineOutput.oninput = function() {
        inclineSlider.value = this.value;
    }


    var azimuthSlider = document.getElementById("azimuthRange");
    var azimuthOutput = document.getElementById("c_azimuth");
    azimuthOutput.value = azimuthSlider.value; // Display the default slider value

    // Update the current slider value (each time you drag the slider handle)
    azimuthSlider.oninput = function() {
        azimuthOutput.value = this.value;
    }

    azimuthOutput.oninput = function() {
        azimuthSlider.value = this.value;
    }


});
