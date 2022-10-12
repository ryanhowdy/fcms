function insertAlertMessage(type, id, text) {
    var alertId = 'msg-'+Math.floor(Math.random() * 10) + 2;

    $('#' + id).after(
        '<div id="' + alertId + '" class="alert-message ' + type + '">'
            + '<a href="#" class="close" onclick="$(this).fadeOut(); return false;">x</a>'
            + text +
        '</div>'
    );

    var t=setTimeout(function() { $('#' + alertId).fadeOut() }, 3000); 
}
