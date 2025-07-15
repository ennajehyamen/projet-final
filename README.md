# API RESTful PHP & MySQL

Cette API RESTful est développée en **PHP** avec une base de données **MySQL** et utilise des **JSON Web Tokens (JWT)** pour l'authentification et l'autorisation. Elle permet la gestion des produits, des comptes utilisateurs et des commandes, avec des niveaux d'accès spécifiques pour les utilisateurs publics, connectés et les administrateurs.

---

## Table des matières

- [Fonctionnalités](#fonctionnalités)
- [Spécifications Techniques](#spécifications-techniques)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration de la Base de Données](#configuration-de-la-base-de-données)
- [Utilisation de l'API](#utilisation-de-lapi)
- [Structure du Projet](#structure-du-projet)
- [Journalisation des Erreurs](#journalisation-des-erreurs)
- [Sécurité](#sécurité)

---

## Fonctionnalités

Cette API offre les fonctionnalités suivantes :

1.  **Gestion des Produits :**
    * Création, lecture (liste et détail), modification et suppression de produits.
    * Accès public pour la lecture (liste et détail).
    * Accès administrateur pour la création, modification et suppression.
2.  **Gestion des Comptes Utilisateurs :**
    * Création et authentification des utilisateurs.
    * Récupération des informations du compte connecté (profil).
3.  **Gestion des Commandes :**
    * Création de nouvelles commandes par les utilisateurs connectés.
    * Liste et détails des commandes propres à l'utilisateur connecté.
4.  **Authentification et Autorisation :**
    * Utilisation de **tokens JWT** pour l'authentification.
    * Trois niveaux d'accès : **Public**, **Utilisateur** (authentifié), et **Admin** (authentifié avec rôle administrateur).
5.  **Journalisation des Erreurs :**
    * Enregistrement des erreurs serveur dans un fichier de log pour faciliter le débogage.

---

## Spécifications Techniques

### Endpoints de l'API

L'API expose les routes suivantes, avec les niveaux d'accès spécifiés :

| Ressource | Méthode & Route           | Description                                  | Niveau d’accès |
| :-------- | :------------------------ | :------------------------------------------- | :------------- |
| **Auth** | `POST /api/auth/register` | Créer un compte utilisateur                  | Public         |
|           | `POST /api/auth/login`    | Authentifier un utilisateur et générer un token | Public         |
|           | `GET /api/auth/me`        | Obtenir les informations du compte connecté  | Utilisateur    |
| **Produits** | `GET /api/products`       | Lister tous les produits                     | Public         |
|           | `GET /api/products/{id}`  | Détails d’un produit spécifique              | Public         |
|           | `POST /api/products`      | Ajouter un nouveau produit                   | Admin          |
|           | `PUT /api/products/{id}`  | Modifier un produit existant                 | Admin          |
|           | `DELETE /api/products/{id}` | Supprimer un produit                         | Admin          |
| **Commandes** | `POST /api/orders`        | Créer une nouvelle commande                  | Utilisateur    |
|           | `GET /api/orders`         | Lister les commandes de l’utilisateur connecté | Utilisateur    |
|           | `GET /api/orders/{id}`    | Détails d’une commande spécifique            | Utilisateur    |

---

## Prérequis

Avant de commencer, assurez-vous d'avoir les éléments suivants installés sur votre système :

* **PHP** (version 7.4 ou supérieure recommandée)
* **MySQL** (ou MariaDB)
* **Serveur Web** (Apache avec `mod_rewrite` activé ou Nginx)
* **Composer** (Gestionnaire de dépendances pour PHP)

---

## Installation

Suivez ces étapes pour configurer et exécuter le projet localement :

1.  **Cloner le dépôt** (ou télécharger les fichiers) :
    ```bash
    git clone [https://github.com/ennajehyamen/projet-final.git](https://github.com/ennajehyamen/projet-final.git)
    cd projet-final
    ```
    *(Si vous avez simplement copié/collé les fichiers, placez-vous dans le répertoire racine du projet.)*

2.  **Installer les dépendances Composer** :
    ```bash
    composer install
    ```
    Cela installera la bibliothèque JWT nécessaire.

3.  **Configuration du serveur web (Apache exemple)** :
    Assurez-vous que le fichier `.htaccess` est présent à la racine de votre projet et que `mod_rewrite` est activé sur votre serveur Apache. Votre VirtualHost ou la configuration de votre répertoire doit avoir `AllowOverride All`.

    ```apache
    # .htaccess (à la racine du projet)
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^api/(.*)$ index.php [QSA,L]
    ```
    Assurez-vous que le chemin `^api/(.*)$` correspond au chemin de votre API sur le serveur. Si votre API est directement à la racine de votre domaine (ex: `http://api.example.com`), vous pouvez simplifier la règle ou l'ajuster en conséquence.

---

## Configuration de la Base de Données

1.  **Créer la base de données MySQL** :
    ```sql
    CREATE DATABASE `your_database_name`;
    ```

2.  **Importer le schéma de la base de données** :
    Exécutez les scripts SQL dans le dossier db dans le projet

    ```

3.  **Mettre à jour le fichier de configuration de la base de données** :
    Ouvrez `config/database.php` et renseignez vos identifiants MySQL.

    ```php
    <?php
    // config/database.php
    return [
        'host' => 'localhost',
        'dbname' => 'your_database_name', // <-- Mettez votre nom de BD ici
        'user' => 'your_username',     // <-- Mettez votre nom d'utilisateur MySQL
        'password' => 'your_password', // <-- Mettez votre mot de passe MySQL
        'charset' => 'utf8mb4'
    ];
    ```

4.  **Mettre à jour le fichier de constantes** :
    Ouvrez `config/constants.php` et définissez une clé secrète forte pour les JWT.

    ```php
    <?php
    // config/constants.php
    define('JWT_SECRET_KEY', 'your_super_secret_jwt_key_that_is_at_least_32_chars'); // <-- Changez cette clé !
    define('LOG_FILE', __DIR__ . '/../logs/error.log');
    define('APP_ENV', 'development'); // Changez à 'production' en déploiement
    ```

---

## Utilisation de l'API

Une fois l'installation et la configuration terminées, vous pouvez interagir avec l'API en utilisant des outils comme **Postman**, **Insomnia**, ou `curl`.

L'URL de base de votre API sera probablement `http://localhost/votre_dossier_projet/api` (ou similaire, selon la configuration de votre serveur web).

### Exemples de requêtes :

#### Authentification et Enregistrement

* **Enregistrer un nouvel utilisateur :**
    ```bash
    curl -X POST \
      http://localhost/votre_dossier_projet/api/auth/register \
      -H 'Content-Type: application/json' \
      -d '{
        "username": "monutilisateur",
        "email": "mon@email.com",
        "password": "monmotdepasse"
      }'
    ```

* **Connecter un utilisateur et obtenir un token :**
    ```bash
    curl -X POST \
      http://localhost/votre_dossier_projet/api/auth/login \
      -H 'Content-Type: application/json' \
      -d '{
        "username": "monutilisateur",
        "password": "monmotdepasse"
      }'
    ```
    La réponse contiendra un `token` JWT que vous utiliserez pour les requêtes authentifiées.

#### Requêtes Authentifiées (Exemples)

* **Obtenir les informations de l'utilisateur connecté :**
    ```bash
    curl -X GET \
      http://localhost/votre_dossier_projet/api/auth/me \
      -H 'Authorization: Bearer VOTRE_TOKEN_JWT'
    ```

* **Ajouter un nouveau produit (nécessite un token ADMIN) :**
    ```bash
    curl -X POST \
      http://localhost/votre_dossier_projet/api/products \
      -H 'Content-Type: application/json' \
      -H 'Authorization: Bearer VOTRE_TOKEN_ADMIN_JWT' \
      -d '{
        "name": "Smartphone X",
        "description": "Le dernier smartphone haut de gamme.",
        "price": 799.99,
        "stock_quantity": 100
      }'
    ```

* **Créer une nouvelle commande (nécessite un token UTILISATEUR) :**
    ```bash
    curl -X POST \
      http://localhost/votre_dossier_projet/api/orders \
      -H 'Content-Type: application/json' \
      -H 'Authorization: Bearer VOTRE_TOKEN_UTILISATEUR_JWT' \
      -d '{
        "items": [
          { "product_id": 1, "quantity": 2 },
          { "product_id": 2, "quantity": 1 }
        ]
      }'
    ```
    *(Remplacez 1 et 2 par des IDs de produits existants dans votre BD.)*

---

## Structure du Projet

├── .htaccess                 # Règles de réécriture d'URL pour Apache
├── index.php                 # Point d'entrée unique de l'API (Front Controller)
├── composer.json             # Dépendances du projet (JWT)
├── composer.lock             # Fichier généré par Composer
├── vendor/                   # Dépendances installées par Composer
├── config/
│   ├── database.php          # Configuration de la connexion à la base de données
│   └── constants.php         # Constantes du projet (clé JWT, chemin des logs)
├── src/
│   ├── Models/               # Classes pour interagir avec la base de données (User, Product, Order)
│   │   ├── User.php
│   │   ├── Product.php
│   │   └── Order.php
│   ├── Controllers/          # Logique métier et gestion des requêtes HTTP
│   │   ├── AuthController.php
│   │   ├── ProductController.php
│   │   └── OrderController.php
│   ├── Utils/                # Classes utilitaires (connexion DB, JWT, Logger)
│   │   ├── Database.php
│   │   ├── JWTHelper.php
│   │   └── Logger.php
│   ├── Routes/
│   │   └── Router.php           # Définition des routes de l'API
├── logs/
│   └── error.log             # Fichier de journalisation des erreurs serveur

## Journalisation des Erreurs

Les erreurs serveur sont journalisées dans le fichier `logs/error.log`. En mode `development` (défini dans `config/constants.php`), les erreurs PHP sont également affichées. Il est recommandé de passer en mode `production` pour les environnements de déploiement afin de masquer les détails des erreurs aux utilisateurs finaux.

---