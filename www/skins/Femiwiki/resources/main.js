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
  BBS_NS: [3902, 3904]
};


$(function () {
  function fwMenuResize(){
    var containerWidth = parseFloat($('#fw-menu').css('width'))
      -parseFloat($('#fw-menu').css('padding-left'))
      -parseFloat($('#fw-menu').css('padding-right')),
      itemPadding
        =parseFloat($('#fw-menu > div').css('padding-left'))
        +parseFloat($('#fw-menu > div').css('padding-right')),
      itemMargin
        =parseFloat($('#fw-menu > div').css('margin-left'))
        +parseFloat($('#fw-menu > div').css('margin-right')),
      itemActualMinWidth = 
        parseFloat($('#fw-menu > div').css('min-width'))
        +itemPadding+itemMargin,
      itemLength = $('#fw-menu > div').filter(function() {
          return $(this).css('display') !== 'none';
      }).length,
      horizontalCapacity = Math.min(Math.floor(containerWidth / itemActualMinWidth),itemLength);

    $("#fw-menu > div").css("width",Math.floor(containerWidth/horizontalCapacity-itemPadding-itemMargin));
  }

  $('#fw-menu-toggle').click(function () {
    $('#fw-menu').toggle();
    fwMenuResize();
    $('#fw-menu-toggle .badge')
      .removeClass('active')
  });
  $(window).resize(fwMenuResize);

  if($('#lastmod').length !==0 && $('#lastmod')[0].innerHTML!=='')
    $('#p-links-toggle').click(function () {
      $('#p-page-tb').toggle();
      $('#p-site-tb').toggle();
    });
  else {
    $('#p-links-toggle').css("display","none");
    $('#p-page-tb').css("display","block");
    $('#p-site-tb').css("display","block");
  }

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

  // Move fw-catlinks
  var $catlinks = $('.fw-catlinks');
  $('#bodyContent').append($catlinks);

  // Do not show edit page when user clicks red link
  $('#bodyContent a').each(function() {
    this.href = this.href.replace('&action=edit&redlink=1', '&redlink=1');
  });
});
