<?php
/**
 * Writes the page Caddy serves with a 429. The title is MediaWiki's own
 * "actionthrottled", read from each MediaWiki release. The page itself is
 * English (it is what crawlers get); a browser that asks for Korean before
 * English swaps in the Korean text, which the page carries inline. A separate
 * file for it cost a second CloudFront request for every refusal, and every
 * other language had only the title translated (infra#1269).
 *
 * The body is ours rather than "actionthrottledtext". The limits that produce
 * this page are shared between everyone who is not logged in, so a reader can
 * arrive here having done nothing, and every one of them exempts a request
 * carrying a femiwikiUserID cookie. Neither is something the MediaWiki message
 * can say.
 *
 * Usage: php 429.php <mediawiki root> <output dir>
 */

$root = $argv[1] ?? '/srv/femiwiki.com';
$out = $argv[2] ?? '/srv/femiwiki.com';

$title = static function ( string $lang ) use ( $root ): string {
	$messages = json_decode( file_get_contents( "$root/languages/i18n/$lang.json" ), true );
	return $messages['actionthrottled'];
};

$login = '/w/' . rawurlencode( '특수:로그인' );
$signup = '/w/' . rawurlencode( '특수:계정만들기' );
$en = [
	't' => $title( 'en' ),
	'b' => 'There are too many requests from logged-out users right now. Please try again later, '
		. 'or log in to continue.',
	'c' => 'Log in',
	'd' => 'Create an account',
];
$ko = [
	't' => $title( 'ko' ),
	'b' => '로그인하지 않은 사용자의 접속이 많아 일시적으로 이용이 제한되었습니다. '
		. '잠시 후 다시 시도하거나 로그인해 주세요.',
	'c' => '로그인',
	'd' => '가입하기',
];
// JSON_HEX_TAG keeps a "</script>" in a message from closing the element
$koJson = json_encode( $ko, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG );

$enTitle = htmlspecialchars( $en['t'] );
$enText = htmlspecialchars( $en['b'] );
$enCall = htmlspecialchars( $en['c'] );
$enJoin = htmlspecialchars( $en['d'] );

file_put_contents( "$out/429.html", <<<HTML
<!doctype html>
<html lang="en">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width">
<meta name="robots" content="noindex,nofollow">
<title>$enTitle</title>
<style>body{font:16px/1.6 sans-serif;max-width:36em;margin:4em auto;padding:0 1em}
h1{font-size:1.4em;margin:0 0 .6em}</style>
<h1 id="title">$enTitle</h1>
<p id="text">$enText</p>
<p><a id="login" href="$login" rel="nofollow">$enCall</a> &middot;
<a href="$signup" id="signup" rel="nofollow">$enJoin</a></p>
<script>
// Bring the reader back to the page that was refused once they have logged in
(function () {
	try {
		var query = new URLSearchParams(location.search);
		var title = location.pathname.indexOf("/w/") === 0
			? decodeURIComponent(location.pathname.slice(3))
			: query.get("title");
		query.delete("title");
		if (!title) return;
		var back = "?returnto=" + encodeURIComponent(title);
		if (query.toString()) back += "&returntoquery=" + encodeURIComponent(query.toString());
		document.getElementById("login").search = back;
		document.getElementById("signup").search = back;
	} catch (e) {}
})();
(function () {
	var ko = $koJson;
	var langs = navigator.languages || [navigator.language];
	for (var i = 0; i < langs.length; i++) {
		var l = (langs[i] || "").toLowerCase().split("-")[0];
		if (l === "en") return;
		if (l !== "ko") continue;
		document.getElementById("title").textContent = ko.t;
		document.getElementById("text").textContent = ko.b;
		document.getElementById("login").textContent = ko.c;
		document.getElementById("signup").textContent = ko.d;
		document.documentElement.lang = "ko";
		document.title = ko.t;
		return;
	}
})();
</script>

HTML
);
