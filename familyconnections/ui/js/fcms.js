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
    $('a.new_window').click(function() {
        window.open($(this).attr('href'), '', 'width=650, height=620, location=no, status=no, menubar=no, toolbar=no');
        return false;
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
    $('#check-all').prepend(
        '<input type="checkbox" id="allbox" name="allbox" onclick="checkUncheckAll();" value="' + selectText + '">' +
        '<label for="allbox">' + selectText + '</label>'
    );

    // Add CheckCheckAll() to each checkbox
    $('#check_all_form input:checkbox').each(function () {
        if ($(this).attr('name') != 'allbox') {
            $(this).click(checkCheckAll);
        }
    });

    return true;
}
function checkUncheckAll()
{
    $('#check_all_form input:checkbox').each(function () {
        if ($(this).attr('name') != 'allbox') {

            $(this).prop('checked', false);

            if ($('#allbox').is(':checked'))
            {
                $(this).prop('checked', true);
            }
        }
    });
}
function checkCheckAll()
{
    var total_boxes = 0;
    var total_on    = 0;

    $('#check_all_form input:checkbox').each(function () {
        if ($(this).attr('name') != 'allbox') {
            total_boxes++;
            if ($(this).prop('checked')) {
                total_on++;
            }
        }
    });

    if (total_boxes == total_on) {
        $('#allbox').prop('checked', true);
    }
    else {
        $('#allbox').prop('checked', false);
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
function endsWith(str, suffix) {
    return str.indexOf(suffix, str.length - suffix.length) !== -1;
}

/* =PHOTO =GALLERY
------------------------------------------------*/
function hideUploadOptions(rotateText, catText, newCatText) {
    // Hide Rotate options
    if ($('#rotate-options').length) {
        $('#rotate-options').hide();

        $('#rotate-options').before(
            '<p style="text-align:center;">'
                + '<a href="#" class="u" onclick="function() { $(\'#rotate-options\').toggle(); return false; };">'
                    + rotateText
                + '</a>'
            + '</p>'
        );
    }

    // Hide Existing Categories
    if ($('#existing-categories').length) {
        $('#existing-categories').hide();

        $('#existing-categories').after(
            '<span><a href="#" id="category-link" class="u">' + catText + '</a></span>'
        );

        $('#category-link').click(function() {
            if ($('#new-category').is(':visible')) {
                $('#existing-categories').show();
                $('#new-category').hide();
                $('#category-link').text(newCatText);
            }
            else {
                $('#existing-categories').hide();
                $('#new-category').show();
                $('#category-link').text(catText);
            }
            return false;
        });
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
function removeTagged (anchor)
{
    $anchor = $(anchor);

    var userid = $anchor.attr('alt');

    // The id of the ul might have the # we are looking for
    var $ul = $anchor.closest('ul');
    var txt = $ul.attr('id');
    // remove the autocomplete_selected_ part
    var id = txt.substr(22);

    $('input.tagged').each(function() {
        $input = $(this);
        if ($input.val() == userid) {
            if (id) {
                if ($input.attr('id') == 'tagged_' + id) {
                    $input.remove();
                }
            } else {
                $input.remove();
            }
        }
    });

    var $li = $anchor.closest('li');
    $li.remove();

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
    $(".picasa ul").mouseover(function(event) {
        var jqMousedList = $(event.target).closest('li');
        if (jqMousedList) {
            jqMousedList.find('span').show();
        }
    });
    $(".picasa ul").mouseout(function(event) {
        var jqMousedList = $(event.target).closest('li');
        if (jqMousedList && !jqMousedList.hasClass("selected")) {
            jqMousedList.find('span').hide();
        }
    });
    $(".picasa ul").click(function(event) {
        var jqClickedList = $(event.target).closest('li');
        if (jqClickedList) {
            var jqChk = jqClickedList.find("input");
            if (jqChk.prop('checked')) {
                jqClickedList.addClass("selected");
                jqClickedList.find("span").show();
                jqClickedList.find("img").css('opacity', '0.4');
            }
            else {
                jqClickedList.removeClass("selected");
                jqClickedList.find("span").hide();
                jqClickedList.find("img").css('opacity', '1');
            }
        }
    });

    $('.picasa > p').on('change', '> #albums', function() {
        $("#photo_list").empty();
        loadPicasaPhotos(token, errorMessage);
    });
}

function loadPicasaPhotos (token, errorMessage)
{
    var albumId = $('#albums').val();

    $('.picasa').prepend('<img id="ajax-loader" src="../ui/img/ajax-bar.gif" />');

    $.ajax({
        url  : 'index.php',
        type : 'POST',
        data : {
            ajax                 : "picasa_photos",
            picasa_session_token : token,
            albumId              : albumId,
        }
    }).done(function(data) {
        $('#ajax-loader').remove();
        $('#photo_list').prepend(data)
    }).fail(function() {
        $('#ajax-loader').remove();
        $('.picasa').prepend('<p class="error-alert">' + errorMessage + '</p>')
    });
}
function loadMorePicasaPhotos (startIndex, token, errorMessage)
{
    var albumId = $('#albums').val();

    $('.picasa').append('<img id="ajax-loader" src="../ui/img/ajax-bar.gif" />');

    $.ajax({
        url  : 'index.php',
        type : 'POST',
        data : {
            ajax                 : "more_picasa_photos",
            picasa_session_token : token,
            albumId              : albumId,
            start_index          : startIndex,
        }
    }).done(function(data) {
        $('#ajax-loader').remove();
        $('#photo_list').append(data);
    }).fail(function() {
        $('#ajax-loader').remove();
        $('.picasa').prepend('<p class="error-alert">' + errorMessage + '</p>');
    });
}
function loadPicasaAlbums (token, errorMessage)
{
    $('.picasa').prepend('<img id="ajax-loader" src="../ui/img/ajax-bar.gif" />');

    $.ajax({
        url  : 'index.php',
        type : 'POST',
        data : {
            ajax                 : "picasa_albums",
            picasa_session_token : token,
        }
    }).done(function(data) {
        $('.picasa').prepend(data);
        $('#ajax-loader').remove();
    }).fail(function() {
        $('.picasa').prepend('<p class="error-alert">' + errorMessage + '</p>');
        $('#ajax-loader').remove();
    });
}
function picasaSelectAll ()
{
    $('.picasa input[type=checkbox]').each(function() {
        this.checked = true;
        jqLi = $(this).closest('li');
        jqLi.addClass("selected");
        jqLi.find("span").show();
        jqLi.find("img").css('opacity', '0.4');
    });
}
function picasaSelectNone ()
{
    $('.picasa input[type=checkbox]').each(function(item) {
        item.checked = false;
        jqLi = $(this).closest('li');
        jqLi.removeClass("selected");
        jqLi.find("span").hide();
        jqLi.find("img").css('opacity', '1');
    });
}

function loadPhotoGalleryPhotos (type, errorMessage)
{
    var albumId = $('#albums').val();

    $('.' + type).prepend('<img id="ajax-loader" src="../ui/img/ajax-bar.gif" />');

    $.ajax({
        url  : 'index.php',
        type : 'POST',
        data : {
            ajax    : 1,
            type    : type,
            albumId : albumId,
        }
    }).done(function(data) {
        $('#ajax-loader').remove();
        $('#photo_list').prepend(data)
    }).fail(function() {
        $('#ajax-loader').remove();
        $('.' + type).prepend('<p class="error-alert">' + errorMessage + '</p>')
    });
}
function loadMorePhotoGalleryPhotos (type, startIndex, errorMessage)
{
    var albumId = $('#albums').val();

    $('.' + type).append('<img id="ajax-loader" src="../ui/img/ajax-bar.gif" />');

    $.ajax({
        url  : 'index.php',
        type : 'POST',
        data : {
            ajax       : 1,
            type       : type,
            albumId    : albumId,
            startIndex : startIndex,
        }
    }).done(function(data) {
        $('#ajax-loader').remove();
        $('#photo_list').append(data);
    }).fail(function() {
        $('#ajax-loader').remove();
        $('.picasa').prepend('<p class="error-alert">' + errorMessage + '</p>');
    });
}
function loadPhotoGalleryPhotoEvents (type, errorMessage)
{
    $('.' + type + ' ul').mouseover(function(event) {
        var jqMousedList = $(event.target).closest('li');
        if (jqMousedList) {
            jqMousedList.find('span').show();
        }
    });
    $('.' + type + ' ul').mouseout(function(event) {
        var jqMousedList = $(event.target).closest('li');
        if (jqMousedList && !jqMousedList.hasClass('selected')) {
            jqMousedList.find('span').hide();
        }
    });
    $('.' + type + ' ul').click(function(event) {
        var jqClickedList = $(event.target).closest('li');
        if (jqClickedList) {
            var jqChk = jqClickedList.find("input");
            if (jqChk.prop('checked')) {
                jqClickedList.addClass('selected');
                jqClickedList.find('span').show();
                jqClickedList.find('img').css('opacity', '0.4');
            }
            else {
                jqClickedList.removeClass('selected');
                jqClickedList.find('span').hide();
                jqClickedList.find('img').css('opacity', '1');
            }
        }
    });

    $('.' + type + ' > p').on('change', '> #albums', function() {
        $('#photo_list').empty();
        loadPhotoGalleryPhotos(type, errorMessage);
    });
}
function photoGallerySelectAll (e, type)
{
    e.preventDefault();
    $('.' + type + ' input[type=checkbox]').each(function() {
        this.checked = true;
        jqLi = $(this).closest('li');
        jqLi.addClass("selected");
        jqLi.find("span").show();
        jqLi.find("img").css('opacity', '0.4');
    });
}
function photoGallerySelectNone (e, type)
{
    e.preventDefault();
    $('.' + type + ' input[type=checkbox]').each(function(item) {
        item.checked = false;
        jqLi = $(this).closest('li');
        jqLi.removeClass("selected");
        jqLi.find("span").hide();
        jqLi.find("img").css('opacity', '1');
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
    // Name
    $('#name-info').hide();
    $('#name')
        .focus(function() { $('#name-info').show(); })
        .blur(function() { $('#name-info').hide(); });

    // Ingredients
    $('#ingredients-info').hide();
    $('#ingredients')
        .focus(function() { $('#ingredients-info').show(); })
        .blur(function() { $('#ingredients-info').hide(); });
}

/* =SETTINGS
------------------------------------------------*/
// attach onchange event to avatar type select
function initGravatar() {
    if ($('#avatar_type').length) {
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
    if ($('#advanced_tagging_div').length) {
        $('#advanced_tagging_div').show();
    }
}

/* =ADDRESSBOOK =BOOK
------------------------------------------------*/
function initAddressBookClickRow()
{
    $('#address-table > tbody > tr > td').each(function() {
        var $cell = $(this);
        if (!$cell.hasClass('chk')) {
            $cell.click(function() {
                var url = $cell.closest('tr').find('td:nth-of-type(2) > a').attr('href');
                window.location.href=url;
            });
        }
    });
}

/* =VIDEO
------------------------------------------------*/
function initYouTubeVideoStatus(txt)
{
    if ($('#current_complete').length)
    {
        $('#refresh').hide();

        if (jQuery.isNumeric(txt))
        {
            $('#current_complete').text(txt + '%');
        }
        else
        {
            $('#js_msg').text(txt);
        }

        setTimeout(function () {
            $.ajax({
                url  : 'video.php',
                type : 'get',
                data : {
                    check_status : 1,
                },
            })
            .done(function(data) {
                if (jQuery.isNumeric(data))
                {
                    initYouTubeVideoStatus(data);
                }
                else {
                    window.location.reload();
                }
            })
            .fail(function(jqXHR, textStatus) {
                alert('Could not get status: ' + textStatus);
            });
        }, 2000);
    }
}
function initHideVideoEdit(txt)
{
    $('#video_edit')
        .hide()
        .before('<a href="#" class="video_edit_show_hide" onclick="$(\'#video_edit\').toggle(); return false;">' + txt + '</a>');
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
    $('span.tools a.add').each(function() {
        var jqAnchor = $(this);
        jqAnchor.click(function(e) {
            e.preventDefault();

            var tools = jqAnchor.closest('span.tools');
            var href  = jqAnchor.attr('href');
            var id    = href.substring(1);

            var jqImg = $('<img src="ui/img/ajax-bar.gif" id="ajax-loader" style="float:right; margin:20px"/>');
            $('#content').prepend(jqImg);

            $.ajax({
                url  : 'familytree.php',
                type : 'post',
                data : {
                    ajax : "add_relative_menu",
                    id   : id,
                },
            })
            .success(function(data) {
                $('#content').append(data);
                $('#ajax-loader').remove();
            });
        });
    });
}
