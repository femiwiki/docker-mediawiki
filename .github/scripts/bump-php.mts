// Moves php-fpm to the PHP minor that the MediaWiki release we run requires
// at least. Core raises that floor to what Wikimedia production runs, so this
// keeps us on WMF's PHP for the release instead of the newest one it allows.
import { readFileSync, writeFileSync } from 'node:fs';
import type * as Core from '@actions/core';
import type { context as Context } from '@actions/github';

const compare = (a: string, b: string) => {
  const [x, y] = [a, b].map((v) => v.split('.').map(Number));
  for (let i = 0; i < Math.max(x.length, y.length); i++) {
    if ((x[i] ?? 0) !== (y[i] ?? 0)) return (x[i] ?? 0) - (y[i] ?? 0);
  }
  return 0;
};

const get = async (url: string) => {
  const res = await fetch(url);
  if (!res.ok) throw new Error(`${url}: ${res.status}`);
  return res;
};

export default async ({
  core,
  context,
  env,
}: {
  core: typeof Core;
  context: typeof Context;
  env: NodeJS.ProcessEnv;
}) => {
  const mediawiki = readFileSync('dockers/mediawiki/Dockerfile', 'utf8').match(
    /^ARG MEDIAWIKI_VERSION=(\S+)$/m,
  )![1];
  const dockerfile = 'dockers/php-fpm/Dockerfile';
  const text = readFileSync(dockerfile, 'utf8');
  const [, cur, variant] = text.match(/^FROM .*php:(\d+\.\d+\.\d+)-(\S+)$/m)!;

  const composer = `https://github.com/wikimedia/mediawiki/blob/${mediawiki}/composer.json`;
  const manifest = await (
    await get(
      `https://raw.githubusercontent.com/wikimedia/mediawiki/${mediawiki}/composer.json`,
    )
  ).json();
  const php: string = manifest.require.php;
  const floor = php.match(/^>=\s*(\d+\.\d+)/)?.[1];
  if (!floor) throw new Error(`Unexpected require.php in ${composer}: ${php}`);

  if (compare(floor, cur.replace(/\.\d+$/, '')) <= 0) {
    core.info(`MediaWiki ${mediawiki} requires PHP ${floor}; we run ${cur}`);
    return;
  }

  const tags = await (
    await get(
      `https://hub.docker.com/v2/repositories/library/php/tags?name=${floor}.&page_size=100`,
    )
  ).json();
  const pattern = new RegExp(
    `^(${floor.replace('.', '\\.')}\\.\\d+)-${variant}$`,
  );
  const versions = (tags.results as { name: string }[])
    .map((t) => t.name.match(pattern)?.[1])
    .filter((v): v is string => v !== undefined);
  if (versions.length === 0) {
    core.warning(`No php:${floor}.x-${variant} image yet`);
    return;
  }
  const latest = versions.sort(compare).at(-1)!;

  writeFileSync(
    dockerfile,
    text.replace(`php:${cur}-${variant}`, `php:${latest}-${variant}`),
  );
  const run = `${context.serverUrl}/${context.repo.owner}/${context.repo.repo}/actions/runs/${context.runId}`;
  const body = [
    `[MediaWiki ${mediawiki} requires PHP ${floor}](${composer}), so this bumps PHP ${cur} → ${latest}.`,
    '',
    `Opened by [run ${context.runId}](${run}).`,
  ].join('\n');
  writeFileSync(env.BODY!, body + '\n');
  core.info(body);
  core.setOutput('ver', latest);
  core.setOutput('minor', floor);
  core.setOutput('mediawiki', mediawiki.split('.').slice(0, 2).join('.'));
};
