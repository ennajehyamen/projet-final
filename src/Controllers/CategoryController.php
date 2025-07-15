<?php
require_once __DIR__ . '/../Models/Category.php';
require_once __DIR__ . '/../Utils/JWTHelper.php';
require_once __DIR__ . '/../Utils/Logger.php';

class CategoryController {
    private $categoryModel;

    public function __construct() {
        $this->categoryModel = new Category();
    }

    /**
     * Liste toutes les catégories.
     * Accès: Public
     */
    public function index() {
        $categories = $this->categoryModel->getAll();
        http_response_code(200);
        echo json_encode($categories);
    }

    /**
     * Affiche les détails d'une catégorie spécifique.
     * Accès: Public
     * @param int $id L'ID de la catégorie.
     */
    public function show($id) {
        $category = $this->categoryModel->getById($id);
        if ($category) {
            http_response_code(200);
            echo json_encode($category);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Category not found."]);
        }
    }

    /**
     * Crée une nouvelle catégorie.
     * Accès: Admin
     */
    public function store() {
        JWTHelper::requireAuth('admin'); // Nécessite un rôle administrateur
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->name)) {
            http_response_code(400);
            echo json_encode(["message" => "Category name is required."]);
            return;
        }

        if ($this->categoryModel->create($data->name, $data->description ?? null)) {
            http_response_code(201);
            echo json_encode(["message" => "Category created successfully."]);
        } else {
            // Le Logger::logError est déjà appelé dans le modèle en cas d'échec
            // Si le modèle gère déjà l'erreur 409 pour les contraintes d'intégrité, pas besoin de le refaire ici.
            // Sinon, on peut ajouter une vérification pour les noms de catégorie dupliqués ici.
            http_response_code(500);
            echo json_encode(["message" => "Unable to create category."]);
        }
    }

    /**
     * Met à jour une catégorie existante.
     * Accès: Admin
     * @param int $id L'ID de la catégorie à mettre à jour.
     */
    public function update($id) {
        JWTHelper::requireAuth('admin'); // Nécessite un rôle administrateur
        $data = json_decode(file_get_contents("php://input"));

        if (!isset($data->name)) {
            http_response_code(400);
            echo json_encode(["message" => "Category name is required for update."]);
            return;
        }

        if ($this->categoryModel->update($id, $data->name, $data->description ?? null)) {
            http_response_code(200);
            echo json_encode(["message" => "Category updated successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Unable to update category."]);
        }
    }

    /**
     * Supprime une catégorie.
     * Accès: Admin
     * @param int $id L'ID de la catégorie à supprimer.
     */
    public function delete($id) {
        JWTHelper::requireAuth('admin'); // Nécessite un rôle administrateur

        // Le modèle Category gère déjà la réponse 409 en cas de contrainte d'intégrité
        if ($this->categoryModel->delete($id)) {
            http_response_code(200);
            echo json_encode(["message" => "Category deleted successfully."]);
        } else {
            // Si le modèle a déjà envoyé une réponse 409, ne pas envoyer 500
            if (!headers_sent()) {
                http_response_code(500);
                echo json_encode(["message" => "Unable to delete category."]);
            }
        }
    }
}