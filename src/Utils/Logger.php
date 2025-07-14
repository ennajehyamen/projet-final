<?php
// src/Utils/Logger.php
require_once __DIR__ . '/../../config/constants.php';

class Logger {
    public static function logError($message) {
        if (APP_ENV === 'development') {
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[$timestamp] ERROR: $message" . PHP_EOL;
            file_put_contents(LOG_FILE, $logMessage, FILE_APPEND);
        }
    }
}