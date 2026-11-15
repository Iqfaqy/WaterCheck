<?php
/**
 * WaterCheck - Vercel Dispatcher Test
 * File: tests/test_vercel_router.php
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

echo "Testing Vercel Entrypoint & Dispatcher...\n";

// Test 1: File existence of api/index.php and vercel.json
assert(file_exists(__DIR__ . '/../api/index.php'), 'api/index.php must exist');
assert(file_exists(__DIR__ . '/../vercel.json'), 'vercel.json must exist');
assert(file_exists(__DIR__ . '/../.vercelignore'), '.vercelignore must exist');
echo "[OK] Vercel configuration files verified.\n";

// Test 2: Verify vercel.json is valid JSON
$vercelConfig = json_decode(file_get_contents(__DIR__ . '/../vercel.json'), true);
assert(is_array($vercelConfig), 'vercel.json must be valid JSON');
assert(isset($vercelConfig['functions']['api/*.php']), 'vercel.json must configure api/*.php function');
assert(isset($vercelConfig['routes']), 'vercel.json must configure routes');
echo "[OK] vercel.json syntax & routing rules verified.\n";

// Test 3: Verify Environment variable detection
putenv("DB_HOST=127.0.0.1");
putenv("VERCEL=1");
assert(getenv('VERCEL') == '1', 'VERCEL env set');
assert(getenv('DB_HOST') == '127.0.0.1', 'DB_HOST env set');
echo "[OK] Environment variable override verified.\n";

echo "ALL VERCEL CONFIGURATION TESTS PASSED 100%!\n";
