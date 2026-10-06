# Femiwiki extensions

This docker image contains MediaWiki extensions Femiwiki uses.

## Off-branch extensions

`WMF-branches` in `extensions.json` names the branch of a WMF extension that is not on `WMF-branch`. The installer and the monthly and security bumps follow that branch for it.

- UnlinkedWikibase follows `master` for "Keep expired data beyond the TTL if necessary" (Gerrit change 1343968), which REL1_46 lacks; without it, infobox labels from Wikidata go blank whenever their cache entry expires. Drop it from `WMF-branches` when `WMF-branch` moves to a branch that has the change, REL1_47 or later, or when the change is backported to REL1_46.
- PageViewInfoGA follows `master`, as femiwiki's maintainers own it and no longer backport its changes to release branches.

## Patches

`patches/` mirrors `/mediawiki`: every `patches/extensions/<name>/*.patch` and `patches/skins/<name>/*.patch` is applied to that extension or skin after the installer runs. A patch that no longer applies fails the build; refresh it against the new commit or drop it. Name each patch after the branch it was written against.

- `extensions/GrowthExperiments/REL1_46-topics.patch` lets GrowthExperiments show its topic filter without WikimediaMessages, which femiwiki doesn't load; UnifiedExtensionForFemiwiki supplies the topics. `$wgGEHomepageSuggestedEditsEnableTopics` still turns the filter on and off.

## v3.6.1

- Bump Femiwiki to [d5858f3](https://github.com/femiwiki/FemiwikiSkin/commit/d5858f34384a55be1924c6ea3d7c96f7152edb9f)

## v3.6.0

- Bump UnifiedExtensionForFemiwiki to [0517aa8](https://github.com/femiwiki/UnifiedExtensionForFemiwiki/commit/0517aa80142ae5e7bd99f63b678148666e23847f)

## v3.5.5

- Bump UnifiedExtensionForFemiwiki to [2358a2c](https://github.com/femiwiki/UnifiedExtensionForFemiwiki/commit/2358a2cbb9fbfac8294f5fe87d6f28875434e251)

## v3.5.4

- Bump Femiwiki to [00857ec](https://github.com/femiwiki/FemiwikiSkin/commit/00857ec4daf317928f1c9a6ac44a0a174e8743ed)

## v3.5.3

- Bump Femiwiki to [f6519a5](https://github.com/femiwiki/FemiwikiSkin/commit/f6519a5b5425e705871d00c0d7268b74c777bb53)

## v3.5.2

- Bump UnifiedExtensionForFemiwiki to [b569e3a](https://github.com/femiwiki/UnifiedExtensionForFemiwiki/commit/b569e3a6eab1d0ff10c148441ca531a7ff8d95ea)

## v3.5.1

- Bump Femiwiki to [cf322d7](https://github.com/femiwiki/FemiwikiSkin/commit/cf322d7cbf661c43a7f721fc08aacde8377b2879)

## v3.5.0

- Bump Femiwiki to [7af371b](https://github.com/femiwiki/FemiwikiSkin/commit/7af371b00bf755c830ab243dbe27786a099c6031)

## v3.4.0

- Bump Femiwiki to [1f56536](https://github.com/femiwiki/FemiwikiSkin/commit/1f565366139bdfc6529fb20af35964339fc1f71b)

## v3.3.0

- let GrowthExperiments show topics without WikimediaMessages

## v3.2.13

- Move PageViewInfoGA to [c65d851](https://gerrit.wikimedia.org/g/mediawiki/extensions/PageViewInfoGA/+/c65d851ae8e8f4b93a08e4478888a2de94ec339e) of `master`, which asks Google STS for the cloud-platform scope when impersonating a service account. Without it, keyless authentication gets 403 from the IAM Credentials API and page view counts fail; see femiwiki/femiwiki#670.

## v3.2.12

- Move 62 WMF extensions and skins to the heads of `REL1_46`

## v3.2.11

- Bump Femiwiki to [cda3f17](https://github.com/femiwiki/FemiwikiSkin/commit/cda3f171e839b1eacbed66fcf756bf82e23ae50c)

## v3.2.10

- Bump PageViewInfoGA to [e085572](https://gerrit.wikimedia.org/g/mediawiki/extensions/PageViewInfoGA/+/e0855721f41ee2a0ccfa66488028273292d9be29) of `REL1_46` (1.1.0), which gets its tokens from google/auth and accepts Workload Identity Federation configurations as well as service account keys. google/auth has to come from MediaWiki's own vendor, through `composer.local.json`; see femiwiki/femiwiki#670.

## v3.2.9

- Bump PageViewInfoGA to [fecb328](https://gerrit.wikimedia.org/g/mediawiki/extensions/PageViewInfoGA/+/fecb328f91f019350b923fb8b57694646d72f1e8) of `REL1_46` (1.0.0), which reads Google Analytics 4 instead of Universal Analytics and has no Composer dependencies. LocalSettings.php does not load it yet.

## v3.2.8

- Move UnlinkedWikibase to [c08a318](https://github.com/wikimedia/mediawiki-extensions-UnlinkedWikibase/commit/c08a31862037ee03e84d2ea34ee553e84b12b205) of `master` (4.1.1), which keeps an entity's data past its TTL until a fetch job replaces it, so Wikidata labels in infoboxes stop going blank when the cache expires. Cached data now lives 86400 s instead of 3600 s before a refresh.

## v3.2.7

- Bump Femiwiki to [08bdad9](https://github.com/femiwiki/FemiwikiSkin/commit/08bdad96f9a11a1f237c1d287d0251e8e4fb229f)

## v3.2.6

- Bump UnifiedExtensionForFemiwiki to [77d589a](https://github.com/femiwiki/UnifiedExtensionForFemiwiki/commit/77d589ae97f80179eca04a7882e9932d1cf15e7f)

## v3.2.5

- Resolve the extensions' composer dependencies for the PHP that `dockers/php-fpm` runs, 8.3.35 now, instead of skipping the PHP check. OATHAuth's Symfony packages move from 8.1, which needs PHP 8.4 and broke adding a security key or passkey, to 7.4.

## v3.2.4

- Bump UnifiedExtensionForFemiwiki to [5e4039c](https://github.com/femiwiki/UnifiedExtensionForFemiwiki/commit/5e4039cfa1981e36eafb5fb748eb043c249fef29)

## v3.2.3

- Bump Femiwiki to [af72c6e](https://github.com/femiwiki/FemiwikiSkin/commit/af72c6e637eb3f0236347c5a1abfdc3c46d37c4b)

## v3.2.2

- Bump Femiwiki to [53af529](https://github.com/femiwiki/FemiwikiSkin/commit/53af529e7696192515b503767f5e60c1af61998f)

## v3.2.1

- Move FacetedCategory to dd59145 of `REL1_46`, which reads categorylinks through linktarget now that MediaWiki 1.45 dropped `cl_to`. This fixes Special:CategoryIntersectionSearch and the links update after a category page is added to a category.

## v3.2.0

- remove the HTMLTags extension

## v3.1.2

- Turn off git's automatic maintenance while installing, which raced the removal of .git and failed the v3.1.1 build twice on DiscordRCFeed

## v3.1.1

- Bump Femiwiki to [a468700](https://github.com/femiwiki/FemiwikiSkin/commit/a4687007436cdf05c709ceaa61b2d5bf9c03abfd), whose two skinStyles compile on MediaWiki 1.46 again (FemiwikiSkin#1009)

## v3.1.0

- Add CommunityConfiguration, which GrowthExperiments requires on MediaWiki 1.46

## v3.0.0

- Move WMF extensions and skins to the heads of `REL1_46`
- Drop Interwiki, which MediaWiki 1.46 ships in core
- Fetch UnlinkedWikibase from its `REL1_46` branch instead of a release tarball
- Bump EmbedVideo to v4.2.0

## v2.7.5

- Bump Femiwiki to [a468700](https://github.com/femiwiki/FemiwikiSkin/commit/a4687007436cdf05c709ceaa61b2d5bf9c03abfd)

## v2.7.4

- Move 3 WMF extensions and skins with security fixes to the heads of `REL1_43`

## v2.7.3

- shorten every comment block of five or more lines

## v2.7.2

- Bump Femiwiki to [c880abf](https://github.com/femiwiki/FemiwikiSkin/commit/c880abf9879b1f7d7c44c674907bba57d5700221)

## v2.7.1

- Install UnifiedExtensionForFemiwiki from a commit of its `main`, listed under
  `in-house` in `extensions.json`, instead of the v5.0.0 source archive. It
  gains translations for Magahi and Slovak.

## v2.7.0

- Install the Femiwiki skin from a commit of FemiwikiSkin `main`, listed under
  `in-house` in `extensions.json`, instead of the v5.1.2 release asset. npm
  installs the two packages it loads at runtime, the OOUI theme and XEIcon,
  from its lockfile. The asset also carried the skin's development packages,
  so the skin's `node_modules` shrinks from 159 MB to 15 MB.
- The skin gains what `main` has over v5.1.2: the notification flyout fix for
  MediaWiki 1.45 and later (FemiwikiSkin#936), the namespaced GlobalVarConfig
  (FemiwikiSkin#910) and translatewiki.net updates.

## v2.6.1

- Install with a PHP script using symfony/process on `composer:2.10.3`, instead
  of a Ruby one, so the image no longer needs Ruby, Bundler or aria2. The
  extensions are the same; only composer's generated files differ, as composer
  moves from 2.7.7 to 2.10.3.
- Fetch as many extensions at once as twice the building machine's CPU count,
  read with nproc on every build, rather than always 4. The `INSTALL_JOBS`
  build argument overrides it.

## v2.6.0

- Pin every WMF extension and skin to a commit of `WMF-branch` in
  `extensions.json`, instead of taking whatever the branch head is when the
  image builds. Each is fetched at that commit from its GitHub mirror, or from
  Gerrit when the mirror lacks it, with its submodules; Phabricator submodules
  come from the GitHub repositories listed in `submodule-mirrors`. Composer
  runs where extdist would have run it, and `gitinfo.json` and `version` are
  written as extdist writes them.
- Stop installing Graph. extdist no longer serves it, so the image already held
  an empty directory, and LocalSettings.php does not load it.

## v2.5.0

- drop Extension:Lockdown

## v2.4.3

- Remove EventLogging. It was installed only as a dependency of DiscussionTools,
  which no longer requires it.

## v2.4.2

- Bump AWS to v0.14.0
- Bump UnlinkedWikibase to v4.0.0
- Remove FemiwikiCrawlingBlocker

## v2.4.1

- Bump UnifiedExtensionForFemiwiki to v5.0.0

## v2.4.0

- Install Lockdown

## v2.3.1

- Bump FemiwikiCrawlingBlocker to v2.0.1

## v2.3.0

- Add to mw-extenstion FemiwikiCrawlingBlocker

## v2.2.7

- Bump UnlinkedWikibase to v3.3.1

## v2.2.6

- Fix UnlinkedWikibase to v3.0.1

## v2.2.5

- Bump DiscordRCFeed

## v2.2.4

- Bump Femiwiki Skin to v5.1.2

## v2.2.3

- Bump Femiwiki Skin to v5.0.10

## v2.2.2

- Bump Femiwiki Skin to v5.0.9

## v2.2.1

- Bump Femiwiki Skin to v5.0.8

## v2.2.0

- Bump MediaWiki to v1.43

## v2.1.0

- Install SecurePoll.

## v2.0.0

- Do not build arm64 image.

## v1.5.8

- Bump Femiwiki Skin to v5.0.2

## v1.5.7

- Rebuild for https://gerrit.wikimedia.org/r/c/mediawiki/extensions/PageViewInfoGA/+/1074723?usp=dashboard

## v1.5.6

- Bump UnifiedExtensionForFemiwiki

## v1.5.5

- Bump UnifiedExtensionForFemiwiki

## v1.5.4

- Bump UnifiedExtensionForFemiwiki

## v1.5.3

- Bump UnifiedExtensionForFemiwiki

## v1.5.2

- Rebuild for FacetedCategory

## v1.5.1

- Bump AWS to a commit which has added 1.42 support

## v1.5.0

- Bump MediaWiki to REL1_42

## v1.4.0

- Bump UnifiedExtensionForFemiwiki

## v1.3.2

- Bump UnifiedExtensionForFemiwiki

## v1.3.1

- Bump UnifiedExtensionForFemiwiki

## v1.3.0

- Bump UnifiedExtensionForFemiwiki

## v1.2.0

- Install RealMe
- Install GoogleNewsSitemap

## v1.1.1

- Download FlaggedRevs from GitHub

## v1.1.0

- Install TorBlock

## v1.0.1

- Use tag for extension:AWS
- Bump FacetedCategory
