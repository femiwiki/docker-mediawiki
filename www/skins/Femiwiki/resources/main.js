// <gtm>
(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
  new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
  j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
  'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-5TNKVJ');
// </gtm>

// Web-font for Windows
// (function() {
//   if(navigator.userAgent.indexOf('Windows') === -1) return;
//
//   var link = document.createElement('link');
//   link.setAttribute('rel', 'stylesheet');
//   link.setAttribute('href', '//fonts.googleapis.com/earlyaccess/notosanskr.css');
//   document.head.appendChild(link);
// })();

var _FW = {
  BBS_NS: [3902]
};


$(function () {
  $('#fw-menu-toggle').click(function () {
    $('#fw-menu').toggle();
    $('#fw-menu-toggle .badge')
      .removeClass('active')
  });

  // Notification badge
  var alerts = +$('#pt-notifications-alert').text();
  var messages = +$('#pt-notifications-message').text();
  var badge = alerts + messages;
  if (badge !== 0) {
    $('#fw-menu-toggle .badge')
      .addClass('active')
      .text(badge > 10 ? '+9' : badge)
  }

  $('#pt-notifications-alert a').text('알림: ' + alerts);
  $('#pt-notifications-message a').text('메시지: ' + messages);


  // Add links
  var $firstHeading = $('.firstHeading');

  //// Add link to history to header and footer
  var $linkToHistory = $('#ca-history a');
  if ($linkToHistory.length) {
    var $lastmod = $('#footer-info-lastmod');
    if ($lastmod.length) {
      $lastmod.html('<a href="' + $linkToHistory.attr('href') + '">' + $lastmod.text() + '</a>');
      $firstHeading.after('<div class="lastmod">' + $lastmod.html() + '</div>');
    }
  }

  //// Add edit and discussion menus to header and footer
  var $editSection = $('<span class="mw-editsection"></span>').appendTo($firstHeading);
  var $footerMenu = $('#fw-footer-menu');

  var $veEdit = $('#ca-ve-edit');
  if ($veEdit.length) {
    $editSection.append($veEdit.html());
    $footerMenu.append('<li>' + $veEdit.html() + '</li>')
  }
  var $edit = $('#ca-edit');
  if ($edit.length) {
    $editSection.append($edit.html());
    $footerMenu.append('<li>' + $edit.html() + '</li>')
  }

  //// Add namespaces to footer
  var $namespaces = $('#p-namespaces ul li');
  $namespaces.each(function () {
    $footerMenu.append(this);
  });
  // Change label of first namespace
  $namespaces[0].firstChild.innerHTML = '문서';

  // Move fw-catlinks
  var $catlinks = $('.fw-catlinks');
  $('#bodyContent').append($catlinks);

  // Do not show edit page when user clicks red link
  $('#bodyContent a').each(function() {
    this.href = this.href.replace('&action=edit&redlink=1', '&redlink=1');
  });

  // Highlight signatures in discussion pages
  var $paragraphs = $('body.ns-talk #mw-content-text').find('p, dd');
  var pSig = /--(<a\s.+?>.+?<\/a>)/g;
  $paragraphs.each(function() {
    var html = this.innerHTML.trim();
    this.innerHTML = html.replace(pSig, '--<span class="fw-signature">$1</span>');
  })
});
