<?php
/**
 * Writes the page Caddy serves with a 429. The page itself is English (it is
 * what crawlers get); a browser that asks for Korean before English swaps in
 * the Korean text, which the page carries inline. A separate file for it cost
 * a second CloudFront request for every refusal, and every other language had
 * only the title translated (infra#1269).
 *
 * The text is ours rather than "actionthrottled" and "actionthrottledtext".
 * The limits that produce this page are shared between everyone who is not
 * logged in, so a reader can arrive here having done nothing, and every one of
 * them exempts a request carrying a femiwikiUserID cookie. Neither is something
 * the MediaWiki messages can say.
 *
 * It looks like the skin (colours from FemiwikiSkin's variables.less, the
 * design guide's buttons) but loads nothing: the styles and the logo are
 * inline, since anything more the refused reader asks for may be refused too.
 *
 * Usage: php 429.php <mediawiki root> <output dir>
 */

$root = $argv[1] ?? '/srv/femiwiki.com';
$out = $argv[2] ?? '/srv/femiwiki.com';

// The wordmark the skin shows in its top bar, without the empty rectangle
// that only sets its size, on one line
$logo = file_get_contents( "$root/fw-resources/logo-transparent-white.svg" );
$logo = preg_replace( '/<path fill="none"[^>]*\/>/', '', $logo );
$logo = preg_replace( '/>\s+</', '><', trim( $logo ) );
$logo = str_replace( '<svg ', '<svg role="img" aria-label="Femiwiki" ', $logo );

$login = '/w/' . rawurlencode( '특수:로그인' );
$signup = '/w/' . rawurlencode( '특수:계정만들기' );
$en = [
	't' => 'Femiwiki is busy right now',
	'b' => 'There are too many requests from logged-out users right now. Please try again in a moment, '
		. 'or log in to continue right away.',
	'c' => 'Log in',
	'r' => 'Try again',
	'n' => 'No account?',
	'd' => 'Create one',
];
$ko = [
	't' => '지금은 접속이 많습니다',
	'b' => '로그인하지 않은 사용자의 접속이 많아 일시적으로 이용이 제한되었습니다. '
		. '잠시 후 다시 시도해 주세요. 로그인하면 기다리지 않고 바로 이어서 볼 수 있습니다.',
	'c' => '로그인',
	'r' => '다시 시도',
	'n' => '계정이 없다면',
	'd' => '가입하기',
];
// JSON_HEX_TAG keeps a "</script>" in a message from closing the element
$koJson = json_encode( $ko, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG );
$h = array_map( 'htmlspecialchars', $en );

file_put_contents( "$out/429.html", <<<HTML
<!doctype html>
<html lang="en">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width">
<meta name="robots" content="noindex,nofollow">
<title>{$h['t']}</title>
<style>
:root{color-scheme:light dark;--bg:#fff;--text:#202122;--link:#736ac9;--tonal:#ecebfc;--tonal-hover:#dfddf8;
--tonal-active:#d3cff4;--tonal-text:#5144a3;--shadow:rgba(0,0,0,.5)}
@media (prefers-color-scheme:dark){:root{--bg:#101418;--text:#eaecf0;--link:#8579c2;--tonal:#36355b;
--tonal-hover:#3d3b68;--tonal-active:#464379;--tonal-text:#c3bffd;--shadow:rgba(0,0,0,.87)}}
body{margin:0;word-break:keep-all;overflow-wrap:break-word;background:var(--bg);color:var(--text);font:17px/1.7 "Apple SD Gothic Neo","Noto Sans KR","본고딕",
"Noto Sans CJK KR","나눔바른고딕","나눔고딕",NanumGothic,"맑은고딕","Malgun Gothic",Arial,Dotum,sans-serif}
header{background:#736ac9;height:44px;display:flex;align-items:center;padding:0 1.3rem}
header a{display:flex}
header svg{height:26px;width:auto}
main{max-width:36rem;margin:3rem auto;padding:0 1.3rem}
h1{font-size:1.6rem;line-height:1.3;margin:0 0 .8rem}
p{margin:0 0 1rem}
a{color:var(--link)}
.buttons{display:flex;flex-wrap:wrap;gap:.6rem;margin:1.6rem 0}
.button{padding:.45rem 1.2rem;border-radius:.3em;font-weight:bold;text-decoration:none;
box-shadow:0 1px 2px var(--shadow)}
.button:active{box-shadow:inset 0 1px 2px var(--shadow)}
.primary{background:#bad75b;color:#414f0a}
.primary:hover{background:#acc94d}
.primary:active{background:#8ca534}
.tonal{background:var(--tonal);color:var(--tonal-text)}
.tonal:hover{background:var(--tonal-hover)}
.tonal:active{background:var(--tonal-active)}
a:focus-visible{outline:2px solid var(--link);outline-offset:2px}
</style>
<header><a href="/" rel="nofollow">$logo</a></header>
<main>
<h1 id="title">{$h['t']}</h1>
<p id="text">{$h['b']}</p>
<p class="buttons"><a class="button primary" id="login" href="$login" rel="nofollow">{$h['c']}</a>
<a class="button tonal" id="retry" href="" rel="nofollow">{$h['r']}</a></p>
<p><span id="noaccount">{$h['n']}</span> <a href="$signup" id="signup" rel="nofollow">{$h['d']}</a></p>
</main>
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
		document.getElementById("retry").textContent = ko.r;
		document.getElementById("noaccount").textContent = ko.n;
		document.getElementById("signup").textContent = ko.d;
		document.documentElement.lang = "ko";
		document.title = ko.t;
		return;
	}
})();
</script>

HTML
);
