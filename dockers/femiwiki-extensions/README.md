# Femiwiki extensions

This docker image contains MediaWiki extensions Femiwiki uses.

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
