<?php
// src/Controllers/OrderController.php
require_once __DIR__ . '/../Models/Order.php';
require_once __DIR__ . '/../Utils/JWTHelper.php';
require_once __DIR__ . '/../Utils/Logger.php';

class OrderController {
    private $orderModel;

    public function __construct() {
        $this->orderModel = new Order();
    }

    public function store() {
        $userData = JWTHelper::requireAuth('user'); // Requires user role (or admin)
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->items) || !is_array($data->items) || empty($data->items)) {
            http_response_code(400);
            echo json_encode(["message" => "Order must contain at least one item."]);
            return;
        }

        foreach ($data->items as $item) {
            if (!isset($item->product_id) || !isset($item->quantity) || !is_numeric($item->quantity) || $item->quantity <= 0) {
                http_response_code(400);
                echo json_encode(["message" => "Invalid item format (product_id and quantity required, quantity must be positive)."]);
                return;
            }
        }

        $orderId = $this->orderModel->create($userData->userId, $data->items);

        if ($orderId) {
            http_response_code(201);
            echo json_encode(["message" => "Order created successfully.", "order_id" => $orderId]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Unable to create order."]);
            // Logger::logError will be called inside Order Model on failure
        }
    }

    public function index() {
        $userData = JWTHelper::requireAuth('user'); // Requires user role
        $orders = $this->orderModel->getOrdersByUserId($userData->userId);
        http_response_code(200);
        echo json_encode($orders);
    }

    public function show($id) {
        $userData = JWTHelper::requireAuth('user'); // Requires user role
        $order = $this->orderModel->getOrderById($id, $userData->userId);

        if ($order) {
            http_response_code(200);
            echo json_encode($order);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Order not found or you don't have access to this order."]);
        }
    }
    public function updateStatus($id) {
        JWTHelper::requireAuth('admin'); // Requires admin role
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->status) || !in_array($data->status, ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])) {
            http_response_code(400);
            echo json_encode(["message" => "Invalid status."]);
            return;
        }

        if ($this->orderModel->updateOrderStatus($id, $data->status)) {
            http_response_code(200);
            echo json_encode(["message" => "Order status updated successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Unable to update order status."]);
        }
    }
    public function delete($id) {
        JWTHelper::requireAuth('admin'); // Requires admin role
        if ($this->orderModel->delete($id)) {
            http_response_code(200);
            echo json_encode(["message" => "Order deleted successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Unable to delete order."]);
        }
    }

    public function getOrderById($id) {
        $userData = JWTHelper::requireAuth('user'); // Requires user role
        $order = $this->orderModel->getOrderById($id, $userData->userId);

        if ($order) {
            http_response_code(200);
            echo json_encode($order);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Order not found or you don't have access to this order."]);
        }
    }
    public function getAllOrders() {
        JWTHelper::requireAuth('admin'); // Requires admin role
        $orders = $this->orderModel->getAllOrders();
        http_response_code(200);
        echo json_encode($orders);
    }
}