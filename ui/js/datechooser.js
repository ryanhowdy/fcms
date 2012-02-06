/*
DateChooser 2.9
February 13, 2008
For usage details see http://yellow5.us/projects/datechooser/

Creative Commons Attribution 2.0 License
http://creativecommons.org/licenses/by/2.0/
*/

if (!objPHPDate)
{
	var objPHPDate =
	{
		/* These values are defaults. Please feel free to modify them as needed. */

		aDay: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
		aShortDay: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
		aLetterDay: ['S', 'M', 'T', 'W', 'T', 'F', 'S'],
		aMonth: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
		aShortMonth: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
		aSuffix: ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th', 'th', 'th', 'th', 'th', 'th', 'th', 'th', 'th', 'th', 'th', 'th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th', 'th', 'st'],

		/* End user-editable values */

		sTimezoneOffset: '',

		GetTimezoneOffset: function()
		{
			var objLocal = new Date();
			objLocal.setHours(12);
			objLocal.setMinutes(0);
			objLocal.setSeconds(0);
			objLocal.setMilliseconds(0);

			var objUTC = new Date();
			objUTC.setMilliseconds(objLocal.getUTCMilliseconds());
			objUTC.setSeconds(objLocal.getUTCSeconds());
			objUTC.setMinutes(objLocal.getUTCMinutes());
			objUTC.setHours(objLocal.getUTCHours());
			objUTC.setDate(objLocal.getUTCDate());
			objUTC.setMonth(objLocal.getUTCMonth());
			objUTC.setFullYear(objLocal.getUTCFullYear());

			this.sTimezoneOffset = ((objLocal.getTime() - objUTC.getTime()) / (1000 * 3600));
			var bNegative = (this.sTimezoneOffset < 0);

			this.sTimezoneOffset  = bNegative ? (this.sTimezoneOffset + '').substring(1) : this.sTimezoneOffset + '';
			this.sTimezoneOffset  = this.sTimezoneOffset.replace(/\.5/, (parseInt('$1', 10) * 60) + '');
			this.sTimezoneOffset += (this.sTimezoneOffset.substring(this.sTimezoneOffset.length - 3) != ':30') ? ':00' : '';
			this.sTimezoneOffset  = (this.sTimezoneOffset.substr(0, this.sTimezoneOffset.indexOf(':')).length == 1) ? '0' + this.sTimezoneOffset : this.sTimezoneOffset;
			this.sTimezoneOffset  = bNegative ? '-' + this.sTimezoneOffset : '+' + this.sTimezoneOffset;

			delete objLocal;
			delete objUTC;
			return true;
		},

		PHPDate: function()
		{
			var sFormat = (arguments.length > 0) ? arguments[0] : '';

			var nYear = this.getFullYear();
			var sYear = nYear + '';

			var nMonth = this.getMonth();
			var sMonth = (nMonth + 1) + '';
			var sPaddedMonth = (sMonth.length == 1) ? '0' + sMonth : sMonth;

			var nDate = this.getDate();
			var sDate = nDate + '';
			var sPaddedDate = (sDate.length == 1) ? '0' + sDate : sDate;

			var nDay = this.getDay();
			var sDay = nDay + '';

			sFormat = sFormat.replace(/([cDdFjLlMmNnrSUwYy])/g, 'y5-cal-regexp:$1');
			sFormat = sFormat.replace(/y5-cal-regexp:c/g, sYear + '-' + sPaddedMonth + '-' + sPaddedDate + 'T00:00:00' + objPHPDate.sTimezoneOffset);
			sFormat = sFormat.replace(/y5-cal-regexp:D/g, objPHPDate.aShortDay[nDay]);
			sFormat = sFormat.replace(/y5-cal-regexp:d/g, sPaddedDate);
			sFormat = sFormat.replace(/y5-cal-regexp:F/g, objPHPDate.aMonth[nMonth]);
			sFormat = sFormat.replace(/y5-cal-regexp:j/g, nDate);
			sFormat = sFormat.replace(/y5-cal-regexp:L/g, objPHPDate.aLetterDay[nDay]);
			sFormat = sFormat.replace(/y5-cal-regexp:l/g, objPHPDate.aDay[nDay]);
			sFormat = sFormat.replace(/y5-cal-regexp:M/g, objPHPDate.aShortMonth[nMonth]);
			sFormat = sFormat.replace(/y5-cal-regexp:m/g, sPaddedMonth);
			sFormat = sFormat.replace(/y5-cal-regexp:N/g, (nDay == 0) ? 7 : nDay);
			sFormat = sFormat.replace(/y5-cal-regexp:n/g, sMonth);
			sFormat = sFormat.replace(/y5-cal-regexp:r/g, objPHPDate.aShortDay[nDay] + ', ' + sPaddedDate + ' ' + objPHPDate.aShortMonth[nMonth] + ' ' + sYear + ' 00:00:00 ' + objPHPDate.sTimezoneOffset.replace(/:/, ''));
			sFormat = sFormat.replace(/y5-cal-regexp:S/g, objPHPDate.aSuffix[nDate]);
			sFormat = sFormat.replace(/y5-cal-regexp:U/g, parseInt((this.getTime() / 1000), 10));
			sFormat = sFormat.replace(/y5-cal-regexp:w/g, nDay);
			sFormat = sFormat.replace(/y5-cal-regexp:Y/g, sYear);
			sFormat = sFormat.replace(/y5-cal-regexp:y/g, sYear.substring(2));

			return sFormat;
		}
	};

	objPHPDate.GetTimezoneOffset();
	Date.prototype.getPHPDate = objPHPDate.PHPDate;
}

function DateChooser()
{
	/* These values are defaults. Please feel free to modify them as needed. */

	var nWeekStartDay = 0;
	var nXOffset = 0;
	var nYOffset = 0;
	var nTimeout = 0;
	var objAllowedDays = {'0':true, '1':true, '2':true, '3':true, '4':true, '5':true, '6':true};
	var fnUpdate = null;
	var sDefaultIcon = false;
	var objUpdateFields = {};
	var objEarliestDate = null;
	var objLatestDate = null;

	/* End user-editable values */

	if (!arguments || !document.getElementById || !document.getElementsByTagName) return null;
	var ndBodyElement = document.getElementsByTagName('body').length ? document.getElementsByTagName('body')[0] : document;
	var objTimeout = null;
	var ndFrame = null;

	/*@cc_on@*/
	/*@if(@_jscript_version < 6)
		if (document.getElementById('iframehack'))
		{
			ndFrame = document.getElementById('iframehack');
		}
		else
		{
			ndFrame = xb.createElement('iframe');
			ndFrame.id = 'iframehack';
			ndFrame.src = 'javascript:null;';
			ndFrame.scrolling = 'no';
			ndFrame.frameBorder = 0;
			ndFrame.style.border = '0';
			ndFrame.style.padding = 0;
			ndFrame.style.display = 'none';
			ndFrame.style.position = 'absolute';
			ndFrame.style.zIndex = '5000';

			ndBodyElement.appendChild(ndFrame);
		}
	/*@end@*/

	var nDateChooserID = 0;
	while (document.getElementById('calendar' + nDateChooserID)) ++nDateChooserID;
	var sDateChooserID = 'calendar' + nDateChooserID;

	var objSelectedDate = null;

	var objStartDate = new Date();
	objStartDate.setHours(12);
	objStartDate.setMinutes(0);
	objStartDate.setSeconds(0);
	objStartDate.setMilliseconds(0);

	var objMonthYear = new Date(objStartDate);
	objMonthYear.setDate(1);

	var ndDateChooser = xb.createElement('div');
	ndDateChooser.id = sDateChooserID;
	ndDateChooser.className = 'calendar';
	ndDateChooser.style.visibility = 'hidden';
	ndDateChooser.style.position = 'absolute';
	ndDateChooser.style.zIndex = '5001';
	ndDateChooser.style.top = '0';
	ndDateChooser.style.left = '0';
	ndBodyElement.appendChild(ndDateChooser);

	var AddClickEvents = function()
	{
		var aNavLinks = ndDateChooser.getElementsByTagName('thead')[0].getElementsByTagName('a');
		for (var nNavLink = 0; aNavLinks[nNavLink]; ++nNavLink)
		{
			events.add(aNavLinks[nNavLink], 'click', function(e)
			{
				e = e || events.fix(event);
				var ndClicked = e.target || e.srcElement;
				if (ndClicked.nodeName == '#text') ndClicked = ndClicked.parentNode;

				var sClass = ndClicked.className;

				if (sClass == 'previousyear')
				{
					objMonthYear.setFullYear(objMonthYear.getFullYear() - 1);
					if (objEarliestDate && objEarliestDate.getTime() > objMonthYear.getTime())
					{
						objMonthYear.setMonth(objEarliestDate.getMonth());
						objMonthYear.setFullYear(objEarliestDate.getFullYear());
					}
				}
				else if (sClass == 'previousmonth')
				{
					objMonthYear.setMonth(objMonthYear.getMonth() - 1);
					if (objEarliestDate && objEarliestDate.getTime() > objMonthYear.getTime())
					{
						objMonthYear.setMonth(objEarliestDate.getMonth());
						objMonthYear.setFullYear(objEarliestDate.getFullYear());
					}
				}
				else if (sClass == 'currentdate')
				{
					objMonthYear.setMonth(objStartDate.getMonth());
					objMonthYear.setFullYear(objStartDate.getFullYear());
				}
				else if (sClass == 'nextmonth')
				{
					objMonthYear.setMonth(objMonthYear.getMonth() + 1);
					if (objLatestDate && objLatestDate.getTime() < objMonthYear.getTime())
					{
						objMonthYear.setMonth(objLatestDate.getMonth());
						objMonthYear.setFullYear(objLatestDate.getFullYear());
					}
				}
				else if (sClass == 'nextyear')
				{
					objMonthYear.setFullYear(objMonthYear.getFullYear() + 1);
					if (objLatestDate && objLatestDate.getTime() < objMonthYear.getTime())
					{
						objMonthYear.setMonth(objLatestDate.getMonth());
						objMonthYear.setFullYear(objLatestDate.getFullYear());
					}
				}

				RefreshDisplay();
				return false;
			});
		}

		var aDateLinks = ndDateChooser.getElementsByTagName('tbody')[0].getElementsByTagName('a');
		for (var nDateLink = 0; aDateLinks[nDateLink]; ++nDateLink)
		{
			events.add(aDateLinks[nDateLink], 'click', function(e)
			{
				e = e || events.fix(event);
				var ndClicked = e.target || e.srcElement;
				if (ndClicked.nodeName == '#text') ndClicked = ndClicked.parentNode;

				for (var nLink = 0; aDateLinks[nLink]; ++nLink)
				{
					if (aDateLinks[nLink].className == 'selecteddate') aDateLinks[nLink].removeAttribute('class');
				}

				var objTempDate = new Date(objMonthYear);
				objTempDate.setDate(parseInt(ndClicked.childNodes[0].nodeValue, 10));

				var nTime = objTempDate.getTime();
				var sWeekday = objTempDate.getPHPDate('w');
				delete objTempDate;

				if (objEarliestDate && objEarliestDate.getTime() > nTime) return false;
				if (objLatestDate && objLatestDate.getTime() < nTime) return false;
				if (!objAllowedDays[sWeekday]) return false;

				objMonthYear.setTime(nTime);
				objMonthYear.setDate(1);
				if (!objSelectedDate) objSelectedDate = new Date(nTime);
				objSelectedDate.setTime(nTime);
				ndClicked.className = 'selecteddate';

				if (ndFrame) ndFrame.style.display = 'none';
				ndDateChooser.style.visibility = 'hidden';

				if (objTimeout) clearTimeout(objTimeout);

				UpdateFields();

				if (fnUpdate) fnUpdate(objSelectedDate);
				return false;
			});
		}

		return true;
	};

	var UpdateFields = function()
	{
		if (!objSelectedDate) return true;

		for (var sFieldName in objUpdateFields)
		{
			var ndField = document.getElementById(sFieldName);
			if (ndField) ndField.value = objSelectedDate.getPHPDate(objUpdateFields[sFieldName]);
		}

		return true;
	};

	var RefreshDisplay = function()
	{
		var ndTable, ndTHead, ndTR, ndTH, ndA, ndTBody, ndTD, nTime, sWeekday;
		var sClass = '';

		var objTempDate = new Date(objMonthYear);

		var objToday = new Date();
		objToday.setHours(12);
		objToday.setMinutes(0);
		objToday.setSeconds(0);
		objToday.setMilliseconds(0);

		ndTable = xb.createElement('table');
		ndTable.setAttribute('summary', 'DateChooser');

		ndTHead = xb.createElement('thead');
		ndTable.appendChild(ndTHead);

		ndTR = xb.createElement('tr');
		ndTHead.appendChild(ndTR);

		ndTH = xb.createElement('th');
		ndTR.appendChild(ndTH);
		ndA = xb.createElement('a');
		ndA.className = 'previousyear';
		ndA.setAttribute('href', '#');
		ndA.setAttribute('title', 'Previous Year');
		ndTH.appendChild(ndA);
		ndA.appendChild(document.createTextNode(String.fromCharCode(171)));

		ndTH = xb.createElement('th');
		ndTR.appendChild(ndTH);
		ndA = xb.createElement('a');
		ndA.className = 'previousmonth';
		ndA.setAttribute('href', '#');
		ndA.setAttribute('title', 'Previous Month');
		ndTH.appendChild(ndA);
		ndA.appendChild(document.createTextNode(String.fromCharCode(60)));

		ndTH = xb.createElement('th');
		ndTH.setAttribute('colspan', '3');
		/*@cc_on@*/
		/*@if(@_jscript)
			ndTH.colSpan = '3';
		/*@end@*/
		ndTR.appendChild(ndTH);
		ndA = xb.createElement('a');
		ndA.className = 'currentdate';
		ndA.setAttribute('href', '#');
		ndA.setAttribute('title', 'Current Date');
		ndTH.appendChild(ndA);
		ndA.appendChild(document.createTextNode(objMonthYear.getPHPDate("M Y")));

		ndTH = xb.createElement('th');
		ndTR.appendChild(ndTH);
		ndA = xb.createElement('a');
		ndA.className = 'nextmonth';
		ndA.setAttribute('href', '#');
		ndA.setAttribute('title', 'Next Month');
		ndTH.appendChild(ndA);
		ndA.appendChild(document.createTextNode(String.fromCharCode(62)));

		ndTH = xb.createElement('th');
		ndTR.appendChild(ndTH);
		ndA = xb.createElement('a');
		ndA.className = 'nextyear';
		ndA.setAttribute('href', '#');
		ndA.setAttribute('title', 'Next Year');
		ndTH.appendChild(ndA);
		ndA.appendChild(document.createTextNode(String.fromCharCode(187)));

		ndTR = xb.createElement('tr');
		ndTHead.appendChild(ndTR);

		for (var nDay = 0; objPHPDate.aLetterDay[nDay]; ++nDay)
		{
			ndTD = xb.createElement('td');
			ndTR.appendChild(ndTD);
			ndTD.appendChild(document.createTextNode(objPHPDate.aLetterDay[(nWeekStartDay + nDay) % objPHPDate.aLetterDay.length]));
		}

		ndTBody = xb.createElement('tbody');
		ndTable.appendChild(ndTBody);

		while (objTempDate.getMonth() == objMonthYear.getMonth())
		{
			ndTR = xb.createElement('tr');
			ndTBody.appendChild(ndTR);

			for (nDay = 0; nDay < 7; ++nDay)
			{
				var nWeek = (nWeekStartDay + nDay) % objPHPDate.aLetterDay.length;
				if ((objTempDate.getUTCDay() == nWeek) && (objTempDate.getMonth() == objMonthYear.getMonth()))
				{
					nTime = objTempDate.getTime();
					sWeekday = objTempDate.getPHPDate('w');

					sClass  = (objSelectedDate && (objTempDate.getTime() == objSelectedDate.getTime())) ? 'selectedday' : '';
					sClass += (objTempDate.getTime() == objToday.getTime()) ? ' today' : '';
					sClass  = ((sClass.length > 0) && (sClass[1] == ' ')) ? sClass.substr(1, sClass.length - 1) : sClass;

					ndTD = xb.createElement('td');
					if ((objEarliestDate && objEarliestDate.getTime() > nTime) || (objLatestDate && objLatestDate.getTime() < nTime) || !objAllowedDays[sWeekday]) ndTD.className = 'invalidday';
					ndTR.appendChild(ndTD);

					ndA = xb.createElement('a');
					if (sClass.length > 0) ndA.className = sClass;
					ndA.setAttribute('href', '#');
					ndTD.appendChild(ndA);
					ndA.appendChild(document.createTextNode(objTempDate.getDate()));

					objTempDate.setDate(objTempDate.getDate() + 1);
				}
				else
				{
					ndTD = xb.createElement('td');
					ndTR.appendChild(ndTD);
				}
			}
		}

		while (ndDateChooser.hasChildNodes()) ndDateChooser.removeChild(ndDateChooser.firstChild);
		ndDateChooser.appendChild(ndTable);

		if (ndFrame)
		{
			ndFrame.style.display = 'block';
			ndFrame.style.top = ndDateChooser.style.top;
			ndFrame.style.left = ndDateChooser.style.left;
			ndFrame.style.width = (ndTable.clientWidth + 2) + 'px';
			ndFrame.style.height = (ndTable.clientHeight + 4) + 'px';
		}

		AddClickEvents();

		delete objTempDate;
		delete objToday;
		return true;
	};

	var DisplayDateChooser = function()
	{
		var sPositionX = (arguments.length > 0) ? arguments[0] : 'auto';
		var sPositionY = (arguments.length > 1) ? arguments[1] : 'auto';

		var ndStyle = ndDateChooser.style;
		ndStyle.top = sPositionY + '';
		ndStyle.left = sPositionX + '';

		ndDateChooser.style.visibility = 'visible';
		if (objTimeout) clearTimeout(objTimeout);

		if (objSelectedDate)
		{
			objMonthYear.setTime(objSelectedDate.getTime());
		}
		else
		{
			objMonthYear.setTime(objStartDate.getTime());
		}

		objMonthYear.setHours(12);
		objMonthYear.setMinutes(0);
		objMonthYear.setSeconds(0);
		objMonthYear.setMilliseconds(0);
		objMonthYear.setDate(1);

		return RefreshDisplay();
	};

	var GetPosition = function(ndNode)
	{
		var nTop = 0, nLeft = 0;
		if (ndNode.offsetParent)
		{
			nTop = ndNode.offsetTop;
			nLeft = ndNode.offsetLeft;

			while (ndNode.offsetParent)
			{
				ndNode = ndNode.offsetParent;

				nTop += ndNode.offsetTop;
				nLeft += ndNode.offsetLeft;
			}
		}

		return ({'top' : nTop, 'left' : nLeft});
	};

	this.displayPosition = function()
	{
		var sPositionX = (arguments.length > 0) ? arguments[0] : 'auto';
		var sPositionY = (arguments.length > 1) ? arguments[1] : 'auto';

		return DisplayDateChooser(sPositionX, sPositionY);
	};

	this.display = function(e)
	{
		e = e || events.fix(event);

		var ndClicked = e.target || e.srcElement;
		if (ndClicked.nodeName == '#text') ndClicked = ndClicked.parentNode;

		var objPosition = GetPosition(ndClicked);

		DisplayDateChooser(objPosition.left + nXOffset + 'px', objPosition.top + nYOffset + 'px');

		return false;
	};

	this.setXOffset = function()
	{
		nXOffset = ((arguments.length > 0) && (typeof(arguments[0]) == 'number')) ? parseInt(arguments[0], 10) : nXOffset;

		return true;
	};

	this.setYOffset = function()
	{
		nYOffset = ((arguments.length > 0) && (typeof(arguments[0]) == 'number')) ? parseInt(arguments[0], 10) : nYOffset;

		return true;
	};

	this.setCloseTime = function()
	{
		nTimeout = ((arguments.length > 0) && (typeof(arguments[0]) == 'number') && (arguments[0] >= 0)) ? arguments[0] : nTimeout;

		return true;
	};

	this.setUpdateFunction = function()
	{
		if ((arguments.length > 0) && (typeof(arguments[0]) == 'function')) fnUpdate = arguments[0];

		return true;
	};

	this.setUpdateField = function()
	{
		objUpdateFields = {};
		if ((typeof(arguments[0]) == 'string') && (typeof(arguments[1]) == 'string') && document.getElementById(arguments[0]))
		{
			objUpdateFields[arguments[0]] = arguments[1];
		}
		else if ((typeof(arguments[0]) == 'object') && (typeof(arguments[1]) == 'object'))
		{
			for (var nField = 0; arguments[0][nField] !== undefined; ++nField)
			{
				if (nField >= arguments[1].length) break;
				objUpdateFields[arguments[0][nField]] = arguments[1][nField];
			}
		}
		else if (typeof(arguments[0]) == 'object')
		{
			objUpdateFields = arguments[0];
		}

		return true;
	};

	this.setLink = function()
	{
		var sLinkText = ((arguments.length > 0) && (typeof(arguments[0]) == 'string')) ? arguments[0] : 'Choose a date';
		var ndNode = ((arguments.length > 1) && (typeof(arguments[1]) == 'string')) ? document.getElementById(arguments[1]) : null;
		var bPlaceRight = ((arguments.length <= 2) || arguments[2]);
		var sTitleText = ((arguments.length > 3) && (typeof(arguments[3]) == 'string')) ? arguments[3] : 'Click to choose a date';

		if (!ndNode) return false;

		var ndAnchor = xb.createElement('a');
		ndAnchor.className = 'calendarlink';
		ndAnchor.href = '#';

		if (sTitleText.length > 0) ndAnchor.setAttribute('title', sTitleText);
		ndAnchor.appendChild(document.createTextNode(sLinkText));

		if (bPlaceRight)
		{
			if (ndNode.nextSibling)
			{
				ndNode.parentNode.insertBefore(ndAnchor, ndNode.nextSibling);
			}
			else
			{
				ndNode.parentNode.appendChild(ndAnchor);
			}
		}
		else
		{
			ndNode.parentNode.insertBefore(ndAnchor, ndNode);
		}

		events.add(ndAnchor, 'click', this.display);

		return true;
	};

	this.setIcon = function()
	{
		var sIconFile = ((arguments.length > 0) && (typeof(arguments[0]) == 'string')) ? arguments[0] : sDefaultIcon;
		var ndNode = ((arguments.length > 1) && (typeof(arguments[1]) == 'string')) ? document.getElementById(arguments[1]) : null;
		var bPlaceRight = ((arguments.length <= 2) || arguments[2]);
		var sTitleText = ((arguments.length > 3) && (typeof(arguments[3]) == 'string')) ? arguments[3] : 'Click to choose a date';

		if (!ndNode || !sIconFile) return false;

		var ndIcon = xb.createElement('img');
		ndIcon.className = 'calendaricon';
		ndIcon.src = sIconFile;
		ndIcon.setAttribute('alt', 'DateChooser Icon ' + (nDateChooserID + 1));
		if (sTitleText.length > 0) ndIcon.setAttribute('title', sTitleText);

		if (bPlaceRight)
		{
			if (ndNode.nextSibling)
			{
				ndNode.parentNode.insertBefore(ndIcon, ndNode.nextSibling);
			}
			else
			{
				ndNode.parentNode.appendChild(ndIcon);
			}
		}
		else
		{
			ndNode.parentNode.insertBefore(ndIcon, ndNode);
		}

		events.add(ndIcon, 'click', this.display);

		return true;
	};

	this.setStartDate = function()
	{
		if (!arguments.length || !(typeof(arguments[0]) == 'object') || !arguments[0].getTime) return false;

		objStartDate.setTime(arguments[0].getTime());
		objStartDate.setHours(12);
		objStartDate.setMinutes(0);
		objStartDate.setSeconds(0);
		objStartDate.setMilliseconds(0);

		if (objEarliestDate && objEarliestDate.getTime() > objStartDate.getTime())
		{
			objStartDate.setTime(objEarliestDate.getTime());
		}
		else if (objLatestDate && objLatestDate.getTime() < objStartDate.getTime())
		{
			objStartDate.setTime(objLatestDate.getTime());
		}

		objMonthYear.setMonth(objStartDate.getMonth());
		objMonthYear.setFullYear(objStartDate.getFullYear());

		if (!objSelectedDate) objSelectedDate = new Date(objStartDate);
		objSelectedDate.setTime(objStartDate);

		return true;
	};

	this.setEarliestDate = function()
	{
		if (!arguments.length || (typeof(arguments[0]) != 'object') || !arguments[0].getTime) return false;

		objEarliestDate = new Date();
		objEarliestDate.setTime(arguments[0].getTime());
		objEarliestDate.setHours(12);
		objEarliestDate.setMinutes(0);
		objEarliestDate.setSeconds(0);
		objEarliestDate.setMilliseconds(0);

		if (objEarliestDate.getTime() > objStartDate.getTime())
		{
			objStartDate.setTime(objEarliestDate.getTime());
			objMonthYear.setMonth(objEarliestDate.getMonth());
			objMonthYear.setFullYear(objEarliestDate.getFullYear());
		}

		if (objSelectedDate && (objEarliestDate.getTime() > objSelectedDate.getTime()))
		{
			objSelectedDate.setTime(objEarliestDate.getTime());
			objMonthYear.setMonth(objEarliestDate.getMonth());
			objMonthYear.setFullYear(objEarliestDate.getFullYear());
		}

		return true;
	};

	this.setLatestDate = function()
	{
		if (!arguments.length || !(typeof(arguments[0]) == 'object') || !arguments[0].getTime) return false;

		objLatestDate = new Date();
		objLatestDate.setTime(arguments[0].getTime());
		objLatestDate.setHours(12);
		objLatestDate.setMinutes(0);
		objLatestDate.setSeconds(0);
		objLatestDate.setMilliseconds(0);

		if (objLatestDate.getTime() < objStartDate.getTime())
		{
			objStartDate.setTime(objLatestDate.getTime());
			objMonthYear.setMonth(objLatestDate.getMonth());
			objMonthYear.setFullYear(objLatestDate.getFullYear());
		}

		if (objSelectedDate && (objLatestDate.getTime() < objSelectedDate.getTime()))
		{
			objSelectedDate.setTime(objLatestDate.getTime());
			objMonthYear.setMonth(objLatestDate.getMonth());
			objMonthYear.setFullYear(objLatestDate.getFullYear());
		}

		return true;
	};

	this.setAllowedDays = function()
	{
		if (!arguments.length || !(typeof(arguments[0]) == 'object')) return false;

		var nCount;
		for (nCount = 0; nCount < 7; ++nCount)
		{
			objAllowedDays[nCount + ''] = false;
		}

		for (nCount = 0; arguments[0][nCount] !== undefined; ++nCount)
		{
			objAllowedDays[arguments[0][nCount] + ''] = true;
		}

		return true;
	};

	this.setWeekStartDay = function()
	{
		if (!arguments.length || !(typeof(arguments[0]) == 'number')) return false;

		var nNewStartDay = parseInt(arguments[0], 10);
		if ((nNewStartDay < 0) || (nNewStartDay > 6)) return false;

		nWeekStartDay = nNewStartDay;

		return true;
	};

	this.getSelectedDate = function()
	{
		return objSelectedDate;
	};

	this.setSelectedDate = function(objDate)
	{
		if (!objSelectedDate) objSelectedDate = new Date(objDate);

		objSelectedDate.setTime(objDate.getTime());
		objSelectedDate.setHours(12);
		objSelectedDate.setMinutes(0);
		objSelectedDate.setSeconds(0);
		objSelectedDate.setMilliseconds(0);

		UpdateFields();

		return true;
	};

	this.updateFields = function()
	{
		return UpdateFields();
	};

	var clickWindow = function(e)
	{
		e = e || events.fix(event);
		var ndTarget = e.target || e.srcElement;
		if (ndTarget.nodeName == '#text') ndTarget = ndTarget.parentNode;

		while (ndTarget && (ndTarget != document))
		{
			if (ndTarget.className == 'calendar') return true;
			ndTarget = ndTarget.parentNode;
		}

		for (var nCount = 0; nCount <= nDateChooserID; ++nCount)
		{
			if (ndFrame) ndFrame.style.display = 'none';
			document.getElementById('calendar' + nCount).style.visibility = 'hidden';
		}

		return true;
	};

	var mouseoverDateChooser = function()
	{
		if (objTimeout) clearTimeout(objTimeout);
		return true;
	};

	var mouseoutDateChooser = function()
	{
		if (nTimeout > 0) objTimeout = setTimeout('document.getElementById("' + sDateChooserID + '").style.visibility = "hidden"; if (document.getElementById("iframehack")) document.getElementById("iframehack").style.display = "none";', nTimeout);

		return true;
	};

	events.add(ndDateChooser, 'mouseover', mouseoverDateChooser);
	events.add(ndDateChooser, 'mouseout', mouseoutDateChooser);
	events.add(document, 'mousedown', clickWindow);

	return true;
}

if (!Array.prototype.push)
{
	Array.prototype.push = function()
	{
		for (var nCount = 0; arguments[nCount] !== undefined; nCount++)
		{
			this[this.length] = arguments[nCount];
		}

		return this.length;
	};
}

if (!xb)
{
	var xb =
	{
		createElement: function(sElement)
		{
			if (document.createElementNS) return document.createElementNS('http://www.w3.org/1999/xhtml', sElement);
			if (document.createElement) return document.createElement(sElement);

			return null;
		},

		getElementsByAttribute: function(ndNode, sAttributeName, sAttributeValue)
		{
			var aReturnElements = [];

			if (!ndNode.all && !ndNode.getElementsByTagName) return aReturnElements;

			var rAttributeValue = RegExp('(^|\\s)' + sAttributeValue + '(\\s|$)');
			var sValue, aElements = ndNode.all || ndNode.getElementsByTagName('*');

			for (var nIndex = 0; aElements[nIndex]; ++nIndex)
			{
				if (!aElements[nIndex].getAttribute) continue;
				sValue = (sAttributeName == 'class') ? aElements[nIndex].className : aElements[nIndex].getAttribute(sAttributeName);
				if ((typeof(sValue) != 'string') || (sValue.length == 0)) continue;

				if (rAttributeValue.test(sValue)) aReturnElements.push(aElements[nIndex]);
			}

			return aReturnElements;
		},

		getOption: function(ndNode, sOption)
		{
			var sText = ndNode.getAttribute(sOption);
			if (sText) return sText;

			var sDefault = (arguments.length == 3) ? arguments[2] : false;
			var aMatch = ndNode.className.match(RegExp('(?:^|\\s)' + sOption + '=(?:\\\'|\\\")([^\\\'\\\"]+)(?:\\\'|\\\"|$)'));

			return aMatch ? aMatch[1] : sDefault;
		}
	};
}

// This is a variation of the addEvent script written by Dean Edwards (dean.edwards.name).
if (!events)
{
	var events =
	{
		nEventID: 1,

		add: function(ndElement, sType, fnHandler)
		{
			if (!fnHandler.$$nEventID) fnHandler.$$nEventID = this.nEventID++;
			if (ndElement.objEvents === undefined) ndElement.objEvents = {};

			var aHandlers = ndElement.objEvents[sType];
			if (!aHandlers)
			{
				aHandlers = ndElement.objEvents[sType] = {};
				if (ndElement['on' + sType]) aHandlers[0] = ndElement['on' + sType];
			}

			aHandlers[fnHandler.$$nEventID] = fnHandler;
			ndElement['on' + sType] = this.handle;

			return true;
		},

		handle: function(e)
		{
			e = e || events.fix(event);

			var bReturn = true, aHandlers = this.objEvents[e.type];
			for (var nIndex in aHandlers)
			{
				this.$$handle = aHandlers[nIndex];
				if (this.$$handle(e) === false) bReturn = false;
			}

			return bReturn;
		},

		fix: function(e)
		{
			e.preventDefault = this.fix.preventDefault;
			e.stopPropagation = this.fix.stopPropagation;

			return e;
		}
	};

	events.fix.preventDefault = function()
	{
		this.returnValue = false;

		return true;
	}

	events.fix.stopPropagation = function()
	{
		this.cancelBubble = true;

		return true;
	}
}

events.add(window, 'load', function()
{
	var ndDateChooser, ndElement, sLastID, sLinkID, objUpdateField, objDate, aPatternNodes;
	var sDateFormat, sIcon, sText, sXOffset, sYOffset, sCloseTime, sOnUpdate, sStartDate, sEarliestDate, sLatestDate, sAllowedDays, sWeekStartDay, sLinkPosition;
	var nFieldID = 0;

	objDate = new Date();
	objDate.setHours(12);
	objDate.setMinutes(0);
	objDate.setMilliseconds(0);

	var aElements = xb.getElementsByAttribute(document, 'class', 'datechooser');
	for (var nIndex = 0; aElements[nIndex]; ++nIndex)
	{
		ndDateChooser = aElements[nIndex];
		if (!ndDateChooser.id) ndDateChooser.id = 'dc-id-' + (++nFieldID);
		sLastID = ndDateChooser.id;

		sDateFormat = xb.getOption(ndDateChooser, 'dc-dateformat');
		sIcon = xb.getOption(ndDateChooser, 'dc-iconlink');
		sText = xb.getOption(ndDateChooser, 'dc-textlink');
		sXOffset = xb.getOption(ndDateChooser, 'dc-offset-x');
		sYOffset = xb.getOption(ndDateChooser, 'dc-offset-y');
		sCloseTime = xb.getOption(ndDateChooser, 'dc-closetime');
		sOnUpdate = xb.getOption(ndDateChooser, 'dc-onupdate');
		sStartDate = xb.getOption(ndDateChooser, 'dc-startdate');
		sEarliestDate = xb.getOption(ndDateChooser, 'dc-earliestdate');
		sLatestDate = xb.getOption(ndDateChooser, 'dc-latestdate');
		sAllowedDays = xb.getOption(ndDateChooser, 'dc-alloweddays');
		sWeekStartDay = xb.getOption(ndDateChooser, 'dc-weekstartday');
		sLinkPosition = xb.getOption(ndDateChooser, 'dc-linkposition');

		if (sLinkPosition) sLinkID = ndDateChooser.id;

		objUpdateField = {};
		if (sDateFormat) objUpdateField[ndDateChooser.id] = sDateFormat;

		aPatternNodes = ndDateChooser.all || ndDateChooser.getElementsByTagName('*');
		for (var nPattern = 0; aPatternNodes[nPattern]; ++nPattern)
		{
			ndElement = aPatternNodes[nPattern];

			sDateFormat = xb.getOption(ndElement, 'dc-dateformat');
			if (!sDateFormat) continue;

			if (!ndElement.id) ndElement.id = 'dc-id-' + (++nFieldID);

			sLastID = ndElement.id;
			objUpdateField[sLastID] = sDateFormat;

			if (!sLinkPosition) xb.getOption(ndElement, 'dc-linkposition');
			if (sLinkPosition) sLinkID = sLastID;
		}

		if (!sLinkPosition)
		{
			sLinkID = sLastID;
			sLinkPosition = 'right';
		}

		ndDateChooser.DateChooser = new DateChooser();
		if (sXOffset) ndDateChooser.DateChooser.setXOffset(sXOffset);
		if (sYOffset) ndDateChooser.DateChooser.setYOffset(sYOffset);
		if (sCloseTime) ndDateChooser.DateChooser.setCloseTime(sCloseTime);
		if (sOnUpdate) ndDateChooser.DateChooser.setUpdateFunction(eval(sOnUpdate));

		if (sStartDate)
		{
			objDate = new Date();
			objDate.setDate(parseInt(sStartDate.substring(2, 4), 10));
			objDate.setMonth(parseInt(sStartDate.substring(0, 2), 10) - 1);
			objDate.setFullYear(parseInt(sStartDate.substring(4), 10));

			ndDateChooser.DateChooser.setStartDate(objDate);
		}

		if (sEarliestDate)
		{
			objDate = new Date();
			objDate.setDate(parseInt(sEarliestDate.substring(2, 4), 10));
			objDate.setMonth(parseInt(sEarliestDate.substring(0, 2), 10) - 1);
			objDate.setFullYear(parseInt(sEarliestDate.substring(4), 10));

			ndDateChooser.DateChooser.setEarliestDate(objDate);
		}

		if (sLatestDate)
		{
			objDate = new Date();
			objDate.setDate(parseInt(sLatestDate.substring(2, 4), 10));
			objDate.setMonth(parseInt(sLatestDate.substring(0, 2), 10) - 1);
			objDate.setFullYear(parseInt(sLatestDate.substring(4), 10));

			ndDateChooser.DateChooser.setLatestDate(objDate);
		}

		if (sAllowedDays) ndDateChooser.DateChooser.setAllowedDays(sAllowedDays.split(','));
		if (sWeekStartDay) ndDateChooser.DateChooser.setWeekStartDay(parseInt(sWeekStartDay, 10));
		if (sIcon) ndDateChooser.DateChooser.setIcon(sIcon, sLinkID, (sLinkPosition != 'left'));
		if (sText) ndDateChooser.DateChooser.setLink(sText, sLinkID, (sLinkPosition != 'left'));
		ndDateChooser.DateChooser.setUpdateField(objUpdateField);
	}

	delete objDate;

	return true;
});