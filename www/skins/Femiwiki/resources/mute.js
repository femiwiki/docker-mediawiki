(function() {
  var _muteWords = loadData();

  var $muteLinkEl = $('<li id="footer-places-mute"><a href="#">뮤트</a></li>');
  var $editorEl = $(
    '<div id="muteEditor" style="display: none;">' +
    '<form>' +
    '  <textarea id="muteEditor-text" placeholder="감출 키워드를 한 줄에 하나씩 입력하세요."></textarea>' +
    '  <input type="submit" value="저장">' +
    '  <input type="reset" value="취소">' +
    '</form>' +
    '<p>' +
    '  키워드는 페미위키 서버가 아닌 사용자 브라우저에 저장됩니다.' +
    '  여러 브라우저로 페미위키를 이용하시는 분들은 각 브라우저별로' +
    '  키워드를 등록해주시기 바랍니다.' +
    '</p>' +
    '</div>'
  );

  function _initUI() {
    $('#footer-places').append($muteLinkEl);
    $(document.body).append($editorEl);

    // Event handlers
    $muteLinkEl.find('a').on('click', function(e) {
      e.preventDefault();
      $editorEl.show();

      var muteWords = loadData();
      $('#muteEditor-text')
        .val(muteWords.join('\n'))
        .focus();
    });
    $editorEl.find('form').on('submit', function(e) {
      e.preventDefault();
      var muteWords = $('#muteEditor-text').val().trim();
      saveData(muteWords ? muteWords.split('\n') : []);
      $editorEl.hide();
    });
    $editorEl.find('input[type="reset"]').on('click', function(e) {
      e.preventDefault();
      $editorEl.hide();
    });
  }

  // Load, save
  function loadData() {
    return JSON.parse(localStorage.getItem('muteWords') || '[]');
  }

  function saveData(muteWords) {
    localStorage.setItem('muteWords', JSON.stringify(muteWords));
    _muteWords = muteWords;
  }

  // Should mute or not?
  function shouldMute(text) {
    for(var i = 0; i < _muteWords.length; i++) {
      if(text.match(_muteWords[i])) return true;
    }
    return false;
  }

  // Init UI
  $(function() {
    _initUI();
  });

  // Expose public APIs
  _FW.mute = {
    loadData: loadData,
    saveData: saveData,
    shouldMute: shouldMute
  };
})();

