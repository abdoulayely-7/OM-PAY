<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## OM-Pay API

API Laravel pour le système de paiement OM-Pay avec authentification OAuth2 via Passport.

### 🚀 Déploiement sur Render

#### Prérequis
- Compte Render (https://render.com)
- GitHub repository

#### Déploiement automatique

1. **Connecter votre repository GitHub à Render**
2. **Créer un nouveau service Web**
3. **Configuration du déploiement :**
   - **Runtime** : Docker
   - **Build Command** : `docker build -t om-pay .`
   - **Start Command** : `docker run -p $PORT:80 om-pay`

#### Variables d'environnement (dans Render Dashboard)

```
APP_NAME=OM-Pay
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=pgsql
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
```

La base de données PostgreSQL sera automatiquement créée par Render.

### 🛠️ Développement local

#### Avec Docker Compose

```bash
# Cloner le repository
git clone <your-repo-url>
cd om-pay

# Copier le fichier d'environnement
cp .env.example .env

# Générer la clé d'application
php artisan key:generate

# Démarrer les services
docker-compose up -d

# Accéder à l'API
# http://localhost:8000
```

#### Installation manuelle

```bash
# Installer les dépendances
composer install

# Configuration de la base de données
php artisan migrate
php artisan db:seed
php artisan passport:install

# Démarrer le serveur
php artisan serve
```

### 📚 API Documentation

#### Authentification

##### Inscription d'un client
```http
POST /api/v1/auth/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "telephone": "+221771234569",
  "password": "password123",
  "password_confirmation": "password123"
}
```

##### Connexion
```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "identifier": "john@example.com", // ou "+221771234569"
  "password": "password123"
}
```

##### Rafraîchir le token
```http
POST /api/v1/auth/refresh
Content-Type: application/json

{
  "refresh_token": "your_refresh_token"
}
```

##### Déconnexion
```http
POST /api/v1/auth/logout
Authorization: Bearer your_access_token
```

### 🔐 Authentification

- **OAuth2** avec Laravel Passport
- **Connexion flexible** : Email ou numéro de téléphone
- **Tokens JWT** : Access + Refresh tokens
- **Cookies sécurisés** (httpOnly, secure, sameSite)
- **Validation stricte** des numéros sénégalais

### 📱 Numéros de téléphone supportés

Format sénégalais obligatoire :
- `+221` (facultatif) + préfixe (77/70/76/75/78) + 7 chiffres
- Exemples : `+221771234569`, `771234569`

### 🏗️ Architecture

- **Laravel 11** avec PHP 8.2
- **PostgreSQL** pour la base de données
- **Docker** pour la conteneurisation
- **Middleware** personnalisés pour l'authentification et les rôles
- **API Resources** pour le formatage des réponses
- **Validation** stricte des données

### 🧪 Tests

```bash
# Exécuter tous les tests
php artisan test

# Tests spécifiques
php artisan test --filter AuthTest
```

### 📦 Structure du projet

```
om-pay/
├── app/
│   ├── Http/
│   │   ├── Controllers/AuthController.php
│   │   ├── Middleware/
│   │   │   ├── AuthMiddleware.php
│   │   │   ├── RoleMiddleware.php
│   │   │   └── LoggingMiddleware.php
│   │   ├── Requests/RegisterRequest.php
│   │   └── Resources/UserResource.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Compte.php
│   │   ├── Transaction.php
│   │   └── Marchand.php
│   └── Traits/ApiResponseTrait.php
├── database/
│   ├── migrations/
│   └── seeders/
├── docker/
│   ├── Dockerfile
│   ├── docker-compose.yml
│   └── render.yaml
├── routes/api.php
└── README.md
```

### 🤝 Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

### 📄 License

Ce projet est sous licence MIT - voir le fichier [LICENSE](LICENSE) pour plus de détails.

---

**Développé avec ❤️ pour le système OM-Pay**
