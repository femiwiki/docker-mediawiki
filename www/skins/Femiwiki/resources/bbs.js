$(function () {
  var BBS_NS = ['3902'];

  var listElement = document.querySelector('.fw-bbs-list');
  if(listElement) {
    handleListPage(listElement);
    return;
  }

  var $body = $(document.body);
  var nsId = document.body.className.match(/\bns-(\d+)\b/)[1];
  var isEditPage = $body.hasClass('action-edit');
  if(!isEditPage && BBS_NS.indexOf(nsId) !== -1) {
    handleReadPage();
  }

  function handleListPage(element) {
    var nsId = element.dataset.nsid;
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
      location.href = '/index.php?title=' + encodeURIComponent(nsName + ':' + title + '_(' + timestamp + ')') + '&action=edit&classes=bbs';
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
        '<li class="col user"><a href="/w/' + encodeURIComponent('사용자:' + row.user) + '">' + escapeEntity(row.user) + '</a></li>' +
        '<li class="col title"><a href="/w/' + encodeURIComponent(row.title) + '">' + escapeEntity(row.displayTitle) + '</a></li>' +
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
    var title = titleEl.childNodes[0].nodeValue;
    var match = title.match(/^(.+?):(.+?)( \([a-f0-9]+\))?$/);
    var nsName = match[1];
    var newTitle = match[2];
    titleEl.childNodes[0].nodeValue = newTitle;

    // Render BBS menu
    $('#fw-footer-menu').prepend('<li><a href="#" class="list">글목록</a></li>');
    $('a.list').on('click', onClickList);

    // Done
    $('body, #mw-wrapper').addClass('bbs-read');

    function onClickList(e) {
      e.preventDefault();

      location.href = '/w/' + encodeURIComponent('페미위키:' + nsName) + '?classes=bbs-list';
    }
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
