function addSmiley(smileystring) {
	var textarea = document.getElementById('post');
	if (textarea) {
		if (textarea.value == "message") {
			textarea.value = smileystring + ' ';
		} else {
			textarea.value += smileystring + ' ';
		}
		textarea.focus();
	}
	return true;
}
function addQuote(qstr) {
	var textarea = document.getElementById('post');
	rExp = /\[br\]/gi;
	newString = new String ("\n");
	qstr = qstr.replace(rExp, newString);
	if (textarea) {
		if (textarea.value == "message") {
			textarea.value = qstr + ' ';
		} else {
			textarea.value += qstr + ' ';
		}
		textarea.focus();
	}
	return true;
}
function removeDefault(defaulttext, formitem) {
	if (defaulttext == formitem.value) {
		formitem.value = '';
	}
	return true;
}
function setBackDefault(defaulttext, formitem) {
	if (formitem.value == '') {
		formitem.value = defaulttext;
	}
	return true;
}
var BBCode = function() {
	window.undefined = window.undefined;
	this.initDone = false;
}
BBCode.prototype.init = function(t) {
	if(this.initDone) return false;
	if(t == undefined) return false;
	this._target = t ? document.getElementById(t) : t;
	this.initDone = true;
	return true;
}
BBCode.prototype.noForm = function() {
	return this._target == undefined;
}
// insertcode is used for bold, italic, underline and quote and just 
// wraps the tags around a selection or prompts the user for some 
// text to apply the tag to
BBCode.prototype.insertCode = function(tag, desc, endtag) {
	if(this.noForm()) return false;
	var isDesc = (desc == undefined || desc == '') ? false : true;
	// our textfield
	var textarea = this._target;
	// our open tag 
	var open = '['+tag+']';
	var close = '[/'+((endtag == undefined) ? tag : endtag)+']';
	if (!textarea.setSelectionRange) {
		var selected = document.selection.createRange().text;
		if (selected.length<=0) { 
			// no text was selected so prompt the user for some text 
			if (textarea.value == "message") {
				textarea.value = open+((isDesc) ? prompt("Please enter the text you'd like to "+desc, "")+close : '');
			} else {
				textarea.value += open+((isDesc) ? prompt("Please enter the text you'd like to "+desc, "")+close : '');
			}

			
		} else {
			// put the code around the selected text 
			document.selection.createRange().text = open+selected+((isDesc) ? close : '');
		}
	} else { 
		// the text before the selection 
		var pretext = textarea.value.substring(0, textarea.selectionStart);
		// the selected text with tags before and after 
		var codetext = open+textarea.value.substring(textarea.selectionStart, textarea.selectionEnd)+((isDesc) ? close : '');
		// the text after the selection 
		var posttext = textarea.value.substring(textarea.selectionEnd, textarea.value.length);
		// check if there was a selection 
		if (codetext == open+close) { 
			//prompt the user 
			codetext = open+((isDesc) ? prompt("Please enter the text you'd like to "+desc, "")+close : '');
		}
		// update the text field 
		textarea.value = pretext+codetext+posttext;
	}
	// set the focus on the text field 
	textarea.focus();
}
// inserts an image by prompting the user for the url 
BBCode.prototype.insertImage = function (html) {
	if(this.noForm()) return false;
	var src = prompt('Please enter the url', 'http://'); this.insertCode('IMG='+src);
}
// inserts a link by prompting the user for a url 
BBCode.prototype.insertLink = function (html) {
	if(this.noForm()) return false;
	this.insertCode('URL='+prompt("Please enter the url", "http://"), 'as text of the link', 'url')
}
