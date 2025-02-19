<?php

if (!defined('ABSPATH')) {
    exit;
}

class ERP_Logger {

    private static $log_file = __DIR__ . '/../logs/error_log.txt';

    public static function log_error($message) {
        $time = date('Y-m-d H:i:s');
        error_log("[$time] ERROR: $message" . PHP_EOL, 3, self::$log_file);
    }

    public static function get_errors() {
        if (file_exists(self::$log_file)) {
            return file_get_contents(self::$log_file);
        }
        return "No errors logged.";
    }
}
