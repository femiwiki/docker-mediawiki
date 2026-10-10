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

  // The commits an image's Dockerfile takes in between two trees: each base
  // image's after its old pin up to its new one, and what those build on
  const builtOn = async (
    image: string,
    from: string,
    to: string,
  ): Promise<string[]> => {
    const before = pins(await readFile(`dockers/${image}/Dockerfile`, from));
    const after = pins(await readFile(`dockers/${image}/Dockerfile`, to));
    const out: string[] = [];
    for (const [base, version] of Object.entries(after)) {
      const was = before[base];
      if (!was || was === version) {
        continue;
      }
      // Newest first. A change under dockers/<base> raises its README
      // version, so the version tells which pin first carries a commit.
      const found: string[] = [];
      let last: string | undefined;
      walk: for await (const { data } of github.paginate.iterator(
        github.rest.repos.listCommits,
        { owner, repo, sha: to, path: `dockers/${base}`, per_page: 100 },
      )) {
        for (const c of data) {
          const v =
            (await readFile(`dockers/${base}/README.md`, c.sha)).match(
              /^## v(.+)$/m,
            )?.[1] ?? '';
          if (newer(v, version)) {
            continue;
          }
          if (!newer(v, was)) {
            last = c.sha;
            break walk;
          }
          found.push(c.sha);
        }
      }
      if (found.length > 0 && last) {
        out.push(...(await builtOn(base, last, found[0])));
      }
      out.push(...found.reverse());
    }
    return out;
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

  // Another image's change reaches this one only once its Dockerfile pins it,
  // wherever it was merged
  const own: string[] = [];
  for (const c of commits) {
    const { data } = await github.rest.repos.getCommit({ owner, repo, ref: c });
    const files = (data.files ?? []).map((f) => f.filename);
    if (
      files.length === 0 ||
      !files.every(
        (f) => f.startsWith('dockers/') && !f.startsWith('dockers/femiwiki/'),
      )
    ) {
      own.push(c);
    }
  }
  // One merged before the running image comes first; one merged in this range
  // keeps its place
  const pinned = old ? await builtOn('femiwiki', old, sha) : [];
  commits = [
    ...pinned.filter((c) => !commits.includes(c)),
    ...commits.filter((c) => own.includes(c) || pinned.includes(c)),
  ];

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

// Whether version a is above version b, both dotted numbers
function newer(a: string, b: string) {
  const x = a.split('.').map(Number);
  const y = b.split('.').map(Number);
  for (let i = 0; i < Math.max(x.length, y.length); i++) {
    if ((x[i] ?? 0) !== (y[i] ?? 0)) {
      return (x[i] ?? 0) > (y[i] ?? 0);
    }
  }
  return false;
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
