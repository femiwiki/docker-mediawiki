// Lists the pull requests between the femiwiki image infra runs and this one,
// for the body of the infra bump pull request. A pull request that only bumps
// an upstream image says nothing by its title, so the upstream README entries
// it brings in are listed under it.
import { readFileSync } from 'node:fs';
import type * as Core from '@actions/core';
import type { getOctokit } from '@actions/github';

type GitHub = ReturnType<typeof getOctokit>;

// The sections and their order are release-please's
const HEADINGS: Record<string, string> = {
  feat: 'Features',
  fix: 'Bug Fixes',
  perf: 'Performance Improvements',
  revert: 'Reverts',
  docs: 'Documentation',
  style: 'Styles',
  refactor: 'Code Refactoring',
  test: 'Tests',
  build: 'Build System',
  ci: 'Continuous Integration',
  chore: 'Miscellaneous Chores',
  other: 'Other',
};

export default async ({
  github,
  core,
  env,
}: {
  github: GitHub;
  core: typeof Core;
  env: NodeJS.ProcessEnv;
}) => {
  const [owner, repo] = env.REPO!.split('/');
  const sha = env.SHA!;
  // The image tag ends in the commit it was built from
  const old = readFileSync(env.CONTAINER_TF!, 'utf8').match(
    /ghcr\.io\/femiwiki\/femiwiki:[^"]*-([0-9a-f]{8})"/,
  )?.[1];

  const readFile = async (path: string, ref: string) => {
    try {
      const { data } = await github.rest.repos.getContent({
        owner,
        repo,
        path,
        ref,
        mediaType: { format: 'raw' },
      });
      return data as unknown as string;
    } catch {
      return '';
    }
  };

  let commits = [sha];
  if (old) {
    try {
      const { data } = await github.rest.repos.compareCommits({
        owner,
        repo,
        base: old,
        head: sha,
      });
      if (data.commits.length > 0) {
        commits = data.commits.map((c) => c.sha);
      }
    } catch {
      core.warning(`Could not compare ${old}...${sha}; listing ${sha} alone.`);
    }
  }

  // The upstream versions the running image was built on, so a bump lists
  // only what is new to production
  const deployed = old
    ? pins(await readFile('dockers/femiwiki/Dockerfile', old))
    : {};

  const sections: Record<string, string[]> = {};
  const add = (type: string, lines: string[]) => {
    sections[type] ??= [];
    if (!sections[type].includes(lines[0])) {
      sections[type].push(...lines);
    }
  };
  const prs: number[] = [];

  for (const c of commits) {
    const { data: pulls } =
      await github.rest.repos.listPullRequestsAssociatedWithCommit({
        owner,
        repo,
        commit_sha: c,
      });
    const pr = pulls[0];
    if (!pr) {
      add('other', [`* ${owner}/${repo}@${c}`]);
      continue;
    }
    prs.push(pr.number);
    // A bump lists the upstream changes in its body, so the line links those
    // and not only the bump
    const ref = [
      ...(pr.head.ref.startsWith('bump-') ? upstream(pr.body ?? '') : []),
      `${owner}/${repo}#${pr.number}`,
    ].join(', ');

    const m = pr.title.match(/^([a-z]+)(\(([^)]+)\))?!?: (.*)$/);
    if (!m || !HEADINGS[m[1]]) {
      add('other', [`* ${pr.title} (${ref})`]);
      continue;
    }
    // A bump of an extension or skin is scoped to the image it lands in and
    // ends in "(<name> <version>)"; name the repository it came from instead
    let [, , , scope, subject] = m;
    const source = pr.head.ref.startsWith('bump-')
      ? pr.body?.match(
          /^\[[^\]]*\]\(https:\/\/github\.com\/[\w.-]+\/([\w.-]+)\/compare\//,
        )?.[1]
      : undefined;
    if (source) {
      scope = source;
      subject = subject.replace(/ \([^()\s]+ [^()\s]+\)$/, '');
    }
    const lines = [`* ${scope ? `**${scope}:** ` : ''}${subject} (${ref})`];

    const bump = subject.match(/^bump ([\w-]+) image to v([\d.]+)$/);
    if (bump) {
      const [, image, version] = bump;
      const readme = await readFile(`dockers/${image}/README.md`, sha);
      lines.push(...entries(readme, version, deployed[image]));
      deployed[image] = version;
    }
    add(m[1], lines);
  }

  let refs = '';
  for (const [type, heading] of Object.entries(HEADINGS)) {
    if (sections[type]) {
      refs += `### ${heading}\n\n${sections[type].join('\n')}\n\n`;
    }
  }
  core.setOutput('refs', refs);
  core.setOutput('prs', prs.join(' '));
};

// The femiwiki images a Dockerfile builds on, each at the version it pins
function pins(dockerfile: string) {
  const out: Record<string, string> = {};
  for (const m of dockerfile.matchAll(
    /ghcr\.io\/femiwiki\/([\w-]+):([\d.]+)/g,
  )) {
    out[m[1]] = m[2];
  }
  return out;
}

// The femiwiki pull requests a bump body lists the changes of, as
// OWNER/REPO#N: a commit subject ends in one, a release note links one
function upstream(body: string) {
  const refs = new Set<string>();
  for (const line of body.split('\n')) {
    if (!/^[-*] /.test(line)) {
      continue;
    }
    for (const m of line.matchAll(
      /\((femiwiki\/[\w.-]+)#(\d+)\)|\(\[#\d+\]\(https:\/\/github\.com\/(femiwiki\/[\w.-]+)\/(?:pull|issues)\/(\d+)\)\)/g,
    )) {
      refs.add(`${m[1] ?? m[3]}#${m[2] ?? m[4]}`);
    }
  }
  return [...refs];
}

// The README entries from `## v<to>` down to, not including, `## v<from>`,
// each as a nested list item led by its version
function entries(readme: string, to: string, from?: string) {
  const out: string[] = [];
  let version = '';
  for (const line of readme.split('\n')) {
    const heading = line.match(/^## v(.+)$/);
    if (heading) {
      if (heading[1] === from || (version && !from)) {
        break;
      }
      if (version || heading[1] === to) {
        version = heading[1];
      }
      continue;
    }
    if (!version) {
      continue;
    }
    const item = line.match(/^(\s*)- (.*)$/);
    if (item) {
      out.push(item[1] ? `    * ${item[2]}` : `  * v${version}: ${item[2]}`);
    }
  }
  return out;
}
