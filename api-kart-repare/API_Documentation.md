# 🏎️ API Documentation - KartRepair

## 📋 Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Authentification & Rôles](#authentification--rôles)
3. [Format des réponses](#format-des-réponses)
4. [Gestion d'erreurs](#gestion-derreurs)
5. [Endpoints API](#endpoints-api)
   - [Authentification](#authentification-1)
   - [Utilisateurs](#utilisateurs)
   - [Pilotes](#pilotes)
   - [Karts](#karts)
   - [Statuts de demande](#statuts-de-demande)
   - [Demandes de réparation](#demandes-de-réparation)
   - [Produits](#produits)
   - [Produits de demande de réparation](#produits-de-demande-de-réparation)
6. [Modèles de données](#modèles-de-données)
7. [Validation & Règles](#validation--règles)
8. [Codes de statut](#codes-de-statut)
9. [Exemples d'intégration](#exemples-dintégration)

---

## 🌟 Vue d'ensemble

L'API KartRepair est une **API RESTful** construite avec **Laravel 11**, utilisant **Laravel Sanctum** pour l'authentification par tokens. Elle gère un système complet de réparation de karts incluant :

- **Gestion des utilisateurs** avec système de rôles et permissions
- **Gestion des pilotes** et de leurs informations personnelles
- **Gestion des karts** et de leur maintenance
- **Système de demandes de réparation** avec workflow complet
- **Gestion de l'inventaire** et des pièces détachées
- **Facturation** et suivi des coûts

**Informations techniques :**
- **Base URL:** `https://your-domain.com/api`
- **Version:** 1.0.0
- **Authentication:** Bearer Token (Laravel Sanctum)
- **Format:** JSON uniquement
- **Charset:** UTF-8
- **Timezone:** Europe/Paris

---

## 🔐 Authentification & Rôles

L'API utilise **Laravel Sanctum** avec un système de tokens Bearer et un contrôle d'accès basé sur les rôles (RBAC).

### Système de rôles

| Rôle | Description | Permissions |
|------|-------------|-------------|
| **admin** | Administrateur système | ✅ Toutes les permissions, gestion complète |
| **bureau_staff** | Personnel de bureau | ✅ Gestion réparations, clients, mécaniciens<br/>❌ Gestion admins |
| **mechanic** | Mécanicien | ✅ Consultation et mise à jour des réparations assignées<br/>❌ Création/suppression |
| **client** | Client | ✅ Consultation de ses propres données uniquement |

### Headers requis

```http
Authorization: Bearer {your-token}
Content-Type: application/json
Accept: application/json
```

### Abilities (permissions détaillées)

Les tokens incluent des **abilities** spécifiques selon le rôle :

**Admin :**
```json
["*"]
```

**Bureau Staff :**
```json
[
  "users:view", "users:create", "users:update",
  "repairs:view", "repairs:create", "repairs:update", 
  "invoices:view", "invoices:create", "invoices:update",
  "parts:view", "parts:create", "parts:update"
]
```

**Mechanic :**
```json
[
  "users:view-own", "repairs:view", "repairs:update",
  "parts:view", "parts:consume"
]
```

**Client :**
```json
[
  "users:view-own", "repairs:view-own", "invoices:view-own"
]
```

---

## 📊 Format des réponses

Toutes les réponses respectent un **format JSON cohérent** avec des structures standardisées.

### Réponse de succès
```json
{
    "message": "Description du succès",
    "data": {}, // ou [] pour les collections
    "meta": {
        "timestamp": "2025-09-03T12:00:00.000000Z",
        "version": "1.0",
        "request_id": "uuid-v4"
    }
}
```

### Réponse avec pagination
```json
{
    "data": [],
    "links": {
        "first": "https://api.example.com/users?page=1",
        "last": "https://api.example.com/users?page=5",
        "prev": null,
        "next": "https://api.example.com/users?page=2"
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "to": 15,
        "last_page": 5,
        "per_page": 15,
        "total": 67
    }
}
```

### Réponse de collection sans pagination
```json
{
    "data": [],
    "meta": {
        "total": 25,
        "count": 25,
        "timestamp": "2025-09-03T12:00:00.000000Z"
    }
}
```

---

## ⚠️ Gestion d'erreurs

### Codes d'erreur HTTP standardisés

| Code | Description | Usage |
|------|-------------|-------|
| **400** | Bad Request | Requête malformée, paramètres invalides |
| **401** | Unauthorized | Token manquant, expiré ou invalide |
| **403** | Forbidden | Permissions insuffisantes pour l'action |
| **404** | Not Found | Ressource inexistante ou supprimée |
| **409** | Conflict | Conflit de données (unicité, etc.) |
| **422** | Unprocessable Entity | Erreurs de validation |
| **429** | Too Many Requests | Rate limiting dépassé |
| **500** | Internal Server Error | Erreur serveur interne |

### Format des erreurs

**Erreur simple :**
```json
{
    "message": "Description de l'erreur",
    "error_code": "VALIDATION_FAILED",
    "timestamp": "2025-09-03T12:00:00.000000Z"
}
```

**Erreur avec détails de validation :**
```json
{
    "message": "Données de validation invalides",
    "error_code": "VALIDATION_FAILED",
    "errors": {
        "email": ["L'adresse email doit être valide"],
        "password": ["Le mot de passe doit contenir au moins 8 caractères"]
    },
    "timestamp": "2025-09-03T12:00:00.000000Z"
}
```

**Erreur d'autorisation :**
```json
{
    "message": "Vous n'avez pas l'autorisation d'effectuer cette action",
    "error_code": "INSUFFICIENT_PERMISSIONS",
    "required_role": "admin",
    "current_role": "client",
    "timestamp": "2025-09-03T12:00:00.000000Z"
}
```

---

## 🔗 Endpoints API

## Authentification

### POST /auth/login
Connexion utilisateur avec génération de token

**Permissions :** Public

**Paramètres (Body JSON) :**
```json
{
    "email": "user@example.com",        // required|email
    "password": "password123"           // required|string|min:6
}
```

**Validation :**
- Email doit être une adresse valide
- Mot de passe minimum 6 caractères
- Compte doit être actif (`is_active = true`)

**Réponse de succès (200) :**
```json
{
    "message": "Connexion réussie",
    "user": {
        "id": 1,
        "first_name": "John",
        "last_name": "Doe",
        "full_name": "John Doe",
        "email": "user@example.com",
        "role": "client",
        "role_label": "Client",
        "phone": "+33123456789",
        "address": "123 Rue Example",
        "company": "Example Corp",
        "is_active": true,
        "email_verified_at": "2025-01-01T12:00:00.000000Z",
        "last_login_at": "2025-09-03T12:00:00.000000Z",
        "created_at": "2025-01-01T10:00:00.000000Z",
        "updated_at": "2025-09-03T12:00:00.000000Z"
    },
    "token": "1|abcd1234efgh5678ijkl9012mnop3456qrst7890",
    "token_type": "Bearer"
}
```

**Erreurs possibles :**
- `401` : Identifiants incorrects
- `403` : Compte désactivé
- `422` : Validation échouée

### POST /auth/register
Inscription d'un nouvel utilisateur (rôle client par défaut)

**Permissions :** Public

**Paramètres (Body JSON) :**
```json
{
    "first_name": "John",               // required|string|max:255
    "last_name": "Doe",                // required|string|max:255
    "email": "user@example.com",       // required|email|unique:users
    "password": "password123",         // required|string|min:8|confirmed
    "password_confirmation": "password123",
    "phone": "+33123456789",           // nullable|string|max:20
    "address": "123 Rue Example",      // nullable|string|max:1000
    "company": "Example Corp"          // nullable|string|max:255
}
```

**Réponse de succès (201) :** Identique à `/auth/login`

### GET /auth/me
Profil de l'utilisateur connecté

**Permissions :** Authentifié

**Headers requis :** `Authorization: Bearer {token}`

**Réponse de succès (200) :**
```json
{
    "message": "Profil récupéré avec succès",
    "user": {
        // Structure utilisateur complète
    }
}
```

### POST /auth/logout
Déconnexion et révocation du token actuel

**Permissions :** Authentifié

**Headers requis :** `Authorization: Bearer {token}`

**Réponse de succès (200) :**
```json
{
    "message": "Déconnexion réussie"
}
```

### POST /auth/refresh
Rafraîchissement du token (révoque l'ancien et crée un nouveau)

**Permissions :** Authentifié

**Headers requis :** `Authorization: Bearer {token}`

**Réponse de succès (200) :** Identique à `/auth/login`

---

## 👥 Utilisateurs

### GET /users
Liste des utilisateurs avec pagination et filtres

**Permissions :** admin, bureau_staff

**Query Parameters :**
- `page` : Numéro de page (défaut: 1)
- `per_page` : Éléments par page (défaut: 15, max: 100)
- `search` : Recherche dans prénom, nom, email, entreprise
- `role` : Filtrer par rôle (client, mechanic, bureau_staff, admin)
- `is_active` : Filtrer par statut (true/false)
- `sort` : Colonne de tri (name, email, created_at, last_login_at) (défaut: created_at)
- `direction` : Direction du tri (asc, desc) (défaut: desc)

**Réponse de succès (200) :**
```json
{
    "data": [
        {
            "id": 1,
            "first_name": "John",
            "last_name": "Doe",
            "full_name": "John Doe",
            "email": "user@example.com",
            "role": "client",
            "role_label": "Client",
            "phone": "+33123456789",
            "address": "123 Rue Example",
            "company": "Example Corp",
            "is_active": true,
            "email_verified_at": "2025-01-01T12:00:00.000000Z",
            "last_login_at": "2025-09-03T12:00:00.000000Z",
            "created_at": "2025-01-01T10:00:00.000000Z",
            "updated_at": "2025-09-03T12:00:00.000000Z",
            "pilots_count": 2,
            "active_pilots_count": 2
        }
    ],
    "links": { /* pagination */ },
    "meta": { /* pagination */ }
}
```

### POST /users
Créer un nouvel utilisateur

**Permissions :** admin, bureau_staff

**Paramètres (Body JSON) :**
```json
{
    "first_name": "John",               // required|string|max:255
    "last_name": "Doe",                // required|string|max:255
    "email": "user@example.com",       // required|email|unique:users
    "password": "password123",         // required|string|min:8|confirmed
    "password_confirmation": "password123",
    "role": "client",                  // required|in:client,bureau_staff,mechanic,admin
    "phone": "+33123456789",           // nullable|string|max:20
    "address": "123 Rue Example",      // nullable|string|max:1000
    "company": "Example Corp",         // nullable|string|max:255
    "is_active": true                  // boolean (défaut: true)
}
```

**Réponse de succès (201) :**
```json
{
    "message": "Utilisateur créé avec succès",
    "data": {
        // Structure utilisateur complète
    }
}
```

### GET /users/{id}
Détails d'un utilisateur spécifique

**Permissions :** admin, bureau_staff ou propriétaire

**Réponse de succès (200) :**
```json
{
    "message": "Utilisateur récupéré avec succès",
    "data": {
        // Structure utilisateur complète avec relations
        "pilots": [
            // Liste des pilotes de ce client
        ]
    }
}
```

### PUT /users/{id}
Modifier un utilisateur existant

**Permissions :** 
- admin : peut tout modifier
- bureau_staff : peut modifier clients et mécaniciens (pas role/is_active)
- propriétaire : peut modifier ses données personnelles uniquement

**Paramètres (Body JSON) :**
```json
{
    "first_name": "John Updated",      // sometimes|required|string|max:255
    "last_name": "Doe Updated",       // sometimes|required|string|max:255
    "email": "updated@example.com",   // sometimes|required|email|unique
    "role": "mechanic",               // admin uniquement
    "phone": "+33987654321",          // sometimes|nullable|string|max:20
    "address": "456 Rue Updated",     // sometimes|nullable|string|max:1000
    "company": "Updated Corp",        // sometimes|nullable|string|max:255
    "is_active": false,               // admin uniquement
    "password": "newpassword123",     // sometimes|string|min:8|confirmed
    "password_confirmation": "newpassword123"
}
```

### DELETE /users/{id}
Supprimer un utilisateur (soft delete)

**Permissions :** admin, bureau_staff

**Réponse de succès (200) :**
```json
{
    "message": "Utilisateur supprimé avec succès"
}
```

### GET /users/profile
Récupérer son propre profil

**Permissions :** Authentifié

**Headers requis :** `Authorization: Bearer {token}`

**Réponse de succès (200) :**
```json
{
    "message": "Profil récupéré avec succès",
    "data": {
        // Structure utilisateur complète
    }
}
```

### PUT /users/profile
Modifier son propre profil

**Permissions :** Authentifié

**Headers requis :** `Authorization: Bearer {token}`

**Paramètres (Body JSON) :**
```json
{
    "first_name": "John",             // sometimes|required|string|max:255
    "last_name": "Doe",              // sometimes|required|string|max:255
    "email": "user@example.com",     // sometimes|required|email|unique
    "phone": "+33123456789",         // sometimes|nullable|string|max:20
    "address": "123 Rue Example",    // sometimes|nullable|string|max:1000
    "company": "Example Corp",       // sometimes|nullable|string|max:255
    "password": "newpassword123",    // sometimes|string|min:8|confirmed
    "password_confirmation": "newpassword123"
}
```

### PATCH /users/{id}/toggle-status
Activer/Désactiver un utilisateur

**Permissions :** admin

**Réponse de succès (200) :**
```json
{
    "message": "Statut utilisateur modifié avec succès",
    "data": {
        "id": 1,
        "is_active": false,
        "updated_at": "2025-09-03T12:00:00.000000Z"
    }
}
```

### GET /users/statistics
Statistiques des utilisateurs

**Permissions :** admin

**Réponse de succès (200) :**
```json
{
    "message": "Statistiques récupérées avec succès",
    "data": {
        "total_users": 150,
        "active_users": 142,
        "inactive_users": 8,
        "by_role": {
            "client": 125,
            "mechanic": 15,
            "bureau_staff": 8,
            "admin": 2
        },
        "recent_registrations": 12,   // 7 derniers jours
        "recent_logins": 89,          // 7 derniers jours
        "never_logged_in": 15
    }
}
```

### GET /users/trashed
Liste des utilisateurs supprimés (soft delete)

**Permissions :** admin

**Réponse de succès (200) :**
```json
{
    "data": [
        {
            // Structure utilisateur avec deleted_at non null
            "deleted_at": "2025-09-01T10:00:00.000000Z"
        }
    ]
}
```

### PATCH /users/{id}/restore
Restaurer un utilisateur supprimé

**Permissions :** admin

**Réponse de succès (200) :**
```json
{
    "message": "Utilisateur restauré avec succès"
}
```

### DELETE /users/{id}/force-delete
Suppression définitive d'un utilisateur

**Permissions :** admin

**Réponse de succès (200) :**
```json
{
    "message": "Utilisateur supprimé définitivement"
}
```

---

## 👤 Pilotes

### GET /pilots
Liste des pilotes avec filtres et pagination

**Permissions :** 
- admin, bureau_staff : Tous les pilotes
- client : Uniquement ses propres pilotes

**Query Parameters :**
- `page`, `per_page`, `sort`, `direction` : Pagination standard
- `search` : Recherche dans prénom, nom
- `client_id` : Filtrer par client (admin/bureau_staff seulement)
- `is_active` : Filtrer par statut (true/false)

**Réponse de succès (200) :**
```json
{
    "data": [
        {
            "id": 1,
            "first_name": "Max",
            "last_name": "Verstappen",
            "full_name": "Max Verstappen",
            "date_of_birth": "1997-09-30",
            "license_number": "LIC123456",
            "license_expiry": "2025-12-31",
            "phone": "+33123456789",
            "email": "max@example.com",
            "address": "123 Racing Street",
            "emergency_contact_name": "Jos Verstappen",
            "emergency_contact_phone": "+33987654321",
            "medical_info": "Aucune allergie connue",
            "is_active": true,
            "client_id": 5,
            "created_at": "2025-01-01T10:00:00.000000Z",
            "updated_at": "2025-09-03T12:00:00.000000Z",
            "client": {
                "id": 5,
                "full_name": "Red Bull Racing",
                "email": "team@redbull.com"
            },
            "age": 27,
            "license_expires_soon": false,
            "karts_count": 3,
            "size_tshirt": "L",
            "size_pants": "M",
            "size_shoes": 42,
            "size_glove": "L",
            "size_suit": "L",
            "is_minor": false,
            "note": "Pilote expérimenté"
        }
    ],
    "links": { /* pagination */ },
    "meta": { /* pagination */ }
}
```

### POST /pilots
Créer un nouveau pilote

**Permissions :** 
- admin, bureau_staff : Peut créer pour n'importe quel client
- client : Peut créer uniquement pour lui-même

**Paramètres (Body JSON) :**
```json
{
    "client_id": 5,                           // required|exists:users,id
    "first_name": "Max",                      // required|string|max:255
    "last_name": "Verstappen",               // required|string|max:255
    "date_of_birth": "1997-09-30",          // required|date|before:today
    "license_number": "LIC123456",           // nullable|string|unique
    "license_expiry": "2025-12-31",         // nullable|date|after:today
    "phone": "+33123456789",                // nullable|string|max:255
    "email": "max@example.com",             // nullable|email|max:255
    "address": "123 Racing Street",         // nullable|string
    "emergency_contact_name": "Jos Verstappen",    // required|string|max:255
    "emergency_contact_phone": "+33987654321",    // required|string|max:255
    "medical_info": "Aucune allergie connue",     // nullable|string
    "size_tshirt": "L",                     // nullable|in:XS,S,M,L,XL,XXL
    "size_pants": "M",                      // nullable|in:XS,S,M,L,XL,XXL
    "size_shoes": 42,                       // nullable|integer|between:20,50
    "size_glove": "L",                      // nullable|in:XS,S,M,L,XL
    "size_suit": "L",                       // nullable|in:XS,S,M,L,XL,XXL
    "is_minor": false,                      // boolean
    "note": "Pilote expérimenté",          // nullable|string
    "is_active": true                       // boolean (défaut: true)
}
```

**Réponse de succès (201) :**
```json
{
    "message": "Pilote créé avec succès",
    "data": {
        // Structure pilote complète
    }
}
```

### GET /pilots/{id}
Détails d'un pilote spécifique

**Permissions :** 
- admin, bureau_staff : Tous les pilotes
- client : Uniquement ses propres pilotes

**Réponse de succès (200) :**
```json
{
    "message": "Pilote récupéré avec succès",
    "data": {
        // Structure pilote complète avec relations
        "karts": [
            // Liste des karts de ce pilote
        ]
    }
}
```

### PUT /pilots/{id}
Modifier un pilote existant

**Permissions :** 
- admin, bureau_staff : Tous les pilotes
- client : Uniquement ses propres pilotes

**Paramètres (Body JSON) :** Mêmes que POST mais avec `sometimes` au lieu de `required`

### DELETE /pilots/{id}
Supprimer un pilote (soft delete)

**Permissions :** 
- admin, bureau_staff : Tous les pilotes
- client : Uniquement ses propres pilotes

**Réponse de succès (200) :**
```json
{
    "message": "Pilote supprimé avec succès"
}
```

### GET /pilots/statistics
Statistiques des pilotes

**Permissions :** 
- admin, bureau_staff : Toutes les statistiques
- client : Statistiques de ses pilotes

**Réponse de succès (200) :**
```json
{
    "message": "Statistiques récupérées avec succès",
    "data": {
        "total_pilots": 87,
        "active_pilots": 82,
        "inactive_pilots": 5,
        "licenses_expiring_soon": 12,    // Moins de 30 jours
        "average_age": 28.5,
        "minors_count": 15,
        "by_client": {
            "Red Bull Racing": 3,
            "Mercedes AMG": 2
        },
        "by_age_group": {
            "under_18": 15,
            "18_25": 25,
            "26_35": 30,
            "over_35": 17
        }
    }
}
```

### Routes supplémentaires (soft delete)

**GET /pilots/trashed** - Liste des pilotes supprimés (admin)
**PATCH /pilots/{id}/restore** - Restaurer un pilote (admin)
**DELETE /pilots/{id}/force-delete** - Suppression définitive (admin)

---

## 🏎️ Karts

### GET /karts
Liste des karts avec filtres

**Permissions :** 
- admin, bureau_staff : Tous les karts
- client : Uniquement les karts de ses pilotes

**Query Parameters :**
- Pagination standard
- `search` : Recherche dans numéro châssis, marque, modèle
- `pilot_id` : Filtrer par pilote
- `brand` : Filtrer par marque
- `engine_type` : Filtrer par type moteur (2T, 4T, ELECTRIC)
- `is_active` : Filtrer par statut
- `year` : Filtrer par année

**Réponse de succès (200) :**
```json
{
    "data": [
        {
            "id": 1,
            "chassis_number": "RB19-001",
            "brand": "Red Bull",
            "model": "RB19",
            "year": 2023,
            "color": "Bleu/Rouge",
            "engine_type": "4T",
            "pilot_id": 1,
            "is_active": true,
            "last_maintenance": "2025-08-15",
            "next_maintenance": "2025-10-15",
            "created_at": "2025-01-01T10:00:00.000000Z",
            "updated_at": "2025-09-03T12:00:00.000000Z",
            "pilot": {
                "id": 1,
                "full_name": "Max Verstappen",
                "client": {
                    "id": 5,
                    "full_name": "Red Bull Racing"
                }
            },
            "full_identification": "RB19-001 - Red Bull RB19 (2023)",
            "maintenance_due_soon": false,      // Dans les 30 jours
            "maintenance_overdue": false,
            "repair_requests_count": 2,
            "note": "Configuration optimisée pour circuits rapides"
        }
    ]
}
```

### POST /karts
Créer un nouveau kart

**Permissions :** 
- admin, bureau_staff : Pour n'importe quel pilote
- client : Pour ses propres pilotes uniquement

**Paramètres (Body JSON) :**
```json
{
    "pilot_id": 1,                      // required|exists:pilots,id
    "brand": "Red Bull",                // required|string|max:255
    "model": "RB19",                   // required|string|max:255
    "chassis_number": "RB19-001",      // required|string|max:255|unique:karts
    "year": 2023,                      // required|integer|min:1950|max:current_year+1
    "color": "Bleu/Rouge",             // nullable|string|max:100
    "engine_type": "4T",               // nullable|in:2T,4T,ELECTRIC
    "is_active": true,                 // boolean (défaut: true)
    "note": "Configuration spéciale"    // nullable|string
}
```

### GET /karts/{id}
Détails d'un kart spécifique

**Permissions :** Selon propriété du pilote

**Réponse de succès (200) :**
```json
{
    "message": "Kart récupéré avec succès",
    "data": {
        // Structure kart complète avec relations
        "repair_requests": [
            // Historique des réparations
        ]
    }
}
```

### PUT /karts/{id}
Modifier un kart existant

**Permissions :** Selon propriété du pilote

**Paramètres (Body JSON) :** Mêmes que POST mais avec `sometimes`

### DELETE /karts/{id}
Supprimer un kart (soft delete)

**Permissions :** Selon propriété du pilote

### GET /karts/statistics
Statistiques des karts

**Permissions :** Selon rôle

**Réponse de succès (200) :**
```json
{
    "message": "Statistiques récupérées avec succès",
    "data": {
        "total_karts": 45,
        "active_karts": 42,
        "inactive_karts": 3,
        "maintenance_due_soon": 8,
        "maintenance_overdue": 2,
        "by_engine_type": {
            "2T": 15,
            "4T": 25,
            "ELECTRIC": 5
        },
        "by_year": {
            "2023": 20,
            "2022": 15,
            "2021": 10
        },
        "average_age": 2.1
    }
}
```

---

## 📊 Statuts de demande

### GET /request-statuses
Liste de tous les statuts de demande

**Permissions :** Authentifié (tous rôles)

**Réponse de succès (200) :**
```json
{
    "data": [
        {
            "id": 1,
            "name": "En attente",
            "hex_color": "#ffc107",
            "is_final": false,
            "created_at": "2025-01-01T10:00:00.000000Z",
            "updated_at": "2025-09-03T12:00:00.000000Z",
            "repair_requests_count": 15
        },
        {
            "id": 2,
            "name": "En cours",
            "hex_color": "#007bff",
            "is_final": false,
            "created_at": "2025-01-01T10:00:00.000000Z",
            "updated_at": "2025-09-03T12:00:00.000000Z",
            "repair_requests_count": 8
        },
        {
            "id": 3,
            "name": "Terminée",
            "hex_color": "#28a745",
            "is_final": true,
            "created_at": "2025-01-01T10:00:00.000000Z",
            "updated_at": "2025-09-03T12:00:00.000000Z",
            "repair_requests_count": 142
        }
    ]
}
```

### POST /request-statuses
Créer un nouveau statut

**Permissions :** admin, bureau_staff

**Paramètres (Body JSON) :**
```json
{
    "name": "En révision",               // required|string|max:50|unique
    "hex_color": "#ff6b35",             // required|regex:/^#[a-fA-F0-9]{6}$/
    "is_final": false                   // boolean (défaut: false)
}
```

**Réponse de succès (201) :**
```json
{
    "message": "Statut de demande créé avec succès",
    "data": {
        "id": 4,
        "name": "En révision",
        "hex_color": "#ff6b35",
        "is_final": false,
        "created_at": "2025-09-03T12:00:00.000000Z",
        "updated_at": "2025-09-03T12:00:00.000000Z"
    }
}
```

### GET /request-statuses/{id}
Détails d'un statut spécifique

**Permissions :** Authentifié

### PUT /request-statuses/{id}
Modifier un statut existant

**Permissions :** admin, bureau_staff

**Paramètres (Body JSON) :**
```json
{
    "name": "En révision technique",     // sometimes|string|max:50|unique
    "hex_color": "#ff8c35",             // sometimes|regex:/^#[a-fA-F0-9]{6}$/
    "is_final": false                   // sometimes|boolean
}
```

### DELETE /request-statuses/{id}
Supprimer un statut (soft delete)

**Permissions :** admin, bureau_staff

**Notes :** Ne peut pas supprimer un statut utilisé par des demandes actives

---

## 🔧 Demandes de réparation

### GET /repair-requests
Liste des demandes de réparation avec filtres avancés

**Permissions :** 
- admin, bureau_staff : Toutes les demandes
- mechanic : Demandes assignées + demandes créées
- client : Uniquement ses propres demandes

**Query Parameters :**
- Pagination standard
- `search` : Recherche dans titre, description, référence
- `status_id` : Filtrer par statut
- `priority` : Filtrer par priorité (high, medium, low)
- `kart_id` : Filtrer par kart
- `assigned_to` : Filtrer par mécanicien assigné
- `created_by` : Filtrer par créateur
- `completion_status` : Filtrer par statut completion (pending, in_progress, completed)
- `date_from`, `date_to` : Filtrer par période de création

**Réponse de succès (200) :**
```json
{
    "data": [
        {
            "id": 1,
            "reference": "REP-2025-001",
            "title": "Réparation moteur",
            "description": "Problème de surchauffe moteur détecté lors des essais libres",
            "priority": "high",
            "priority_label": "Haute",
            "estimated_cost": 1500.00,
            "actual_cost": 1350.00,
            "estimated_hours": 8.0,
            "actual_hours": 7.5,
            "estimated_completion": "2025-09-10T18:00:00.000000Z",
            "completion_percentage": 75,
            "is_completed": false,
            "is_overdue": false,
            "kart_id": 1,
            "client_id": 5,
            "created_by": 2,
            "assigned_to": 3,
            "status_id": 2,
            "started_at": "2025-09-01T14:00:00.000000Z",
            "completed_at": null,
            "created_at": "2025-09-01T09:00:00.000000Z",
            "updated_at": "2025-09-03T12:00:00.000000Z",
            "kart": {
                "id": 1,
                "chassis_number": "RB19-001",
                "brand": "Red Bull",
                "model": "RB19",
                "full_identification": "RB19-001 - Red Bull RB19 (2023)"
            },
            "client": {
                "id": 5,
                "full_name": "Red Bull Racing",
                "email": "team@redbull.com"
            },
            "assignedUser": {
                "id": 3,
                "full_name": "John Mechanic",
                "role": "mechanic"
            },
            "createdBy": {
                "id": 2,
                "full_name": "Jane Admin",
                "role": "admin"
            },
            "status": {
                "id": 2,
                "name": "En cours",
                "hex_color": "#007bff",
                "is_final": false
            },
            "days_since_created": 2,
            "products_count": 3,
            "total_products_cost": 450.00,
            "workflow_status": "in_progress"
        }
    ]
}
```

### POST /repair-requests
Créer une nouvelle demande de réparation

**Permissions :** admin, bureau_staff, client (pour ses karts)

**Paramètres (Body JSON) :**
```json
{
    "kart_id": 1,                        // required|integer|exists:karts,id
    "title": "Réparation moteur",        // required|string|max:255|min:3
    "description": "Description détaillée du problème", // nullable|string|max:5000
    "priority": "high",                  // required|in:low,medium,high
    "status_id": 1,                     // required|integer|exists:request_statuses,id
    "estimated_cost": 1500.00,          // required|numeric|min:0|max:99999999.99
    "actual_cost": null,                // nullable|numeric|min:0|max:99999999.99
    "estimated_hours": 8.0,             // nullable|numeric|min:0|max:999.99
    "estimated_completion": "2025-09-10T18:00:00Z", // nullable|date|after_or_equal:today
    "assigned_to": 3                    // nullable|integer|exists:users,id
}
```

**Réponse de succès (201) :**
```json
{
    "message": "Demande de réparation créée avec succès",
    "data": {
        // Structure complète de la demande créée
        "reference": "REP-2025-055",  // Auto-généré
        "created_by": 2               // Utilisateur connecté
    }
}
```

### GET /repair-requests/{id}
Détails d'une demande spécifique

**Permissions :** Selon propriété/assignation

**Réponse de succès (200) :**
```json
{
    "message": "Demande de réparation récupérée avec succès",
    "data": {
        // Structure complète avec relations et historique
        "products": [
            // Liste des produits associés
        ],
        "timeline": [
            // Historique des modifications
        ]
    }
}
```

### PUT /repair-requests/{id}
Modifier une demande existante

**Permissions :** admin, bureau_staff, créateur

**Paramètres (Body JSON) :** Mêmes que POST avec `sometimes`

### DELETE /repair-requests/{id}
Supprimer une demande (soft delete)

**Permissions :** admin, bureau_staff, créateur

### PATCH /repair-requests/{id}/start
Démarrer une réparation

**Permissions :** admin, bureau_staff

**Paramètres (Body JSON) :**
```json
{
    "assigned_to": 3,                   // required|integer|exists:users,id (role: mechanic)
    "notes": "Réparation assignée à John pour traitement prioritaire" // nullable|string
}
```

**Réponse de succès (200) :**
```json
{
    "message": "Réparation démarrée avec succès",
    "data": {
        "started_at": "2025-09-03T14:30:00.000000Z",
        "assigned_to": 3,
        "status_updated": true
    }
}
```

### PATCH /repair-requests/{id}/complete
Terminer une réparation

**Permissions :** admin, bureau_staff, mécanicien assigné

**Paramètres (Body JSON) :**
```json
{
    "actual_cost": 1350.00,             // nullable|numeric|min:0
    "actual_hours": 7.5,               // nullable|numeric|min:0
    "completion_notes": "Réparation terminée avec succès. Tests effectués." // nullable|string
}
```

**Réponse de succès (200) :**
```json
{
    "message": "Réparation terminée avec succès",
    "data": {
        "completed_at": "2025-09-03T16:45:00.000000Z",
        "is_completed": true,
        "final_cost": 1350.00,
        "duration_hours": 7.5
    }
}
```

### PATCH /repair-requests/{id}/assign
Assigner/Réassigner un mécanicien

**Permissions :** admin, bureau_staff

**Paramètres (Body JSON) :**
```json
{
    "assigned_to": 4,                   // required|integer|exists:users,id (role: mechanic)
    "reason": "Réassignation pour spécialisation moteur" // nullable|string
}
```

### GET /repair-requests/statistics
Statistiques des demandes de réparation

**Permissions :** admin, bureau_staff

**Réponse de succès (200) :**
```json
{
    "message": "Statistiques récupérées avec succès",
    "data": {
        "total_requests": 245,
        "by_status": {
            "En attente": 25,
            "En cours": 45,
            "Terminée": 175
        },
        "by_priority": {
            "high": 45,
            "medium": 120,
            "low": 80
        },
        "average_completion_days": 4.2,
        "total_revenue": 125750.00,
        "overdue_requests": 8,
        "completion_rate": 89.5,
        "average_cost": 850.30,
        "this_month": {
            "new_requests": 18,
            "completed_requests": 22,
            "revenue": 18750.00
        }
    }
}
```

---

## 📦 Produits

### GET /products
Liste des produits avec filtres et gestion de stock

**Permissions :** 
- admin, bureau_staff : Tous les produits avec gestion
- mechanic : Consultation uniquement
- client : Consultation limitée

**Query Parameters :**
- Pagination standard
- `search` : Recherche dans nom, référence, marque, modèle
- `category` : Filtrer par catégorie
- `brand` : Filtrer par marque
- `unity` : Filtrer par unité (Piece, Liters, Hours, Kg)
- `stock_status` : Filtrer par statut stock (in_stock, low_stock, out_of_stock)
- `price_min`, `price_max` : Filtrer par fourchette de prix
- `is_active` : Filtrer par statut actif

**Réponse de succès (200) :**
```json
{
    "data": [
        {
            "id": 1,
            "name": "Pneu avant Michelin Pilot Sport",
            "ref": "MICH-PS-F-001",
            "description": "Pneu avant haute performance pour conditions sèches",
            "category": "Pneumatiques",
            "brand": "Michelin",
            "model": "Pilot Sport",
            "unity": "Piece",
            "price": 150.00,
            "stock_quantity": 25,
            "min_stock": 10,
            "max_stock": 100,
            "is_active": true,
            "created_at": "2025-01-01T10:00:00.000000Z",
            "updated_at": "2025-09-03T12:00:00.000000Z",
            "stock_status": "in_stock",           // in_stock, low_stock, out_of_stock
            "stock_level": "normal",             // critical, low, normal, high
            "needs_restock": false,
            "stock_value": 3750.00,             // quantity * price
            "price_formatted": "150,00 €",
            "days_until_stockout": null,        // Estimation basée sur consommation
            "usage_count": 45,                  // Nombre d'utilisations dans réparations
            "last_used_at": "2025-09-01T15:30:00.000000Z"
        }
    ]
}
```

### POST /products
Créer un nouveau produit

**Permissions :** admin, bureau_staff

**Paramètres (Body JSON) :**
```json
{
    "name": "Pneu avant Michelin Pilot Sport",    // required|string|max:255
    "ref": "MICH-PS-F-001",                      // required|string|max:100|unique
    "description": "Pneu avant haute performance", // nullable|string
    "category": "Pneumatiques",                   // required|string|max:100
    "brand": "Michelin",                         // nullable|string|max:100
    "model": "Pilot Sport",                      // nullable|string|max:100
    "unity": "Piece",                           // required|in:Piece,Liters,Hours,Kg
    "price": 150.00,                           // required|numeric|min:0|max:99999999.99
    "stock_quantity": 25,                      // required|integer|min:0|max:999999
    "min_stock": 10,                          // required|integer|min:0|max:stock_quantity
    "max_stock": 100,                         // required|integer|min:stock_quantity
    "is_active": true                         // boolean (défaut: true)
}
```

**Réponse de succès (201) :**
```json
{
    "message": "Produit créé avec succès",
    "data": {
        // Structure produit complète
    }
}
```

### GET /products/{id}
Détails d'un produit spécifique

**Permissions :** Authentifié

**Réponse de succès (200) :**
```json
{
    "message": "Produit récupéré avec succès",
    "data": {
        // Structure produit complète avec historique
        "stock_movements": [
            // Historique des mouvements de stock
        ],
        "recent_usage": [
            // Utilisations récentes dans les réparations
        ]
    }
}
```

### PUT /products/{id}
Modifier un produit existant

**Permissions :** admin, bureau_staff

**Paramètres (Body JSON) :** Mêmes que POST avec `sometimes`

### DELETE /products/{id}
Supprimer un produit (soft delete)

**Permissions :** admin, bureau_staff

**Notes :** Ne peut pas supprimer un produit utilisé dans des réparations actives

### PATCH /products/{id}/stock
Mettre à jour le stock d'un produit

**Permissions :** admin, bureau_staff

**Paramètres (Body JSON) :**
```json
{
    "operation": "add",                 // required|in:add,subtract,set
    "quantity": 10,                    // required|integer|min:1
    "reason": "Réapprovisionnement fournisseur Michelin" // required|string|max:255
}
```

**Réponse de succès (200) :**
```json
{
    "message": "Stock mis à jour avec succès",
    "data": {
        "old_quantity": 25,
        "new_quantity": 35,
        "operation": "add",
        "quantity_changed": 10,
        "reason": "Réapprovisionnement fournisseur Michelin",
        "updated_by": 2,
        "updated_at": "2025-09-03T14:30:00.000000Z",
        "new_stock_status": "in_stock",
        "stock_value_change": 1500.00
    }
}
```

### GET /products/statistics
Statistiques détaillées des produits et stocks

**Permissions :** admin, bureau_staff

**Réponse de succès (200) :**
```json
{
    "message": "Statistiques récupérées avec succès",
    "data": {
        "inventory_overview": {
            "total_products": 157,
            "active_products": 142,
            "inactive_products": 15,
            "total_stock_value": 145750.50,
            "total_stock_quantity": 2847
        },
        "stock_status": {
            "in_stock": 128,
            "low_stock": 12,
            "out_of_stock": 2,
            "needs_restock": 14
        },
        "by_category": {
            "Pneumatiques": {
                "count": 45,
                "stock_value": 52500.00,
                "low_stock": 5
            },
            "Moteur": {
                "count": 38,
                "stock_value": 48750.00,
                "low_stock": 3
            },
            "Carrosserie": {
                "count": 32,
                "stock_value": 28500.00,
                "low_stock": 2
            }
        },
        "by_unity": {
            "Piece": 89,
            "Liters": 34,
            "Hours": 12,
            "Kg": 22
        },
        "top_used_products": [
            {
                "id": 5,
                "name": "Huile moteur Castrol",
                "usage_count": 125,
                "stock_quantity": 8
            }
        ],
        "movement_summary": {
            "this_month": {
                "additions": 245,
                "consumptions": 189,
                "adjustments": 12
            }
        }
    }
}
```

### GET /products/low-stock
Produits nécessitant un réapprovisionnement

**Permissions :** admin, bureau_staff

**Réponse de succès (200) :**
```json
{
    "data": [
        {
            "id": 5,
            "name": "Huile moteur Castrol GTX",
            "ref": "CASTROL-GTX-5W30",
            "current_stock": 3,
            "min_stock": 10,
            "max_stock": 50,
            "stock_deficit": 7,
            "recommended_order": 25,
            "days_until_stockout": 5,    // Basé sur consommation moyenne
            "average_monthly_usage": 18,
            "last_restock": "2025-08-01T10:00:00.000000Z",
            "supplier_info": "Castrol Professional",
            "estimated_cost": 375.00,    // recommended_order * unit_price
            "priority": "urgent"         // urgent, high, medium, low
        }
    ]
}
```

### Routes de compatibilité (legacy)

**POST /products/{id}/stock/add** - Ajouter au stock (utilise PATCH /stock avec operation=add)
**POST /products/{id}/stock/reduce** - Retirer du stock (utilise PATCH /stock avec operation=subtract)

---

## 🔩 Produits de demande de réparation

### GET /repair-request-products
Liste des produits associés aux demandes de réparation avec workflow complet

**Permissions :** 
- admin, bureau_staff : Tous les produits
- mechanic : Produits des réparations assignées
- client : Produits de ses propres demandes

**Query Parameters :**
- Pagination standard
- `repair_request_id` : Filtrer par demande de réparation
- `product_id` : Filtrer par produit
- `priority` : Filtrer par priorité (high, medium, low)
- `workflow_status` : Filtrer par étape workflow (pending, invoiced, completed, approved)
- `invoiced` : Filtrer par facturation (true/false)
- `completed` : Filtrer par completion (true/false)
- `approved` : Filtrer par approbation (true/false)
- `search` : Recherche dans notes et informations produit

**Réponse de succès (200) :**
```json
{
    "data": [
        {
            "id": 1,
            "repair_request_id": 1,
            "product_id": 5,
            "quantity": 2,
            "unit_price": 150.00,
            "total_price": 300.00,
            "priority": "high",
            "priority_label": "Haute",
            "note": "Pneus à changer d'urgence suite à usure anormale",
            
            // Workflow Status
            "workflow_status": "invoiced",      // pending, invoiced, completed, approved
            "is_invoiced": true,
            "is_completed": false,
            "is_approved": false,
            
            // Workflow Capabilities
            "can_be_invoiced": false,
            "can_be_completed": true,
            "can_be_approved": false,
            "can_revert_invoice": true,
            "can_revert_completion": false,
            
            // Workflow Timestamps
            "invoiced_at": "2025-09-02T10:00:00.000000Z",
            "completed_at": null,
            "approved_at": null,
            "created_at": "2025-09-01T14:00:00.000000Z",
            "updated_at": "2025-09-02T10:00:00.000000Z",
            
            // Relations
            "repair_request": {
                "id": 1,
                "reference": "REP-2025-001",
                "title": "Réparation moteur",
                "priority": "high",
                "status": {
                    "name": "En cours",
                    "hex_color": "#007bff"
                }
            },
            "product": {
                "id": 5,
                "name": "Pneu avant Michelin Pilot Sport",
                "ref": "MICH-PS-F-001",
                "category": "Pneumatiques",
                "brand": "Michelin",
                "model": "Pilot Sport",
                "unity": "Piece",
                "current_price": 150.00,
                "stock_quantity": 23,
                "is_active": true
            },
            
            // Workflow Users
            "invoiced_by_user": {
                "id": 2,
                "full_name": "Jane Admin",
                "role": "admin"
            },
            "completed_by_user": null,
            "approved_by_user": null,
            
            // Computed Fields
            "days_since_created": 2,
            "days_since_invoiced": 1,
            "processing_time": null,
            "total_cost_formatted": "300,00 €",
            "workflow_progress": 33,        // Percentage (0-100)
            "workflow_next_step": "complete",
            "estimated_completion": "2025-09-04T16:00:00.000000Z"
        }
    ]
}
```

### POST /repair-request-products
Ajouter un produit à une demande de réparation

**Permissions :** admin, bureau_staff

**Paramètres (Body JSON) :**
```json
{
    "repair_request_id": 1,                    // required|integer|exists:repair_requests,id
    "product_id": 5,                          // required|integer|exists:products,id
    "quantity": 2,                           // required|integer|min:1|max:1000
    "priority": "high",                      // required|in:high,medium,low
    "note": "Pneus à changer d'urgence",    // nullable|string|max:1000
    "unit_price": 150.00                    // nullable|numeric|min:0 (défaut: prix produit)
}
```

**Validation spéciale :**
- Vérification unicité : Un produit ne peut être ajouté qu'une fois par demande
- Vérification stock : Quantité disponible suffisante
- Vérification statut : Demande de réparation non terminée

**Réponse de succès (201) :**
```json
{
    "message": "Produit ajouté à la demande de réparation avec succès",
    "data": {
        // Structure complète du produit créé
        "stock_reserved": 2,
        "remaining_stock": 23
    }
}
```

### GET /repair-request-products/{id}
Détails d'un produit de demande spécifique

**Permissions :** Selon propriété de la demande

**Réponse de succès (200) :**
```json
{
    "message": "Produit de demande récupéré avec succès",
    "data": {
        // Structure complète avec historique workflow
        "workflow_history": [
            {
                "action": "created",
                "user": "Jane Admin",
                "timestamp": "2025-09-01T14:00:00.000000Z",
                "notes": "Produit ajouté à la demande"
            },
            {
                "action": "invoiced",
                "user": "Jane Admin", 
                "timestamp": "2025-09-02T10:00:00.000000Z",
                "notes": "Facturé au client"
            }
        ]
    }
}
```

### PUT /repair-request-products/{id}
Modifier un produit de demande

**Permissions :** admin, bureau_staff

**Notes :** Modifications limitées selon l'état workflow

**Paramètres (Body JSON) :**
```json
{
    "quantity": 3,                          // sometimes|integer|min:1|max:1000
    "priority": "medium",                   // sometimes|in:high,medium,low
    "note": "Quantité mise à jour",        // sometimes|nullable|string|max:1000
    "unit_price": 145.00                   // sometimes|nullable|numeric|min:0
}
```

### DELETE /repair-request-products/{id}
Supprimer un produit de demande

**Permissions :** admin, bureau_staff

**Notes :** Suppression possible uniquement si pas encore facturé

### PATCH /repair-request-products/{id}/invoice
Facturer un produit (étape 1 du workflow)

**Permissions :** admin, bureau_staff

**Conditions :**
- Statut actuel : `pending`
- Stock suffisant disponible
- Demande de réparation active

**Réponse de succès (200) :**
```json
{
    "message": "Produit facturé avec succès",
    "data": {
        "is_invoiced": true,
        "invoiced_at": "2025-09-03T14:30:00.000000Z",
        "invoiced_by": 2,
        "workflow_status": "invoiced",
        "workflow_progress": 33,
        "next_step": "complete",
        "stock_impact": "reserved"
    }
}
```

### PATCH /repair-request-products/{id}/complete
Marquer un produit comme installé/utilisé (étape 2 du workflow)

**Permissions :** admin, bureau_staff, mécanicien assigné à la demande

**Conditions :**
- Statut actuel : `invoiced`
- Stock réservé disponible

**Paramètres optionnels (Body JSON) :**
```json
{
    "completion_note": "Installation terminée sans problème, tests OK" // nullable|string|max:500
}
```

**Réponse de succès (200) :**
```json
{
    "message": "Produit marqué comme terminé avec succès",
    "data": {
        "is_completed": true,
        "completed_at": "2025-09-03T16:45:00.000000Z",
        "completed_by": 3,
        "workflow_status": "completed",
        "workflow_progress": 66,
        "next_step": "approve",
        "stock_impact": "consumed",
        "remaining_stock": 23
    }
}
```

### PATCH /repair-request-products/{id}/approve
Approuver un produit (étape 3 finale du workflow)

**Permissions :** admin, bureau_staff

**Conditions :**
- Statut actuel : `completed`
- Validation qualité OK

**Réponse de succès (200) :**
```json
{
    "message": "Produit approuvé avec succès",
    "data": {
        "is_approved": true,
        "approved_at": "2025-09-03T17:00:00.000000Z",
        "approved_by": 2,
        "workflow_status": "approved",
        "workflow_progress": 100,
        "workflow_completed": true,
        "total_processing_time": "3 jours 3 heures",
        "final_validation": true
    }
}
```

### PATCH /repair-request-products/{id}/revert-invoice
Annuler la facturation (admin seulement)

**Permissions :** admin

**Conditions :** Pas encore complété

**Réponse de succès (200) :**
```json
{
    "message": "Facturation annulée avec succès",
    "data": {
        "is_invoiced": false,
        "invoiced_at": null,
        "workflow_status": "pending",
        "stock_impact": "released"
    }
}
```

### PATCH /repair-request-products/{id}/revert-completion
Annuler la completion (admin seulement)

**Permissions :** admin

**Conditions :** Pas encore approuvé

**Réponse de succès (200) :**
```json
{
    "message": "Completion annulée avec succès",
    "data": {
        "is_completed": false,
        "completed_at": null,
        "workflow_status": "invoiced",
        "stock_impact": "re_reserved"
    }
}
```

### GET /repair-request-products/statistics
Statistiques détaillées des produits de demandes

**Permissions :** admin, bureau_staff

**Réponse de succès (200) :**
```json
{
    "message": "Statistiques récupérées avec succès",
    "data": {
        "workflow_overview": {
            "total_products": 245,
            "by_status": {
                "pending": 45,
                "invoiced": 38,
                "completed": 89,
                "approved": 73
            },
            "completion_rate": 66.1
        },
        "by_priority": {
            "high": 52,
            "medium": 134,
            "low": 59
        },
        "financial": {
            "total_value": 45750.50,
            "invoiced_value": 35220.30,
            "completed_value": 28450.80,
            "approved_value": 22180.60
        },
        "performance": {
            "average_processing_days": 3.2,
            "average_invoice_to_completion": 1.8,
            "average_completion_to_approval": 1.4,
            "workflow_efficiency": 87.5
        },
        "top_products": [
            {
                "product": "Pneu avant Michelin",
                "usage_count": 45,
                "total_value": 6750.00
            }
        ],
        "monthly_trends": {
            "current_month": {
                "new_products": 25,
                "completed": 32,
                "approved": 28
            },
            "previous_month": {
                "new_products": 22,
                "completed": 28,
                "approved": 31
            }
        }
    }
}
```

---

## 📋 Modèles de données

### Structure Utilisateur (User)
```json
{
    "id": "integer (PK, auto-increment)",
    "first_name": "string (required, max:255)",
    "last_name": "string (required, max:255)",
    "full_name": "string (computed: first_name + ' ' + last_name)",
    "email": "string (required, unique, email format, max:255)",
    "email_verified_at": "datetime|null",
    "password": "string (hashed, min:8 characters)",
    "role": "enum (client|bureau_staff|mechanic|admin)",
    "role_label": "string (computed, human-readable role)",
    "phone": "string|null (max:20)",
    "address": "string|null (max:1000)",
    "company": "string|null (max:255)",
    "is_active": "boolean (default: true)",
    "last_login_at": "datetime|null",
    "remember_token": "string|null",
    "deleted_at": "datetime|null (soft delete)",
    "created_at": "datetime",
    "updated_at": "datetime",
    
    // Relations
    "pilots": "hasMany (Pilot) - if role is client",
    "created_repair_requests": "hasMany (RepairRequest) - via created_by",
    "assigned_repair_requests": "hasMany (RepairRequest) - via assigned_to",
    "tokens": "hasMany (PersonalAccessToken) - Sanctum tokens"
}
```

### Structure Pilote (Pilot)
```json
{
    "id": "integer (PK, auto-increment)",
    "client_id": "integer (FK to users.id, required)",
    "first_name": "string (required, max:255)",
    "last_name": "string (required, max:255)",
    "full_name": "string (computed)",
    "date_of_birth": "date (required, before:today)",
    "license_number": "string|null (unique, max:50)",
    "license_expiry": "date|null (after:today)",
    "phone": "string|null (max:255)",
    "email": "string|null (email, max:255)",
    "address": "text|null",
    "emergency_contact_name": "string (required, max:255)",
    "emergency_contact_phone": "string (required, max:255)",
    "medical_info": "text|null",
    "size_tshirt": "enum|null (XS|S|M|L|XL|XXL)",
    "size_pants": "enum|null (XS|S|M|L|XL|XXL)",
    "size_shoes": "integer|null (between:20,50)",
    "size_glove": "enum|null (XS|S|M|L|XL)",
    "size_suit": "enum|null (XS|S|M|L|XL|XXL)",
    "is_minor": "boolean (computed from date_of_birth)",
    "note": "text|null",
    "deleted_at": "datetime|null",
    "created_at": "datetime",
    "updated_at": "datetime",
    
    // Computed Attributes
    "age": "integer (computed from date_of_birth)",
    "license_expires_soon": "boolean (within 30 days)",
    
    // Relations
    "client": "belongsTo (User)",
    "karts": "hasMany (Kart)"
}
```

### Structure Kart (Kart)
```json
{
    "id": "integer (PK, auto-increment)",
    "pilot_id": "integer (FK to pilots.id, required)",
    "brand": "string (required, max:255)",
    "model": "string (required, max:255)", 
    "chassis_number": "string (required, unique, max:255)",
    "year": "integer (required, between:1950,current_year+1)",
    "color": "string|null (max:100)",
    "engine_type": "enum|null (2T|4T|ELECTRIC)",
    "note": "text|null",
    "is_active": "boolean (default: true)",
    "deleted_at": "datetime|null",
    "created_at": "datetime",
    "updated_at": "datetime",
    
    // Computed Attributes
    "full_identification": "string (chassis_number - brand model (year))",
    "age_years": "integer (current_year - year)",
    
    // Relations
    "pilot": "belongsTo (Pilot)",
    "repair_requests": "hasMany (RepairRequest)"
}
```

### Structure Statut de Demande (RequestStatus)
```json
{
    "id": "integer (PK, auto-increment)",
    "name": "string (required, unique, max:50)",
    "hex_color": "string (required, regex: /^#[a-fA-F0-9]{6}$/)",
    "is_final": "boolean (default: false)",
    "deleted_at": "datetime|null",
    "created_at": "datetime",
    "updated_at": "datetime",
    
    // Relations
    "repair_requests": "hasMany (RepairRequest)"
}
```

### Structure Demande de Réparation (RepairRequest)
```json
{
    "id": "integer (PK, auto-increment)",
    "reference": "string (auto-generated, unique, format: REP-YYYY-###)",
    "kart_id": "integer (FK to karts.id, required)",
    "title": "string (required, max:255, min:3)",
    "description": "text|null (max:5000)",
    "priority": "enum (low|medium|high, required)",
    "priority_label": "string (computed, human-readable)",
    "status_id": "integer (FK to request_statuses.id, required)",
    "created_by": "integer (FK to users.id, auto-filled)",
    "assigned_to": "integer|null (FK to users.id, mechanic role)",
    "estimated_cost": "decimal(10,2) (required, min:0)",
    "actual_cost": "decimal(10,2)|null (min:0)",
    "estimated_hours": "decimal(5,2)|null (min:0, max:999.99)",
    "actual_hours": "decimal(5,2)|null (min:0, max:999.99)",
    "estimated_completion": "datetime|null (after_or_equal:today)",
    "started_at": "datetime|null (before_or_equal:now)",
    "completed_at": "datetime|null (after_or_equal:started_at)",
    "deleted_at": "datetime|null",
    "created_at": "datetime",
    "updated_at": "datetime",
    
    // Computed Attributes
    "is_completed": "boolean (completed_at is not null)",
    "is_overdue": "boolean (estimated_completion < now and not completed)",
    "days_since_created": "integer",
    "duration_days": "integer|null (completed_at - started_at)",
    "completion_percentage": "integer (0-100, based on products completion)",
    
    // Relations
    "kart": "belongsTo (Kart)",
    "status": "belongsTo (RequestStatus)",
    "createdBy": "belongsTo (User) - via created_by",
    "assignedUser": "belongsTo (User) - via assigned_to",
    "products": "hasMany (RepairRequestProduct)",
    "client": "belongsTo (User) - via kart->pilot->client"
}
```

### Structure Produit (Product)
```json
{
    "id": "integer (PK, auto-increment)",
    "name": "string (required, max:255)",
    "ref": "string (required, unique, max:100)",
    "description": "text|null",
    "category": "string (required, max:100)",
    "brand": "string|null (max:100)",
    "model": "string|null (max:100)",
    "unity": "enum (Piece|Liters|Hours|Kg, required)",
    "price": "decimal(10,2) (required, min:0)",
    "stock_quantity": "integer (required, min:0, max:999999)",
    "min_stock": "integer (required, min:0)",
    "max_stock": "integer (required, min:stock_quantity)",
    "is_active": "boolean (default: true)",
    "deleted_at": "datetime|null",
    "created_at": "datetime",
    "updated_at": "datetime",
    
    // Computed Attributes
    "stock_status": "enum (in_stock|low_stock|out_of_stock)",
    "stock_level": "enum (critical|low|normal|high)",
    "needs_restock": "boolean (stock_quantity <= min_stock)",
    "stock_value": "decimal (stock_quantity * price)",
    "price_formatted": "string (formatted with currency)",
    
    // Relations
    "repair_request_products": "hasMany (RepairRequestProduct)"
}
```

### Structure Produit de Demande de Réparation (RepairRequestProduct)
```json
{
    "id": "integer (PK, auto-increment)",
    "repair_request_id": "integer (FK to repair_requests.id, required)",
    "product_id": "integer (FK to products.id, required)",
    "quantity": "integer (required, min:1, max:1000)",
    "unit_price": "decimal(10,2) (required, min:0)",
    "total_price": "decimal(10,2) (computed: quantity * unit_price)",
    "priority": "enum (high|medium|low, required)",
    "priority_label": "string (computed)",
    "note": "text|null (max:1000)",
    
    // Workflow Status
    "is_invoiced": "boolean (default: false)",
    "is_completed": "boolean (default: false)",
    "is_approved": "boolean (default: false)",
    
    // Workflow Users
    "invoiced_by": "integer|null (FK to users.id)",
    "completed_by": "integer|null (FK to users.id)",
    "approved_by": "integer|null (FK to users.id)",
    
    // Workflow Timestamps
    "invoiced_at": "datetime|null",
    "completed_at": "datetime|null",
    "approved_at": "datetime|null",
    "created_at": "datetime",
    "updated_at": "datetime",
    
    // Computed Attributes
    "workflow_status": "enum (pending|invoiced|completed|approved)",
    "workflow_progress": "integer (0-100)",
    "can_be_invoiced": "boolean",
    "can_be_completed": "boolean",
    "can_be_approved": "boolean",
    "can_revert_invoice": "boolean",
    "can_revert_completion": "boolean",
    "days_since_created": "integer",
    "processing_time": "string|null (human-readable duration)",
    "total_cost_formatted": "string",
    
    // Relations
    "repair_request": "belongsTo (RepairRequest)",
    "product": "belongsTo (Product)",
    "invoicedBy": "belongsTo (User) - via invoiced_by",
    "completedBy": "belongsTo (User) - via completed_by",
    "approvedBy": "belongsTo (User) - via approved_by",
    
    // Constraints
    "unique": ["repair_request_id", "product_id"]
}
```

---

## ✅ Validation & Règles

### Règles de validation communes

#### Pagination
```php
'page' => 'integer|min:1',
'per_page' => 'integer|min:1|max:100',
'sort' => 'string|in:id,name,created_at,updated_at',
'direction' => 'string|in:asc,desc'
```

#### Recherche et filtres
```php
'search' => 'string|max:255',
'is_active' => 'boolean',
'date_from' => 'date|before_or_equal:date_to',
'date_to' => 'date|after_or_equal:date_from'
```

### Règles spécifiques par entité

#### Utilisateurs
```php
// Création
'first_name' => 'required|string|max:255',
'last_name' => 'required|string|max:255',
'email' => 'required|email|unique:users,email|max:255',
'password' => 'required|string|min:8|confirmed',
'role' => 'required|in:client,bureau_staff,mechanic,admin',
'phone' => 'nullable|string|max:20',
'address' => 'nullable|string|max:1000',
'company' => 'nullable|string|max:255',
'is_active' => 'boolean'

// Modification (avec sometimes)
'first_name' => 'sometimes|required|string|max:255',
'email' => 'sometimes|required|email|max:255|unique:users,email,' . $user->id,
'password' => 'sometimes|string|min:8|confirmed'
```

#### Pilotes
```php
'client_id' => 'required|exists:users,id',
'first_name' => 'required|string|max:255',
'last_name' => 'required|string|max:255',
'date_of_birth' => 'required|date|before:today',
'license_number' => 'nullable|string|max:50|unique:pilots,license_number',
'license_expiry' => 'nullable|date|after:today',
'emergency_contact_name' => 'required|string|max:255',
'emergency_contact_phone' => 'required|string|max:255',
'size_tshirt' => 'nullable|in:XS,S,M,L,XL,XXL',
'size_shoes' => 'nullable|integer|between:20,50'
```

#### Karts
```php
'pilot_id' => 'required|exists:pilots,id',
'brand' => 'required|string|max:255',
'model' => 'required|string|max:255',
'chassis_number' => 'required|string|max:255|unique:karts,chassis_number',
'year' => 'required|integer|min:1950|max:' . (date('Y') + 1),
'engine_type' => 'nullable|in:2T,4T,ELECTRIC'
```

#### Demandes de réparation
```php
'kart_id' => 'required|integer|exists:karts,id,deleted_at,NULL',
'title' => 'required|string|max:255|min:3',
'description' => 'nullable|string|max:5000',
'priority' => 'required|in:low,medium,high',
'status_id' => 'required|integer|exists:request_statuses,id',
'estimated_cost' => 'required|numeric|min:0|max:99999999.99',
'estimated_hours' => 'nullable|numeric|min:0|max:999.99',
'assigned_to' => 'nullable|integer|exists:users,id,deleted_at,NULL'
```

#### Produits
```php
'name' => 'required|string|max:255',
'ref' => 'required|string|max:100|unique:products,ref',
'category' => 'required|string|max:100',
'unity' => 'required|in:Piece,Liters,Hours,Kg',
'price' => 'required|numeric|min:0|max:99999999.99',
'stock_quantity' => 'required|integer|min:0|max:999999',
'min_stock' => 'required|integer|min:0',
'max_stock' => 'required|integer|min:stock_quantity'
```

#### Produits de demande de réparation
```php
'repair_request_id' => 'required|integer|exists:repair_requests,id,deleted_at,NULL',
'product_id' => 'required|integer|exists:products,id,deleted_at,NULL',
'quantity' => 'required|integer|min:1|max:1000',
'priority' => 'required|in:high,medium,low',
'note' => 'nullable|string|max:1000',
'unit_price' => 'nullable|numeric|min:0'
```

### Messages d'erreur personnalisés

Les messages d'erreur sont **entièrement en français** avec des textes explicites :

```json
{
    "first_name.required": "Le prénom est obligatoire.",
    "email.email": "L'adresse email doit être valide.",
    "password.min": "Le mot de passe doit contenir au moins 8 caractères.",
    "role.in": "Le rôle doit être client, bureau_staff, mechanic ou admin.",
    "chassis_number.unique": "Ce numéro de châssis existe déjà.",
    "date_of_birth.before": "La date de naissance doit être antérieure à aujourd'hui.",
    "estimated_cost.numeric": "Le coût estimé doit être un nombre.",
    "stock_quantity.max": "La quantité en stock ne peut pas dépasser 999999."
}
```

### Contraintes d'intégrité

#### Contraintes de base de données
- **Unicité** : emails, références produits, numéros châssis
- **Clés étrangères** : intégrité référentielle avec cascade/restrict appropriés
- **Soft delete** : préservation de l'intégrité avec les relations

#### Contraintes métier
- Un pilote ne peut avoir qu'un seul kart actif par marque
- Un produit ne peut être ajouté qu'une fois par demande de réparation
- Les statuts finaux ne peuvent pas être modifiés
- Stock insuffisant bloque la facturation des produits

---

## 📊 Codes de statut

### Codes de succès HTTP

| Code | Nom | Usage | Exemple |
|------|-----|-------|---------|
| **200** | OK | Récupération/modification réussie | GET /users, PUT /users/1 |
| **201** | Created | Création réussie | POST /users |
| **204** | No Content | Suppression réussie | DELETE /users/1 |

### Codes d'erreur HTTP détaillés

| Code | Nom | Description | Cas d'usage |
|------|-----|-------------|-------------|
| **400** | Bad Request | Requête malformée | JSON invalide, paramètres incorrects |
| **401** | Unauthorized | Non authentifié | Token manquant/expiré |
| **403** | Forbidden | Non autorisé | Permissions insuffisantes |
| **404** | Not Found | Ressource inexistante | Utilisateur, produit introuvable |
| **409** | Conflict | Conflit de données | Email déjà utilisé, chassis_number existant |
| **422** | Unprocessable Entity | Validation échouée | Erreurs de formulaire |
| **429** | Too Many Requests | Rate limiting | Trop de requêtes |
| **500** | Internal Server Error | Erreur serveur | Exception non gérée |

### Structure des réponses d'erreur par code

#### 400 - Bad Request
```json
{
    "message": "Requête incorrecte",
    "error_code": "BAD_REQUEST",
    "details": "Le format JSON est invalide",
    "timestamp": "2025-09-03T12:00:00.000000Z"
}
```

#### 401 - Unauthorized
```json
{
    "message": "Token d'authentification requis",
    "error_code": "UNAUTHENTICATED",
    "timestamp": "2025-09-03T12:00:00.000000Z"
}
```

#### 403 - Forbidden
```json
{
    "message": "Permissions insuffisantes pour cette action",
    "error_code": "INSUFFICIENT_PERMISSIONS",
    "required_role": "admin",
    "current_role": "client",
    "timestamp": "2025-09-03T12:00:00.000000Z"
}
```

#### 404 - Not Found
```json
{
    "message": "Ressource non trouvée",
    "error_code": "RESOURCE_NOT_FOUND",
    "resource_type": "User",
    "resource_id": 999,
    "timestamp": "2025-09-03T12:00:00.000000Z"
}
```

#### 409 - Conflict
```json
{
    "message": "Conflit de données détecté",
    "error_code": "DATA_CONFLICT",
    "conflicting_field": "email",
    "conflicting_value": "user@example.com",
    "existing_resource_id": 123,
    "timestamp": "2025-09-03T12:00:00.000000Z"
}
```

#### 422 - Unprocessable Entity
```json
{
    "message": "Données de validation invalides",
    "error_code": "VALIDATION_FAILED",
    "errors": {
        "email": ["L'adresse email doit être valide"],
        "password": ["Le mot de passe doit contenir au moins 8 caractères"],
        "role": ["Le rôle sélectionné est invalide"]
    },
    "timestamp": "2025-09-03T12:00:00.000000Z"
}
```

#### 429 - Too Many Requests
```json
{
    "message": "Limite de requêtes atteinte",
    "error_code": "RATE_LIMIT_EXCEEDED",
    "retry_after": 60,
    "limit": 100,
    "remaining": 0,
    "reset_at": "2025-09-03T13:00:00.000000Z",
    "timestamp": "2025-09-03T12:00:00.000000Z"
}
```

#### 500 - Internal Server Error
```json
{
    "message": "Une erreur interne s'est produite",
    "error_code": "INTERNAL_SERVER_ERROR",
    "request_id": "req_1234567890abcdef",
    "timestamp": "2025-09-03T12:00:00.000000Z"
}
```

---

## 🚀 Exemples d'intégration

### Configuration de base

```javascript
// Configuration API de base
const API_BASE_URL = 'https://your-domain.com/api';
const API_HEADERS = {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
};

// Fonction pour ajouter le token d'authentification
function getAuthHeaders(token) {
    return {
        ...API_HEADERS,
        'Authorization': `Bearer ${token}`
    };
}

// Gestion des erreurs API
function handleApiError(error) {
    if (error.response) {
        const { status, data } = error.response;
        console.error(`API Error ${status}:`, data.message);
        
        switch (status) {
            case 401:
                // Rediriger vers la page de connexion
                window.location.href = '/login';
                break;
            case 403:
                alert('Vous n\'avez pas les permissions nécessaires');
                break;
            case 422:
                // Afficher les erreurs de validation
                displayValidationErrors(data.errors);
                break;
            default:
                alert('Une erreur s\'est produite: ' + data.message);
        }
    }
    throw error;
}
```

### 1. Authentification complète

```javascript
class AuthService {
    constructor() {
        this.token = localStorage.getItem('auth_token');
        this.user = JSON.parse(localStorage.getItem('user') || 'null');
    }

    // Connexion
    async login(email, password) {
        try {
            const response = await fetch(`${API_BASE_URL}/auth/login`, {
                method: 'POST',
                headers: API_HEADERS,
                body: JSON.stringify({ email, password })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Erreur de connexion');
            }

            // Stocker le token et les infos utilisateur
            this.token = data.token;
            this.user = data.user;
            
            localStorage.setItem('auth_token', this.token);
            localStorage.setItem('user', JSON.stringify(this.user));

            return data;
        } catch (error) {
            console.error('Login error:', error);
            throw error;
        }
    }

    // Inscription
    async register(userData) {
        try {
            const response = await fetch(`${API_BASE_URL}/auth/register`, {
                method: 'POST',
                headers: API_HEADERS,
                body: JSON.stringify(userData)
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Erreur d\'inscription');
            }

            // Auto-login après inscription
            this.token = data.token;
            this.user = data.user;
            
            localStorage.setItem('auth_token', this.token);
            localStorage.setItem('user', JSON.stringify(this.user));

            return data;
        } catch (error) {
            console.error('Register error:', error);
            throw error;
        }
    }

    // Déconnexion
    async logout() {
        if (this.token) {
            try {
                await fetch(`${API_BASE_URL}/auth/logout`, {
                    method: 'POST',
                    headers: getAuthHeaders(this.token)
                });
            } catch (error) {
                console.error('Logout error:', error);
            }
        }

        this.token = null;
        this.user = null;
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user');
    }

    // Rafraîchir le token
    async refreshToken() {
        try {
            const response = await fetch(`${API_BASE_URL}/auth/refresh`, {
                method: 'POST',
                headers: getAuthHeaders(this.token)
            });

            const data = await response.json();

            if (response.ok) {
                this.token = data.token;
                this.user = data.user;
                localStorage.setItem('auth_token', this.token);
                localStorage.setItem('user', JSON.stringify(this.user));
            }

            return data;
        } catch (error) {
            console.error('Token refresh error:', error);
            throw error;
        }
    }

    // Vérifier si l'utilisateur est connecté
    isAuthenticated() {
        return !!this.token && !!this.user;
    }

    // Vérifier les rôles
    hasRole(role) {
        return this.user && this.user.role === role;
    }

    canAccess(requiredRoles) {
        if (!this.user) return false;
        return requiredRoles.includes(this.user.role);
    }
}

// Utilisation
const auth = new AuthService();

// Connexion
document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;

    try {
        await auth.login(email, password);
        window.location.href = '/dashboard';
    } catch (error) {
        document.getElementById('error').textContent = error.message;
    }
});
```

### 2. Gestion des ressources avec pagination

```javascript
class ResourceManager {
    constructor(authService) {
        this.auth = authService;
    }

    // Récupérer une liste paginée
    async getList(endpoint, filters = {}) {
        const params = new URLSearchParams();
        
        // Ajouter les filtres
        Object.keys(filters).forEach(key => {
            if (filters[key] !== undefined && filters[key] !== '') {
                params.append(key, filters[key]);
            }
        });

        const url = `${API_BASE_URL}/${endpoint}?${params.toString()}`;

        try {
            const response = await fetch(url, {
                method: 'GET',
                headers: getAuthHeaders(this.auth.token)
            });

            const data = await response.json();

            if (!response.ok) {
                handleApiError({ response: { status: response.status, data } });
            }

            return data;
        } catch (error) {
            handleApiError(error);
        }
    }

    // Créer une ressource
    async create(endpoint, resourceData) {
        try {
            const response = await fetch(`${API_BASE_URL}/${endpoint}`, {
                method: 'POST',
                headers: getAuthHeaders(this.auth.token),
                body: JSON.stringify(resourceData)
            });

            const data = await response.json();

            if (!response.ok) {
                handleApiError({ response: { status: response.status, data } });
            }

            return data;
        } catch (error) {
            handleApiError(error);
        }
    }

    // Mettre à jour une ressource
    async update(endpoint, id, resourceData) {
        try {
            const response = await fetch(`${API_BASE_URL}/${endpoint}/${id}`, {
                method: 'PUT',
                headers: getAuthHeaders(this.auth.token),
                body: JSON.stringify(resourceData)
            });

            const data = await response.json();

            if (!response.ok) {
                handleApiError({ response: { status: response.status, data } });
            }

            return data;
        } catch (error) {
            handleApiError(error);
        }
    }

    // Supprimer une ressource
    async delete(endpoint, id) {
        try {
            const response = await fetch(`${API_BASE_URL}/${endpoint}/${id}`, {
                method: 'DELETE',
                headers: getAuthHeaders(this.auth.token)
            });

            if (response.status !== 204) {
                const data = await response.json();
                if (!response.ok) {
                    handleApiError({ response: { status: response.status, data } });
                }
                return data;
            }

            return { message: 'Suppression réussie' };
        } catch (error) {
            handleApiError(error);
        }
    }
}

// Utilisation pour les utilisateurs
const resourceManager = new ResourceManager(auth);

// Récupérer les utilisateurs avec filtres
async function loadUsers() {
    const filters = {
        page: 1,
        per_page: 15,
        search: document.getElementById('search').value,
        role: document.getElementById('roleFilter').value,
        is_active: document.getElementById('statusFilter').value
    };

    try {
        const response = await resourceManager.getList('users', filters);
        displayUsers(response.data);
        displayPagination(response.links, response.meta);
    } catch (error) {
        console.error('Failed to load users:', error);
    }
}
```

### 3. Workflow complet de réparation

```javascript
class RepairWorkflow {
    constructor(authService) {
        this.auth = authService;
        this.resourceManager = new ResourceManager(authService);
    }

    // Créer une demande de réparation
    async createRepairRequest(kartId, repairData) {
        const requestData = {
            kart_id: kartId,
            title: repairData.title,
            description: repairData.description,
            priority: repairData.priority,
            status_id: 1, // En attente
            estimated_cost: repairData.estimatedCost,
            estimated_hours: repairData.estimatedHours
        };

        return await this.resourceManager.create('repair-requests', requestData);
    }

    // Ajouter des produits à une demande
    async addProductToRequest(repairRequestId, productData) {
        const data = {
            repair_request_id: repairRequestId,
            product_id: productData.productId,
            quantity: productData.quantity,
            priority: productData.priority,
            note: productData.note,
            unit_price: productData.unitPrice
        };

        return await this.resourceManager.create('repair-request-products', data);
    }

    // Démarrer une réparation
    async startRepair(repairRequestId, mechanicId, notes = '') {
        try {
            const response = await fetch(`${API_BASE_URL}/repair-requests/${repairRequestId}/start`, {
                method: 'PATCH',
                headers: getAuthHeaders(this.auth.token),
                body: JSON.stringify({
                    assigned_to: mechanicId,
                    notes: notes
                })
            });

            const data = await response.json();

            if (!response.ok) {
                handleApiError({ response: { status: response.status, data } });
            }

            return data;
        } catch (error) {
            handleApiError(error);
        }
    }

    // Facturer un produit
    async invoiceProduct(productId) {
        try {
            const response = await fetch(`${API_BASE_URL}/repair-request-products/${productId}/invoice`, {
                method: 'PATCH',
                headers: getAuthHeaders(this.auth.token)
            });

            const data = await response.json();

            if (!response.ok) {
                handleApiError({ response: { status: response.status, data } });
            }

            return data;
        } catch (error) {
            handleApiError(error);
        }
    }

    // Marquer comme terminé
    async completeProduct(productId, completionNote = '') {
        try {
            const response = await fetch(`${API_BASE_URL}/repair-request-products/${productId}/complete`, {
                method: 'PATCH',
                headers: getAuthHeaders(this.auth.token),
                body: JSON.stringify({
                    completion_note: completionNote
                })
            });

            const data = await response.json();

            if (!response.ok) {
                handleApiError({ response: { status: response.status, data } });
            }

            return data;
        } catch (error) {
            handleApiError(error);
        }
    }

    // Approuver un produit
    async approveProduct(productId) {
        try {
            const response = await fetch(`${API_BASE_URL}/repair-request-products/${productId}/approve`, {
                method: 'PATCH',
                headers: getAuthHeaders(this.auth.token)
            });

            const data = await response.json();

            if (!response.ok) {
                handleApiError({ response: { status: response.status, data } });
            }

            return data;
        } catch (error) {
            handleApiError(error);
        }
    }

    // Terminer une réparation complète
    async completeRepair(repairRequestId, actualCost, actualHours, notes = '') {
        try {
            const response = await fetch(`${API_BASE_URL}/repair-requests/${repairRequestId}/complete`, {
                method: 'PATCH',
                headers: getAuthHeaders(this.auth.token),
                body: JSON.stringify({
                    actual_cost: actualCost,
                    actual_hours: actualHours,
                    completion_notes: notes
                })
            });

            const data = await response.json();

            if (!response.ok) {
                handleApiError({ response: { status: response.status, data } });
            }

            return data;
        } catch (error) {
            handleApiError(error);
        }
    }
}

// Exemple d'utilisation complète
async function fullRepairWorkflow() {
    const workflow = new RepairWorkflow(auth);

    try {
        // 1. Créer la demande de réparation
        const repair = await workflow.createRepairRequest(1, {
            title: 'Réparation moteur urgente',
            description: 'Surchauffe détectée lors des essais',
            priority: 'high',
            estimatedCost: 1500.00,
            estimatedHours: 8.0
        });

        console.log('Demande créée:', repair.data.reference);

        // 2. Ajouter des produits
        const product1 = await workflow.addProductToRequest(repair.data.id, {
            productId: 5,
            quantity: 2,
            priority: 'high',
            note: 'Pneus avant à remplacer',
            unitPrice: 150.00
        });

        // 3. Démarrer la réparation
        await workflow.startRepair(repair.data.id, 3, 'Assigné à John pour traitement prioritaire');

        // 4. Workflow produit
        await workflow.invoiceProduct(product1.data.id);
        await workflow.completeProduct(product1.data.id, 'Installation OK, tests réussis');
        await workflow.approveProduct(product1.data.id);

        // 5. Terminer la réparation
        await workflow.completeRepair(repair.data.id, 1350.00, 7.5, 'Réparation terminée avec succès');

        console.log('Workflow complet terminé avec succès');

    } catch (error) {
        console.error('Erreur dans le workflow:', error);
    }
}
```

### 4. Composant React d'exemple

```jsx
import React, { useState, useEffect } from 'react';

const RepairRequestList = () => {
    const [repairs, setRepairs] = useState([]);
    const [loading, setLoading] = useState(true);
    const [filters, setFilters] = useState({
        page: 1,
        per_page: 15,
        search: '',
        priority: '',
        status_id: ''
    });
    const [pagination, setPagination] = useState(null);

    // Charger les demandes de réparation
    useEffect(() => {
        loadRepairs();
    }, [filters]);

    const loadRepairs = async () => {
        setLoading(true);
        try {
            const response = await resourceManager.getList('repair-requests', filters);
            setRepairs(response.data);
            setPagination(response.meta);
        } catch (error) {
            console.error('Failed to load repairs:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleFilterChange = (field, value) => {
        setFilters(prev => ({
            ...prev,
            [field]: value,
            page: 1 // Reset page when filtering
        }));
    };

    const handlePageChange = (page) => {
        setFilters(prev => ({ ...prev, page }));
    };

    if (loading) {
        return <div className="loading">Chargement...</div>;
    }

    return (
        <div className="repair-requests">
            <h2>Demandes de réparation</h2>
            
            {/* Filtres */}
            <div className="filters">
                <input
                    type="text"
                    placeholder="Rechercher..."
                    value={filters.search}
                    onChange={(e) => handleFilterChange('search', e.target.value)}
                />
                
                <select
                    value={filters.priority}
                    onChange={(e) => handleFilterChange('priority', e.target.value)}
                >
                    <option value="">Toutes priorités</option>
                    <option value="high">Haute</option>
                    <option value="medium">Moyenne</option>
                    <option value="low">Basse</option>
                </select>
            </div>

            {/* Liste des réparations */}
            <div className="repairs-grid">
                {repairs.map(repair => (
                    <div key={repair.id} className="repair-card">
                        <h3>{repair.title}</h3>
                        <p className="reference">Ref: {repair.reference}</p>
                        <p className="kart">{repair.kart.full_identification}</p>
                        <div className="priority">
                            <span className={`priority-badge priority-${repair.priority}`}>
                                {repair.priority_label}
                            </span>
                        </div>
                        <div className="status" style={{color: repair.status.hex_color}}>
                            {repair.status.name}
                        </div>
                        <div className="costs">
                            <span>Estimé: {repair.estimated_cost}€</span>
                            {repair.actual_cost && (
                                <span>Réel: {repair.actual_cost}€</span>
                            )}
                        </div>
                        <div className="dates">
                            <small>
                                Créé: {new Date(repair.created_at).toLocaleDateString()}
                            </small>
                        </div>
                    </div>
                ))}
            </div>

            {/* Pagination */}
            {pagination && (
                <div className="pagination">
                    <button 
                        disabled={pagination.current_page === 1}
                        onClick={() => handlePageChange(pagination.current_page - 1)}
                    >
                        Précédent
                    </button>
                    
                    <span>
                        Page {pagination.current_page} sur {pagination.last_page}
                        ({pagination.total} éléments)
                    </span>
                    
                    <button 
                        disabled={pagination.current_page === pagination.last_page}
                        onClick={() => handlePageChange(pagination.current_page + 1)}
                    >
                        Suivant
                    </button>
                </div>
            )}
        </div>
    );
};

export default RepairRequestList;
```

### 5. Gestion des stocks en temps réel

```javascript
class StockManager {
    constructor(authService) {
        this.auth = authService;
        this.resourceManager = new ResourceManager(authService);
    }

    // Mettre à jour le stock
    async updateStock(productId, operation, quantity, reason) {
        try {
            const response = await fetch(`${API_BASE_URL}/products/${productId}/stock`, {
                method: 'PATCH',
                headers: getAuthHeaders(this.auth.token),
                body: JSON.stringify({
                    operation: operation, // 'add', 'subtract', 'set'
                    quantity: quantity,
                    reason: reason
                })
            });

            const data = await response.json();

            if (!response.ok) {
                handleApiError({ response: { status: response.status, data } });
            }

            return data;
        } catch (error) {
            handleApiError(error);
        }
    }

    // Obtenir les produits en rupture de stock
    async getLowStockProducts() {
        return await this.resourceManager.getList('products/low-stock');
    }

    // Surveiller les stocks
    async monitorStock() {
        try {
            const lowStockResponse = await this.getLowStockProducts();
            const lowStockProducts = lowStockResponse.data;

            if (lowStockProducts.length > 0) {
                this.showLowStockAlert(lowStockProducts);
            }

            return lowStockProducts;
        } catch (error) {
            console.error('Failed to monitor stock:', error);
        }
    }

    showLowStockAlert(products) {
        const urgentProducts = products.filter(p => p.priority === 'urgent');
        
        if (urgentProducts.length > 0) {
            const message = `Attention: ${urgentProducts.length} produit(s) en rupture urgente de stock!`;
            
            // Affichage d'une notification
            if ('Notification' in window && Notification.permission === 'granted') {
                new Notification('Stock Alert', {
                    body: message,
                    icon: '/icon-warning.png'
                });
            } else {
                alert(message);
            }
        }
    }
}

// Surveillance automatique des stocks
const stockManager = new StockManager(auth);

// Vérifier les stocks toutes les 5 minutes
setInterval(() => {
    if (auth.isAuthenticated() && auth.canAccess(['admin', 'bureau_staff'])) {
        stockManager.monitorStock();
    }
}, 5 * 60 * 1000);
```

### 6. WebSockets pour les mises à jour en temps réel (optionnel)

```javascript
class RealtimeUpdates {
    constructor(authService) {
        this.auth = authService;
        this.ws = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
    }

    connect() {
        if (!this.auth.isAuthenticated()) return;

        const wsUrl = `wss://your-domain.com/ws?token=${this.auth.token}`;
        this.ws = new WebSocket(wsUrl);

        this.ws.onopen = () => {
            console.log('WebSocket connected');
            this.reconnectAttempts = 0;
        };

        this.ws.onmessage = (event) => {
            const data = JSON.parse(event.data);
            this.handleRealtimeUpdate(data);
        };

        this.ws.onclose = () => {
            console.log('WebSocket disconnected');
            this.reconnect();
        };

        this.ws.onerror = (error) => {
            console.error('WebSocket error:', error);
        };
    }

    handleRealtimeUpdate(data) {
        switch (data.type) {
            case 'repair_status_updated':
                this.updateRepairStatus(data.repair_id, data.new_status);
                break;
            case 'stock_updated':
                this.updateProductStock(data.product_id, data.new_quantity);
                break;
            case 'new_repair_request':
                this.showNewRepairNotification(data.repair);
                break;
        }
    }

    reconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            setTimeout(() => {
                console.log(`Attempting to reconnect... (${this.reconnectAttempts})`);
                this.connect();
            }, 1000 * Math.pow(2, this.reconnectAttempts));
        }
    }

    disconnect() {
        if (this.ws) {
            this.ws.close();
            this.ws = null;
        }
    }
}

// Utilisation
const realtimeUpdates = new RealtimeUpdates(auth);

// Se connecter après l'authentification
auth.login(email, password).then(() => {
    realtimeUpdates.connect();
});

// Se déconnecter lors de la déconnexion
auth.logout().then(() => {
    realtimeUpdates.disconnect();
});
```

---

## 📚 Notes importantes pour les développeurs

### **Sécurité et bonnes pratiques**

1. **Tokens d'authentification** : Toujours stocker les tokens de manière sécurisée et implémenter la rotation automatique
2. **Validation côté client** : Dupliquer les validations API côté frontend pour une meilleure UX
3. **Gestion d'erreurs** : Implémenter une gestion d'erreurs robuste avec fallbacks appropriés
4. **Rate limiting** : Respecter les limites de requêtes et implémenter un système de retry avec backoff
5. **Cache** : Mettre en cache les données statiques comme les statuts et les rôles

### **Performance**

1. **Pagination** : Toujours utiliser la pagination pour les grandes listes
2. **Filtrage** : Filtrer côté serveur plutôt que côté client
3. **Lazy loading** : Charger les relations seulement quand nécessaire
4. **Debouncing** : Implémenter le debouncing pour les champs de recherche

### **Tests et débogage**

1. **Logging** : Activer les logs détaillés en développement
2. **Request ID** : Utiliser les request_id dans les réponses d'erreur pour le debugging
3. **Monitoring** : Surveiller les performances API et les taux d'erreur
4. **Testing** : Tester tous les scénarios d'erreur et cas limites

---

*Documentation API KartRepair - Version 2.0.0 - Mise à jour complète 2025*
*Toutes les fonctionnalités testées et validées avec 156 tests passants*
