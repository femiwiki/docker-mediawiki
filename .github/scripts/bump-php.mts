// Moves php-fpm to the latest patch of the PHP minor we run, or to the minor
// MediaWiki requires, which follows WMF production rather than the newest PHP
import { readFileSync, writeFileSync } from "node:fs";
import type * as Core from "@actions/core";
import type { context as Context } from "@actions/github";

const compare = (a: string, b: string) => {
	const [x, y] = [a, b].map((v) => v.split(".").map(Number));
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

const need = (env: NodeJS.ProcessEnv, name: string) => {
	const value = env[name];
	if (!value) throw new Error(`${name} is not set`);
	return value;
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
	const mediawiki = readFileSync("dockers/mediawiki/Dockerfile", "utf8").match(
		/^ARG MEDIAWIKI_VERSION=(\S+)$/m,
	)?.[1];
	if (!mediawiki)
		throw new Error("No MEDIAWIKI_VERSION in dockers/mediawiki/Dockerfile");
	const dockerfile = "dockers/php-fpm/Dockerfile";
	const text = readFileSync(dockerfile, "utf8");
	const from = text.match(/^FROM .*php:(\d+\.\d+\.\d+)-(\S+)$/m);
	if (!from) throw new Error(`No php FROM line in ${dockerfile}`);
	const [, cur, variant] = from;

	const composer = `https://github.com/wikimedia/mediawiki/blob/${mediawiki}/composer.json`;
	const manifest = await (
		await get(
			`https://raw.githubusercontent.com/wikimedia/mediawiki/${mediawiki}/composer.json`,
		)
	).json();
	const php: string = manifest.require.php;
	const floor = php.match(/^>=\s*(\d+\.\d+)/)?.[1];
	if (!floor) throw new Error(`Unexpected require.php in ${composer}: ${php}`);

	// A newer minor waits for MediaWiki to require it; patches carry security fixes
	const minor = cur.replace(/\.\d+$/, "");
	const target = compare(floor, minor) > 0 ? floor : minor;

	const tags = await (
		await get(
			`https://hub.docker.com/v2/repositories/library/php/tags?name=${target}.&page_size=100`,
		)
	).json();
	const pattern = new RegExp(
		`^(${target.replace(".", "\\.")}\\.\\d+)-${variant}$`,
	);
	const versions = (tags.results as { name: string }[])
		.map((t) => t.name.match(pattern)?.[1])
		.filter((v): v is string => v !== undefined);
	if (versions.length === 0) {
		core.warning(`No php:${target}.x-${variant} image yet`);
		return;
	}
	const latest = versions.sort(compare)[versions.length - 1];
	if (compare(latest, cur) <= 0) {
		core.info(
			`MediaWiki ${mediawiki} requires PHP ${floor}; we run ${cur}, the latest ${target}`,
		);
		return;
	}

	writeFileSync(
		dockerfile,
		text.replace(`php:${cur}-${variant}`, `php:${latest}-${variant}`),
	);
	const run = `${context.serverUrl}/${context.repo.owner}/${context.repo.repo}/actions/runs/${context.runId}`;
	const patch = target === minor;
	const body = [
		patch
			? `This bumps PHP ${cur} → ${latest}, the latest ${minor} release.`
			: `[MediaWiki ${mediawiki} requires PHP ${floor}](${composer}), so this bumps PHP ${cur} → ${latest}.`,
		"",
		`Opened by [run ${context.runId}](${run}).`,
		"",
		"```wikitext",
		patch ? "===보안 패치===" : "===내부 변화===",
		"",
		`*PHP ${latest} 업데이트`,
		"```",
	].join("\n");
	writeFileSync(need(env, "BODY"), `${body}\n`);
	core.info(body);
	core.setOutput("ver", latest);
	core.setOutput("patch", patch ? "true" : "");
};
