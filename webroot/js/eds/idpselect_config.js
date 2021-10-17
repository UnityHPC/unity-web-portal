
/** @class IdP Selector UI */
function IdPSelectUIParms(){
    //
    // Adjust the following to fit into your local configuration
    //
    this.alwaysShow = true;          // If true, this will show results as soon as you start typing
    this.dataSource = '/Shibboleth.sso/DiscoFeed';   // Where to get the data from
    this.defaultLanguage = 'en';     // Language to use if the browser local doesnt have a bundle
    this.defaultLogo = 'blank.gif';  // Replace with your own logo
    this.defaultLogoWidth = 1;
    this.defaultLogoHeight = 1 ;
    this.defaultReturn = null;       // If non null, then the default place to send users who are not
    // Approaching via the Discovery Protocol for example
    //this.defaultReturn = "https://example.org/Shibboleth.sso/DS?SAMLDS=1&target=https://example.org/secure";
    this.defaultReturnIDParam = null;
    this.returnWhiteList = [ "^https:\/\/unity\.rc\.umass\.edu\/Shibboleth\.sso\/Login.*$", "^https:\/\/netbox\.rc\.umass\.edu\/Shibboleth\.sso\/Login.*$" ];
    this.helpURL = 'https://wiki.shibboleth.net/confluence/display/SHIB2/DSRoadmap';
    this.ie6Hack = null;             // An array of structures to disable when drawing the pull down (needed to 
    // handle the ie6 z axis problem
    this.insertAtDiv = 'idpSelect';  // The div where we will insert the data
    this.maxResults = 10;            // How many results to show at once or the number at which to
    // start showing if alwaysShow is false
    this.myEntityID = null;          // If non null then this string must match the string provided in the DS parms
    this.preferredIdP = [ "https://webauth.umass.edu/idp/shibboleth" ];        // Array of entityIds to always show
    this.hiddenIdPs = null;          // Array of entityIds to delete
    this.ignoreKeywords = false;     // Do we ignore the <mdui:Keywords/> when looking for candidates
    this.showListFirst = true;      // Do we start with a list of IdPs or just the dropdown
    this.samlIdPCookieTTL = 730;     // in days
    this.setFocusTextBox = true;     // Set to false to supress focus 
    this.testGUI = false;

    this.autoFollowCookie = "_saml_idp_auto";  //  If you want auto-dispatch, set this to the cookie name to use
    this.autoFollowCookieTTLs = 60; // Cookie life (in days).  Changing this requires changes to idp_select_languages

    //
    // Language support. 
    //
    // The minified source provides "en", "de", "pt-br" and "jp".  
    //
    // Override any of these below, or provide your own language
    //
    this.langBundles = {
	'en': {
	    'fatal.divMissing': '<div> specified  as "insertAtDiv" could not be located in the HTML',
	    'fatal.noXMLHttpRequest': 'Browser does not support XMLHttpRequest, unable to load IdP selection data',
	    'fatal.wrongProtocol' : 'Policy supplied to DS was not "urn:oasis:names:tc:SAML:profiles:SSO:idpdiscovery-protocol:single"',
	    'fatal.wrongEntityId' : 'entityId supplied by SP did not match configuration',
	    'fatal.noData' : 'Metadata download returned no data',
	    'fatal.loadFailed': 'Failed to download metadata from ',
	    'fatal.noparms' : 'No parameters to discovery session and no defaultReturn parameter configured',
	    'fatal.noReturnURL' : "No URL return parameter provided",
	    'fatal.badProtocol' : "Return request must start with https:// or http://",
	    'idpList.defaultOptionLabel': 'Please select your organization...',
	    'submitButton.label': 'Continue',
        'autoFollow.time' : ' Remember My Choice'
	}
    };
    
    //
    // The following should not be changed without changes to the css.  Consider them as mandatory defaults
    //
    this.maxPreferredIdPs = 0;
    this.maxIdPCharsButton = 33;
    this.maxIdPCharsDropDown = 58;
    this.maxIdPCharsAltTxt = 60;

    this.minWidth = 20;
    this.minHeight = 20;
    this.maxWidth = 115;
    this.maxHeight = 69;
    this.bestRatio = Math.log(80 / 60);
}
