function insertAlertMessage(type, id, text) {
    var msg = document.createElement('div');
    var alertId = 'msg-'+Math.floor(Math.random() * 10) + 2;

    var close = document.createElement('a');
    close.href = '#';
    close.addClassName('close');
    close.onclick = function() { Effect.Fade(alertId); return false; };
    close.appendChild(document.createTextNode('x'));

    msg.id = alertId;
    msg.addClassName('alert-message');
    msg.addClassName(type);
    if (msg.style.setAttribute) {
        msg.style.setAttribute('cssText', 'display:none');
    } else {
        msg.setAttribute('style', 'display:none');
    }
    msg.appendChild(close);
    msg.appendChild(document.createTextNode(text));
    $(id).insert({'after':msg});

    Effect.Appear(msg);

    var t=setTimeout(function() { Effect.Fade(alertId) }, 3000); 
}
