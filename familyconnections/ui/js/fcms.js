/* =GENERAL =GLOBAL
------------------------------------------------*/

// Mobile navigation
$(document).ready(function() {
    $("#mobile-topmenu").on('change', function(event) {
        window.location = $("#mobile-topmenu option:selected").val();
    });

    if ($("#top").length > 0) {
        $('html, body').animate({
            scrollTop: $("#top").position().top 
        });
    }
});

// New on load events
function addLoadEvent(func) {   
    var oldonload = window.onload;
    if (typeof window.onload != 'function'){
        window.onload = func;
    } else {
        window.onload = function(){
        oldonload();
        func();
        }
    }
}
function initNewWindow() {
    if (!$$('a.new_window')) { return; }
    $$('a.new_window').each(function(link) {
        link.onclick = function() {
            window.open(this.href, '', 'width=650, height=620, location=no, status=no, menubar=no, toolbar=no');
            return false;
        };
    });
}

// Make this link a confirmation link
function deleteConfirmationLink(linkId, confirmTxt) {
    $('#' + linkId).click(function(event) {
        return confirmDeleteLink(this, confirmTxt, event);
    });
}

// Make all links of this class a confirmation link
function deleteConfirmationLinks(linkClass, confirmTxt) {
    $('.' + linkClass).click(function(event) {
        return confirmDeleteLink(this, confirmTxt, event);
    });
}

// Create the confirmation delete
function confirmDeleteLink(element, confirmTxt, event) {
    event.preventDefault();

    var itemClicked = $(element); // Could be button or link

    if (confirm(confirmTxt)) {
        // Form
        if (itemClicked.prop('tagName') == 'INPUT') {
            var jqForm = itemClicked.closest('form');
            var name   = itemClicked.attr('name');
            var value  = itemClicked.val();
            jqForm.append('<input type="hidden" name="confirmed" value="1" />');
            jqForm.append('<input type="hidden" name="' + name + '" value="' + value + '" />');
            jqForm.submit();
            return true;
        }
        // Link
        else {
            var url = itemClicked.attr('href');
            document.location = url+'&confirmed=1';
            return false;
        }
    }

    return false;
}
function addSmiley(smileystring) {
    if (!document.getElementById('post')) { return; }
    var textarea=document.getElementById("post");
    if (textarea) {
        if (textarea.value=="message") {
            textarea.value=smileystring+" "
        } else { 
            textarea.value+=smileystring+" "
        }
        textarea.focus()
    }
    return true
}
function addQuote(qstr) {
    var textarea=document.getElementById("post");
    rExp=/\[br\]/gi;
    newString=new String("\n");
    qstr=qstr.replace(rExp,newString);
    if (textarea) {
        if (textarea.value=="message") {
            textarea.value=qstr+" "
        } else {
            textarea.value+=qstr+" "
        }
        textarea.focus()
    }
    return true
}
function removeDefault(defaulttext,formitem) {
    if (defaulttext==formitem.value) {
        formitem.value=""
    }
    return true
}
function setBackDefault(defaulttext,formitem) {
    if (formitem.value=="") {
        formitem.value=defaulttext
    }
    return true
}
var BBCode=function() {
    window.undefined=window.undefined;
    this.initDone=false
};
BBCode.prototype.init=function(t) {
    if (this.initDone) {
        return false
    }
    if (t==undefined) {
        return false
    }
    this._target=t?document.getElementById(t):t;
    this.initDone=true;
    return true
};
BBCode.prototype.noForm=function() {
    return this._target==undefined
};
BBCode.prototype.insertCode=function(tag,desc,endtag) {
    if (this.noForm()) {
        return false
    }
    var isDesc=(desc==undefined||desc=="")?false:true;
    var textarea=this._target;
    var open="["+tag+"]";
    var close="[/"+((endtag==undefined)?tag:endtag)+"]";
    if (!textarea.setSelectionRange) {
        var selected=document.selection.createRange().text;
        if (selected.length<=0) {
            if (textarea.value=="message") {
                textarea.value=open+((isDesc)?prompt("Please enter the text you'd like to "+desc,"")+close:"")
            } else {
                textarea.value+=open+((isDesc)?prompt("Please enter the text you'd like to "+desc,"")+close:"")
            }
        } else {
            document.selection.createRange().text=open+selected+((isDesc)?close:"")
        }
    } else {
        var pretext=textarea.value.substring(0,textarea.selectionStart);
        var codetext=open+textarea.value.substring(textarea.selectionStart,textarea.selectionEnd)+((isDesc)?close:"");
        var posttext=textarea.value.substring(textarea.selectionEnd,textarea.value.length);
        if (codetext==open+close) {
            codetext=open+((isDesc)?prompt("Please enter the text you'd like to "+desc,"")+close:"")
        }
        textarea.value=pretext+codetext+posttext
    }
    textarea.focus()
};
BBCode.prototype.insertImage=function(html) {
    if (this.noForm()) {
        return false
    }
    var src=prompt("Please enter the url","http://");
    this.insertCode("IMG="+src)
};
BBCode.prototype.insertLink=function(html) {
    if (this.noForm()) {
        return false
    }
    this.insertCode("URL="+prompt("Please enter the url","http://"),"as text of the link","url")
};
function initCheckAll(selectText)
{
    var frm = $('check_all_form');
    if (frm) {
        // Create Check All box
        var chk = document.createElement('input');
        chk.setAttribute('type', 'checkbox');
        chk.setAttribute('id', 'allbox');
        chk.setAttribute('name', 'allbox');
        chk.setAttribute('value', 'Check All');
        chk.onclick = function () { checkUncheckAll(document.mass_mail_form); }
        var lbl = document.createElement('label');
        lbl.setAttribute('for', 'allbox');
        lbl.appendChild(document.createTextNode(selectText));
        $('check-all').appendChild(chk);
        $('check-all').appendChild(lbl);
        
        // Add CheckCheckAll() to each checkbox
        frm.getInputs('checkbox').each(function(item) {
            if (item.name != 'allbox') {
                item.onclick = checkCheckAll;
            }
        });
    }
    return true;
}
function checkUncheckAll(frmobj)
{
    var checkall = 0;

    if ($('allbox').checked) {
        checkall++;
    }

    $('check_all_form').getInputs('checkbox').each(function(item) {
        if (item.name != 'allbox') {
            if (checkall > 0) {
                item.checked = true;
            } else {
                item.checked = false;
            }
        }
    });
}
function checkCheckAll(frmobj)
{
    var total_boxes = 0;
    var total_on    = 0;

    $('check_all_form').getInputs('checkbox').each(function(item) {
        if (item.name != 'allbox') {
            total_boxes++;
            if (item.checked) {
                total_on++;
            }
        }
    });

    if (total_boxes == total_on) {
        $('allbox').checked = true;
    } else {
        $('allbox').checked = false;
    }
}

// Opens the chat window
function openChat (path)
{
    window.open(path + 'inc/chat/index.php', 'name', 'width=750,height=550,scrollbars=yes,resizable=yes,location=no,menubar=no,status=no'); 
    return false;
}

// Create the chat bar
function initChatBar(txt, path)
{
    var footer   = $('#footer');
    var linkText = txt + ' (0)';
    var time     = 2000;
    var chatLink = '<a href="#" id="chat_bar" class="chat_bar" '
                 + 'onclick="window.open(\'' + path + 'inc/chat/index.php\', \'chat\', '
                 + '\'width=750,height=550,scrollbars=yes,resizable=yes,location=no,menubar=no,status=no\'); return false;">' + linkText + '</a>';

    $(chatLink).appendTo(footer);

    (function worker() {
        $.ajax({
            type :  'GET',
            url  :  path + 'inc/chat/whoisonline.php',
        })
        .success (function (data) {
            if (data === linkText) {
                time = time * 1.2;
            }
            else {
                time = 2000;
            }
            $('#chat_bar').text(data);

            setTimeout(worker, time);
            linkText = data;
        });
    })();
}

// show tooltip on avatar
function showTooltip (obj)
{
    var link = $(obj);
    link.attr('title', '');
    var tip = link.next();
    var h = tip.outerHeight(true);
    h = h + 3;
    tip.css('top', '-' + h + 'px');
    tip.show();
}

// hide avatar tooltip
function hideTooltip (obj)
{
    $(obj).next().hide();
}

// Handles color changing of yes/no/maybe checkboxes
function initAttendingEvent ()
{
    var jqYes   = $('#yes');
    var jqMaybe = $('#maybe');
    var jqNo    = $('#no');

    jqYes.click(function(event) {
        if (jqYes.is(':checked')) {
            jqYes.prev().addClass("yes_checked");
            jqMaybe.prev().removeClass("maybe_checked");
            jqNo.prev().removeClass("no_checked");
        } else {
            jqYes.prev().removeClass("yes_checked");
        }
    });
    jqMaybe.click(function(event) {
        if (jqMaybe.is(':checked')) {
            jqMaybe.prev().addClass("maybe_checked");
            jqYes.prev().removeClass("yes_checked");
            jqNo.prev().removeClass("no_checked");
        } else {
            jqMaybe.prev().removeClass("maybe_checked");
        }
    });
    jqNo.click(function(event) {
        if (jqNo.is(':checked')) {
            jqNo.prev().addClass("no_checked");
            jqYes.prev().removeClass("yes_checked");
            jqMaybe.prev().removeClass("maybe_checked");
        } else {
            jqNo.prev().removeClass("no_checked");
        }
    });
}

// Allows j/k to scroll through news events
function nextPrevNews (e) {
    if (!e) { e = window.event; }

    var jDown = 74;
    var kUp   = 75;

    if (e.srcElement.id == "status")
    {
        return;
    }

    if (e.keyCode == jDown)
    {
        position++;
    }
    else if (e.keyCode == kUp && position > 1)
    {
        position--;
    }
    else
    {
        return;
    }

    $('div.new').each(function() {
        $(this).removeClass('selected');
    });

    var positionId = position.toString();
    $("#" + positionId).addClass('selected');
    $('html, body').animate({ scrollTop: $("#" + positionId).position().top });
}

/* UTILITIES
------------------------------------------------*/
function setElementDisplayNone(el) {
    if (el.style.setAttribute) {
        el.style.setAttribute('cssText', 'display:none');
    } else {
        el.setAttribute('style', 'display:none');
    }
}
function endsWith(str, suffix) {
    return str.indexOf(suffix, str.length - suffix.length) !== -1;
}

/* =PHOTO =GALLERY
------------------------------------------------*/
function hideUploadOptions(rotateText, catText, newCatText) {
    // Hide Rotate options
    if ($('rotate-options')) {
        var rDiv = $('rotate-options');
        var rPara = document.createElement('p');
        if (rDiv.style.setAttribute) {
            rDiv.style.setAttribute('cssText', 'display:none');
            rPara.style.setAttribute('cssText', 'text-align:center');
        } else {
            rDiv.setAttribute('style', 'display:none');
            rPara.setAttribute('style', 'text-align:center');
        }
        var rLink = Element.extend(document.createElement('a'));
        rLink.href = '#';
        rLink.addClassName('u');
        rLink.appendChild(document.createTextNode(rotateText));
        rLink.onclick = function() { $('rotate-options').toggle(); return false; };
        rPara.appendChild(rLink);
        rDiv.insert({'before':rPara});
    }
    // Hide Existing Categories
    if ($('existing-categories')) {
        var eDiv = $('existing-categories');
        var ePara = Element.extend(document.createElement('span'));
        if (eDiv.style.setAttribute) {
            eDiv.style.setAttribute('cssText', 'display:none');
        } else {
            eDiv.setAttribute('style', 'display:none');
        }
        var eLink = Element.extend(document.createElement('a'));
        eLink.id = 'category-link';
        eLink.href = '#';
        eLink.addClassName('u');
        eLink.appendChild(document.createTextNode(catText));
        eLink.onclick = function() {
            if ($('new-category').visible()) {
                $('existing-categories').show();
                $('new-category').hide();
                $('category-link').update(newCatText);
            } else {
                $('existing-categories').hide();
                $('new-category').show();
                $('category-link').update(catText);
            }
            return false;
        };
        ePara.appendChild(eLink);
        eDiv.insert({'after':ePara});
    }
}
function hidePhotoDetails(txt) {
    if ($('photo_details_sub')) {
        var pDiv = $('photo_details_sub');
        var pPara = document.createElement('p');
        if (pDiv.style.setAttribute) {
            pDiv.style.setAttribute('cssText', 'display:none');
        } else {
            pDiv.setAttribute('style', 'display:none');
        }
        var pLink = Element.extend(document.createElement('a'));
        pLink.href = '#';
        pLink.appendChild(document.createTextNode(txt));
        pLink.onclick = function() { $('photo_details_sub').toggle(); return false; };
        pPara.appendChild(pLink);
        pDiv.insert({'before':pPara});
    }
}
function initPreviouslyTagged(users_lkup)
{
    $$('input.tagged').each(function(item) {
        var id = item.getValue();
        var txt = users_lkup[id];

        var li = document.createElement("li");
        Element.extend(li);
        li.update(txt);
        var a = document.createElement("a");
        Element.extend(a);
        a.href = "#";
        a.writeAttribute("alt", id);
        a.onclick = removeTagged;
        a.update("x");
        li.appendChild(a);
        $("autocomplete_selected").appendChild(li);
    });
}
function initMultiPreviouslyTagged(key, users_lkup)
{
    $$('input.tagged').each(function(item) {
        var name = item.id;

        if (name != 'tagged_'+key) {
            return; // each is a function call, not a for loop
        }

        var id = item.getValue();
        var txt = users_lkup[id];

        var li = document.createElement("li");
        Element.extend(li);
        li.update(txt);
        var a = document.createElement("a");
        Element.extend(a);
        a.href = "#";
        a.writeAttribute("alt", id);
        a.onclick = removeTagged;
        a.update("x");
        li.appendChild(a);
        $("autocomplete_selected_"+key).appendChild(li);
    });
}
function newUpdateElement(li)
{
    $("autocomplete_input").clear().focus();

    var selection = li.innerHTML;
    var indx = selection.indexOf(":");
    var id = selection.substring(0, indx);
    var txt = selection.substring(indx+1, selection.length);

    var newli = document.createElement("li");
    Element.extend(newli);
    newli.update(txt);
    var a = document.createElement("a");
    Element.extend(a);
    a.href = "#";
    a.writeAttribute("alt", id);
    a.onclick = removeTagged;
    a.update("x");
    newli.appendChild(a);
    $("autocomplete_selected").appendChild(newli);

    var tag = document.createElement("input");
    Element.extend(tag);
    tag.writeAttribute("type","hidden");
    tag.writeAttribute("name","tagged[]");
    tag.addClassName("tagged");
    tag.setValue(id);
    $("autocomplete_form").appendChild(tag);

    return false;
}
function newMultiUpdateElement(li)
{
    var i = li.parentNode.parentNode;
    i = i.identify();

    var c = i.lastIndexOf('_');
    i = i.substring(c+1);

    $("autocomplete_input_"+i).clear().focus();

    var selection = li.innerHTML;
    var indx = selection.indexOf(":");
    var id = selection.substring(0, indx);
    var txt = selection.substring(indx+1, selection.length);

    var newli = document.createElement("li");
    Element.extend(newli);
    newli.update(txt);
    var a = document.createElement("a");
    Element.extend(a);
    a.href = "#";
    a.writeAttribute("alt", id);
    a.onclick = removeTagged;
    a.update("x");
    newli.appendChild(a);
    $("autocomplete_selected_"+i).appendChild(newli);

    var tag = document.createElement("input");
    Element.extend(tag);
    tag.writeAttribute("type","hidden");
    tag.writeAttribute("name","tagged["+i+"][]");
    tag.addClassName("tagged");
    tag.setValue(id);
    $("autocomplete_form").appendChild(tag);

    return false;
}
function removeTagged ()
{
    var userid = this.readAttribute("alt");

    // The id of the ul might have the # we are looking for
    var ul = this.up('ul');
    var txt = ul.id;
    // remove the autocomplete_selected_ part
    var id = txt.substr(22);

    $$(".tagged").each(function(item) {
        if (item.getValue() == userid) {
            if (id) {
                if (item.id == 'tagged_'+id) {
                    item.remove();
                }
            } else {
                item.remove();
            }
        }
    });

    var li = this.parentNode;
    li.remove();
    return false;
}
function clickMassTagMember (event)
{
    if (this.checked) {
        this.up().addClassName('tag_photo_checked');
    } else {
        this.up().removeClassName('tag_photo_checked');
    }
}
function loadPicasaPhotoEvents (token, errorMessage)
{
    $$(".picasa ul").invoke("observe", "mouseover", function(event) {
        var mousedList = event.findElement("li");
        if (mousedList) {
            mousedList.down("span").show();
        }
    });
    $$(".picasa ul").invoke("observe", "mouseout", function(event) {
        var mousedList = event.findElement("li");
        if (mousedList && !mousedList.hasClassName("selected")) {
            mousedList.down("span").hide();
        }
    });
    $$(".picasa ul").invoke("observe", "click", function(event) {
        var clickedList = event.findElement("li");
        if (clickedList) {
            var chk = clickedList.down("input");
            if (chk.checked) {
                clickedList.addClassName("selected");
                clickedList.down("span").show();
                clickedList.down("img").setStyle({ opacity: 0.4 });
            }
            else {
                clickedList.removeClassName("selected");
                clickedList.down("span").hide();
                clickedList.down("img").setStyle({ opacity: 1 });
            }
        }
    });

    if (!$('albums')) { return; }

    Event.observe($("albums"), "change", function() {
        $$("#photo_list li").each(function (item) {
            item.remove();
        });
        loadPicasaPhotos(token, errorMessage);
    });
}

function loadPicasaPhotos (token, errorMessage)
{
    var albumId = $F("albums");

    var img = document.createElement("img");
    img.setAttribute("src", "../ui/img/ajax-bar.gif");
    img.setAttribute("id", "ajax-loader");
    $("photo_list").insert({"before":img});

    new Ajax.Request("index.php", {
        method: "post",
        parameters: {
            ajax                 : "picasa_photos",
            picasa_session_token : token,
            albumId              : albumId,
        },
        onSuccess: function(transport) {
            var response = transport.responseText;
            loadPicasaPhotoEvents(token, errorMessage);
            $("photo_list").insert({"bottom":response});
            $("ajax-loader").remove();
        },
        onFailure: function() {
            var para = document.createElement("p");
            para.setAttribute("class", "error-alert");
            para.appendChild(document.createTextNode(errorMessage));
            $("ajax-loader").insert({"before":para});
            $("ajax-loader").remove();
        }
    });
}
function loadMorePicasaPhotos (startIndex, token, errorMessage)
{
    var albumId = $F("albums");

    var img = document.createElement("img");
    var li  = document.createElement("li");
    img.setAttribute("src", "../ui/img/ajax-bar.gif");
    img.setAttribute("id", "ajax-loader");
    li.appendChild(img);
    $("photo_list").insert({"bottom":li});

    new Ajax.Request("index.php", {
        method: "post",
        parameters: {
            ajax                 : "more_picasa_photos",
            picasa_session_token : token,
            albumId              : albumId,
            start_index          : startIndex,
        },
        onSuccess: function(transport) {
            var response = transport.responseText;
            loadPicasaPhotoEvents(token, errorMessage);
            $("ajax-loader").remove();
            $("photo_list").insert({"bottom":response});
        },
        onFailure: function() {
            var para = document.createElement("p");
            para.setAttribute("class", "error-alert");
            para.appendChild(document.createTextNode(errorMessage));
            $("ajax-loader").insert({"before":para});
            $("ajax-loader").remove();
        }
    });
}
function loadPicasaAlbums (token, errorMessage)
{
    var img = document.createElement("img");
    img.setAttribute("src", "../ui/img/ajax-bar.gif");
    img.setAttribute("id", "ajax-loader");

    $$(".picasa").each(function (item) {
        item.insert({"top":img});
    });

    new Ajax.Request("index.php", {
        method: "post",
        parameters: {
            ajax                 : "picasa_albums",
            picasa_session_token : token,
        },
        onSuccess: function(transport) {
            var response = transport.responseText;
            $("ajax-loader").insert({"before":response});
            $("ajax-loader").remove();
        },
        onFailure: function() {
            var para = document.createElement("p");
            para.setAttribute("class", "error-alert");
            para.appendChild(document.createTextNode(errorMessage));
            $("ajax-loader").insert({"before":para});
            $("ajax-loader").remove();
        }
    });
}
function picasaSelectAll ()
{
    $$('.picasa input[type=checkbox]').each(function(item) {
        item.checked = true;
        var li = item.up('li');
        li.addClassName("selected");
        li.down("span").show();
        li.down("img").setStyle({ opacity: 0.4 });
    });
}
function picasaSelectNone ()
{
    $$('.picasa input[type=checkbox]').each(function(item) {
        item.checked = false;
        var li = item.up('li');
        li.removeClassName("selected");
        li.down("span").hide();
        li.down("img").setStyle({ opacity: 1 });
    });
}


/* =CALENDAR
------------------------------------------------*/
// Hide detail options when creating a new calendar event
function initHideMoreDetails(txt) {
    var jqDetails = $('#cal-details');
    if (jqDetails.length > 0) {
        jqDetails.before('<a id="cal-details-link" href="#">' + txt + '</a>');
        $('#cal-details-link').click(function() {
            $('#cal-details').toggle();
            return false;
        });
        jqDetails.hide();
    }
}

// disable times if the event is for all day
function initDisableTimes() {
    var jqStart = $('#timestart');
    var jqEnd   = $('#timeend');

    if ($('#all-day').is(':checked')) { 
        jqStart.prop('disabled', true); 
        jqEnd.prop('disabled', true); 
    }
}

// toggles disable for all arguments passed to it
// aguments must be jquery objects
function toggleDisable() { 
    for (var i = 0; i < arguments.length; i++) { 
        var element = arguments[i]; 
        if (element.is(':disabled')) { 
            element.prop('disabled', false); 
        }
        else { 
            element.prop('disabled', true); 
        } 
    } 
} 

// On invite screen make clicking row, also check the checkbox
function initCalendarClickRow()
{
    $('#invite-table tbody tr').each(function() {
        var jqRow = $(this);
        if (!jqRow.hasClass('header')) {
            var chk = jqRow.find('td input[type="checkbox"]');
            jqRow.children('td').each(function() {
                var jqTd = $(this);
                if (!jqTd.hasClass('chk')) {
                    jqTd.click(function() {
                        if (chk.is(':checked')) {
                            jqRow.removeClass('checked');
                            chk.prop('checked', false);
                        }
                        else {
                            jqRow.addClass('checked');
                            chk.prop('checked', true);
                        }
                    });
                }
            });
        }
    });
}

// Hide the member list if we are inviting everyone
function initInviteAll ()
{
    $('#all-members').click(function() {
        if ($('#all-members').is(':checked')) {
            $('#invite-members-list').hide();
        }
        else {
            $('#invite-members-list').show();
        }
    });
}

// Toggle the members listed in who's coming section
function initInviteAttending ()
{
    $('#whos_coming .coming_details').each(function() {
        $(this).hide();
    });

    $('#whos_coming h3.coming').click(function() {
        $(this).next().toggle();
    });
}

/* =RECIPE
------------------------------------------------*/
function initHideAddFormDetails() {
    if ($('addform')) {
        // Name
        setElementDisplayNone($('name-info'));
        $('name').onfocus = function() { $('name-info').show(); };
        $('name').onblur  = function() { $('name-info').hide(); };
        // Ingredients
        setElementDisplayNone($('ingredients-info'));
        $('ingredients').onfocus = function() { $('ingredients-info').show(); };
        $('ingredients').onblur  = function() { $('ingredients-info').hide(); };
    }
}

/* =SETTINGS
------------------------------------------------*/
// attach onchange event to avatar type select
function initGravatar() {
    if ($('#avatar_type')) {
        handleAvatar();
        $('#avatar_type').change(function() { handleAvatar() });
    }
}

// handle changing avatar type
function handleAvatar() {
    var avatarType = $('#avatar_type option:selected').val();

    if (avatarType == "fcms") {
        $('#fcms').show();
        $('#gravatar').hide();
        $('#default').hide();
    }
    if (avatarType == "gravatar") {
        $('#fcms').hide();
        $('#gravatar').show();
        $('#default').hide();
    }
    if (avatarType == "default") {
        $('#fcms').hide();
        $('#gravatar').hide();
        $('#default').show();
    }
}

function initAdvancedTagging() {
    if ($('advanced_tagging_div')) {
        $('advanced_tagging_div').show();
    }
}

/* =ADDRESSBOOK =BOOK
------------------------------------------------*/
function initAddressBookClickRow()
{
    if ($('address-table')) {
        $$('tbody tr').each(function(row) {
            if (!row.hasClassName('header')) {
                var url = row.down('td', 1).down('a').href;
                row.childElements().each(function(td) {
                    if (!td.hasClassName('chk')) {
                        td.onclick = function() { window.location.href=url; };
                    }
                });
            }
        });
    }
}

/* =VIDEO
------------------------------------------------*/
function initYouTubeVideoStatus(txt)
{
    if ($('current_status')) {
        $('refresh').hide();
        $('js_msg').update(txt);
        pu = new Ajax.PeriodicalUpdater('current_status', 'video.php', {
            method: 'get', 
            parameters : 'check_status=1',
            frequency: 3, 
            decay: 2,
            onSuccess : function(t) {
                if (t.responseText == 'Finished') {
                    pu.stop();
                    window.location.reload();
                }
            },
            onFailure : function(t) {
                alert('Could not get status: ' + t.responseText);
            }
        });
    }
}
function initHideVideoEdit(txt)
{
    if ($('video_edit')) {
        $('video_edit').hide();
        var vDiv = $('video_edit');
        var vLink = Element.extend(document.createElement('a'));
        vLink.href = '#';
        vLink.addClassName('video_edit_show_hide');
        vLink.appendChild(document.createTextNode(txt));
        vLink.onclick = function() { $('video_edit').toggle(); return false; };
        vDiv.insert({'before':vLink});
    }
}

/* =FAMILYTREE =TREE
------------------------------------------------*/
function initLivingDeceased()
{
    if (!$('living_deceased')) { return; }

    $('living_deceased').show();
    $('deceased').hide();
    $('living_option').onchange = initLivingDeceased;
    $('deceased_option').onchange = initLivingDeceased;

    if ($('deceased_option').checked)
    {
        $('deceased').show();
    }
}
function initAddRelative()
{
    $$('.tools a.add').each(function(anchor) {
        anchor.observe("click", function(e) {
            e.preventDefault();

            var tools = anchor.up();
            var href = anchor.readAttribute("href");
            var id = href.substring(1);

            var img = document.createElement("img");
            img.setAttribute("src", "ui/img/ajax-bar.gif");
            img.setAttribute("id", "ajax-loader");
            img.setAttribute("style", "float:right; margin:20px;");
            $('content').insert({"top":img});

            new Ajax.Request("familytree.php", {
                method: "post",
                parameters: {
                    ajax : "add_relative_menu",
                    id   : id,
                },
                onSuccess: function(transport) {
                    var response = transport.responseText;
                    $('content').insert({"bottom":response});

                    $("ajax-loader").remove();
                },
                onFailure: function(transport) {
alert('oops');
                }
            });
        });
    });
}
