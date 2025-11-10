<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# OM-Pay API

API REST Laravel pour le système de paiement OM-Pay avec authentification OAuth2 via Passport.

## 🚀 Fonctionnalités

- **Authentification OAuth2** avec Laravel Passport
- **Inscription et connexion** des utilisateurs
- **Gestion des comptes** bancaires
- **Système de transactions** (dépôt, retrait, paiement, transfert)
- **Validation stricte** des numéros de téléphone sénégalais
- **API RESTful** avec réponses standardisées
- **Middleware de sécurité** (authentification, rôles, logging)

## 📋 Prérequis

- Docker & Docker Compose
- Git

## 🛠️ Installation et Configuration

### Développement Local

1. **Cloner le repository**
   ```bash
   git clone <repository-url>
   cd om-pay
   ```

2. **Configuration de l'environnement**
   ```bash
   cp .env.example .env
   # Modifier les variables d'environnement si nécessaire
   ```

3. **Lancer avec Docker Compose**
   ```bash
   docker compose up --build
   ```

4. **Accéder à l'API**
   - API: `http://localhost:8000`
   - Documentation: `http://localhost:8000/api/documentation`

### Déploiement sur Render

1. **Créer un compte Render** et lier votre repository GitHub
2. **Utiliser le fichier `render.yaml`** pour le déploiement automatique
3. **Configuration des variables d'environnement** dans Render Dashboard

## 📚 API Endpoints

### Authentification
- `POST /api/v1/auth/register` - Inscription d'un client
- `POST /api/v1/auth/login` - Connexion
- `POST /api/v1/auth/refresh` - Rafraîchir le token
- `POST /api/v1/auth/logout` - Déconnexion

### Utilisateur (Protégé)
- `GET /api/user` - Informations de l'utilisateur connecté

## 🔐 Authentification

L'API utilise **OAuth2 avec Laravel Passport** :

1. **Inscription** : Crée un compte utilisateur avec numéro de téléphone sénégalais
2. **Connexion** : Retourne `access_token` et `refresh_token`
3. **Utilisation** : Envoyer le token dans l'en-tête `Authorization: Bearer {token}`

### Format des numéros de téléphone
- Préfixes acceptés : 77, 70, 76, 75, 78
- Format : `+221771234569` ou `771234569` (normalisé automatiquement)

## 🗄️ Base de Données

- **PostgreSQL** pour la production
- **Tables principales** :
  - `users` - Utilisateurs
  - `comptes` - Comptes bancaires
  - `transactions` - Transactions financières
  - `marchands` - Marchands/Commerçants

## 🧪 Tests

```bash
# Exécuter les tests
php artisan test

# Tests spécifiques
php artisan test --filter AuthTest
```

## 📦 Déploiement

### Avec Docker (Production)
```bash
docker build -t om-pay .
docker run -p 80:80 om-pay
```

### Avec Render
1. Pousser le code sur GitHub
2. Créer un service Render avec le blueprint `render.yaml`
3. Configurer les variables d'environnement
4. Déployer automatiquement

## 🔧 Variables d'Environnement

```env
APP_NAME=OM-Pay
APP_ENV=production
APP_KEY=base64:key
APP_DEBUG=false
APP_URL=https://your-render-app.com

# Database
DB_CONNECTION=pgsql
DB_HOST=your-db-host
DB_PORT=5432
DB_DATABASE=om_pay
DB_USERNAME=your-username
DB_PASSWORD=your-password

# Passport
PASSPORT_PERSONAL_ACCESS_CLIENT_ID=1
PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET=your-secret
```

## 📖 Documentation API

### Inscription d'un client
```bash
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

### Connexion
```bash
POST /api/v1/auth/login
Content-Type: application/json

{
  "identifier": "john@example.com",
  "password": "password123"
}
```

## 🤝 Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📝 License

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

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
