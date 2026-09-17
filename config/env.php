<?php
// config/env.php

/**
 * Loads key-value pairs from a .env file into PHP environment variables.
 */
function load_env(string $path): void {
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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
        $value = trim($value, " \t\n\r\0\x0B\"'");

        // Populate environment superglobals without overwriting existing system envs
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv("{$name}={$value}");
            $_ENV[$name]    = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Automatically load the .env located at the project root
load_env(dirname(__DIR__) . '/.env');