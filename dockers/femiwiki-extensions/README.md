# Femiwiki extensions

This docker image contains MediaWiki extensions Femiwiki uses.

## Off-branch extensions

`WMF-branches` in `extensions.json` names the branch of a WMF extension that is
not on `WMF-branch`. The installer and the monthly and security bumps follow
that branch for it.

- UnlinkedWikibase follows `master` for "Keep expired data beyond the TTL if
  necessary" (Gerrit change 1343968), which REL1_46 lacks; without it, infobox
  labels from Wikidata go blank whenever their cache entry expires. Drop it from
  `WMF-branches` when `WMF-branch` moves to a branch that has the change,
  REL1_47 or later, or when the change is backported to REL1_46.
- PageViewInfoGA follows `master`, as femiwiki's maintainers own it and no
  longer backport its changes to release branches.
- Sanctions follows `master`, as femiwiki's maintainers own it and no longer
  backport its changes to release branches. master needs MediaWiki 1.44 or
  later.

## Patches

`patches/` mirrors `/mediawiki`: every `patches/extensions/<name>/*.patch` and
`patches/skins/<name>/*.patch` is applied to that extension or skin after the
installer runs. A patch that no longer applies fails the build; refresh it
against the new commit or drop it. Name each patch after the branch it was
written against.

- `extensions/GrowthExperiments/REL1_46-topics.patch` lets GrowthExperiments
  show its topic filter without WikimediaMessages, which femiwiki doesn't load;
  UnifiedExtensionForFemiwiki supplies the topics.
  `$wgGEHomepageSuggestedEditsEnableTopics` still turns the filter on and off.
