/**
 * Support for number switching on pages with dynamic content. This
 * script relies on jQuery version 1.8 and up.
 *
 * Copyright (c) 2014. by Way2CU, http://way2cu.com
 * Authors: Mladen Mijatov
 */
$(document).ajaxComplete(function(event, xhr, options) {
	var web_content = new RegExp('');  // url match for triggering

	// if URL matches, replace phone numbers after short delay
	if (options.url.match(web_content)) {
		setTimeout(function() {
			__ctm.main.runNow();
		}, 100);
	}
});
