(function() {

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
  }

  // Init UI
  $(function() {
    _initUI();
  });
})();

