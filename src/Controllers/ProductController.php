<?php
// src/Controllers/ProductController.php
require_once __DIR__ . '/../Models/Product.php';
require_once __DIR__ . '/../Utils/JWTHelper.php';
require_once __DIR__ . '/../Utils/Logger.php';

class ProductController {
    private $productModel;

    public function __construct() {
        $this->productModel = new Product();
    }

    public function index() {
        $products = $this->productModel->getAll();
        http_response_code(200);
        echo json_encode($products);
    }

    public function show($id) {
        $product = $this->productModel->getById($id);
        if ($product) {
            http_response_code(200);
            echo json_encode($product);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Product not found."]);
        }
    }

    public function store() {
        JWTHelper::requireAuth('admin'); // Requires admin role
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->title) || !isset($data->price) || !isset($data->stock)) {
            http_response_code(400);
            echo json_encode(["message" => "Missing required product fields (title, price, stock)."]);
            return;
        }

        if ($this->productModel->create($data->title, $data->description ?? null, $data->price, $data->stock, $data->image_url ?? null, $data->category_id ?? null)) {
            http_response_code(201);
            echo json_encode(["message" => "Product created successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Unable to create product."]);
            Logger::logError("Failed to create product: " . $data->title);
        }
    }

    public function update($id) {
        JWTHelper::requireAuth('admin'); // Requires admin role
        $data = json_decode(file_get_contents("php://input"));

        if (!$this->productModel->getById($id)) {
            http_response_code(404);
            echo json_encode(["message" => "Product not found."]);
            return;
        }

        if ($this->productModel->update($id, $data)) {
            http_response_code(200);
            echo json_encode(["message" => "Product updated successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Unable to update product."]);
            Logger::logError("Failed to update product ID: " . $id);
        }
    }

    public function delete($id) {
        JWTHelper::requireAuth('admin'); // Requires admin role

        if ($this->productModel->delete($id)) {
            http_response_code(200);
            echo json_encode(["message" => "Product deleted successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Unable to delete product."]);
            Logger::logError("Failed to delete product ID: " . $id);
        }
    }
}