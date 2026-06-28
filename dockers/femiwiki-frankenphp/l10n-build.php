<?php
// Build-time config for rebuildLocalisationCache only. Loads the real, env-driven LocalSettings
// from /a (the staged canonical file; secret/upgrade keys fall back to '' at LocalSettings.php:88,91;
// no DB/memcached is needed for an l10n rebuild), applies the baked-l10n override, then forces all
// caches to CACHE_NONE so the build never touches the network.
require '/a/LocalSettings.php';
require '/a/backend-overrides.php';

// Hermetic/offline: no memcached, no DB cache during the build.
$wgMainCacheType    = CACHE_NONE;
$wgParserCacheType  = CACHE_NONE;
$wgMessageCacheType = CACHE_NONE;
$wgSessionCacheType = CACHE_NONE;
$wgMemCachedServers = [];

// CDBs must land in the baked directory (already set by backend-overrides; assert here).
$wgLocalisationCacheConf['storeDirectory'] = '/var/cache/mw-l10n';
$wgLocalisationCacheConf['manualRecache']  = true;
