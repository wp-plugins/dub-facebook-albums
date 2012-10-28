//When the document is ready...
jQuery(document).ready(function(){
	
	//If the ColorBox plugin has loaded, and there is at least one photo on the page...
	if (typeof colorbox == 'object' && jQuery('.dub-fa-thumb').length > 0) {
		
		//Apply ColorBox to the photos...
		jQuery('.dub-fa-photo').colorbox({ rel: 'dub-fa-photos', current: "Image {current} of {total}" });
		
	}
	
});