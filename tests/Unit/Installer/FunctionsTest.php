<?php

/**
 * These tests run functions.php in isolated PHP subprocesses because the file
 * defines base_path(), redirect(), and old() which conflict with Laravel's
 * global helpers. Each test spawns a fresh PHP process that loads only
 * functions.php without Laravel's bootstrap, avoiding redefinition errors.
 */

/**
 * Run a PHP snippet with functions.php pre-loaded, returning captured output.
 */
function runFunctionsTest(string $phpCode): string
{
    $root = realpath(__DIR__.'/../../../');
    $functionsPath = var_export($root.'/public/install/inc/functions.php', true);

    $script = "<?php\nrequire {$functionsPath};\n{$phpCode}";
    $tmpFile = tempnam(sys_get_temp_dir(), 'moneta_fntest_');
    file_put_contents($tmpFile, $script);

    $output = [];
    $exitCode = -1;
    exec(PHP_BINARY.' '.escapeshellarg($tmpFile).' 2>&1', $output, $exitCode);
    unlink($tmpFile);

    if ($exitCode !== 0) {
        throw new RuntimeException('Subprocess failed: '.implode("\n", $output));
    }

    return implode("\n", $output);
}

// ─── generateAppKey ───────────────────────────────────────────────

test('generateAppKey returns a string starting with base64:', function () {
    $output = runFunctionsTest('echo generateAppKey();');

    expect($output)->toStartWith('base64:');
});

test('generateAppKey returns a key of expected length', function () {
    $output = runFunctionsTest('echo generateAppKey();');

    $prefixRemoved = substr($output, 7);
    $decoded = base64_decode($prefixRemoved, true);

    expect($decoded)->not->toBeFalse()
        ->and(strlen($decoded))->toBe(32);
});

test('generateAppKey produces different keys on each call', function () {
    $output = runFunctionsTest('echo generateAppKey() . "|" . generateAppKey();');

    $keys = explode('|', $output);
    expect($keys[0])->not->toBe($keys[1]);
});

// ─── formatBytes ──────────────────────────────────────────────────

test('formatBytes returns B for small values', function () {
    $output = runFunctionsTest('echo json_encode(formatBytes(0));');

    expect(json_decode($output))->toBe('0 B');
});

test('formatBytes returns 1 B for 1 byte', function () {
    $output = runFunctionsTest('echo json_encode(formatBytes(1));');

    expect(json_decode($output))->toBe('1 B');
});

test('formatBytes converts KB correctly', function () {
    $output = runFunctionsTest('echo json_encode(formatBytes(1024, 0));');

    expect(json_decode($output))->toBe('1 KB');
});

test('formatBytes converts MB correctly', function () {
    $output = runFunctionsTest('echo json_encode(formatBytes(1048576, 1));');

    // round(1.0, 1) returns float 1.0; PHP trims .0 on string conversion
    expect(json_decode($output))->toBe('1 MB');
});

test('formatBytes converts GB correctly', function () {
    $output = runFunctionsTest('echo json_encode(formatBytes(1073741824, 1));');

    // round(1.0, 1) returns float 1.0; PHP trims .0 on string conversion
    expect(json_decode($output))->toBe('1 GB');
});

test('formatBytes respects precision parameter', function () {
    // 1536 bytes = 1.5 KB
    $output = runFunctionsTest('echo json_encode(formatBytes(1536, 1));');

    expect(json_decode($output))->toBe('1.5 KB');
});

test('formatBytes handles zero precision', function () {
    // 1536 bytes = 1.5 KB → rounds to 2 KB at precision 0
    $output = runFunctionsTest('echo json_encode(formatBytes(1536, 0));');

    expect(json_decode($output))->toBe('2 KB');
});

test('formatBytes handles negative input as zero', function () {
    $output = runFunctionsTest('echo json_encode(formatBytes(-100));');

    expect(json_decode($output))->toBe('0 B');
});

// ─── checkRequirement ─────────────────────────────────────────────

test('checkRequirement returns passed requirement', function () {
    $output = runFunctionsTest('echo json_encode(checkRequirement("Test", true, "OK", "Description"));');

    $result = json_decode($output, true);

    expect($result)->toBe([
        'label' => 'Test',
        'passed' => true,
        'value' => 'OK',
        'description' => 'Description',
        'optional' => false,
    ]);
});

test('checkRequirement returns failed requirement', function () {
    $output = runFunctionsTest('echo json_encode(checkRequirement("PHP Version", false, "8.1.0", "PHP 8.3 required"));');

    $result = json_decode($output, true);

    expect($result)->toBe([
        'label' => 'PHP Version',
        'passed' => false,
        'value' => '8.1.0',
        'description' => 'PHP 8.3 required',
        'optional' => false,
    ]);
});

test('checkRequirement marks optional requirements', function () {
    $output = runFunctionsTest('echo json_encode(checkRequirement("Frontend", false, "Not built", "Build assets", true));');

    $result = json_decode($output, true);

    expect($result['passed'])->toBeFalse()
        ->and($result['optional'])->toBeTrue();
});

// ─── base_path ─────────────────────────────────────────────────────

test('base_path returns fallback when BASE_PATH is undefined', function () {
    // When BASE_PATH is not defined, base_path() uses dirname(__DIR__).
    // Since functions.php lives in install/inc/, __DIR__ is install/inc/
    // so dirname(__DIR__) is install/
    $output = runFunctionsTest('echo base_path();');

    // The subprocess runs a script in a temp dir, so dirname(__DIR__)
    // resolves relative to that script's location.
    $tmpDir = dirname($output = str_replace('\\', '/', $output));
    $script = str_replace('\\', '/', __FILE__);

    // The path should contain "install" since functions.php is in install/inc/
    expect($output)->toContain('install');
});

test('base_path appends path when argument given', function () {
    $output = runFunctionsTest('echo base_path(".env");');

    expect($output)->toEndWith('/.env');
});

test('base_path uses BASE_PATH constant when defined', function () {
    $output = runFunctionsTest(
        'define("BASE_PATH", "/custom/project/path");'
        .'echo base_path("storage/logs");'
    );

    expect($output)->toBe('/custom/project/path/storage/logs');
});

test('base_path strips leading slashes from child path', function () {
    $output = runFunctionsTest(
        'define("BASE_PATH", "/base");'
        .'echo base_path("/vendor/autoload.php");'
    );

    expect($output)->toBe('/base/vendor/autoload.php');
});

test('base_path returns base only when null given', function () {
    $output = runFunctionsTest(
        'define("BASE_PATH", "/project");'
        .'echo base_path(null);'
    );

    expect($output)->toBe('/project');
});

// ─── old ───────────────────────────────────────────────────────────

test('old returns value from POST data', function () {
    $output = runFunctionsTest(
        '$_POST["email"] = "user@example.com";'
        .'echo old("email");'
    );

    expect($output)->toBe('user@example.com');
});

test('old returns default when key missing from POST', function () {
    $output = runFunctionsTest('echo old("missing_key", "fallback");');

    expect($output)->toBe('fallback');
});

test('old returns empty string as default when not specified', function () {
    $output = runFunctionsTest('echo old("nonexistent");');

    expect($output)->toBe('');
});

test('old falls back to SESSION data when POST key is missing', function () {
    $output = runFunctionsTest(
        'session_start();'
        .'$_SESSION["install_post"] = ["name" => "John"];'
        .'echo old("name");'
    );

    expect($output)->toBe('John');
});

test('old prefers POST over SESSION when both exist', function () {
    $output = runFunctionsTest(
        'session_start();'
        .'$_POST["email"] = "post@example.com";'
        .'$_SESSION["install_post"] = ["email" => "session@example.com"];'
        .'echo old("email");'
    );

    expect($output)->toBe('post@example.com');
});

// ─── isCommandAvailable ─────────────────────────────────────────────

test('isCommandAvailable returns true for php', function () {
    // isCommandAvailable uses `where`/`which` which checks PATH, not full paths
    $output = runFunctionsTest('echo isCommandAvailable("php") ? "yes" : "no";');

    expect($output)->toBe('yes');
});

test('isCommandAvailable returns false for nonexistent command', function () {
    $output = runFunctionsTest('echo isCommandAvailable("q1w2e3r4t5z6nonexistent") ? "yes" : "no";');

    expect($output)->toBe('no');
});
