/*==================================================
	tabber.js by Patrick Fitzgerald pat@barelyfitz.com

	Documentation can be found at the following URL:
	http://www.barelyfitz.com/projects/tabber/

	License (http://www.opensource.org/licenses/mit-license.php)

	Copyright (c) 2006 Patrick Fitzgerald

	Permission is hereby granted, free of charge, to any person
	obtaining a copy of this software and associated documentation files
	(the "Software"), to deal in the Software without restriction,
	including without limitation the rights to use, copy, modify, merge,
	publish, distribute, sublicense, and/or sell copies of the Software,
	and to permit persons to whom the Software is furnished to do so,
	subject to the following conditions:

	The above copyright notice and this permission notice shall be
	included in all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
	EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
	MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
	NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
	BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
	ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
	CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
	SOFTWARE.
	==================================================*/


Element.prototype.tabber_hasClass = function(className)
{
	if (!!this.classList)
	{
		return this.classList.contains(className);
	}
	else
	{
		return !!this.className.match(new RegExp('(\\s|^)' + className + '(\\s|$)'));
	}
}


Element.prototype.tabber_addClass = function(className)
{
	var css_classes = className.split(/[\s]+/);

	for (i=0; i < css_classes.length; i++)
	{
	  if (!this.tabber_hasClass(css_classes[i]))
		{
			this.className += ' ' + css_classes[i];
		}
	}
}


Element.prototype.tabber_removeClass = function(className)
{
	var css_classes = className.split(/[\s]+/);

	for (i=0; i < css_classes.length; i++)
	{
	  if (this.tabber_hasClass(css_classes[i]))
		{
	    var reg = new RegExp('(\\s|^)' + css_classes[i] + '(\\s|$)');
	    this.className = this.className.replace(reg, ' ');
	  }
	}
}


function tabberObj(argsObj)
{
	var arg; /* name of an argument to override */

	/* Element for the main tabber div. If you supply this in argsObj,
		 then the init() method will be called.
	*/
	this.div = null;

	/* Class of the main tabber div */
	this.classMain = "fctabber";

	/* Rename classMain to classMainLive after tabifying
		 (so a different style can be applied)
	*/
	this.classMainLive = "tabberlive";

	/* Class of each DIV that contains a tab */
	this.classTab = "tabbertab";

	/* Class to indicate which tab should be active on startup by default */
	this.classTabDefault = "tabbertabdefault";

	/* Class to indicate which tab should be active on startup ignoring cookie*/
	this.classTabForced = "tabbertabforced";

	/* Class for the navigation UL */
	this.classNav = "tabbernav";

	/* When a tab is to be hidden, instead of setting display='none', we
		 set the class of the div to classTabHide. In your screen
		 stylesheet you should set classTabHide to display:none.  In your
		 print stylesheet you should set display:block to ensure that all
		 the information is printed.
	*/
	this.classTabHide = "tabbertabhide";

	/* Class to set the navigation LI when the tab is active, so you can
		 use a different style on the active tab.
	*/
	this.classNavActive = "tabberactive";

	/* Elements that might contain the title for the tab, only used if a
		 title is not specified in the TITLE attribute of DIV classTab.
	*/
	this.titleElements = ['h3','h2','h4','h5','h6'];

	/* Should we strip out the HTML from the innerHTML of the title elements?
		 This should usually be true.
	*/
	this.titleElementsStripHTML = true;

	/* If the user specified the tab names using a TITLE attribute on
		 the DIV, then the browser will display a tooltip whenever the
		 mouse is over the DIV. To prevent this tooltip, we can remove the
		 TITLE attribute after getting the tab name.
	*/
	this.removeTitle = true;

	/* If you want to add an id to each link set this to true */
	this.addLinkId = true;

	/* If addIds==true, then you can set a format for the ids.
		 <tabberid> will be replaced with the id of the main tabber div.
		 <tabnumberzero> will be replaced with the tab number
			 (tab numbers starting at zero)
		 <tabnumberone> will be replaced with the tab number
			 (tab numbers starting at one)
		 <tabtitle> will be replaced by the tab title
			 (with all non-alphanumeric characters removed)
	 */
	this.linkIdFormat = '<tabberid>_nav_<tabnumberone>';

	/* You can override the defaults listed above by passing in an object:
		 var mytab = new tabber({property:value,property:value});
	*/
	for (arg in argsObj) { this[arg] = argsObj[arg]; }

	/* Create regular expressions for the class names; Note: if you
		 change the class names after a new object is created you must
		 also change these regular expressions.
	*/
	this.REclassMain = new RegExp('\\b' + this.classMain + '\\b', 'gi');
	this.REclassMainLive = new RegExp('\\b' + this.classMainLive + '\\b', 'gi');
	this.REclassTab = new RegExp('\\b' + this.classTab + '\\b', 'gi');
	this.REclassTabDefault = new RegExp('\\b' + this.classTabDefault + '\\b', 'gi');
	this.REclassTabForced = new RegExp('\\b' + this.classTabForced + '\\b', 'gi');
	this.REclassTabHide = new RegExp('\\b' + this.classTabHide + '\\b', 'gi');

	/* Array of objects holding info about each tab */
	this.tabs = new Array();

	/* If the main tabber div was specified, call init() now */
	if (this.div)
	{
		this.init(this.div);

		/* We don't need the main div anymore, and to prevent a memory leak
			 in IE, we must remove the circular reference between the div
			 and the tabber object. */
		this.div = null;
	}
}


/*--------------------------------------------------
	Methods for tabberObj
	--------------------------------------------------*/


tabberObj.prototype.init = function(e)
{
	/* Set up the tabber interface.

		 e = element (the main containing div)

		 Example:
		 init(document.getElementById('mytabberdiv'))
	 */

	var
	childNodes, /* child nodes of the tabber div */
	i, i2, /* loop indices */
	t, /* object to store info about a single tab */
	defaultTab=0, /* which tab to select by default */
	forcedTab=-1,  /* activate specific tab ignoring last set cookie */
	DOM_ul, /* tabbernav list */
	DOM_li, /* tabbernav list item */
	DOM_a, /* tabbernav link */
	aId, /* A unique id for DOM_a */
	headingElement; /* searching for text to use in the tab */

	/* Verify that the browser supports DOM scripting */
	if (!document.getElementsByTagName) { return false; }

	/* If the main DIV has an ID then save it. */
	if (e.id)
	{
		this.id = e.id;
	}

	/* Clear the tabs array (but it should normally be empty) */
	this.tabs.length = 0;

	/* Loop through an array of all the child nodes within our tabber element. */
	childNodes = e.childNodes;

	for (i = 0; i < childNodes.length; i++)
	{
		/* Find the nodes where class="tabbertab" */
		if (childNodes[i].className && childNodes[i].className.match(this.REclassTab))
		{
			/* Create a new object to save info about this tab */
			t = new Object();

			/* Save a pointer to the div for this tab */
			t.div = childNodes[i];

			/* Add the new object to the array of tabs */
			this.tabs[this.tabs.length] = t;

			/* If the class name contains classTabDefault,
				 then select this tab by default.
			*/
			if (childNodes[i].className.match(this.REclassTabDefault))
			{
				defaultTab = this.tabs.length-1;
			}

			/* If the class name contains classTabForced,
				 then select this tab by default.
			*/
			if (childNodes[i].className.match(this.REclassTabForced))
			{
				forcedTab = this.tabs.length-1;
			}
		}
	}

	/* Create a new UL list to hold the tab headings */
	DOM_ul = document.createElement("ul");
	DOM_ul.className = this.classNav;

	var decode_entities = document.createElement("textarea");  // used to decode html entities

	/* Loop through each tab we found */
	for (i=0; i < this.tabs.length; i++)
	{
		t = this.tabs[i];

		/* Get the label to use for this tab:
			 From the title attribute on the DIV,
			 Or from one of the this.titleElements[] elements,
			 Or use an automatically generated number.
		 */
		t.headingText = t.div.title;
		t.headingTitle = '';
		t.headingIconClass  = t.div.getAttribute('data-icon-class');
		t.headingIcon2Class = t.div.getAttribute('data-icon2-class');
		t.headingPrefixTxt  = t.div.getAttribute('data-prefix-text');

		/* Remove the title attribute to prevent a tooltip from appearing */
		if (this.removeTitle)
		{
			t.div.title = '';
		}

		var tab_classes = '';
		if (!t.headingText)
		{
			/* Title was not defined in the title of the DIV,
				 So try to get the title from an element within the DIV.
				 Go through the list of elements in this.titleElements
				(typically heading elements ['h2','h3','h4'])
			*/
			for (i2=0; i2<this.titleElements.length; i2++)
			{
				headingElement = t.div.getElementsByTagName(this.titleElements[i2])[0];
				if (headingElement)
				{
					decode_entities.innerHTML = headingElement.innerHTML;
					t.headingText = decode_entities.value;
					t.headingTitle = headingElement.title;
					t.headingDataTitle = headingElement.dataset.title;
					t.headingContent = headingElement.dataset.content;
					t.headingPlacement = headingElement.dataset.placement;
					t.headingDataAttrA = headingElement.dataset.data_attr_a;
					t.headingDataAttrB = headingElement.dataset.data_attr_b;
					t.headingOnMouseUp = headingElement.onmouseup;
					if (headingElement.hasAttribute('class'))
					{
						tab_classes=headingElement.getAttribute('class');
					}
					if (this.titleElementsStripHTML)
					{
						t.headingText.replace(/<br>/gi,"[br]");
						t.headingText.replace(/<br\/>/gi,"[br]");
						t.headingText.replace(/<br \/>/gi,"[br]");
						t.headingText = t.headingText.replace(/<[^>]+>/g,"");
						t.headingText = t.headingText.replace(/\[br\]/g,"<br/>");
					}
					break;
				}
			}
		}

		if (!t.headingText)
		{
			/* Title was not found (or is blank) so automatically generate a
				 number for the tab.
			*/
			t.headingText = '' + (i + 1);
		}

		/* Create a list element for the tab */
		DOM_li = document.createElement("li");

		/* Save a reference to this list item so we can later change it to
			 the "active" class */
		t.li = DOM_li;

		/* Create a link to activate the tab */
		DOM_a = document.createElement("a");
		if (t.headingIconClass || t.headingIcon2Class)
		{
			var icon_classes = [];
			icon_classes[0] = t.headingIconClass;
			icon_classes[1] = t.headingIcon2Class;
			for (var j = 0; j < icon_classes.length; j++)
			{
				if (!icon_classes[j]) continue;
				var icon = document.createElement("i");
				icon.setAttribute('class', icon_classes[j]);
				DOM_a.appendChild(icon);
				DOM_a.appendChild(document.createTextNode(' '));
			}
		}
		DOM_a.appendChild(document.createTextNode(t.headingText));
		DOM_a.href = "javascript:void(null);";

		if (t.headingTitle)
		{
			DOM_a.title = t.headingTitle;
		}
		if (t.headingDataTitle)
		{
			DOM_a.dataset.title = t.headingDataTitle;
		}
		if (t.headingContent)
		{
			DOM_a.dataset.content = t.headingContent;
		}
		if (t.headingPlacement)
		{
			DOM_a.dataset.placement = t.headingPlacement;
		}
		if (t.headingDataAttrA)
		{
			DOM_a.dataset.data_attr_a = t.headingDataAttrA;
		}
		if (t.headingDataAttrB)
		{
			DOM_a.dataset.data_attr_b = t.headingDataAttrB;
		}
		if (t.headingOnMouseUp)
		{
			DOM_a.onmouseup = t.headingOnMouseUp;
		}

		DOM_a.onclick = this.navClick;
		if (tab_classes)
		{
			DOM_a.tabber_addClass(tab_classes);
		}

		/* Add some properties to the link so we can identify which tab
			 was clicked. Later the navClick method will need this.
		*/
		this.forcedTab = forcedTab;
		DOM_a.tabber = this;
		DOM_a.tabberIndex = i;

		/* Do we need to add an id to DOM_a? */
		if (this.addLinkId && this.linkIdFormat)
		{
			/* Determine the id name */
			aId = this.linkIdFormat;
			aId = aId.replace(/<tabberid>/gi, this.id);
			aId = aId.replace(/<tabnumberzero>/gi, i);
			aId = aId.replace(/<tabnumberone>/gi, i+1);
			aId = aId.replace(/<tabtitle>/gi, t.headingText.replace(/[^a-zA-Z0-9\-]/gi, ''));

			DOM_a.id = aId;
		}

		/* Add the link to the list element */
		if (t.headingPrefixTxt) {
			var span = document.createElement("span");
			span.setAttribute('class', 'label');
			span.appendChild(document.createTextNode(t.headingPrefixTxt));
			DOM_li.appendChild(span);
			DOM_li.appendChild(document.createElement("br"));
		}
		DOM_li.appendChild(DOM_a);

		/* Add the list element to the list */
		DOM_ul.appendChild(DOM_li);

		if (DOM_a.tabber_hasClass('hasTooltip') && !!jQuery)
		{
			try{
				jQuery(DOM_a).tooltip({html: true, container: DOM_a});
			}catch(e){}
		}

		if (DOM_a.tabber_hasClass('hasPopover') && !!jQuery)
		{
			try {
				jQuery(DOM_a).popover({html: true, container: DOM_a, trigger : 'hover focus'});
			}
			catch(e) {}
		}
	}

	/* Add the UL list to the beginning of the tabber div */
	e.insertBefore(DOM_ul, e.firstChild);

	/* Make the tabber div "live" so different CSS can be applied */
	e.className = e.className.replace(this.REclassMain, this.classMainLive);

	/* Activate the default or forced tab, and do not call the onclick handler */
	this.tabShow(forcedTab >= 0 ? forcedTab : defaultTab);

	/* If the user specified an onLoad function, call it now. */
	if (typeof this.onLoad == 'function') {
		this.onLoad({tabber:this});
	}

	return this;
};


tabberObj.prototype.navClick = function(event)
{
	/* This method should only be called by the onClick event of an <A>
		 element, in which case we will determine which tab was clicked by
		 examining a property that we previously attached to the <A>
		 element.

		 Since this was triggered from an onClick event, the variable
		 "this" refers to the <A> element that triggered the onClick
		 event (and not to the tabberObj).

		 When tabberObj was initialized, we added some extra properties
		 to the <A> element, for the purpose of retrieving them now. Get
		 the tabberObj object, plus the tab number that was clicked.
	*/

	var
	rVal, /* Return value from the user onclick function */
	a, /* element that triggered the onclick event */
	self, /* the tabber object */
	tabberIndex, /* index of the tab that triggered the event */
	onClickArgs; /* args to send the onclick function */

	a = this;
	if (!a.tabber) { return false; }

	self = a.tabber;
	tabberIndex = a.tabberIndex;

	/* Remove focus from the link because it looks ugly.
		 I don't know if this is a good idea...
	*/
	a.blur();

	/* If the user specified an onClick function, call it now.
		 If the function returns false then do not continue.
	*/
	if (typeof self.onClick == 'function')
	{
		onClickArgs = {'tabber':self, 'index':tabberIndex, 'event':event};

		/* IE uses a different way to access the event object */
		if (!event)
		{
			onClickArgs.event = window.event;
		}

		rVal = self.onClick(onClickArgs);
		if (rVal === false) { return false; }
	}

	self.tabShow(tabberIndex);

	return false;
};


tabberObj.prototype.tabHideAll = function()
{
	var i; /* counter */

	/* Hide all tabs and make all navigation links inactive */
	for (i = 0; i < this.tabs.length; i++)
	{
		this.tabHide(i);
	}
};


tabberObj.prototype.tabHide = function(tabberIndex)
{
	var div;

	if (!this.tabs[tabberIndex]) { return false; }

	/* Hide a single tab and make its navigation link inactive */
	div = this.tabs[tabberIndex].div;

	/* Hide the tab contents by adding classTabHide to the div */
	if (!div.className.match(this.REclassTabHide)) {
		div.className += ' ' + this.classTabHide;
	}
	this.navClearActive(tabberIndex);

	return this;
};


tabberObj.prototype.tabShow = function(tabberIndex)
{
	/* Show the tabberIndex tab and hide all the other tabs */

	var div;

	if (!this.tabs[tabberIndex]) { return false; }

	/* Hide all the tabs first */
	this.tabHideAll();

	/* Get the div that holds this tab */
	div = this.tabs[tabberIndex].div;

	/* Remove classTabHide from the div */
	//div.className = div.className.replace(this.REclassTabHide, '');

	/* Use a CSS transition for making tab contents visible */
	div.className = div.className.replace(this.REclassTabHide, '  tabbertablow');
	setTimeout(function(){
		div.className = div.className.replace(' tabbertablow', '');
	}, 10);

	/* Mark this tab navigation link as "active" */
	this.navSetActive(tabberIndex);

	/* If the user specified an onTabDisplay function, call it now. */
	if (typeof this.onTabDisplay == 'function') {
		this.onTabDisplay({'tabber':this, 'index':tabberIndex});
	}

	// Force redraw of any google maps inside TAB
	var elArr = document.querySelectorAll('#' + div.id + ' .has_fc_google_maps_map');
	for (var n = 0, len = elArr.length; n < len; n++)
	{
		google.maps.event.trigger(elArr[n].dataset.google_maps_ref, 'resize');
	}

	// Force redraw of any openstreet maps inside TAB
	var osArr = document.querySelectorAll('#' + div.id + ' .has_fc_openstreet_map');
	for (var n = 0, len = osArr.length; n < len; n++)
	{
		osArr[n].os_maps_ref.invalidateSize();
	}

	return this;
};

tabberObj.prototype.navSetActive = function(tabberIndex)
{
	/* Note: this method does *not* enforce the rule
		 that only one nav item can be active at a time.
	*/

	/* Set classNavActive for the navigation list item */
	this.tabs[tabberIndex].li.className = this.classNavActive;

	return this;
};


tabberObj.prototype.navClearActive = function(tabberIndex)
{
	/* Note: this method does *not* enforce the rule
		 that one nav should always be active.
	*/

	/* Remove classNavActive from the navigation list item */
	this.tabs[tabberIndex].li.className = '';

	return this;
};


/*==================================================*/


function tabberAutomatic(tabberArgs, container_id)
{
	/* This function finds all DIV elements in the document where
		 class=tabber.classMain, then converts them to use the tabber
		 interface.

		 tabberArgs = an object to send to "new tabber()"
	*/
	var
		tempObj, /* Temporary tabber object */
		divs, /* Array of all divs on the page */
		i; /* Loop index */

	if (!tabberArgs) { tabberArgs = {}; }

	/* Create a tabber object without initializing any tabset,so we can get the value of classMain */
	tabberArgs.div = null;
	tempObj = new tabberObj(tabberArgs);

	/* Find all DIV elements in the document that have the configured classname */
	var container_selector = typeof container_id != 'undefined' ? '#' + container_id + ' ' : 'body ';
	var divs = new Array();

	var divArr = document.querySelectorAll(container_selector + 'div.' + tempObj.classMain);
	for (var n = 0, len = divArr.length; n < len; n++)
	{
		divs[n] = divArr[n];
	}

	/* Loop through all found DIV elements, and initialize TABBER in them */
	for (var i = 0; i < divs.length; i++)
	{
		/* Recheck if each DIV has the correct classname */
		if (divs[i].className && divs[i].className.match(tempObj.REclassMain))
		{
			/* Now tabify the DIV */
			tabberArgs.div = divs[i];
			divs[i].tabber = new tabberObj(tabberArgs);
		}
	}

	var elArr = document.querySelectorAll(container_selector + ' .fc-element-auto-init');//.trigger('initialize-fc-element');
	var event;

	if (elArr.length)
	{
		if (window.CustomEvent && typeof(window.CustomEvent) === 'function')
		{
			event = new CustomEvent('initialize-fc-element', {
				bubbles:    true,
				cancelable: true,
				customdata: {}
			});
		}
		// IE trap
		else
		{
			event = document.createEvent('Event');
			event.initEvent('initialize-fc-element', true, true); //can bubble, and is cancellable
			event.customdata = {};
		}

		for (var n = 0, len = elArr.length; n < len; n++)
		{
			elArr[n].dispatchEvent(event);
		}
	}

	return this;
}


/*==================================================*/


function tabberAutomaticOnLoad(tabberArgs)
{
	tabberArgs = tabberArgs || {};

	document.addEventListener('DOMContentLoaded', function()
	{
		tabberAutomatic(tabberArgs);
	});
}


/**
 * Cookie functions
 */

function tabberSetCookie(name, value, expires, path, domain, secure)
{
	document.cookie= name + "=" + escape(value) +
		((expires) ? "; expires=" + expires.toGMTString() : "") +
		((path) ? "; path=" + path : "") +
		((domain) ? "; domain=" + domain : "") +
		((secure) ? "; secure" : "") +
		((secure) ? "; samesite=secure" : "; samesite=lax");
}

function tabberGetCookie(name)
{
	var dc = document.cookie;
	var prefix = name + "=";

	var begin = dc.indexOf("; " + prefix);
	if (begin == -1)
	{
		begin = dc.indexOf(prefix);
		if (begin != 0) return null;
	}
	else
	{
		begin += 2;
	}

	var end = document.cookie.indexOf(";", begin);
	if (end == -1)
	{
		end = dc.length;
	}

	return unescape(dc.substring(begin + prefix.length, end));
}

function tabberDeleteCookie(name, path, domain)
{
	if (getCookie(name))
	{
		document.cookie = name + "=" +
			((path) ? "; path=" + path : "") +
			((domain) ? "; domain=" + domain : "") +
			"; expires=Thu, 01-Jan-70 00:00:01 GMT";
	}
}




/*==================================================
	Set the tabber options (must do this before including tabber.js)
	==================================================*/

var fctabber = new Object();

var tabberOptions =
{
	'cookie':"fctabber", /* Name to use for the cookie */

	'onLoad': function(argsObj)
	{
		var t = argsObj.tabber;
		var i;

		/* Create a reference to the every tabber object that has an HTML tag id */
		if (t.id)
		{
			fctabber[t.id] = argsObj.tabber;
		}

		/* Optional: Add the id of the tabber to the cookie name to allow
			 for multiple tabber interfaces on the site.  If you have
			 multiple tabber interfaces (even on different pages) I suggest
			 setting a unique id on each one, to avoid having the cookie set
			 the wrong tab.
		*/
		if (t.id)
		{
			t.cookie = t.id + t.cookie;
		}

		/* If a cookie was previously set, restore the active tab */
		i = parseInt(tabberGetCookie(t.cookie));

		//window.console.log(t.forcedTab);

		if (isNaN(i) || t.forcedTab >= 0) { return; }
		t.tabShow(i);
	},

	'onClick':function(argsObj)
	{
		var c = argsObj.tabber.cookie;
		var i = argsObj.index;

		tabberSetCookie(c, i);
	}
};


/**
 * Run tabberAutomaticOnload() unless the "manualStartup" option was specified
 */

if (typeof tabberOptions == 'undefined')
{
	tabberAutomaticOnLoad();
}
else if (!tabberOptions['manualStartup'])
{
	tabberAutomaticOnLoad(tabberOptions);
}