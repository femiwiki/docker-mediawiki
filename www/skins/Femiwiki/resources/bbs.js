$(function () {
  var listElement = document.querySelector('.fw-bbs-list');
  if(listElement) {
    handleListPage(listElement);
    return;
  }

  var $body = $(document.body);
  var nsId = document.body.className.match(/\bns-(\d+)\b/);
  nsId = nsId ? +nsId[1] : -1;
  var isEditPage = $body.hasClass('action-edit');
  if(!isEditPage && _FW.BBS_NS.indexOf(nsId) !== -1) {
    handleReadPage();
  }

  function handleListPage(element) {
    var nsId = +element.dataset.nsid;
    var nsName = element.dataset.nsname;

    fetchList(nsId, function (data) {
      var menu = renderMenu();
      var list = renderList(data);

      element.innerHTML = menu + list;
      $(element).find('a.write').on('click', onClickWrite);
    });

    function onClickWrite(e) {
      e.preventDefault();
      var title = window.prompt('새 글의 제목을 입력해주세요');
      if(!title) return;

      // append timestamp to the title in order to avoid name conflict
      var timestamp = Date.now().toString(16);
      location.href = '/index.php?title=' + encodeTitle(nsName + ':' + title + '_(' + timestamp + ')') + '&action=edit&classes=bbs';
    }

    /**
     * Fetch recent changes for given namespace
     *
     * @param nsId
     * @param callback
     */
    function fetchList(nsId, callback) {
      var url = '/w/api.php?action=query&list=recentchanges&rctype=new|edit&rcprop=title|ids|user|timestamp&rclimit=100&format=json&rcnamespace=' + nsId;
      $.get(url, callback);
    }

    /**
     * Render recent changes table as HTML
     * @param data
     */
    function renderList(data) {
      var rows = data.query.recentchanges;
      var buffer = [];

      buffer.push('<ul class="rows">');
      rows.forEach(function (row) {
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

      try {
        row.displayTitle = row.title.match(/^.+?:(.+) \([a-f0-9]+\)$/)[1];
      } catch (e) {
        // ignore article with invalid title
        return '';
      }

      return (
        '<li class="row type-' + row.type + '">' +
        '<ul class="cols">' +
        '<li class="col timestamp">' + row.timestampStr + '</li>' +
        '<li class="col user"><a href="/w/' + encodeTitle('사용자:' + row.user) + '">' + escapeEntity(row.user) + '</a></li>' +
        '<li class="col title"><a href="/w/' + encodeTitle(row.title) + '">' + escapeEntity(row.displayTitle) + '</a></li>' +
        '</ul>' +
        '</li>'
      );
    }

    function renderMenu() {
      return (
        '<ul class="menu">' +
        '<li><a href="#" class="write btn">글쓰기</a></li>' +
        '</ul>'
      );
    }
  }

  function handleReadPage() {
    // Replace title
    var titleEl = document.querySelector('.firstHeading');
    var unparsedTitle = titleEl.childNodes[0].nodeValue;
    var match = unparsedTitle.match(/^(.+?):(.+?)( \([a-f0-9]+\))?$/);
    var nsName = match[1];
    var title = match[2];
    var hash = match[3];
    titleEl.childNodes[0].nodeValue = title;

    // Render BBS menu
    $('#bodyContent').prepend('<div id="#contentSub"><p><span class="subpages">&lt; <a href="#" class="list">글목록</a></span></p></div>');
    $('#fw-footer-menu').prepend('<li><a href="#" class="list">글목록</a></li>');
    $('a.list').on('click', onClickList);

    // Render comments
    renderComments(nsName, title, hash, function($commentList) {
      var $commentSection = $('<div id="fw-bbs-comment-section">');
      var editable = mw.config.get('wgIsProbablyEditable');

      $commentSection.append(
        '<form id="fw-bbs-new-comment-form">' +
        '<label for="fw-bbs-new-comment">새 댓글 쓰기:</label>' +
        '<input type="text" id="fw-bbs-new-comment" ' + (editable ? '' : 'disabled') + ' placeholder="' + (editable ? '댓글' : '권한 없음') + '">' +
        '</form>'
      );
      $commentSection.append($commentList);
      $('#content').append($commentSection);

      $('#fw-bbs-new-comment-form').on('submit', onComment);
    });

    // Done
    $('body, #mw-wrapper').addClass('bbs-read');

    function onComment(e) {
      e.preventDefault();
      var $comment = $('#fw-bbs-new-comment');
      var comment = $comment.val();
      $comment.val('');

      if(!comment) return;

      // Generate comment wikitext
      var userName = mw.config.get('wgUserName');
      var now = new Date();
      var $newComment = $('<p><span class="ts"></span> <span class="user"></span> <span class="text"></span></p>');
      $newComment.find('.ts').text(
        now.getFullYear() + '-' +
        zeropad(now.getMonth() + 1) + '-' +
        zeropad(now.getDate()) + ' ' +
        zeropad(now.getHours()) + ':' +
        zeropad(now.getMinutes()) + ':' +
        zeropad(now.getSeconds())
      );
      $newComment.find('.user').text('[[사용자:' + userName + '|' + userName + ']]');
      $newComment.find('.text').text(comment);
      var commentWikimarkup = $newComment.html();

      // Prepend comment
      $.getJSON('/w/api.php?action=query&meta=tokens&format=json', function(json) {
        var token = json.query.tokens.csrftoken;
        var discussionTitle = nsName + '토론:' + title + ' ' + hash;
        $.post('/w/api.php?action=edit', {summary: '댓글: ' + comment, title: discussionTitle, prependtext: '* ' + commentWikimarkup + '\n', token: token}, function() {
          // reload comment
          renderComments(nsName, title, hash, function($commentList) {
            $('#fw-bbs-comment-list').html($commentList.html());
          });
        });
      });
    }

    function onClickList(e) {
      e.preventDefault();

      location.href = '/w/' + encodeTitle('페미위키:' + nsName) + '?classes=bbs-list';
    }

    function renderComments(nsName, title, hash, callback) {
      var $commentList = $('<ol id="fw-bbs-comment-list" />');
      $commentList.load(getDiscussionUrl(nsName, title, hash) + ' #mw-content-text > ul > li', function(res, status, xhr) {
        callback($commentList);
      });
    }

    function getDiscussionUrl(nsName, title, hash) {
      var unparsedTitle = nsName + '토론:' + title + ' ' + hash + '';
      return '/w/' + encodeTitle(unparsedTitle);
    }
  }

  function encodeTitle(title) {
    return title.split('/').map(function(path) {return encodeURIComponent(path);}).join('/')
  }

  function escapeEntity(text) {
    return text.replace(/</g, '&lt;')
  }

  function zeropad(num) {
    var padded = '0' + num;
    return padded.length === 2 ? padded : padded.substr(1);
  }
})
;
