// GitHub refuses a title over 256 characters, so a long release keeps the
// leading changes that fit and counts the rest.
import { readFileSync } from 'node:fs';
import type * as Core from '@actions/core';

export const MAX = 256;

const length = (s: string) => [...s].length;

export const releaseTitle = ({
  type,
  scope,
  changes,
  name,
  ver,
}: {
  type: string;
  scope: string;
  changes: string[];
  name: string;
  ver: string;
}) => {
  if (changes.length === 0) return `build(${scope}): bump ${name} to ${ver}`;
  const head = `${type}(${scope}): `;
  const tail = ` (${name} ${ver})`;
  const all = head + changes.join('; ') + tail;
  if (length(all) <= MAX) return all;
  for (let kept = changes.length - 1; kept > 0; kept--) {
    const title = `${head}${changes.slice(0, kept).join('; ')}; and ${changes.length - kept} more${tail}`;
    if (length(title) <= MAX) return title;
  }
  // Not even the first change fits whole
  const more = changes.length > 1 ? `; and ${changes.length - 1} more` : '';
  const room = MAX - length(head + '…' + more + tail);
  return `${head}${[...changes[0]].slice(0, room).join('')}…${more}${tail}`;
};

export default ({
  core,
  env,
}: {
  core: typeof Core;
  env: NodeJS.ProcessEnv;
}) => {
  const changes = readFileSync(env.CHANGES!, 'utf8')
    .split('\n')
    .filter((line) => line !== '');
  const title = releaseTitle({
    type: env.TYPE!,
    scope: env.SCOPE!,
    changes,
    name: env.NAME!,
    ver: env.VER!,
  });
  core.info(title);
  core.setOutput('title', title);
};
