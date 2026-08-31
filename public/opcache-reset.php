<?php

// Emergency OPcache reset. Requires ?key=... to match. Delete this file once
// the emergency is resolved - it should not stay on a production server.
$expectedKey = 'sb-emergency-2026-08-31';

if (($_GET['key'] ?? '') !== $expectedKey) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

if (function_exists('opcache_reset')) {
    $result = opcache_reset();
    echo $result ? 'OPcache reset: OK' : 'OPcache reset: FAILED (opcache_reset() returned false)';
} else {
    echo 'opcache_reset() function does not exist - OPcache extension may not be loaded.';
}
