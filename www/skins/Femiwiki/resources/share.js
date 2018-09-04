(function() {
  const firebaseKey = 'AIzaSyCmc0RONpZQLy22clpSsE7jVbGmKQ8pZC8';
  const facebookAppId = '1937597133150935';
  var longURL, shortURL;

  var $editorEl = $(
    '<div id="share" class="dialog" style="display: none;">' +
    '<ul class="share-list">' +
    '<li id="share-facebook"><a href="#"><span>페이스북</span></a></li>' +
    '<li id="share-twitter"><a href="#"><span>트위터</span></a></li>' +
    '</ul>' +
    '<form>' +
    '  <textarea readonly id="share-text"></textarea>' +
    '</form>' +
    '<form>' +
    '  <input type="reset" value="닫기">' +
    '</form>' +
    '</div>'
  );

  function _initUI() {
    longURL = window.location.href;

    $(document.body).append($editorEl);
    $('#p-share a').on('click', function(e) {
      e.preventDefault();

      if ( longURL != window.location.href ) {
        longURL = window.location.href;
        shortURL = undefined;
        updateURL( longURL );
      }

      $editorEl.show();

      updateURL( shortURL!==undefined?shortURL:longURL );
      $('textarea#share-text').select();

      if ( shortURL===undefined )
        createShortURL( longURL );
     });

    $('textarea#share-text').focus(function(){
      $(this).select();
    });

    $editorEl.find('input[type="reset"]').on('click', function(e) {
      e.preventDefault();
      $editorEl.hide();
    });

    window.fbAsyncInit = function() {
      FB.init({
        appId            : facebookAppId,
        autoLogAppEvents : true,
        xfbml            : true,
        version          : 'v2.10'
      });
      FB.AppEvents.logPageView();
    };

    (function(d, s, id){
       var js, fjs = d.getElementsByTagName(s)[0];
       if (d.getElementById(id)) {return;}
       js = d.createElement(s); js.id = id;
       js.src = "//connect.facebook.net/en_US/sdk.js";
       fjs.parentNode.insertBefore(js, fjs);
     })(document, 'script', 'facebook-jssdk');

    $('#share-facebook a').on('click', function(e) {
      e.preventDefault();
      
      FB.ui({
      method: 'share',
      href: longURL,
    }, function(response){});
    });
  }

  function updateURL( url ) {
    $('textarea#share-text').html(url).select();

    var tweet = mw.config.get( 'wgPageName' ) + ' ' + url + ' #페미위키';

    $('#share-twitter a').attr('href', 'https://twitter.com/intent/tweet?text=' + encodeURIComponent( tweet ) );
  }

  function createShortURL( longURL ) {
    shortURL = undefined;

    var params = {
    "dynamicLinkInfo": {
      "dynamicLinkDomain": "fmwk.page.link",
      "link": longURL,
      "analyticsInfo": {
        "googlePlayAnalytics": {
          "utmCampaign": "share"
        }
      }
    },
    "suffix": {
      "option": "SHORT"
    }
  };

  $.ajax({
    url: 'https://firebasedynamiclinks.googleapis.com/v1/shortLinks?key=' + firebaseKey,
    type: 'POST',
    data: JSON.stringify(params),
    contentType: "application/json",
    success: function (response) {
        if ( longURL != window.location.href )
          return;
        shortURL = response.shortLink;
        updateURL(shortURL);
    },
    error: function (request, status, error) {
        console.log(request.responseText);
    }
});     
  }

  // Init UI
  $(function() {
    _initUI();
  });
})();

