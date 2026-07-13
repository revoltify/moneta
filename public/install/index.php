<?php

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

define('INSTALL_BASE', __DIR__);
define('BASE_PATH', dirname(dirname(INSTALL_BASE)));

require INSTALL_BASE.'/inc/functions.php';
require INSTALL_BASE.'/inc/env.php';
require INSTALL_BASE.'/inc/installer.php';

$is_installed = isAlreadyInstalled();

$step = isset($_GET['step']) ? max(1, min(4, (int) $_GET['step'])) : 1;

if (empty($_SESSION['_token'])) {
    $_SESSION['_token'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['_token'];

if (! isset($_SESSION['install_max_step'])) {
    $_SESSION['install_max_step'] = 1;
}

if ($step > $_SESSION['install_max_step'] + 1) {
    $step = min($_SESSION['install_max_step'] + 1, 4);
}

if ($_SESSION['install_max_step'] >= 4 && $step < 4) {
    redirect('?step=4');
}

if (isset($_GET['ajax']) && $_GET['ajax'] === '1' && $_SERVER['REQUEST_METHOD'] === 'POST' && ! $is_installed) {
    handleAjaxRequest();
    exit;
}

$errors = [];
$success = '';
$installOutput = null;

$detectedUrl = 'http://localhost';
if (! empty($_SERVER['HTTP_HOST'])) {
    $scheme = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $detectedUrl = $scheme.'://'.$_SERVER['HTTP_HOST'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ! $is_installed) {
    $_SESSION['install_post'] = array_merge($_SESSION['install_post'] ?? [], $_POST);
    $submittedToken = $_POST['_token'] ?? '';

    if (! hash_equals($token, $submittedToken)) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        switch ($step) {
            case 1:
                $reqs = checkRequirements();
                $failures = array_filter($reqs, fn ($r) => ! $r['passed'] && ! ($r['optional'] ?? false));
                if (empty($failures)) {
                    $_SESSION['install_max_step'] = max($_SESSION['install_max_step'], 1);
                    redirect('?step=2');
                }
                $errors[] = 'Please fix all required checks before proceeding.';
                break;

            case 2:
                $dbConfig = getDatabaseEnvConfig($_POST);
                $errors = validateDatabaseConfig($dbConfig);

                if (empty($errors)) {
                    try {
                        $pdo = connectToDatabase($dbConfig);
                        $pdo = null;

                        $envPath = base_path('.env');
                        if (! file_exists($envPath)) {
                            copy(base_path('.env.example'), $envPath);
                        }

                        writeEnvFile($envPath, $dbConfig);
                        writeEnvFile($envPath, [
                            'APP_KEY' => generateAppKey(),
                            'APP_ENV' => 'production',
                            'APP_DEBUG' => 'false',
                        ]);

                        if ($dbConfig['DB_CONNECTION'] === 'sqlite') {
                            $dbFile = $dbConfig['DB_DATABASE'] ?? 'database.sqlite';
                            if (! str_contains($dbFile, '/') && ! str_contains($dbFile, '\\')) {
                                $dbPath = base_path('storage/'.$dbFile);
                            } else {
                                $dbPath = $dbFile;
                            }
                            if (! file_exists($dbPath)) {
                                $dbDir = dirname($dbPath);
                                if (! is_dir($dbDir)) {
                                    mkdir($dbDir, 0755, true);
                                }
                                touch($dbPath);
                            }
                        }

                        $_SESSION['install_max_step'] = max($_SESSION['install_max_step'], 2);
                        redirect('?step=3');
                    } catch (Exception $e) {
                        $errors[] = 'Database connection failed: '.$e->getMessage();
                    }
                }
                break;

            case 3:
                if (($_SESSION['install_max_step'] ?? 0) >= 3) {
                    redirect('?step=4');
                }

                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $passwordConfirmation = $_POST['password_confirmation'] ?? '';
                $company = trim($_POST['company'] ?? '');

                $appUrl = rtrim(trim($_POST['app_url'] ?? $detectedUrl), '/');

                if (strlen($name) < 1 || strlen($name) > 255) {
                    $errors[] = 'Name is required and must be under 255 characters.';
                }
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Please enter a valid email address.';
                }
                if (strlen($password) < 8) {
                    $errors[] = 'Password must be at least 8 characters.';
                }
                if ($password !== $passwordConfirmation) {
                    $errors[] = 'Passwords do not match.';
                }
                if (strlen($company) < 1 || strlen($company) > 255) {
                    $errors[] = 'Company name is required and must be under 255 characters.';
                }
                if (! filter_var($appUrl, FILTER_VALIDATE_URL)) {
                    $errors[] = 'Please enter a valid application URL.';
                }

                if (empty($errors)) {
                    writeEnvFile(base_path('.env'), ['APP_URL' => $appUrl]);

                    $result = runInstaller([
                        'name' => $name,
                        'email' => $email,
                        'password' => $password,
                        'company' => $company,
                    ]);

                    $installOutput = $result['output'];

                    if ($result['success']) {
                        $_SESSION['install_max_step'] = max($_SESSION['install_max_step'], 3);
                        redirect('?step=4');
                    } else {
                        $errors[] = 'Installation failed. See output below for details.';
                    }
                }
                break;
        }
    }
}

require INSTALL_BASE.'/views/layout.php';

function validateDatabaseConfig(array $config): array
{
    $errors = [];

    if ($config['DB_CONNECTION'] === 'sqlite') {
        return $errors;
    }

    $required = ['DB_HOST' => 'Host', 'DB_DATABASE' => 'Database name', 'DB_USERNAME' => 'Username'];
    foreach ($required as $key => $label) {
        if (empty($config[$key])) {
            $errors[] = "{$label} is required.";
        }
    }

    return $errors;
}

function handleAjaxRequest(): void
{
    $action = $_POST['_action'] ?? '';

    if ($action === 'test_connection') {
        $dbConfig = getDatabaseEnvConfig($_POST);

        try {
            $pdo = connectToDatabase($dbConfig);
            $pdo = null;
            echo 'CONNECTION_OK';
        } catch (Exception $e) {
            echo 'CONNECTION_FAIL:'.$e->getMessage();
        }
    } elseif ($action === 'install_composer') {
        header('Content-Type: application/json');
        echo json_encode(runComposerInstall());
        exit;
    } elseif ($action === 'build_frontend') {
        header('Content-Type: application/json');
        echo json_encode(runFrontendBuild());
        exit;
    }
}
