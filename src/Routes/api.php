<?php
// src/Routes/api.php
require_once __DIR__ . '/../Controllers/AuthController.php';
require_once __DIR__ . '/../Controllers/ProductController.php';
require_once __DIR__ . '/../Controllers/OrderController.php';
require_once __DIR__ . '/../Utils/Logger.php';

// Set content type for JSON responses
header("Content-Type: application/json");

// Get the request method and URI
$method = $_SERVER['REQUEST_METHOD'];
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove the base path if your API is in a subdirectory (e.g., /api_rest_php_mysql/api)
// Adjust this based on your actual server setup
$basePath = '/api_rest_php_mysql/api'; // Change this if your API is at /api directly or another path
if (strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

// Simple routing mechanism
// This can be expanded with a more robust router library (e.g., FastRoute) for larger projects

switch ($method) {
    case 'POST':
        if ($requestUri === '/auth/register') {
            (new AuthController())->register();
        } elseif ($requestUri === '/auth/login') {
            (new AuthController())->login();
        } elseif ($requestUri === '/products') {
            (new ProductController())->store();
        } elseif ($requestUri === '/orders') {
            (new OrderController())->store();
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Endpoint not found."]);
        }
        break;

    case 'GET':
        if ($requestUri === '/auth/me') {
            (new AuthController())->getMe();
        } elseif ($requestUri === '/products') {
            (new ProductController())->index();
        } elseif (preg_match('/^\/products\/(\d+)$/', $requestUri, $matches)) {
            (new ProductController())->show($matches[1]);
        } elseif ($requestUri === '/orders') {
            (new OrderController())->index();
        } elseif (preg_match('/^\/orders\/(\d+)$/', $requestUri, $matches)) {
            (new OrderController())->show($matches[1]);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Endpoint not found."]);
        }
        break;

    case 'PUT':
        if (preg_match('/^\/products\/(\d+)$/', $requestUri, $matches)) {
            (new ProductController())->update($matches[1]);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Endpoint not found."]);
        }
        break;

    case 'DELETE':
        if (preg_match('/^\/products\/(\d+)$/', $requestUri, $matches)) {
            (new ProductController())->delete($matches[1]);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Endpoint not found."]);
        }
        break;

    case 'OPTIONS':
        // Handle CORS preflight requests
        header("Access-Control-Allow-Origin: *"); // Adjust for specific origins in production
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        http_response_code(200);
        exit();
        break;

    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(["message" => "Method not allowed."]);
        break;
}