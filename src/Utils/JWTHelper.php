<?php
// src/Utils/JWTHelper.php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/constants.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTHelper {
    public static function generateToken($userId, $username, $role) {
        $issuedAt = time();
        $expirationTime = $issuedAt + (3600 * 24); // Token valide 24 heures
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'data' => [
                'userId' => $userId,
                'username' => $username,
                'role' => $role
            ]
        ];
        return JWT::encode($payload, JWT_SECRET_KEY, 'HS256');
    }

    public static function validateToken() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authHeader) {
            return ['isValid' => false, 'message' => 'Token not provided.', 'data' => null];
        }

        list($type, $token) = explode(' ', $authHeader);

        if (strtolower($type) !== 'bearer' || empty($token)) {
            return ['isValid' => false, 'message' => 'Invalid token format.', 'data' => null];
        }

        try {
            $decoded = JWT::decode($token, new Key(JWT_SECRET_KEY, 'HS256'));
            return ['isValid' => true, 'data' => $decoded->data];
        } catch (Exception $e) {
            Logger::logError("JWT Validation Error: " . $e->getMessage());
            return ['isValid' => false, 'message' => 'Invalid or expired token.', 'data' => null];
        }
    }

    public static function requireAuth($expectedRole = null) {
        $validationResult = self::validateToken();

        if (!$validationResult['isValid']) {
            http_response_code(401);
            echo json_encode(["message" => $validationResult['message']]);
            exit();
        }

        $userData = $validationResult['data'];

        if ($expectedRole && $userData->role !== $expectedRole) {
            http_response_code(403);
            echo json_encode(["message" => "Access forbidden. Insufficient privileges."]);
            exit();
        }

        return $userData; // Retourne les données de l'utilisateur authentifié
    }
}