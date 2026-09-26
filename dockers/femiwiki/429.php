<?php
/**
 * Writes the page Caddy serves with a 429, and the translations beside it.
 * The title is MediaWiki's own "actionthrottled", so every language
 * translatewiki has comes with each MediaWiki release. The page itself is
 * English only (it is what crawlers get); 429.json holds every language
 * and only a browser running the script fetches it.
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

$all = [];
foreach ( glob( "$root/languages/i18n/*.json" ) as $file ) {
	$lang = basename( $file, '.json' );
	if ( $lang === 'qqq' ) {
		continue;
	}
	$messages = json_decode( file_get_contents( $file ), true );
	if ( isset( $messages['actionthrottled'] ) ) {
		$all[$lang] = [ 't' => $messages['actionthrottled'] ];
	}
}
ksort( $all );

// Our own body, in the languages this wiki is read in. Everything else falls
// back to English, which is what the page is written in.
$login = '/w/' . rawurlencode( '특수:로그인' );
$signup = '/w/' . rawurlencode( '특수:계정만들기' );
$body = [
	'en' => [
		'This limit is shared between everyone who is not logged in, so you may be seeing it '
			. 'without having done anything yourself. It applies to page histories, differences, '
			. 'old revisions and searches, never to reading an article.',
		'Log in',
		'Create an account',
	],
	'ko' => [
		'이 제한은 로그인하지 않은 모든 방문자가 함께 나눠 쓰기 때문에, 직접 아무것도 하지 않았는데도 '
			. '보일 수 있습니다. 문서 역사, 차이, 옛 판, 검색에만 걸리고 문서를 읽는 데에는 걸리지 않습니다.',
		'로그인',
		'계정 만들기',
	],
];
foreach ( $all as $lang => $_ ) {
	$t = $body[$lang] ?? $body['en'];
	$all[$lang]['b'] = $t[0];
	$all[$lang]['c'] = $t[1];
	$all[$lang]['d'] = $t[2];
}
file_put_contents( "$out/429.json", json_encode( $all, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );

$en = htmlspecialchars( $all['en']['t'] );
$enText = htmlspecialchars( $all['en']['b'] );
$enCall = htmlspecialchars( $all['en']['c'] );
$enJoin = htmlspecialchars( $all['en']['d'] );

file_put_contents( "$out/429.html", <<<HTML
<!doctype html>
<html lang="en">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width">
<title>$en</title>
<style>body{font:16px/1.6 sans-serif;max-width:36em;margin:4em auto;padding:0 1em}</style>
<p id="text">$enText</p>
<p><a id="login" href="$login">$enCall</a> &middot; <a href="$signup" id="signup">$enJoin</a></p>
<script>
(function () {
	var want = [];
	(navigator.languages || [navigator.language]).forEach(function (l) {
		l = (l || "").toLowerCase();
		want.push(l);
		if (l.indexOf("-") > 0) want.push(l.split("-")[0]);
	});
	fetch("/429.json").then(function (r) { return r.json(); }).then(function (all) {
		for (var i = 0; i < want.length; i++) {
			var m = all[want[i]];
			if (!m) continue;
			document.getElementById("text").textContent = m.b;
			document.getElementById("login").textContent = m.c;
			document.getElementById("signup").textContent = m.d;
			document.documentElement.lang = want[i];
			document.title = m.t;
			return;
		}
	}).catch(function () {});
})();
</script>

HTML
);
