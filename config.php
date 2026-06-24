<?php
class Config {
    private static $config = [];
    
    public static function load() {
        if (file_exists(__DIR__ . '/.env')) {
            $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    putenv("$key=$value");
                    self::$config[$key] = $value;
                }
            }
        }
    }
    
    public static function get($key, $default = null) {
        return getenv($key) ?: (self::$config[$key] ?? $default);
    }
}

// Load configuration
Config::load();
