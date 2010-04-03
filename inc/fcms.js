/* =GENERAL =GLOBAL
------------------------------------------------*/
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
function initTextFieldHighlight() {
    if (!$$('input[type="text"], input[type="password"]')) { return; }
    $$('input[type="text"], input[type="password"]').each(function(item) {
        item.onfocus = function () {
            item.addClassName('frm_text_highlight');
        }
        item.onblur = function () {
            item.removeClassName('frm_text_highlight');
        }
    });
}
function initRowHighlight() {
    if (!$$('.sortable tr')) { return; }
    $$('.sortable tr').each(function(item) {
        item.observe('mouseover', function() {
            item.addClassName('mouseover');
        });
        item.observe('mouseout', function() {
            item.removeClassName('mouseover');
        });
    });
}
function initSubmitHighlight() {
    if (!$$('input.primary, input.secondary')) { return; }
    $$('input.primary, input.secondary').each(function(item) {
        item.observe('mouseover', function() {
            item.addClassName('mouseover');
        });
        item.observe('mouseout', function() {
            item.removeClassName('mouseover');
        });
    });
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



/* =HOME
------------------------------------------------*/
function initLatestInfoHighlight() {
    if (!$$('p.newthread,p.newpost,p.newaddress,p.newnews,p.newprayer,p.newphoto,p.newcom,p.newmember,p.newrecipe,p.newcal,p.newpoll,p.newdocument')) { return; }
    $$('p.newthread,p.newpost,p.newaddress,p.newnews,p.newprayer,p.newphoto,p.newcom,p.newmember,p.newrecipe,p.newcal,p.newpoll,p.newdocument').each(function(item) {
        item.observe('mouseover', function() {
            item.addClassName('mouseover');
        });
        item.observe('mouseout', function() {
            item.removeClassName('mouseover');
        });
    });
}

/* =PHOTO =GALLERY
------------------------------------------------*/
function hideUploadOptions(rotateText, tagText) {
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
    // Hide Tag Options
    if ($('tag-options')) {
        var tDiv = $('tag-options');
        var tPara = Element.extend(document.createElement('p'));
        if (tDiv.style.setAttribute) {
            tDiv.style.setAttribute('cssText', 'display:none');
            tPara.style.setAttribute('cssText', 'text-align:center');
        } else {
            tDiv.setAttribute('style', 'display:none');
            tPara.setAttribute('style', 'text-align:center');
        }
        var tLink = Element.extend(document.createElement('a'));
        tLink.href = '#';
        tLink.addClassName('u');
        tLink.appendChild(document.createTextNode(tagText));
        tLink.onclick = function() { $('tag-options').toggle(); return false; };
        tPara.appendChild(tLink);
        tDiv.insert({'before':tPara});
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
function initConfirmPhotoDelete(txt) {
    if ($('deletephoto')) {
        var item = $('deletephoto');
        item.onclick = function() { return confirm(txt); };
        var hid = document.createElement('input');
        hid.setAttribute('type', 'hidden');
        hid.setAttribute('name', 'confirmed');
        hid.setAttribute('value', 'true');
        item.insert({'after':hid});
    }
}
function initConfirmCommentDelete(txt) {
    if (!$$('.delcom input[type="submit"]')) { return; }
    $$('.delcom input[type="submit"]').each(function(item) {
        item.onclick = function() { return confirm(txt); };
        var hid = document.createElement('input');
        hid.setAttribute('type', 'hidden');
        hid.setAttribute('name', 'confirmedcom');
        hid.setAttribute('value', 'true');
        item.insert({'after':hid});
    });
}
function initConfirmCategoryDelete(txt) {
    if (!$$('.frm_line .delbtn')) { return; }
    $$('.frm_line .delbtn').each(function(item) {
        item.onclick = function() { return confirm(txt); };
        var hid = document.createElement('input');
        hid.setAttribute('type', 'hidden');
        hid.setAttribute('name', 'confirmedcat');
        hid.setAttribute('value', 'true');
        item.insert({'after':hid});
    });
}

/* =CALENDAR
------------------------------------------------*/
function initCalendarHighlight() {
    if (!$$('#big_calendar td.monthDay, #big_calendar td.monthToday')) { return; }
    $$('#big_calendar td.monthDay, #big_calendar td.monthToday').each(function(item) {
        item.observe('mouseover', function() {
            var link = item.childNodes[1];
            if (link) {
                if (link.getAttribute('href')) {
                    item.addClassName('mouseover');
                    item.setAttribute('onclick', "document.location.href='"+link+"'");
                }
            }
        });
        item.observe('mouseout', function() {
            item.removeClassName('mouseover');
        });
    });
}

/* =ADDRESSBOOK =BOOK
------------------------------------------------*/
function initCheckAll()
{
    // http://www.dustindiaz.com/basement/checkAll.html
    var frm = $('mass_mail_form');
    if (frm) {
        // Create Check All box
        var chk = document.createElement('input');
        chk.setAttribute('type', 'checkbox');
        chk.setAttribute('name', 'allbox');
        chk.setAttribute('value', 'Check All');
        chk.onclick = function () { checkAll(document.mass_mail_form); }
        //chk.appendChild(document.createTextNode("Select All"));
        var head = document.getElementsByTagName('thead')[0];
        // get the last <th>
        var cell = head.childNodes[0].lastChild;
        cell.appendChild(chk);
        
        // Add CheckCheckAll() to each checkbox
        for (var i=0; i<frm.elements.length; i++) {
            var e = frm.elements[i];
            if ((e.name != 'allbox') && (e.type=='checkbox') && (!e.disabled)) {
                e.onclick = function () { checkCheckAll(document.mass_mail_form); }
            }
        }
    }
    return true;
}
function checkAll(frmobj)
{
    for (var i=0; i<frmobj.elements.length; i++) {
        var e = frmobj.elements[i];
        if ((e.name != 'allbox') && (e.type=='checkbox') && (!e.disabled)) {
            e.checked = frmobj.allbox.checked = true;
        }
    }
}
function checkCheckAll(frmobj)
{	
    var TotalBoxes = 0;
    var TotalOn = 0;
    for (var i=0;i<frmobj.elements.length;i++) {
        var e = frmobj.elements[i];
        if ((e.name != 'allbox') && (e.type=='checkbox')){
            TotalBoxes++;
            if (e.checked) {
                TotalOn++;
            }
        }
    }
    if (TotalBoxes==TotalOn) {
        frmobj.allbox.checked=true;
    } else {
        frmobj.allbox.checked=false;
    }
}

// TODO - move these out of here 
addLoadEvent(initTextFieldHighlight);
addLoadEvent(initRowHighlight);
addLoadEvent(initSubmitHighlight);
addLoadEvent(initCheckAll);
