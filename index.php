<?php
// index.php
require_once __DIR__ . '/src/Utils/Logger.php'; // Ensure Logger is available for fatal errors

// CORS headers
header("Access-Control-Allow-Origin: *"); // For development, allow all origins. Restrict this in production.
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 3600"); // Cache preflight requests for 1 hour

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Error handling
set_exception_handler(function ($exception) {
    Logger::logError("Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
    http_response_code(500);
    echo json_encode(["message" => "An unexpected error occurred. Please try again later."]);
    exit();
});

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        // This error code is not included in error_reporting
        return false;
    }
    Logger::logError("PHP Error: [$severity] $message in $file on line $line");
    // Depending on APP_ENV, you might want to show or hide details
    if (APP_ENV === 'development') {
        // For development, display error
        throw new ErrorException($message, 0, $severity, $file, $line);
    } else {
        // For production, just log and return generic error
        http_response_code(500);
        echo json_encode(["message" => "An unexpected error occurred."]);
        exit();
    }
});

require_once __DIR__ . '/src/Routes/Router.php';