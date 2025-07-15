<?php
// src/Models/Product.php
require_once __DIR__ . '/../Utils/Database.php';

class Product {
    private $conn;
    private $table_name = "products";

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function getAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($title, $description, $price, $stock, $image_url = null, $category_id = null) {
        $query = "INSERT INTO " . $this->table_name . " (title, description, price, stock, image_url, category_id) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$title, $description, $price, $stock, $image_url, $category_id]);
        //$stmt->debugDumpParams();
        return;
    }

    public function update($id, $data) {
        $product = $this->getById($id);
        //var_dump($data);
        $title = (!isset($data->title)) ? $product['title'] : $data->title;
        $description = (!isset($data->description)) ? $product['description'] : $data->description;
        $price = (!isset($data->price)) ? $product['price'] : $data->price;
        $stock = (!isset($data->stock)) ? $product['stock'] : $data->stock;
        $image_url = (!isset($data->image_url)) ? $product['image_url'] : $data->image_url;
        $category_id = (!isset($data->category_id)) ? $product['category_id'] : $data->category_id;

        $query = "UPDATE " . $this->table_name . " SET title = ?, description = ?, price = ?, stock = ? , image_url = ? , category_id = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$title, $description, $price, $stock, $image_url, $category_id, $id]);
        //$stmt->debugDumpParams();
        return $stmt->rowCount() > 0; // Return true if the update was successful
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }
}