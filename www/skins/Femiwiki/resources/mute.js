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

  function _showTriggerWarning() {
    // Do nothing if it's edit mode
    if($('body.action-edit').length) return;
    if($('html.ve-activated').length) return;

    var text = $('#content').html();
    if(!shouldMute(text)) return;

    // Show trigger warning
    $('#mw-content-text').prepend(
      '<div class="notice warning">' +
      '가림 단어가 포함된 문서입니다. 이 문서의 제목 또는 본문에 한 개 이상의 가림 단어가 있습니다.' +
      '</div>'
    );
  }

  // Load, save
  function loadData() {
    return JSON.parse(localStorage.getItem('muteWords') || '[]');
  }

  function saveData(muteWords) {
    try {
      localStorage.setItem('muteWords', JSON.stringify(muteWords));
      _muteWords = muteWords;
    } catch(e) {
      alert('프라이버시 모드에서는 이 기능을 사용하실 수 없습니다.');
    }

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
    _showTriggerWarning();
  });

  // Expose public APIs
  _FW.mute = {
    loadData: loadData,
    saveData: saveData,
    shouldMute: shouldMute
  };
})();

