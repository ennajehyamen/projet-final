<?php
// src/Models/User.php
require_once __DIR__ . '/../Utils/Database.php';

class User {
    private $conn;
    private $table_name = "users";

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function create($name, $email, $password, $role = 'user') {
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $query = "INSERT INTO " . $this->table_name . " (name, email, password_hash, role, creation_date) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$name, $email, $password_hash, $role]);
    }

    public function findByEmail($email) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $query = "SELECT id, name, email, role, creation_date FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $name, $email, $role) {
        $query = "UPDATE " . $this->table_name . " SET name = ?, email = ?, role = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$name, $email, $role, $id]);
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }

    public function getAllUsers() {
        $query = "SELECT id, name, email, role, creation_date FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateUserAfterLogin($id, $token) {
        $query = "UPDATE " . $this->table_name . " SET last_login_date = NOW() , token = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $res = $stmt->execute([$token, $id]);
        //$stmt->debugDumpParams();
        return $res;
    }
}