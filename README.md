# OM-Pay API

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-11-red.svg" alt="Laravel">
  <img src="https://img.shields.io/badge/PHP-8.2-blue.svg" alt="PHP">
  <img src="https://img.shields.io/badge/PostgreSQL-15-336791.svg" alt="PostgreSQL">
  <img src="https://img.shields.io/badge/Docker-Ready-blue.svg" alt="Docker">
  <img src="https://img.shields.io/badge/Render-Deployed-46E3B7.svg" alt="Render">
</p>

## 🚀 À propos

OM-Pay est une API REST Laravel pour les services de paiement mobile au Sénégal. Elle permet la gestion des utilisateurs, comptes, transactions et paiements avec authentification sécurisée via Laravel Passport.

### ✨ Fonctionnalités

- 🔐 **Authentification flexible** : Email ou numéro de téléphone sénégalais
- 👥 **Gestion des utilisateurs** : Inscription, connexion, profils
- 💳 **Gestion des comptes** : Comptes bancaires liés aux utilisateurs
- 💰 **Transactions** : Dépôt, retrait, paiement, transfert
- 🏪 **Marchands** : Gestion des partenaires commerciaux
- 🔒 **Sécurité** : JWT tokens, rôles et permissions
- 📱 **API REST** : Endpoints documentés et standardisés

## 🛠 Installation & Développement

### Prérequis

- Docker & Docker Compose
- Git

### Installation rapide oo

```bash
# Cloner le projet
git clone <repository-url>
cd om-pay

# Copier le fichier d'environnement
cp .env.example .env

# Démarrer les services
docker-compose up -d

# Installer les dépendances
docker-compose exec app composer install

# Générer la clé d'application
docker-compose exec app php artisan key:generate

# Exécuter les migrations
docker-compose exec app php artisan migrate

# Peupler la base de données
docker-compose exec app php artisan db:seed

# Installer Passport
docker-compose exec app php artisan passport:install
```

### Accès à l'application

- **API** : http://localhost:8000
- **Documentation API** : http://localhost:8000/api/documentation

## 🚀 Déploiement sur Render

### Configuration automatique

1. **Connecter votre repository GitHub à Render**
2. **Créer un nouveau service Web** avec les paramètres suivants :
   - **Runtime** : Docker
   - **Build Command** : `docker build -t om-pay .`
   - **Start Command** : `docker run -p $PORT:80 om-pay`

3. **Ajouter une base de données PostgreSQL** :
   - Plan : Free
   - Nom : `om_pay`

4. **Variables d'environnement** (configurées automatiquement via `render.yaml`) :
   - `APP_ENV=production`
   - `APP_KEY` (généré automatiquement)
   - `DB_CONNECTION=pgsql`
   - `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` (liés à la DB)

### Déploiement manuel

Si vous préférez configurer manuellement :

```bash
# 1. Build et push l'image Docker
docker build -t om-pay .
docker tag om-pay registry.render.com/om-pay
docker push registry.render.com/om-pay

# 2. Sur Render, créer un service Web avec :
# - Source : Docker
# - Image : registry.render.com/om-pay:latest
```

## 📚 API Documentation

### Authentification

#### Inscription d'un client
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

#### Connexion
```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "identifier": "john@example.com", // ou "+221771234569"
  "password": "password123"
}
```

### Réponse de succès
```json
{
  "success": true,
  "message": "Utilisateur créé avec succès. Vous pouvez maintenant vous connecter.",
  "timestamp": "2025-11-10T00:16:36.114173Z",
  "data": {
    "id": "uuid",
    "name": "John Doe",
    "email": "john@example.com",
    "telephone": "+221771234569",
    "role": "client",
    "compte": {
      "id": "uuid",
      "solde": 0
    }
  }
}
```

### Réponse d'erreur
```json
{
  "success": false,
  "message": "Validation failed",
  "timestamp": "2025-11-10T00:42:02.151884Z",
  "errors": {
    "telephone": ["Le numéro de téléphone doit être au format sénégalais (+221) 77/70/76/75/78 XXX XX XX."]
  }
}
```

## 🧪 Tests

```bash
# Exécuter tous les tests
docker-compose exec app php artisan test

# Tests spécifiques
docker-compose exec app php artisan test --filter AuthTest
```

## 🏗 Architecture

```
om-pay/
├── app/
│   ├── Http/Controllers/
│   │   ├── AuthController.php      # Authentification
│   │   └── ApiController.php       # Base API controller
│   ├── Models/
│   │   ├── User.php               # Utilisateur
│   │   ├── Compte.php             # Compte bancaire
│   │   ├── Transaction.php        # Transaction
│   │   └── Marchand.php           # Marchand
│   ├── Traits/
│   │   └── ApiResponseTrait.php   # Réponses API standardisées
│   └── Services/                  # Logique métier
├── database/
│   ├── migrations/               # Migrations DB
│   └── seeders/                  # Données de test
├── docker/
│   ├── Dockerfile                # Image Laravel
│   ├── Dockerfile.db             # Image PostgreSQL
│   └── docker-compose.yml        # Développement local
├── render.yaml                   # Configuration Render
└── start.sh                      # Script de démarrage
```

## 🔒 Sécurité

- **JWT Tokens** avec Laravel Passport
- **Validation stricte** des numéros sénégalais
- **Hashage des mots de passe** avec bcrypt
- **Protection CSRF** activée
- **CORS** configuré
- **Rate limiting** activé

## 📞 Support

Pour toute question ou problème :
- 📧 Email : support@om-pay.com
- 📱 Téléphone : +221 XX XXX XX XX
- 🐛 Issues : [GitHub Issues](https://github.com/username/om-pay/issues)

## 📄 License

Ce projet est sous licence MIT - voir le fichier [LICENSE](LICENSE) pour plus de détails.

---

<p align="center">Fait avec ❤️ pour la communauté sénégalaise</p>

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
