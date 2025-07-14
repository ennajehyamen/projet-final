<?php
// src/Utils/Database.php
class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        $dbConfig = require __DIR__ . '/../../config/database.php';
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";

        try {
            $this->conn = new PDO($dsn, $dbConfig['user'], $dbConfig['password']);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::logError("Database connection error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["message" => "Database connection error."]);
            exit();
        }
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }
}