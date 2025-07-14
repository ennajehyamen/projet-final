<?php
// src/Controllers/AuthController.php
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Utils/JWTHelper.php';
require_once __DIR__ . '/../Utils/Logger.php';

class AuthController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function register() {
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->name) || !isset($data->email) || !isset($data->password)) {
            http_response_code(400);
            echo json_encode(["message" => "Missing required fields."]);
            return;
        }

        if ($this->userModel->findByEmail($data->email)) {
            http_response_code(409);
            echo json_encode(["message" => "Email already exists."]);
            return;
        }

        if ($this->userModel->create($data->name, $data->email, $data->password)) {
            http_response_code(201);
            echo json_encode(["message" => "User registered successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Unable to register user."]);
            Logger::logError("Failed to register user: " . $data->name);
        }
    }

    public function login() {
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->email) || !isset($data->password)) {
            http_response_code(400);
            echo json_encode(["message" => "Missing email or password."]);
            return;
        }

        $user = $this->userModel->findByEmail($data->email);

        if ($user && password_verify($data->password, $user['password_hash'])) {
            $token = JWTHelper::generateToken($user['id'], $user['email'], $user['role']);
            http_response_code(200);
            echo json_encode(["message" => "Login successful.", "token" => $token, "user" => ["id" => $user['id'], "email" => $user['email'], "role" => $user['role']]]);
        } else {
            http_response_code(401);
            echo json_encode(["message" => "Invalid credentials."]);
        }
    }

    public function getMe() {
        $userData = JWTHelper::requireAuth('user'); // Peut être 'admin' si on veut que seuls les admins voient leur propre profil
        // JWTHelper::requireAuth() est suffisant ici car l'utilisateur doit être connecté.
        // La validation est déjà faite par le middleware implicite de requireAuth.

        $user = $this->userModel->findById($userData->userId);

        if ($user) {
            http_response_code(200);
            echo json_encode(["message" => "User data retrieved.", "user" => $user]);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "User not found."]);
            Logger::logError("User ID " . $userData->userId . " not found during getMe.");
        }
    }
}