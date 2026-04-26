<?php

function env(string $key, $default = null) {
    $value = getenv($key);
    if ($value !== false) {
        return $value;
    }

    static $dotenv = null;
    if ($dotenv === null) {
        $dotenv = [];
        $file = dirname(__DIR__) . '/.env';
        if (is_file($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if ($line[0] === '#' || !str_contains($line, '=')) continue;
                [$k, $v] = explode('=', $line, 2);
                $dotenv[trim($k)] = trim($v);
            }
        }
    }

    return $dotenv[$key] ?? $default;
}
define('BASE_URL', env('BASE_URL'));
define('DB_HOST', env('DB_HOST'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_PASSWORD', env('DB_PASSWORD'));
define('DB_NAME', env('DB_NAME'));
define('DB_USER', env('DB_USER'));
define('DB_CHARSET', env('DB_CHARSET'));
define('DB_COLLATION', env('DB_COLLATION'));
?>