<?php
// src/Models/Order.php
require_once __DIR__ . '/../Utils/Database.php';

class Order {
    private $conn;
    private $table_name = "orders";
    private $order_items_table = "order_items";

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function create($userId, $items) {
        try {
            $this->conn->beginTransaction();

            $totalAmount = 0;
            foreach ($items as $item) {
                $productQuery = "SELECT price FROM products WHERE id = ?";
                $productStmt = $this->conn->prepare($productQuery);
                $productStmt->execute([$item->product_id]);
                $product = $productStmt->fetch(PDO::FETCH_ASSOC);

                if (!$product) {
                    throw new Exception("Product with ID " . $item->product_id . " not found.");
                }
                $totalAmount += $product['price'] * $item->quantity;
            }

            $orderQuery = "INSERT INTO " . $this->table_name . " (user_id, total_amount) VALUES (?, ?)";
            $orderStmt = $this->conn->prepare($orderQuery);
            $orderStmt->execute([$userId, $totalAmount]);
            $orderId = $this->conn->lastInsertId();

            foreach ($items as $item) {
                $productQuery = "SELECT price FROM products WHERE id = ?"; // Re-fetch to ensure price consistency
                $productStmt = $this->conn->prepare($productQuery);
                $productStmt->execute([$item->product_id]);
                $product = $productStmt->fetch(PDO::FETCH_ASSOC);

                $orderItemQuery = "INSERT INTO " . $this->order_items_table . " (order_id, product_id, quantity, price_at_order) VALUES (?, ?, ?, ?)";
                $orderItemStmt = $this->conn->prepare($orderItemQuery);
                $orderItemStmt->execute([$orderId, $item->product_id, $item->quantity, $product['price']]);
            }

            $this->conn->commit();
            return $orderId;
        } catch (Exception $e) {
            $this->conn->rollBack();
            Logger::logError("Order creation failed: " . $e->getMessage());
            return false;
        }
    }

    public function getOrdersByUserId($userId) {
        $query = "SELECT o.id, o.order_date, o.total_amount,
                         GROUP_CONCAT(CONCAT(p.name, ' (Qty: ', oi.quantity, ' @ ', oi.price_at_order, '$)') SEPARATOR '; ') AS items_summary
                  FROM " . $this->table_name . " o
                  JOIN " . $this->order_items_table . " oi ON o.id = oi.order_id
                  JOIN products p ON oi.product_id = p.id
                  WHERE o.user_id = ?
                  GROUP BY o.id
                  ORDER BY o.order_date DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOrderById($orderId, $userId) {
        $query = "SELECT o.id, o.order_date, o.total_amount,
                         u.username, u.email
                  FROM " . $this->table_name . " o
                  JOIN users u ON o.user_id = u.id
                  WHERE o.id = ? AND o.user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            $itemsQuery = "SELECT oi.quantity, oi.price_at_order, p.name, p.description
                           FROM " . $this->order_items_table . " oi
                           JOIN products p ON oi.product_id = p.id
                           WHERE oi.order_id = ?";
            $itemsStmt = $this->conn->prepare($itemsQuery);
            $itemsStmt->execute([$orderId]);
            $order['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $order;
    }

    // La modification et la suppression de commandes sont généralement plus complexes et dépendent des règles métier (ex: annulation, retour). Pour cet exemple, nous nous concentrons sur la création et la lecture. Si nécessaire, les méthodes de mise à jour/suppression devraient impliquer des vérifications de statut de commande.
}