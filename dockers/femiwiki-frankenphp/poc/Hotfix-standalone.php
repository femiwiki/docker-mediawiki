<?php
require_once __DIR__ . '/backend-overrides.php';
// Break-glass engine: forks a lua5.1 process per request via proc_open (no in-process ZTS Lua).
// Functionally first-class but materially slower (per-request IPC, higher DB-thread occupancy);
// acceptable as a fallback, NOT a steady state. Valid keys: 'luasandbox' | 'luastandalone'.
$wgScribuntoDefaultEngine = 'luastandalone';
// cpuLimit/memoryLimit match LocalSettings.php:805,807
$wgScribuntoEngineConf['luastandalone']['cpuLimit'] = 3;
$wgScribuntoEngineConf['luastandalone']['memoryLimit'] = 52428800;
