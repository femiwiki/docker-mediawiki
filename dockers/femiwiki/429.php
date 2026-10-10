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
	'b' => 'Too many of these requests have arrived just now, so this one was turned away. The limit '
		. 'is shared between everyone who is not logged in, so you may be seeing this without '
		. 'having done anything yourself. It applies to page histories, differences, old '
		. 'revisions and searches, never to reading an article.',
	'c' => 'Log in',
	'd' => 'Create an account',
];
$ko = [
	't' => $title( 'ko' ),
	'b' => '지금 이런 요청이 너무 많이 들어와서 이 요청은 받지 못했습니다. 이 한도는 로그인하지 않은 '
		. '방문자가 함께 나눠 쓰기 때문에, 직접 아무것도 하지 않았는데도 이 화면이 보일 수 '
		. '있습니다. 문서 역사, 차이, 옛 판, 검색에만 걸리고 문서를 읽는 데에는 걸리지 않습니다.',
	'c' => '로그인',
	'd' => '계정 만들기',
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
