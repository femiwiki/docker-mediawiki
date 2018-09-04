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
  function menuResize(divId){
    var containerWidth = parseFloat($('#'+divId).css('width'))
      -parseFloat($('#'+divId).css('padding-left'))
      -parseFloat($('#'+divId).css('padding-right')),
      itemPadding
        =parseFloat($('#'+divId+' > div').css('padding-left'))
        +parseFloat($('#'+divId+' > div').css('padding-right')),
      itemMargin
        =parseFloat($('#'+divId+' > div').css('margin-left'))
        +parseFloat($('#'+divId+' > div').css('margin-right')),
      itemActualMinWidth = 
        parseFloat($('#'+divId+' > div').css('min-width'))
        +itemPadding+itemMargin,
      itemLength = $('#'+divId+' > div').filter(function() {
          return $(this).css('display') !== 'none';
      }).length,
      horizontalCapacity = Math.min(Math.floor(containerWidth / itemActualMinWidth),itemLength);

    $('#'+divId+' > div').css("width",Math.floor(containerWidth/horizontalCapacity-itemPadding-itemMargin));
  }

  var searchInput = $('#searchInput'),
   searchClearButton = $('#searchClearButton');
  searchInput.on("input", function(){
    searchClearButton.toggle(!!this.value);
  });
  searchClearButton.click(function () {
    searchInput.val("").trigger('input').focus();
  });

  $('#fw-menu-toggle').click(function () {
    $('#fw-menu').toggle();
    menuResize('fw-menu');
    $('#fw-menu-toggle .badge')
      .removeClass('active')
  });
  $('#p-menu-toggle > a').click(function () {
    e.preventDefault();
    $('#p-actions-and-toolbox').toggle();
    menuResize('p-actions-and-toolbox');
  });
  $(window).resize(function() {
    menuResize('fw-menu');
    menuResize('p-actions-and-toolbox')
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

  // Collapsible category links
  var catlinksToggle = $('<button></button>');
  catlinksToggle.text("►");
  catlinksToggle.addClass('fw-catlinks-toggle');

  var catlinks = $('#mw-normal-catlinks li'),
    directCatAnchors = $('#fw-catlinks li>a'),
    directCatTexts = {};
  for(var i=0,len=directCatAnchors.length;i<len;i++)
    directCatTexts[directCatAnchors[i].text] = true;

  if(directCatAnchors.length !== catlinks.length) {
    for(var i=0,len=catlinks.length;i<len;i++)
      if( !directCatTexts[catlinks[i].innerText] )
       catlinks[i].className += ' collapsible' ;

    $('#catlinks li.collapsible').fadeOut();
    var collapsed = true;
    catlinksToggle.click(function () {
      $(this).text($(this).text() == "▼" ? "►" : "▼");
      if(collapsed)
        $('#catlinks li.collapsible').fadeIn();
      else
        $('#catlinks li.collapsible').fadeOut();
      collapsed = !collapsed;
    });
    $('#mw-normal-catlinks').prepend(catlinksToggle);
  }

  // Do not show edit page when user clicks red link
  $('#bodyContent a').each(function() {
    this.href = this.href.replace('&action=edit&redlink=1', '&redlink=1');
  });

  // Open external links in new tab
  $('#bodyContent a').each(function() {
    var external = this.href.match('^https?://') && !this.href.match('^https?://' + location.hostname);
    if(external) {
      $(this)
        .addClass('external')
        .attr('target', '_blank');
    } else {
      $(this).removeClass('external');
    }
  })
  // Set Mathjax linebreaks configuration
  MathJax.Hub.Config({
    CommonHTML: { linebreaks: { automatic: true } },
    "HTML-CSS": { linebreaks: { automatic: true } },
           SVG: { linebreaks: { automatic: true } }
  });

  // Center single Mathjax line
  MathJax.Hub.Queue(function () {
    $('#content p > span:only-child > span.MathJax,'
    +'#content p > span.mathjax-wrapper:only-child > div').each(function(){
      if(!$(this).parent().parent().clone().children().remove().end().text().trim().length) {
        $(this).parent().css('display','block');
        $(this).parent().css('text-align','center');
      }
    });
  });
});