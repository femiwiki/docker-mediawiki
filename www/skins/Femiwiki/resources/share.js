(function() {
  const facebookAppId = '1937597133150935';

  var $editorEl = $(
    '<div id="share" class="dialog" style="display: none;">' +
    '<p>' +
    '  아래를 복사하세요.' +
    '</p>' +
    '<form>' +
    '  <textarea readonly id="share-text"></textarea>' +
    '  <input type="reset" value="닫기">' +
    '</form>' +
    '</div>'
  );

  function _initUI() {
    $(document.body).append($editorEl);  
    $('#share-copy').find('a').on('click', function(e) {
      e.preventDefault();

      $editorEl.show();
      $('textarea#share-text').html($(this).attr('href')).select().focus(function(){
        $(this).select();
      });
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
     }(document, 'script', 'facebook-jssdk'));
    $('#share-facebook').find('a').on('click', function(e) {
      e.preventDefault();
      
      FB.ui({
      method: 'share',
      href: $(this).attr('href'),
    }, function(response){});
    });
  }

  // Init UI
  $(function() {
    _initUI();
  });
})();

