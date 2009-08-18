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
function initCalendarHighlight() {
    if (!$$('#big_calendar td')) { return; }
    $$('#big_calendar td').each(function(item) {
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
        chk.setAttribute('onClick', "checkAll(document.mass_mail_form);");
        var atext = document.createTextNode("Select All");
        chk.appendChild(atext);
        var head = document.getElementsByTagName('thead')[0];
        // get the last <th>
        var cell = head.childNodes[0].lastChild;
        cell.appendChild(chk);
        
        // Add CheckCheckAll() to each checkbox
        for (var i=0; i<frm.elements.length; i++) {
            var e = frm.elements[i];
            if ((e.name != 'allbox') && (e.type=='checkbox') && (!e.disabled)) {
                e.setAttribute('onClick', "checkCheckAll(document.mass_mail_form);");
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
            e.checked = frmobj.allbox.checked;
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
addLoadEvent(initTextFieldHighlight);
addLoadEvent(initRowHighlight);
addLoadEvent(initSubmitHighlight);
addLoadEvent(initCalendarHighlight);
addLoadEvent(initCheckAll);
