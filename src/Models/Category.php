<?php
require_once __DIR__ . '/../Utils/Database.php';
require_once __DIR__ . '/../Utils/Logger.php';

class Category {
    private $conn;
    private $table_name = "categories";

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    /**
     * Récupère toutes les catégories.
     * @return array Un tableau de toutes les catégories.
     */
    public function getAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère une catégorie par son ID.
     * @param int $id L'ID de la catégorie.
     * @return array|false Les détails de la catégorie ou false si non trouvée.
     */
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Crée une nouvelle catégorie.
     * @param string $name Le nom de la catégorie.
     * @param string|null $description La description de la catégorie (optionnel).
     * @return bool True si la création est réussie, false sinon.
     */
    public function create($name, $description = null) {
        $query = "INSERT INTO " . $this->table_name . " (name, description) VALUES (?, ?)";
        $stmt = $this->conn->prepare($query);
        try {
            return $stmt->execute([$name, $description]);
        } catch (PDOException $e) {
            Logger::logError("Failed to create category '" . $name . "': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour une catégorie existante.
     * @param int $id L'ID de la catégorie à mettre à jour.
     * @param string $name Le nouveau nom de la catégorie.
     * @param string|null $description La nouvelle description de la catégorie (optionnel).
     * @return bool True si la mise à jour est réussie, false sinon.
     */
    public function update($id, $name, $description = null) {
        $query = "UPDATE " . $this->table_name . " SET name = ?, description = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        try {
            return $stmt->execute([$name, $description, $id]);
        } catch (PDOException $e) {
            Logger::logError("Failed to update category ID " . $id . ": " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime une catégorie.
     * @param int $id L'ID de la catégorie à supprimer.
     * @return bool True si la suppression est réussie, false sinon.
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        try {
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            Logger::logError("Failed to delete category ID " . $id . ": " . $e->getMessage());
            // Gérer les erreurs de clé étrangère si des produits sont liés à cette catégorie
            if ($e->getCode() === '23000') { // SQLSTATE for integrity constraint violation
                http_response_code(409); // Conflict
                echo json_encode(["message" => "Cannot delete category: it is linked to existing products."]);
                exit();
            }
            return false;
        }
    }
}