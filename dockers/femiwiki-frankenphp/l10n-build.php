<?php
// Build-time config for rebuildLocalisationCache only. Loads the real, env-driven LocalSettings
// from /a (the staged canonical file; secret/upgrade keys fall back to '' at LocalSettings.php:88,91;
// no DB/memcached is needed for an l10n rebuild), applies the baked-l10n override, then forces all
// caches to CACHE_NONE so the build never touches the network.
require '/a/LocalSettings.php';
require '/a/backend-overrides.php';

// femiwiki's LocalSettings.php registers $wgExtensionFunctions closures that run at Setup time and
// touch the DB (e.g. UploadWizard at LocalSettings.php:907-911 calls SpecialPage::getTitleFor(),
// which builds the special-page list and runs hooks that open a DB connection). There is NO DB at
// image-build time, so clear them. Extension setup functions are runtime-only and are not needed to
// build the l10n cache (messages are registered via extension.json, independent of these closures).
$wgExtensionFunctions = [];

// Hermetic/offline: no memcached, no DB cache during the build.
$wgMainCacheType    = CACHE_NONE;
$wgParserCacheType  = CACHE_NONE;
$wgMessageCacheType = CACHE_NONE;
$wgSessionCacheType = CACHE_NONE;
$wgMemCachedServers = [];

// CDBs must land in the baked directory (already set by backend-overrides; assert here).
$wgLocalisationCacheConf['storeDirectory'] = '/var/cache/mw-l10n';
// manualRecache MUST be false during the BUILD: femiwiki's LocalSettings.php registers a
// $wgExtensionFunctions closure (UploadWizard, LocalSettings.php:907-911) that calls
// SpecialPage::getTitleFor() at Setup time, which needs l10n. With manualRecache=true the lazy
// bootstrap is disabled and the cold rebuild fatals ("No localisation cache found for English").
// false lets that closure lazily bootstrap; rebuildLocalisationCache then writes the requested
// languages to storeDirectory. Runtime keeps manualRecache=true (baked cache, backend-overrides.php).
$wgLocalisationCacheConf['manualRecache'] = false;
