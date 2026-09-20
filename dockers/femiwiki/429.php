<?php
/**
 * Writes the page Caddy serves with a 429, and the translations beside it.
 * The text is MediaWiki's own "actionthrottled" messages, so every language
 * translatewiki has comes with each MediaWiki release. The page itself is
 * small (it is what crawlers get); 429.json holds all languages and only a
 * browser running the script fetches it.
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
	if ( isset( $messages['actionthrottled'], $messages['actionthrottledtext'] ) ) {
		$all[$lang] = [ 't' => $messages['actionthrottled'], 'b' => $messages['actionthrottledtext'] ];
	}
}
ksort( $all );
file_put_contents( "$out/429.json", json_encode( $all, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );

$en = htmlspecialchars( $all['en']['t'] );
$enText = nl2br( htmlspecialchars( $all['en']['b'] ), false );
$koText = nl2br( htmlspecialchars( $all['ko']['b'] ), false );

file_put_contents( "$out/429.html", <<<HTML
<!doctype html>
<html lang="en">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width">
<title>$en</title>
<style>body{font:16px/1.6 sans-serif;max-width:36em;margin:4em auto;padding:0 1em}</style>
<p lang="en" id="first">$enText</p>
<p lang="ko" id="second">$koText</p>
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
			var p = document.getElementById("first");
			p.lang = want[i];
			p.textContent = m.b;
			document.getElementById("second").remove();
			document.documentElement.lang = want[i];
			document.title = m.t;
			return;
		}
	}).catch(function () {});
})();
</script>

HTML
);
