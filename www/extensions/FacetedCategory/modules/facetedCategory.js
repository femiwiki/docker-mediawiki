$( function() {
	// Collapsible category links
	var catlinksToggle = $( '<button></button>' );
	catlinksToggle.text( '►' );
	catlinksToggle.addClass( 'fw-catlinks-toggle' );

	var catlinks = $( '#mw-normal-catlinks li' ),
		directCatAnchors = $( '#fw-catlinks li > a' ),
		directCatTexts = {};
	for ( var i=0, len=directCatAnchors.length ; i < len ; i++ )
		directCatTexts[directCatAnchors[i].text] = true;

	if ( directCatAnchors.length !== catlinks.length ) {
		for ( var i = 0, len = catlinks.length ; i < len ; i++ )
			if ( !directCatTexts[catlinks[i].innerText] )
			 catlinks[i].className += ' collapsible' ;

		$( '#catlinks li.collapsible' ).fadeOut();
		var collapsed = true;
		catlinksToggle.click( function () {
			$( this ).text( $( this ).text() == "▼" ? "►" : "▼" );
			if ( collapsed )
				$( '#catlinks li.collapsible' ).fadeIn();
			else
				$( '#catlinks li.collapsible' ).fadeOut();
			collapsed = !collapsed;
		} );
		$( '#mw-normal-catlinks' ).prepend( catlinksToggle );
	}
} );
