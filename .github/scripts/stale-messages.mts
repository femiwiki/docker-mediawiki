// Lists the wiki's MediaWiki: pages that one MediaWiki install reads and
// another no longer does, so an upgrade can move them before cutover.
//
// Usage: node .github/scripts/stale-messages.mts OLD_DIR NEW_DIR API_URL [TITLE...]
// where each directory is /srv/femiwiki.com copied out of a femiwiki image:
//   docker cp "$(docker create ghcr.io/femiwiki/femiwiki:TAG)":/srv/femiwiki.com old
//
// A page is reported when its message key
// - removed: is in some en.json under OLD_DIR and in none under NEW_DIR, or
// - unread: is written out in the code under OLD_DIR and nowhere under NEW_DIR,
//   which catches on-wiki config such as Cite's tool definition, or
// - orphan: is in neither, and is no key built at runtime.
// TITLE adds a page the wiki no longer has, to check a move already made.
import { execFileSync } from 'node:child_process';
import { globSync, mkdtempSync, readFileSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

// Keys that code builds from a name the wiki chooses
const RUNTIME = [
  /^(abusefilter-(warning|disallowed)|group|grouppage|right|action|tag|gadget|gadget-section|wikibase-sitelinks)-/,
  /^gadgets\//,
  /^visualeditor-cite-tool-name-/,
  /^(common|print|mobile|minerva|vector|vector-2022|femiwiki|group-[^.]+)\.(css|js)$/,
];
const SKIP = ['i18n', 'tests', 'vendor', 'node_modules'];

const [oldDir, newDir, api, ...extra] = process.argv.slice(2);
if (!api) throw new Error('usage: OLD_DIR NEW_DIR API_URL [TITLE...]');

const lcfirst = (s: string) => s[0].toLowerCase() + s.slice(1);
const ucfirst = (s: string) => s[0].toUpperCase() + s.slice(1);
const escape = (s: string) => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

const pages: string[] = [];
for (let cont: Record<string, string> = {}; cont;) {
  const q = new URLSearchParams({
    action: 'query',
    format: 'json',
    formatversion: '2',
    list: 'allpages',
    apnamespace: '8',
    aplimit: 'max',
    ...cont,
  });
  const res = await (await fetch(`${api}?${q}`)).json();
  pages.push(...res.query.allpages.map((p: { title: string }) => p.title));
  cont = res.continue;
}
pages.push(...extra);
const keyOf = (title: string) =>
  lcfirst(
    title
      .slice(title.indexOf(':') + 1)
      .replace(/ /g, '_')
      .replace(/\/[a-z-]+$/, ''),
  );
const wanted = [...new Set(pages.map(keyOf))].flatMap((k) => [k, ucfirst(k)]);
const wantedRe = new RegExp(
  `(?<![\\w-])(${wanted.map(escape).join('|')})(?![\\w-])`,
  'g',
);
const pats = join(mkdtempSync(join(tmpdir(), 'stale-messages-')), 'pats');
writeFileSync(pats, wanted.join('\n'));

const side = (dir: string) => {
  const keys = new Set(
    globSync('**/i18n/**/en.json', { cwd: dir })
      .filter((f) => !/(^|\/)(vendor|node_modules)\//.test(f))
      .flatMap((f) =>
        Object.keys(JSON.parse(readFileSync(join(dir, f), 'utf8'))),
      )
      .map(lcfirst),
  );
  let out = '';
  try {
    out = execFileSync(
      'grep',
      [
        '-rhwFf',
        pats,
        ...['php', 'js', 'json', 'vue'].map((e) => `--include=*.${e}`),
        ...SKIP.map((d) => `--exclude-dir=${d}`),
        dir,
      ],
      { encoding: 'utf8', maxBuffer: 1 << 30 },
    );
  } catch {} // grep exits 1 when nothing matches
  const read = new Set(
    out
      .split('\n')
      // A key in a comment is not a key the code reads
      .filter((line) => !/^\s*(\*|\/\/|\/\*|#)/.test(line))
      .flatMap((line) =>
        [...line.matchAll(wantedRe)].map((m) => lcfirst(m[1])),
      ),
  );
  return { keys, read };
};

const before = side(oldDir);
const after = side(newDir);
const live = [...new Set([...after.keys, ...after.read])];
const words = (k: string) => new Set(k.split(/[-_.]/));
// The keys the new side has that share the most words with a lost one
const hint = (k: string) => {
  const w = words(k);
  const shared = (a: string) => [...words(a)].filter((x) => w.has(x)).length;
  return live
    .filter((a) => shared(a) >= 2)
    .map((a) => [a, shared(a) / Math.max(w.size, words(a).size)] as const)
    .filter(([, s]) => s >= 0.5)
    .sort((x, y) => y[1] - x[1])
    .slice(0, 3)
    .map(([a]) => a)
    .join(', ');
};

const signal = (k: string) =>
  before.keys.has(k) && !after.keys.has(k)
    ? 'removed'
    : before.read.has(k) && !after.read.has(k)
      ? 'unread'
      : [before.keys, after.keys, before.read, after.read].every(
            (s) => !s.has(k),
          ) && !RUNTIME.some((r) => r.test(k))
        ? 'orphan'
        : '';
const order = ['removed', 'unread', 'orphan'];
const rows = pages
  .map((title) => [title, signal(keyOf(title))])
  .filter(([, s]) => s)
  .sort((a, b) => order.indexOf(a[1]) - order.indexOf(b[1]))
  .map(([t, s]) => `| ${t} | ${s} | ${s === 'orphan' ? '' : hint(keyOf(t))} |`);
console.log('| Page | Signal | Live keys alike |\n| --- | --- | --- |');
console.log(rows.join('\n'));
process.exitCode = rows.length ? 1 : 0;
