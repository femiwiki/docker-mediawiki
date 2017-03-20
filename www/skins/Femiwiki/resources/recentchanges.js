$(function() {
  var elements = document.querySelectorAll('.fw-recentchanges');
  if(elements.length === 0) return;

  // Show loading message
  for(var i = 0; i < elements.length; i++) {
    elements[i].innerHTML = '<span class="loading">바뀐글 읽어오는 중...</span>';
  }

  // Fetch data and render
  var url = '/w/api.php?action=query&list=recentchanges&rcprop=parsedcomment|title|ids|user|timestamp|sizes|loginfo&rcshow=!bot&rclimit=200&format=json';
  $.get(url, function(data) {
    var html = render(data);
    for(var i = 0; i < elements.length; i++) {
      elements[i].innerHTML = html;
    }
  });

  /**
   * Render recent changes table as HTML
   * @param data
   */
  function render(data) {
    var rows = data.query.recentchanges;
    var buffer = [];

    buffer.push('<ul class="rows">');
    rows.forEach(function(row) {
      var textsInRow = [row.title, row.user, row.parsedcomment].join('\t');
      row.muted = _FW.mute.shouldMute(textsInRow);
      buffer.push(renderRow(row));
    });
    buffer.push('</ul>');

    return buffer.join('');
  }

  function renderRow(row) {
    row.timestamp = new Date(row.timestamp);
    row.timestampStr = (
      zeropad(row.timestamp.getMonth() + 1) + '-' +
      zeropad(row.timestamp.getDate()) + ' ' +
      zeropad(row.timestamp.getHours()) + ':' +
      zeropad(row.timestamp.getMinutes())
    );
    row.diff = row.newlen - row.oldlen;
    row['flags'] = assignFlags(row);

    // Link comment to article page instead of discussion page
    var url;
    var isComment = _FW.BBS_NS.indexOf(+row.ns - 1) !== -1;
    if(isComment) {
      var match = row.title.match(/^(.+?):(.+?)( \([a-f0-9]+\))?$/);
      var nsName = match[1];
      var title = match[2];
      var hash = match[3];
      url = '/w/' + encodeTitle(nsName.substr(0, nsName.length - 2) + ':' + title + ' ' + hash) + '">' + escapeEntity(nsName + ':' + title);
    } else {
      url = '/w/' + encodeTitle(row.title) + '">' + escapeEntity(row.title);
    }

    var flags = [];
    for(var i = 0; i < row.flags.length; i++) {
      var flag = row.flags[i];
      flags.push('<span class="flag type-' + flag['type'] + ' logtype-' + flag['logtype'] + '">' + flag['text'] + '</span>');
    }

    return (
      '<li class="row type-' + row.type + ' ' + (row.muted ? 'muted' : '') + '">' +
      '<ul class="cols">' +
      '<li class="col flags">' + flags.join('\n') + '</li>' +
      '<li class="col timestamp"><a href="/index.php?title=' + encodeTitle(row.title) + '&action=history"><span class="mono">' + row.timestampStr + '</span> [역사]</a></li>' +
      '<li class="col sizes ' + (row.diff > 0 ? 'added' : (row.diff === 0 ? '' : 'deleted')) + '"><a href="/index.php?title=' + encodeTitle(row.title) + '&curid=' + row.pageid + '&diff=' + row.revid + '&oldid=' + row.old_revid + '"><span class="mono">' + (row.diff > 0 ? '+' : '') + row.diff + '</span> [차이]</a></li>' +
      '<li class="col user"><a href="/w/' + encodeTitle('사용자:' + row.user) + '">' + escapeEntity(row.user) + '</a></li>' +
      '<li class="col title"><a href="' + url + '</a></li>' +
      '<li class="col parsedcomment">' + (row.parsedcomment || '(설명 없음)') + '</li>' +
      '</ul>' +
      '</li>'
    );
  }

  function assignFlags(row) {
    var textMap = [
      [function(row) {return row['type'] === 'log' && row['logtype'] === 'newusers'}, '가입'],
      [function(row) {return row['type'] === 'log' && row['logtype'] === 'protect'}, '문서 보호'],
      [function(row) {return row['type'] === 'log' && row['logtype'] === 'rights'}, '권한 변경'],
      [function(row) {return row['type'] === 'log' && row['logtype'] === 'delete' && row['logaction'] === 'revision'}, '리비전 삭제'],
      [function(row) {return row['type'] === 'log' && row['logtype'] === 'delete' && row['logaction'] === 'delete'}, '문서 삭제'],
      [function(row) {return row['type'] === 'log' && row['logtype'] === 'block' && row['logaction'] === 'block'}, '이용자 차단'],
      [function(row) {return row['type'] === 'log' && row['logtype'] === 'move'}, '문서 이동'],
      [function(row) {return row['type'] === 'log' && row['logtype'] === 'upload'}, '파일 업로드'],
      [function(row) {return row['type'] === 'log'}, '기타 로그'],
      [function(row) {return row['type'] === 'new'}, '새 문서']
    ];
    var text = '';
    for(var i = 0; i < textMap.length; i++) {
      var test = textMap[i][0];
      var value = textMap[i][1];
      if(test(row)) {
        text = value;
        break;
      }
    }

    return [{
      type: row['type'],
      logtype: row['logtype'] || 'null',
      logaction: row['logaction'] || 'null',
      text: text
    }];
  }

  function encodeTitle(title) {
    return title ? title.split('/').map(function(path) {return encodeURIComponent(path);}).join('/') : '';
  }

  function escapeEntity(text) {
    return text ? text.replace(/</g, '&lt;') : '';
  }

  function zeropad(num) {
    var padded = '0' + num;
    return padded.length === 2 ? padded : padded.substr(1);
  }
});
