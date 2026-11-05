<?php
/**
 * The pages warm-up.php asks php-fpm for before a fastcgi container serves, and
 * check-cached-twice.php asks the new Caddy for before it opens a public port.
 * One list, so the preflight never reaches a page the warm-up left cold, as
 * load.php did in femiwiki/infra#1588.
 *
 * Each entry is a path with its query, the status MediaWiki gives, and for a
 * 301 where it must point. {main} is the main page's path from siteinfo, which
 * is why siteinfo comes first; the test stack has a main page of its own.
 */
return [
	[ '/api.php?action=query&meta=siteinfo&format=json', 200 ],
	[ '{main}', 200 ],
	[ '/', 301, '{main}' ],
	// A page an anonymous reader is sent to that compiles the session code
	[ '/w/' . str_replace( '%3A', ':', rawurlencode( '특수:로그인' ) ), 200 ],
	[ '/rest.php/v1/search/title?q=a&limit=1', 200 ],
	[ '/load.php?modules=startup&only=scripts', 200 ],
	[ '/w/Special:Version', 301 ],
	[ '/index.php?title=Special:Version', 301 ],
];
