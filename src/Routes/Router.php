<?php
require_once __DIR__ . '/../Controllers/AuthController.php';
require_once __DIR__ . '/../Controllers/ProductController.php';
require_once __DIR__ . '/../Controllers/OrderController.php';
require_once __DIR__ . '/../Controllers/CategoryController.php';
require_once __DIR__ . '/../Utils/Logger.php';

header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$basePath = '/projet-final/api';
if (strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

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
        } elseif ($requestUri === '/categories') {
            (new CategoryController())->store();
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
        } elseif ($requestUri === '/categories') {
            (new CategoryController())->index();
        } elseif (preg_match('/^\/categories\/(\d+)$/', $requestUri, $matches)) { 
            (new CategoryController())->show($matches[1]);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Endpoint not found."]);
        }
        break;

    case 'PUT':
        if (preg_match('/^\/products\/(\d+)$/', $requestUri, $matches)) {
            (new ProductController())->update($matches[1]);
        } elseif (preg_match('/^\/categories\/(\d+)$/', $requestUri, $matches)) {
            (new CategoryController())->update($matches[1]);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Endpoint not found."]);
        }
        break;

    case 'DELETE':
        if (preg_match('/^\/products\/(\d+)$/', $requestUri, $matches)) {
            (new ProductController())->delete($matches[1]);
        } elseif (preg_match('/^\/categories\/(\d+)$/', $requestUri, $matches)) {
            (new CategoryController())->delete($matches[1]);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Endpoint not found."]);
        }
        break;

    case 'OPTIONS':
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        http_response_code(200);
        exit();
        break;

    default:
        http_response_code(405); // Méthode non autorisée
        echo json_encode(["message" => "Method not allowed."]);
        break;
}