<?php
function load_env(string $path): void {
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip empty lines or comments
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        // Split key and value on the first '='
        if (!str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name  = trim($name);
        // Strip surrounding quotes and whitespace
        $value = trim($value, " \t\n\r\0\x0B\"'");

        // Always register in all three scopes so getenv(), $_ENV, and $_SERVER can read it
        putenv("{$name}={$value}");
        $_ENV[$name]    = $value;
        $_SERVER[$name] = $value;
    }
}

// Automatically load the .env located at the project root
$root_env = dirname(__DIR__) . '/.env';
if (file_exists($root_env)) {
    load_env($root_env);
}