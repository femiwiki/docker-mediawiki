$(function() {
  var element = document.querySelector('.fw-bbs');
  if(!element) return;

  var nsId = element.innerHTML;

  fetch(nsId, function(data) {
    var menu = renderMenu();
    var list = renderList(data);

    element.innerHTML = menu + list + menu;
  });

  /**
   * Fetch recent changes for given namespace
   *
   * @param nsId
   * @param callback
   */
  function fetch(nsId, callback) {
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
    rows.forEach(function(row) {
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

    return (
      '<li class="row type-' + row.type + '">' +
      '<ul class="cols">' +
      '<li class="col timestamp">' + row.timestampStr + '</li>' +
      '<li class="col user"><a href="/w/' + encodeURI('사용자:' + row.user) + '">' + escapeEntity(row.user) + '</a></li>' +
      '<li class="col title"><a href="/w/' + encodeURI(row.title) + '">' + escapeEntity(row.title.substr(row.title.indexOf(':') + 1)) + '</a></li>' +
      '</ul>' +
      '</li>'
    );
  }

  function renderMenu() {
    return (
      '<ul class="menu">' +
      '<li><a href="#">새글</a></li>' +
      '</ul>'
    );
  }

  function escapeEntity(text) {
    return text.replace(/</g, '&lt;')
  }

  function zeropad(num) {
    var padded = '0' + num;
    return padded.length === 2 ? padded : padded.substr(1);
  }
});
