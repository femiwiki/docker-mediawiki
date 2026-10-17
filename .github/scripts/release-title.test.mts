import assert from "node:assert/strict";
import { test } from "node:test";
import { MAX, releaseTitle } from "./release-title.mts";

const bump = {
	type: "feat",
	scope: "femiwiki-extensions",
	name: "FemiwikiSkin",
	ver: "v6.0.0",
};

test("a short release lists every change", () => {
	assert.equal(
		releaseTitle({ ...bump, changes: ["Add a menu", "Drop the old footer"] }),
		"feat(femiwiki-extensions): Add a menu; Drop the old footer (FemiwikiSkin v6.0.0)",
	);
});

test("a release with no changes is a plain bump", () => {
	assert.equal(
		releaseTitle({ ...bump, type: "build", changes: [] }),
		"build(femiwiki-extensions): bump FemiwikiSkin to v6.0.0",
	);
});

test("a long release keeps the leading changes and counts the rest", () => {
	const changes = Array.from(
		{ length: 50 },
		(_, i) => `Restyle the part number ${i + 1} of the skin`,
	);
	const title = releaseTitle({ ...bump, changes });
	assert.ok([...title].length <= MAX, `${[...title].length} characters`);
	const kept = title.match(
		/^feat\(femiwiki-extensions\): (.*); and (\d+) more \(FemiwikiSkin v6\.0\.0\)$/,
	);
	assert.ok(kept, title);
	const listed = kept[1].split("; ");
	assert.deepEqual(listed, changes.slice(0, listed.length));
	assert.equal(listed.length + Number(kept[2]), changes.length);
	// One more change would not have fit
	const longer = `feat(femiwiki-extensions): ${changes.slice(0, listed.length + 1).join("; ")}; and ${changes.length - listed.length - 1} more (FemiwikiSkin v6.0.0)`;
	assert.ok([...longer].length > MAX);
});

test("a first change too long to fit is cut", () => {
	const title = releaseTitle({
		...bump,
		changes: ["가".repeat(300), "Add a menu"],
	});
	assert.equal([...title].length, MAX);
	assert.match(
		title,
		/^feat\(femiwiki-extensions\): 가+…; and 1 more \(FemiwikiSkin v6\.0\.0\)$/,
	);
});
