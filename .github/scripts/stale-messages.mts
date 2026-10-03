// Lists the wiki's MediaWiki: pages that the MediaWiki of one branch of this
// repository reads and the MediaWiki of another no longer does, so an upgrade
// can move or recreate them before cutover.
//
// Usage: node .github/scripts/stale-messages.mts OLD_REF NEW_REF API_URL
//   e.g. node .github/scripts/stale-messages.mts origin/rel-1.43 origin/main \
//          https://femiwiki.com/api.php
//
// Each repository is fetched at depth 1 into a cache under ~/.cache. A page is
// reported when its message key
// - removed: is in some en.json at OLD_REF and in none at NEW_REF, or
// - unread: is written out in the code at OLD_REF and nowhere at NEW_REF,
//   which catches on-wiki config such as Cite's tool definition, or
// - orphan: is in neither, and is no key built at runtime.
import { execFile } from 'node:child_process';
import { mkdirSync, mkdtempSync, writeFileSync } from 'node:fs';
import { homedir, tmpdir } from 'node:os';
import { join } from 'node:path';
import { promisify } from 'node:util';

// Keys that code builds from a name the wiki chooses
const RUNTIME = [
  /^(abusefilter-(warning|disallowed)|group|grouppage|right|action|tag|gadget|gadget-section|wikibase-sitelinks)-/,
  /^gadgets\//,
  /^visualeditor-cite-tool-name-/,
  /^(common|print|mobile|minerva|vector|vector-2022|femiwiki|group-[^.]+)\.(css|js)$/,
];
const CODE = [
  '*.php',
  '*.js',
  '*.json',
  '*.vue',
  ':!*/i18n/*',
  ':!i18n/*',
  ':!*tests/*',
];

type Repo = { url: string; rev: string; noSubmodules?: boolean };
const [oldRef, newRef, api, ...extra] = process.argv.slice(2);
if (!api) throw new Error('usage: OLD_REF NEW_REF API_URL [TITLE...]');
const cache = join(
  process.env.XDG_CACHE_HOME ?? join(homedir(), '.cache'),
  'stale-messages',
);

const run = promisify(execFile);
const git = async (args: string[], cwd?: string) =>
  (await run('git', args, { cwd, encoding: 'utf8', maxBuffer: 1 << 30 }))
    .stdout;

const extensionsJson = async (ref: string) =>
  JSON.parse(
    await git([
      'show',
      `${ref}:dockers/femiwiki-extensions/extension-installer/extensions.json`,
    ]),
  );

// Core is fetched at its release tag, whose submodules are the bundled
// extensions, not ours
const repos = async (ref: string): Promise<Repo[]> => {
  const ext = await extensionsJson(ref);
  const core = (
    await git(['show', `${ref}:dockers/mediawiki/Dockerfile`])
  ).match(/^ARG MEDIAWIKI_VERSION=(\S+)$/m)![1];
  const list: Repo[] = [
    {
      url: 'https://github.com/wikimedia/mediawiki',
      rev: core,
      noSubmodules: true,
    },
  ];
  for (const [n, rev] of Object.entries<string>(ext['WMF-extensions']))
    list.push({
      url: `https://github.com/wikimedia/mediawiki-extensions-${n}`,
      rev,
    });
  for (const [n, rev] of Object.entries<string>(ext['WMF-skins']))
    list.push({
      url: `https://github.com/wikimedia/mediawiki-skins-${n}`,
      rev,
    });
  for (const e of Object.values<any>(ext['in-house']))
    list.push({ url: e.repository, rev: e.commit });
  for (const e of Object.values<any>(ext['non-WMF']))
    list.push({ url: e.template.replace(/\/archive\/.*/, ''), rev: e.version });
  return list;
};

// Fetches a repository and its submodules, and returns [dir, commit] pairs
const fetchAll = async (
  { url, rev, noSubmodules }: Repo,
  mirrors: Record<string, string>,
): Promise<[string, string][]> => {
  const dir = join(
    cache,
    url.replace(/^https?:\/\//, '').replace(/[^\w.-]/g, '_'),
  );
  const ref = `refs/stale/${rev}`;
  mkdirSync(dir, { recursive: true });
  await git(['init', '-q', '--bare'], dir);
  try {
    await git(['rev-parse', '-q', '--verify', ref], dir);
  } catch {
    const src = /^[0-9a-f]{40}$/.test(rev) ? rev : `refs/tags/${rev}`;
    await git(
      [
        'fetch',
        '-q',
        '--depth=1',
        '--no-write-fetch-head',
        url,
        `+${src}:${ref}`,
      ],
      dir,
    );
  }
  const commit = (await git(['rev-parse', `${ref}^{commit}`], dir)).trim();
  const out: [string, string][] = [[dir, commit]];
  let modules = '';
  if (!noSubmodules)
    try {
      modules = await git(
        ['config', '--blob', `${commit}:.gitmodules`, '--get-regexp', 'url$'],
        dir,
      );
    } catch {}
  for (const line of modules.trim().split('\n').filter(Boolean)) {
    const [key, subUrl] = line.split(' ');
    const path = (
      await git(
        [
          'config',
          '--blob',
          `${commit}:.gitmodules`,
          key.replace(/url$/, 'path'),
        ],
        dir,
      )
    ).trim();
    const sub = (await git(['ls-tree', commit, path], dir)).split(/\s+/)[2];
    out.push(
      ...(await fetchAll(
        { url: mirrors[subUrl] ?? new URL(subUrl, url + '/').href, rev: sub },
        mirrors,
      )),
    );
  }
  return out;
};

const scan = async (dir: string, commit: string, pats: string) => {
  const keys: string[] = [];
  const read: string[] = [];
  const files = (
    await git(['ls-tree', '-r', '--name-only', commit], dir)
  ).split('\n');
  for (const f of files.filter((f) => /(^|\/)i18n\/(.+\/)?en\.json$/.test(f)))
    keys.push(
      ...Object.keys(
        JSON.parse(await git(['show', `${commit}:${f}`], dir)),
      ).map(lcfirst),
    );
  try {
    const out = await git(
      ['grep', '-h', '-w', '-F', '-f', pats, commit, '--', ...CODE],
      dir,
    );
    // A key in a comment is not a key the code reads
    for (const line of out.split('\n'))
      if (!/^\s*(\*|\/\/|\/\*|#)/.test(line.replace(/^[0-9a-f]{40}:/, '')))
        read.push(...[...line.matchAll(wantedRe)].map((m) => lcfirst(m[1])));
  } catch {} // git grep exits 1 when nothing matches
  return { keys, read };
};

const side = async (ref: string, pats: string) => {
  const mirrors = (await extensionsJson(ref))['submodule-mirrors'] ?? {};
  const keys = new Set<string>();
  const read = new Set<string>();
  const queue = await repos(ref);
  const worker = async () => {
    for (let repo; (repo = queue.shift());)
      for (const [dir, commit] of await fetchAll(repo, mirrors)) {
        const r = await scan(dir, commit, pats);
        r.keys.forEach((k) => keys.add(k));
        r.read.forEach((k) => read.add(k));
      }
  };
  await Promise.all(Array.from({ length: 8 }, worker));
  return { keys, read };
};

const lcfirst = (s: string) => s[0].toLowerCase() + s.slice(1);

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
const wanted = [...new Set(pages.map(keyOf))];

const ucfirst = (s: string) => s[0].toUpperCase() + s.slice(1);
const escape = (s: string) => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
const wantedRe = new RegExp(
  `(?<![\\w-])(${wanted
    .flatMap((k) => [k, ucfirst(k)])
    .map(escape)
    .join('|')})(?![\\w-])`,
  'g',
);
const pats = join(mkdtempSync(join(tmpdir(), 'stale-messages-')), 'pats');
writeFileSync(pats, wanted.flatMap((k) => [k, ucfirst(k)]).join('\n'));
// One side at a time, so two workers never fetch into the same repository
const before = await side(oldRef, pats);
const after = await side(newRef, pats);
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

const rows: [string, string][] = [];
for (const title of pages) {
  const k = keyOf(title);
  const signal =
    before.keys.has(k) && !after.keys.has(k)
      ? 'removed'
      : before.read.has(k) && !after.read.has(k)
        ? 'unread'
        : !before.keys.has(k) &&
            !after.keys.has(k) &&
            !before.read.has(k) &&
            !after.read.has(k) &&
            !RUNTIME.some((r) => r.test(k))
          ? 'orphan'
          : '';
  if (signal)
    rows.push([
      signal,
      `| ${title} | ${signal} | ${signal === 'orphan' ? '' : hint(k)} |`,
    ]);
}
const order = ['removed', 'unread', 'orphan'];
rows.sort((a, b) => order.indexOf(a[0]) - order.indexOf(b[0]));
console.log('| Page | Signal | Live keys alike |\n| --- | --- | --- |');
console.log(rows.map(([, row]) => row).join('\n'));
process.exitCode = rows.length ? 1 : 0;
