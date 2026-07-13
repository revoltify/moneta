<?php

function base_path(?string $path = null): string
{
    $base = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__);

    return $path ? $base.'/'.ltrim($path, '/') : $base;
}

function redirect(string $url): never
{
    header('Location: '.$url);
    exit;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_POST[$key] ?? $_SESSION['install_post'][$key] ?? $default;
}

function generateAppKey(): string
{
    return 'base64:'.base64_encode(random_bytes(32));
}

function isAlreadyInstalled(): bool
{
    $envPath = base_path('.env');
    if (! file_exists($envPath)) {
        return false;
    }

    $env = parseEnvFile($envPath);
    if (empty($env['APP_KEY'])) {
        return false;
    }

    try {
        $pdo = connectToDatabase($env);
        $stmt = $pdo->query('SELECT COUNT(*) FROM users');

        return $stmt->fetchColumn() > 0;
    } catch (Exception) {
        return false;
    }
}

function checkRequirements(): array
{
    $requirements = [];

    $requirements[] = checkRequirement(
        'PHP Version >= 8.3',
        PHP_VERSION_ID >= 80300,
        PHP_VERSION,
        'PHP 8.3 or higher is required'
    );

    $extensions = [
        'BCMath' => ['BCMath', 'Required for mathematical operations'],
        'Ctype' => ['ctype', 'Required by Laravel'],
        'Fileinfo' => ['fileinfo', 'Required by Laravel'],
        'JSON' => ['json', 'Required by Laravel'],
        'Mbstring' => ['mbstring', 'Required by Laravel'],
        'OpenSSL' => ['openssl', 'Required for encryption'],
        'PDO' => ['PDO', 'Required for database access'],
        'Tokenizer' => ['tokenizer', 'Required by Laravel'],
        'XML' => ['xml', 'Required by Laravel'],
        'DOM' => ['dom', 'Required by Laravel for migration output'],
        'sodium' => ['sodium', 'Required by Passport'],
    ];

    foreach ($extensions as $name => [$ext, $note]) {
        $requirements[] = checkRequirement(
            "PHP Extension: {$name}",
            extension_loaded($ext),
            extension_loaded($ext) ? 'Loaded' : 'Missing',
            $note
        );
    }

    $paths = [
        'storage/' => base_path('storage'),
        'storage/framework/cache/' => base_path('storage/framework/cache'),
        'storage/framework/sessions/' => base_path('storage/framework/sessions'),
        'storage/framework/views/' => base_path('storage/framework/views'),
        'storage/logs/' => base_path('storage/logs'),
        'bootstrap/cache/' => base_path('bootstrap/cache'),
    ];

    $envPath = base_path('.env');
    if (file_exists($envPath)) {
        $paths['.env'] = $envPath;
    }

    foreach ($paths as $name => $path) {
        $writable = is_writable($path);
        $requirements[] = checkRequirement(
            "Permission: {$name}",
            $writable,
            $writable ? 'Writable' : 'Not writable',
            'Directory must be writable by the web server'
        );
    }

    $vendorExists = file_exists(base_path('vendor/autoload.php'));
    $requirements[] = checkRequirement(
        'Composer Dependencies',
        $vendorExists,
        $vendorExists ? 'Installed' : 'Missing',
        'Run `composer install` to install dependencies'
    );

    $buildExists = is_dir(base_path('public/build'));
    $requirements[] = checkRequirement(
        'Frontend Assets (Build)',
        $buildExists,
        $buildExists ? 'Built' : 'Not built',
        'Run `npm install && npm run build` to build frontend assets (can be done after installation)',
        ! $buildExists
    );

    return $requirements;
}

function checkRequirement(string $label, bool $passed, string $value, string $description, bool $optional = false): array
{
    return [
        'label' => $label,
        'passed' => $passed,
        'value' => $value,
        'description' => $description,
        'optional' => $optional,
    ];
}

function connectToDatabase(array $config): PDO
{
    $connection = $config['DB_CONNECTION'] ?? 'sqlite';

    if ($connection === 'sqlite') {
        $dbPath = $config['DB_DATABASE'] ?? base_path('storage/database.sqlite');
        if (! str_contains($dbPath, '/') && ! str_contains($dbPath, '\\')) {
            $dbPath = base_path("storage/{$dbPath}");
        }
        $dbDir = dirname($dbPath);
        if (! is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }
        if (! file_exists($dbPath)) {
            touch($dbPath);
        }

        return new PDO("sqlite:{$dbPath}");
    }

    $dsnMap = [
        'mysql' => 'mysql',
        'mariadb' => 'mysql',
        'pgsql' => 'pgsql',
        'sqlsrv' => 'sqlsrv',
    ];

    $driver = $dsnMap[$connection] ?? $connection;
    $host = $config['DB_HOST'] ?? '127.0.0.1';
    $port = $config['DB_PORT'] ?? '';
    if ($port === '') {
        $port = match ($connection) {
            'pgsql' => '5432',
            'sqlsrv' => '1433',
            default => '3306',
        };
    }
    $database = $config['DB_DATABASE'] ?? 'moneta';

    $dsn = "{$driver}:host={$host};port={$port};dbname={$database}";

    if ($connection === 'sqlsrv') {
        $dsn = "sqlsrv:Server={$host},{$port};Database={$database}";
    }

    $username = $config['DB_USERNAME'] ?? 'root';
    $password = $config['DB_PASSWORD'] ?? '';

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    return new PDO($dsn, $username, $password, $options);
}

function formatBytes(int $bytes, int $precision = 2): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    return round($bytes / pow(1024, $pow), $precision).' '.$units[$pow];
}

function runComposerInstall(): array
{
    set_time_limit(300);

    $composer = findComposer();

    if ($composer === null) {
        return [
            'success' => false,
            'output' => 'Composer could not be found.'."\n"
                .'Please install dependencies manually:'."\n"
                .'1. Upload the "vendor" folder from your local installation'."\n"
                .'2. Or run "composer install" via SSH/terminal',
        ];
    }

    $output = [];
    $exitCode = -1;

    $cmd = $composer.' --working-dir='.escapeshellarg(base_path())
        .' install --no-interaction --prefer-dist --no-dev --no-ansi 2>&1';

    exec($cmd, $output, $exitCode);

    return [
        'success' => $exitCode === 0,
        'output' => implode("\n", $output),
    ];
}

function findComposer(): ?string
{
    $phars = [base_path('composer.phar')];
    if (defined('INSTALL_BASE')) {
        $phars[] = INSTALL_BASE.'/composer.phar';
    }

    foreach ($phars as $phar) {
        if (file_exists($phar)) {
            return escapeshellcmd(PHP_BINARY).' '.escapeshellarg($phar);
        }
    }

    if (isCommandAvailable('composer')) {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec('where composer 2>NUL', $output, $code);
        } else {
            exec('which composer 2>/dev/null', $output, $code);
        }

        if ($code === 0 && ! empty($output[0])) {
            $dir = dirname($output[0]);
            $phar = $dir.'/composer.phar';
            if (file_exists($phar)) {
                return escapeshellcmd(PHP_BINARY).' '.escapeshellarg($phar);
            }
            if (is_file($output[0])) {
                return escapeshellcmd(PHP_BINARY).' '.escapeshellarg($output[0]);
            }
        }

        return 'composer';
    }

    return null;
}

function runFrontendBuild(): array
{
    set_time_limit(300);

    if (! isCommandAvailable('npm')) {
        return [
            'success' => false,
            'output' => 'npm could not be found.'."\n"
                .'Please build frontend assets manually:'."\n"
                .'1. Run "npm install && npm run build" via SSH/terminal'."\n"
                .'2. Or upload the "public/build" folder from your local installation',
        ];
    }

    $projectPath = base_path();
    chdir($projectPath);

    $installOutput = [];
    $exitCode = -1;

    exec('npm install --no-audit --no-fund 2>&1', $installOutput, $exitCode);
    $installText = implode("\n", $installOutput);

    if ($exitCode !== 0) {
        return [
            'success' => false,
            'output' => "--- npm install ---\n".$installText,
        ];
    }

    $buildOutput = [];
    exec('npm run build 2>&1', $buildOutput, $exitCode);
    $buildText = implode("\n", $buildOutput);

    return [
        'success' => $exitCode === 0,
        'output' => "--- npm install ---\n".$installText."\n\n--- npm run build ---\n".$buildText,
    ];
}

function isCommandAvailable(string $command): bool
{
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        exec('where '.$command.' 2>NUL', $output, $code);
    } else {
        exec('which '.$command.' 2>/dev/null', $output, $code);
    }

    return ($code ?? 1) === 0;
}
