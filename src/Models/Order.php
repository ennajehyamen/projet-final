<?php
require_once __DIR__ . '/../Utils/Database.php';
require_once __DIR__ . '/../Utils/Logger.php';
class Order {
    private $conn;
    private $table_name = "orders";
    private $order_items_table = "order_items";

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    /**
     * Crée une nouvelle commande et ses articles associés.
     * @param int $userId L'ID de l'utilisateur qui passe la commande.
     * @param array $items Un tableau d'objets, chaque objet ayant 'product_id' et 'quantity'.
     * @return int|bool L'ID de la nouvelle commande si succès, false sinon.
     */
    public function create($userId, $items) {
        try {
            $this->conn->beginTransaction();

            $totalAmount = 0;
            // Calculer le montant total et vérifier l'existence des produits
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

            // Insérer la commande principale
            $orderQuery = "INSERT INTO " . $this->table_name . " (user_id, total, status) VALUES (?, ?, 'pending')";
            $orderStmt = $this->conn->prepare($orderQuery);
            $orderStmt->execute([$userId, $totalAmount]);
            $orderId = $this->conn->lastInsertId();

            // Insérer les articles de la commande
            foreach ($items as $item) {
                // Récupérer le prix du produit au moment de la commande pour price_snapshot
                $productQuery = "SELECT price FROM products WHERE id = ?";
                $productStmt = $this->conn->prepare($productQuery);
                $productStmt->execute([$item->product_id]);
                $product = $productStmt->fetch(PDO::FETCH_ASSOC);

                if (!$product) {
                    // Ceci ne devrait pas arriver si la première boucle a fonctionné, mais c'est une sécurité
                    throw new Exception("Product with ID " . $item->product_id . " disappeared during transaction.");
                }

                // Utilisation de 'price_snapshot'
                $orderItemQuery = "INSERT INTO " . $this->order_items_table . " (order_id, product_id, quantity, price_snapshot) VALUES (?, ?, ?, ?)";
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

    /**
     * Récupère toutes les commandes pour un utilisateur donné.
     * @param int $userId L'ID de l'utilisateur.
     * @return array Un tableau des commandes de l'utilisateur.
     */
    public function getOrdersByUserId($userId) {
        $query = "SELECT o.id, o.creation_date, o.total, o.status,
                         GROUP_CONCAT(CONCAT(p.name, ' (Qty: ', oi.quantity, ' @ ', oi.price_snapshot, '$)') SEPARATOR '; ') AS items_summary
                  FROM " . $this->table_name . " o
                  JOIN " . $this->order_items_table . " oi ON o.id = oi.order_id
                  JOIN products p ON oi.product_id = p.id
                  WHERE o.user_id = ?
                  GROUP BY o.id, o.creation_date, o.total, o.status
                  ORDER BY o.creation_date DESC"; // Tri par creation_date
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les détails d'une commande spécifique pour un utilisateur donné.
     * @param int $orderId L'ID de la commande.
     * @param int $userId L'ID de l'utilisateur (pour s'assurer qu'il a accès à cette commande).
     * @return array|false Les détails de la commande avec ses articles, ou false si non trouvée.
     */
    public function getOrderById($orderId, $userId) {
        $query = "SELECT o.id, o.creation_date, o.total, o.status,
                         u.username, u.email
                  FROM " . $this->table_name . " o
                  JOIN users u ON o.user_id = u.id
                  WHERE o.id = ? AND o.user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            // Récupérer les articles associés à cette commande
            $itemsQuery = "SELECT oi.quantity, oi.price_snapshot, p.name, p.description
                           FROM " . $this->order_items_table . " oi
                           JOIN products p ON oi.product_id = p.id
                           WHERE oi.order_id = ?";
            $itemsStmt = $this->conn->prepare($itemsQuery);
            $itemsStmt->execute([$orderId]);
            $order['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $order;
    }

    /**
     * Met à jour le statut d'une commande.
     * Cette méthode est ajoutée pour montrer comment gérer la colonne 'status'.
     * @param int $orderId L'ID de la commande à mettre à jour.
     * @param string $newStatus Le nouveau statut (doit être une valeur valide de l'ENUM).
     * @return bool True si la mise à jour est réussie, false sinon.
     */
    public function updateOrderStatus($orderId, $newStatus) {
        // Optionnel: Vérifier que $newStatus est une valeur valide de l'ENUM
        $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($newStatus, $validStatuses)) {
            Logger::logError("Invalid order status provided: " . $newStatus);
            return false;
        }

        $query = "UPDATE " . $this->table_name . " SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        try {
            return $stmt->execute([$newStatus, $orderId]);
        } catch (PDOException $e) {
            Logger::logError("Failed to update order status for ID " . $orderId . ": " . $e->getMessage());
            return false;
        }
    }
    /**
     * Supprime une commande et ses articles associés.
     * @param int $orderId L'ID de la commande à supprimer.
     * @return bool True si la suppression est réussie, false sinon.
     */
    public function delete($orderId) {
        try {
            $this->conn->beginTransaction();

            // Supprimer les articles de la commande
            $deleteItemsQuery = "DELETE FROM " . $this->order_items_table . " WHERE order_id = ?";
            $deleteItemsStmt = $this->conn->prepare($deleteItemsQuery);
            $deleteItemsStmt->execute([$orderId]);

            // Supprimer la commande principale
            $deleteOrderQuery = "DELETE FROM " . $this->table_name . " WHERE id = ?";
            $deleteOrderStmt = $this->conn->prepare($deleteOrderQuery);
            $result = $deleteOrderStmt->execute([$orderId]);

            $this->conn->commit();
            return $result;
        } catch (Exception $e) {
            $this->conn->rollBack();
            Logger::logError("Failed to delete order ID " . $orderId . ": " . $e->getMessage());
            return false;
        }
    }
    /**
     * Récupère toutes les commandes (pour les admins).
     * @return array Un tableau de toutes les commandes.
     */
    public function getAllOrders() {
        $query = "SELECT o.id, o.creation_date, o.total, o.status, u.username
                  FROM " . $this->table_name . " o
                  JOIN users u ON o.user_id = u.id
                  ORDER BY o.creation_date DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}