<?php
/**
 * Writes the page Caddy serves with a 429. The text is MediaWiki's own
 * "actionthrottled" messages, so translations come from translatewiki with
 * every MediaWiki release. The languages are those of 미디어위키:Loginlanguagelinks.
 *
 * Usage: php 429.php <mediawiki root> > 429.html
 */

$langs = [ 'ko', 'en', 'ja', 'fr', 'de', 'es', 'ru' ];
$root = $argv[1] ?? '/srv/femiwiki.com';

$titles = [];
$paras = [];
$selectors = [];
foreach ( $langs as $lang ) {
	$messages = json_decode( file_get_contents( "$root/languages/i18n/$lang.json" ), true );
	$title = htmlspecialchars( $messages['actionthrottled'] );
	$text = nl2br( htmlspecialchars( $messages['actionthrottledtext'] ), false );
	$titles[$lang] = $messages['actionthrottled'];
	$paras[] = "<p lang=\"$lang\">$text</p>";
	$selectors[] = "html:lang($lang) p:lang($lang)";
}
$titlesJson = json_encode( $titles, JSON_UNESCAPED_UNICODE );
$titleKo = htmlspecialchars( $titles['ko'] );
$paraHtml = implode( "\n", $paras );
$selectorCss = implode( ',', $selectors );

echo <<<HTML
<!doctype html>
<html lang="ko">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width">
<title>$titleKo</title>
<style>
body{font:16px/1.6 sans-serif;max-width:36em;margin:4em auto;padding:0 1em}
p{display:none}
$selectorCss{display:block}
</style>
$paraHtml
<script>
var titles=$titlesJson;
for(var i=0,l=navigator.languages||[navigator.language];i<l.length;i++){var c=(l[i]||"").slice(0,2).toLowerCase();if(titles[c]){document.documentElement.lang=c;document.title=titles[c];break}}
</script>

HTML;
