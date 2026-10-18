// Lists the pull requests, base images' included, between the femiwiki image
// infra runs and this one, for the body of the infra bump pull request.
import { readFileSync } from "node:fs";
import type * as Core from "@actions/core";
import type { getOctokit } from "@actions/github";

type GitHub = ReturnType<typeof getOctokit>;

const need = (env: NodeJS.ProcessEnv, name: string) => {
	const value = env[name];
	if (!value) throw new Error(`${name} is not set`);
	return value;
};

// The sections and their order are release-please's
const HEADINGS: Record<string, string> = {
	feat: "Features",
	fix: "Bug Fixes",
	perf: "Performance Improvements",
	revert: "Reverts",
	docs: "Documentation",
	style: "Styles",
	refactor: "Code Refactoring",
	test: "Tests",
	build: "Build System",
	ci: "Continuous Integration",
	chore: "Miscellaneous Chores",
	other: "Other",
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
	const [owner, repo] = need(env, "REPO").split("/");
	const sha = need(env, "SHA");
	// The image tag ends in the commit it was built from
	const old = readFileSync(need(env, "CONTAINER_TF"), "utf8").match(
		/ghcr\.io\/femiwiki\/femiwiki:[^"]*-([0-9a-f]{8})"/,
	)?.[1];

	const readFile = async (path: string, ref: string) => {
		try {
			const { data } = await github.rest.repos.getContent({
				owner,
				repo,
				path,
				ref,
				mediaType: { format: "raw" },
			});
			return data as unknown as string;
		} catch {
			return "";
		}
	};

	// The commit a pin was built from: a tag ends in it, and a version from before
	// #1272 is the newest commit whose README entry is not above it
	const builtFrom = async (image: string, pin: string, ref: string) => {
		const tagged = pin.match(/-([0-9a-f]{8})$/)?.[1];
		if (tagged) {
			try {
				const { data } = await github.rest.repos.getCommit({
					owner,
					repo,
					ref: tagged,
				});
				return data.sha;
			} catch {
				return undefined;
			}
		}
		for await (const { data } of github.paginate.iterator(
			github.rest.repos.listCommits,
			{ owner, repo, sha: ref, path: `dockers/${image}`, per_page: 100 },
		)) {
			for (const c of data) {
				const v = (await readFile(`dockers/${image}/README.md`, c.sha)).match(
					/^## v(.+)$/m,
				)?.[1];
				if (v && !newer(v, pin)) {
					return c.sha;
				}
			}
		}
		return undefined;
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
		for (const [base, pin] of Object.entries(after)) {
			const was = before[base];
			if (!was || was === pin) {
				continue;
			}
			const [last, head] = await Promise.all([
				builtFrom(base, was, from),
				builtFrom(base, pin, to),
			]);
			if (!last || !head) {
				core.warning(
					`Could not find what ${base}:${was}...${pin} was built from.`,
				);
				continue;
			}
			const { data } = await github.rest.repos.compareCommits({
				owner,
				repo,
				base: last,
				head,
			});
			const between = new Set(data.commits.map((c) => c.sha));
			// Newest first
			const found: string[] = [];
			walk: for await (const { data } of github.paginate.iterator(
				github.rest.repos.listCommits,
				{ owner, repo, sha: head, path: `dockers/${base}`, per_page: 100 },
			)) {
				for (const c of data) {
					if (!between.has(c.sha)) {
						break walk;
					}
					found.push(c.sha);
				}
			}
			if (found.length > 0) {
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
				(f) => f.startsWith("dockers/") && !f.startsWith("dockers/femiwiki/"),
			)
		) {
			own.push(c);
		}
	}
	// One merged before the running image comes first; one merged in this range
	// keeps its place
	const pinned = old ? await builtOn("femiwiki", old, sha) : [];
	commits = [
		...pinned.filter((c) => !commits.includes(c)),
		...commits.filter((c) => own.includes(c) || pinned.includes(c)),
	];

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
			add("other", [`* ${owner}/${repo}@${c}`]);
			continue;
		}
		prs.push(pr.number);
		// A bump lists the upstream changes in its body, so the line links those
		// and not only the bump
		const ref = [
			...(pr.head.ref.startsWith("bump-")
				? upstream(pr.body ?? "").filter(
						(r) => !r.startsWith(`${owner}/${repo}#`),
					)
				: []),
			`${owner}/${repo}#${pr.number}`,
		].join(", ");

		const m = pr.title.match(/^([a-z]+)(\(([^)]+)\))?!?: (.*)$/);
		if (!m || !HEADINGS[m[1]]) {
			add("other", [`* ${pr.title} (${ref})`]);
			continue;
		}
		// A bump of an extension or skin is scoped to the image it lands in and
		// ends in "(<name> <version>)"; name the repository it came from instead
		let [, , , scope, subject] = m;
		const source = pr.head.ref.startsWith("bump-")
			? pr.body?.match(
					/^\[[^\]]*\]\(https:\/\/github\.com\/[\w.-]+\/([\w.-]+)\/compare\//,
				)?.[1]
			: undefined;
		if (source) {
			scope = source;
			subject = subject.replace(/ \([^()\s]+ [^()\s]+\)$/, "");
		}
		add(m[1], [`* ${scope ? `**${scope}:** ` : ""}${subject} (${ref})`]);
	}

	let refs = "";
	for (const [type, heading] of Object.entries(HEADINGS)) {
		if (sections[type]) {
			refs += `### ${heading}\n\n${sections[type].join("\n")}\n\n`;
		}
	}
	core.setOutput("refs", refs);
	core.setOutput("prs", prs.join(" "));
};

// The femiwiki images a Dockerfile builds on, each at the tag it pins
function pins(dockerfile: string) {
	const out: Record<string, string> = {};
	for (const m of dockerfile.matchAll(
		/ghcr\.io\/femiwiki\/([\w-]+):([\w.-]+)/g,
	)) {
		out[m[1]] = m[2];
	}
	return out;
}

// Whether version a is above version b, both dotted numbers
function newer(a: string, b: string) {
	const x = a.split(".").map(Number);
	const y = b.split(".").map(Number);
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
	for (const line of body.split("\n")) {
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
