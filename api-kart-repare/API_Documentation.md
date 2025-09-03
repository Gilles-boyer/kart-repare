# API Documentation - KartRepair

## 📋 Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Authentification](#authentification)
3. [Format des réponses](#format-des-réponses)
4. [Gestion d'erreurs](#gestion-derreurs)
5. [Endpoints](#endpoints)
   - [Authentification](#authentification-1)
   - [Utilisateurs](#utilisateurs)
   - [Pilotes](#pilotes)
   - [Karts](#karts)
   - [Statuts de demande](#statuts-de-demande)
   - [Demandes de réparation](#demandes-de-réparation)
   - [Produits](#produits)
   - [Produits de demande de réparation](#produits-de-demande-de-réparation)
6. [Modèles de données](#modèles-de-données)
7. [Codes de statut](#codes-de-statut)

---

## 🌟 Vue d'ensemble

L'API KartRepair est une API RESTful construite avec Laravel 11, utilisant Laravel Sanctum pour l'authentification. Elle gère un système de réparation de karts avec gestion des utilisateurs, pilotes, karts, demandes de réparation et inventaire.

**Base URL:** `https://your-domain.com/api`
**Version:** 1.0.0
**Authentication:** Bearer Token (Laravel Sanctum)

---

## 🔐 Authentification

L'API utilise Laravel Sanctum avec des tokens Bearer. Chaque utilisateur a un rôle qui détermine ses permissions :

- **admin** : Toutes les permissions
- **bureau_staff** : Gestion complète sauf administration utilisateurs
- **mechanic** : Consultation et mise à jour des réparations
- **client** : Consultation de ses propres données

### Headers requis pour les routes protégées

```http
Authorization: Bearer {your-token}
Content-Type: application/json
Accept: application/json
```

---

## 📊 Format des réponses

Toutes les réponses suivent un format JSON cohérent :

### Succès
```json
{
    "message": "Description du succès",
    "data": {}, // ou []
    "meta": {
        "timestamp": "2025-09-03T12:00:00.000000Z",
        "version": "1.0"
    }
}
```

### Pagination
```json
{
    "data": [],
    "links": {
        "first": "...",
        "last": "...",
        "prev": null,
        "next": "..."
    },
    "meta": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 67
    }
}
```

---

## ⚠️ Gestion d'erreurs

### Codes d'erreur HTTP
- **400** : Bad Request
- **401** : Non authentifié
- **403** : Non autorisé
- **404** : Ressource non trouvée
- **422** : Erreurs de validation
- **500** : Erreur serveur

### Format des erreurs
```json
{
    "message": "Description de l'erreur",
    "errors": {
        "field": ["Message d'erreur"]
    }
}
```

---

## 🔗 Endpoints

## Authentification

### POST /auth/login
Connexion utilisateur

**Paramètres :**
```json
{
    "email": "user@example.com",
    "password": "password123"
}
```

**Réponse (200) :**
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
        "email_verified_at": "2025-01-01 12:00:00",
        "last_login_at": "2025-09-03 12:00:00",
        "deleted_at": null,
        "created_at": "2025-01-01 10:00:00",
        "updated_at": "2025-09-03 12:00:00"
    },
    "token": "1|abcd1234...",
    "token_type": "Bearer"
}
```

### POST /auth/register
Inscription utilisateur

**Paramètres :**
```json
{
    "first_name": "John",
    "last_name": "Doe",
    "email": "user@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "phone": "+33123456789",
    "address": "123 Rue Example",
    "company": "Example Corp"
}
```

**Réponse (201) :** Identique à `/auth/login`

### GET /auth/me
Profil de l'utilisateur connecté

**Headers requis :** `Authorization: Bearer {token}`

**Réponse (200) :**
```json
{
    "message": "Profil récupéré avec succès",
    "user": {
        // Même structure que login
    }
}
```

### POST /auth/logout
Déconnexion (révocation du token)

**Headers requis :** `Authorization: Bearer {token}`

**Réponse (200) :**
```json
{
    "message": "Déconnexion réussie"
}
```

### POST /auth/refresh
Rafraîchissement du token

**Headers requis :** `Authorization: Bearer {token}`

**Réponse (200) :** Identique à `/auth/login`

---

## 👥 Utilisateurs

### GET /users
Liste des utilisateurs (avec pagination)

**Permissions :** admin, bureau_staff

**Query Parameters :**
- `page` : Numéro de page (défaut: 1)
- `per_page` : Éléments par page (défaut: 15, max: 100)
- `search` : Recherche dans nom, prénom, email
- `role` : Filtrer par rôle (client, mechanic, bureau_staff, admin)
- `is_active` : Filtrer par statut (true/false)
- `sort` : Tri (name, email, created_at) (défaut: created_at)
- `direction` : Direction du tri (asc, desc) (défaut: desc)

**Réponse (200) :**
```json
{
    "data": [
        {
            // Structure utilisateur comme dans /auth/login
        }
    ],
    "links": { /* pagination */ },
    "meta": { /* pagination */ }
}
```

### POST /users
Créer un utilisateur

**Permissions :** admin, bureau_staff

**Paramètres :**
```json
{
    "first_name": "John",
    "last_name": "Doe",
    "email": "user@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "client",
    "phone": "+33123456789",
    "address": "123 Rue Example",
    "company": "Example Corp",
    "is_active": true
}
```

**Réponse (201) :**
```json
{
    "message": "Utilisateur créé avec succès",
    "data": {
        // Structure utilisateur
    }
}
```

### GET /users/{id}
Détails d'un utilisateur

**Permissions :** admin, bureau_staff ou propriétaire

**Réponse (200) :**
```json
{
    "message": "Utilisateur récupéré avec succès",
    "data": {
        // Structure utilisateur
    }
}
```

### PUT /users/{id}
Modifier un utilisateur

**Permissions :** admin, bureau_staff ou propriétaire (limité)

**Paramètres :**
```json
{
    "first_name": "John Updated",
    "last_name": "Doe Updated",
    "email": "updated@example.com",
    "role": "mechanic",
    "phone": "+33987654321",
    "address": "456 Rue Updated",
    "company": "Updated Corp",
    "is_active": false
}
```

### DELETE /users/{id}
Supprimer un utilisateur (soft delete)

**Permissions :** admin, bureau_staff

**Réponse (200) :**
```json
{
    "message": "Utilisateur supprimé avec succès"
}
```

### GET /users/profile
Profil personnel

**Headers requis :** `Authorization: Bearer {token}`

**Réponse (200) :**
```json
{
    "message": "Profil récupéré avec succès",
    "data": {
        // Structure utilisateur
    }
}
```

### PUT /users/profile
Modifier son profil

**Headers requis :** `Authorization: Bearer {token}`

**Paramètres :**
```json
{
    "first_name": "John",
    "last_name": "Doe",
    "phone": "+33123456789",
    "address": "123 Rue Example",
    "company": "Example Corp"
}
```

### PATCH /users/{id}/toggle-status
Activer/Désactiver un utilisateur

**Permissions :** admin

**Réponse (200) :**
```json
{
    "message": "Statut utilisateur modifié avec succès",
    "data": {
        "is_active": false
    }
}
```

### GET /users/statistics
Statistiques des utilisateurs

**Permissions :** admin

**Réponse (200) :**
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
        "recent_registrations": 12,
        "recent_logins": 89
    }
}
```

### GET /users/trashed
Utilisateurs supprimés

**Permissions :** admin

**Réponse (200) :**
```json
{
    "data": [
        {
            // Structure utilisateur avec deleted_at non null
        }
    ]
}
```

### PATCH /users/{id}/restore
Restaurer un utilisateur supprimé

**Permissions :** admin

### DELETE /users/{id}/force-delete
Suppression définitive

**Permissions :** admin

---

## 👤 Pilotes

### GET /pilots
Liste des pilotes

**Permissions :** Authentifié (clients voient seulement leurs pilotes)

**Query Parameters :**
- `page`, `per_page`, `sort`, `direction` : Pagination standard
- `search` : Recherche dans nom, prénom
- `client_id` : Filtrer par client (admin/bureau_staff seulement)
- `is_active` : Filtrer par statut

**Réponse (200) :**
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
            "emergency_contact": "Jos Verstappen - +33987654321",
            "medical_info": "Aucune allergie connue",
            "is_active": true,
            "client_id": 5,
            "created_at": "2025-01-01 10:00:00",
            "updated_at": "2025-09-03 12:00:00",
            "client": {
                "id": 5,
                "full_name": "Red Bull Racing",
                "email": "team@redbull.com"
            },
            "age": 27,
            "license_expires_soon": false,
            "karts_count": 3
        }
    ],
    "links": { /* pagination */ },
    "meta": { /* pagination */ }
}
```

### POST /pilots
Créer un pilote

**Permissions :** admin, bureau_staff, client (pour soi)

**Paramètres :**
```json
{
    "first_name": "Max",
    "last_name": "Verstappen",
    "date_of_birth": "1997-09-30",
    "license_number": "LIC123456",
    "license_expiry": "2025-12-31",
    "phone": "+33123456789",
    "email": "max@example.com",
    "address": "123 Racing Street",
    "emergency_contact": "Jos Verstappen - +33987654321",
    "medical_info": "Aucune allergie connue",
    "client_id": 5,
    "is_active": true
}
```

### GET /pilots/{id}
Détails d'un pilote

**Réponse (200) :**
```json
{
    "message": "Pilote récupéré avec succès",
    "data": {
        // Structure complète du pilote avec relations
    }
}
```

### PUT /pilots/{id}
Modifier un pilote

**Permissions :** admin, bureau_staff, propriétaire

### DELETE /pilots/{id}
Supprimer un pilote (soft delete)

**Permissions :** admin, bureau_staff, propriétaire

### GET /pilots/statistics
Statistiques des pilotes

**Permissions :** admin, bureau_staff, client (ses pilotes)

**Réponse (200) :**
```json
{
    "message": "Statistiques récupérées avec succès",
    "data": {
        "total_pilots": 87,
        "active_pilots": 82,
        "inactive_pilots": 5,
        "licenses_expiring_soon": 12,
        "average_age": 28.5,
        "by_client": {
            "Red Bull Racing": 3,
            "Mercedes AMG": 2
        }
    }
}
```

---

## 🏎️ Karts

### GET /karts
Liste des karts

**Query Parameters :**
- Pagination standard
- `search` : Recherche dans numéro châssis, marque, modèle
- `pilot_id` : Filtrer par pilote
- `brand` : Filtrer par marque
- `is_active` : Filtrer par statut
- `year` : Filtrer par année

**Réponse (200) :**
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
            "engine_type": "Honda RA623H",
            "pilot_id": 1,
            "is_active": true,
            "last_maintenance": "2025-08-15",
            "next_maintenance": "2025-10-15",
            "created_at": "2025-01-01 10:00:00",
            "updated_at": "2025-09-03 12:00:00",
            "pilot": {
                "id": 1,
                "full_name": "Max Verstappen",
                "client": {
                    "id": 5,
                    "full_name": "Red Bull Racing"
                }
            },
            "full_identification": "RB19-001 - Red Bull RB19 (2023)",
            "maintenance_due_soon": false,
            "maintenance_overdue": false,
            "repair_requests_count": 2
        }
    ]
}
```

### POST /karts
Créer un kart

**Paramètres :**
```json
{
    "chassis_number": "RB19-001",
    "brand": "Red Bull",
    "model": "RB19",
    "year": 2023,
    "color": "Bleu/Rouge",
    "engine_type": "Honda RA623H",
    "pilot_id": 1,
    "is_active": true,
    "last_maintenance": "2025-08-15",
    "next_maintenance": "2025-10-15"
}
```

---

## 📊 Statuts de demande

### GET /request-statuses
Liste des statuts de demande

**Réponse (200) :**
```json
{
    "data": [
        {
            "id": 1,
            "name": "En attente",
            "description": "Demande en attente de traitement",
            "color": "#ffc107",
            "is_final": false,
            "is_active": true,
            "created_at": "2025-01-01 10:00:00",
            "updated_at": "2025-09-03 12:00:00",
            "repair_requests_count": 15
        }
    ]
}
```

### POST /request-statuses
Créer un statut

**Permissions :** admin, bureau_staff

**Paramètres :**
```json
{
    "name": "En cours",
    "description": "Réparation en cours",
    "color": "#007bff",
    "is_final": false,
    "is_active": true
}
```

---

## 🔧 Demandes de réparation

### GET /repair-requests
Liste des demandes de réparation

**Query Parameters :**
- Pagination standard
- `search` : Recherche dans titre, description, référence
- `status` : Filtrer par statut
- `priority` : Filtrer par priorité (high, medium, low)
- `kart_id` : Filtrer par kart
- `assigned_to` : Filtrer par mécanicien assigné
- `completion_status` : Filtrer par statut de completion

**Réponse (200) :**
```json
{
    "data": [
        {
            "id": 1,
            "reference": "REP-2025-001",
            "title": "Réparation moteur",
            "description": "Problème de surchauffe moteur",
            "priority": "high",
            "priority_label": "Haute",
            "estimated_cost": 1500.00,
            "actual_cost": 1350.00,
            "estimated_hours": 8,
            "actual_hours": 7.5,
            "status": "in_progress",
            "completion_percentage": 65,
            "is_completed": false,
            "is_overdue": false,
            "kart_id": 1,
            "client_id": 5,
            "assigned_to": 3,
            "created_at": "2025-09-01 09:00:00",
            "updated_at": "2025-09-03 12:00:00",
            "completed_at": null,
            "kart": {
                "id": 1,
                "chassis_number": "RB19-001",
                "full_identification": "RB19-001 - Red Bull RB19 (2023)"
            },
            "client": {
                "id": 5,
                "full_name": "Red Bull Racing"
            },
            "mechanic": {
                "id": 3,
                "full_name": "John Mechanic"
            },
            "days_since_created": 2,
            "estimated_completion": "2025-09-05",
            "products_count": 3
        }
    ]
}
```

### POST /repair-requests
Créer une demande de réparation

**Paramètres :**
```json
{
    "title": "Réparation moteur",
    "description": "Problème de surchauffe moteur détecté lors des essais",
    "priority": "high",
    "kart_id": 1,
    "estimated_cost": 1500.00,
    "estimated_hours": 8,
    "due_date": "2025-09-10"
}
```

### PATCH /repair-requests/{id}/start
Démarrer une réparation

**Permissions :** admin, bureau_staff

**Paramètres :**
```json
{
    "assigned_to": 3,
    "notes": "Réparation assignée à John"
}
```

### PATCH /repair-requests/{id}/complete
Terminer une réparation

**Permissions :** admin, bureau_staff, mécanicien assigné

**Paramètres :**
```json
{
    "actual_cost": 1350.00,
    "actual_hours": 7.5,
    "completion_notes": "Réparation terminée avec succès"
}
```

### PATCH /repair-requests/{id}/assign
Assigner un mécanicien

**Permissions :** admin, bureau_staff

**Paramètres :**
```json
{
    "assigned_to": 3
}
```

---

## 📦 Produits

### GET /products
Liste des produits

**Query Parameters :**
- Pagination standard
- `search` : Recherche dans nom, référence, marque, modèle
- `category` : Filtrer par catégorie
- `brand` : Filtrer par marque
- `unity` : Filtrer par unité (Piece, Liters, Hours, Kg)
- `stock_status` : Filtrer par statut stock (in_stock, out_of_stock, low_stock)
- `price_min`, `price_max` : Filtrer par prix
- `is_active` : Filtrer par statut

**Réponse (200) :**
```json
{
    "data": [
        {
            "id": 1,
            "name": "Pneu avant Michelin",
            "reference": "MICH-F-001",
            "description": "Pneu avant haute performance",
            "category": "Pneumatiques",
            "brand": "Michelin",
            "model": "Pilot Sport",
            "unity": "Piece",
            "price": 150.00,
            "stock_quantity": 25,
            "min_stock": 10,
            "max_stock": 100,
            "is_active": true,
            "created_at": "2025-01-01 10:00:00",
            "updated_at": "2025-09-03 12:00:00",
            "stock_status": "in_stock",
            "stock_level": "normal",
            "needs_restock": false,
            "price_formatted": "150,00 €"
        }
    ]
}
```

### POST /products
Créer un produit

**Permissions :** admin, bureau_staff

**Paramètres :**
```json
{
    "name": "Pneu avant Michelin",
    "reference": "MICH-F-001",
    "description": "Pneu avant haute performance",
    "category": "Pneumatiques",
    "brand": "Michelin",
    "model": "Pilot Sport",
    "unity": "Piece",
    "price": 150.00,
    "stock_quantity": 25,
    "min_stock": 10,
    "max_stock": 100,
    "is_active": true
}
```

### PATCH /products/{id}/stock
Mettre à jour le stock

**Permissions :** admin, bureau_staff

**Paramètres :**
```json
{
    "operation": "add", // "add", "subtract", "set"
    "quantity": 10,
    "reason": "Réapprovisionnement fournisseur"
}
```

**Réponse (200) :**
```json
{
    "message": "Stock mis à jour avec succès",
    "data": {
        "old_quantity": 25,
        "new_quantity": 35,
        "operation": "add",
        "quantity_changed": 10
    }
}
```

### GET /products/statistics
Statistiques des produits

**Permissions :** admin, bureau_staff

**Réponse (200) :**
```json
{
    "message": "Statistiques récupérées avec succès",
    "data": {
        "total_products": 157,
        "active_products": 142,
        "inactive_products": 15,
        "total_stock_value": 45750.50,
        "low_stock_products": 12,
        "out_of_stock_products": 3,
        "by_category": {
            "Pneumatiques": 45,
            "Moteur": 23,
            "Carrosserie": 38
        },
        "by_unity": {
            "Piece": 89,
            "Liters": 34,
            "Hours": 12,
            "Kg": 22
        }
    }
}
```

### GET /products/low-stock
Produits nécessitant un réapprovisionnement

**Permissions :** admin, bureau_staff

**Réponse (200) :**
```json
{
    "data": [
        {
            "id": 5,
            "name": "Huile moteur Castrol",
            "stock_quantity": 3,
            "min_stock": 10,
            "recommended_order": 25,
            "days_until_stockout": 5
        }
    ]
}
```

---

## 🔩 Produits de demande de réparation

### GET /repair-request-products
Liste des produits de demande de réparation

**Query Parameters :**
- Pagination standard
- `repair_request_id` : Filtrer par demande de réparation
- `product_id` : Filtrer par produit
- `priority` : Filtrer par priorité
- `status` : Filtrer par statut (pending, invoiced, completed, approved)
- `invoiced` : Filtrer par facturation (true/false)
- `completed` : Filtrer par completion (true/false)
- `approved` : Filtrer par approbation (true/false)
- `search` : Recherche dans notes et informations produit

**Réponse (200) :**
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
            "note": "Pneus à changer d'urgence",
            "status": "invoiced",
            "is_invoiced": true,
            "is_completed": false,
            "is_approved": false,
            "can_be_invoiced": false,
            "can_be_completed": true,
            "can_be_approved": false,
            "invoiced_at": "2025-09-02 10:00:00",
            "completed_at": null,
            "approved_at": null,
            "created_at": "2025-09-01 14:00:00",
            "updated_at": "2025-09-02 10:00:00",
            "repair_request": {
                "id": 1,
                "reference": "REP-2025-001",
                "status": "in_progress",
                "priority": "high",
                "description": "Réparation moteur",
                "created_at": "2025-09-01 09:00:00"
            },
            "product": {
                "id": 5,
                "name": "Pneu avant Michelin",
                "reference": "MICH-F-001",
                "category": "Pneumatiques",
                "brand": "Michelin",
                "model": "Pilot Sport",
                "price": 150.00,
                "stock_quantity": 25,
                "is_active": true
            },
            "invoiced_by_user": {
                "id": 2,
                "name": "Jane Admin",
                "email": "admin@example.com"
            },
            "completed_by_user": null,
            "days_since_created": 2,
            "days_to_complete": null,
            "total_cost_formatted": "300,00 €",
            "workflow_progress": 50,
            "workflow_next_step": "complete"
        }
    ]
}
```

### POST /repair-request-products
Ajouter un produit à une demande de réparation

**Permissions :** admin, bureau_staff

**Paramètres :**
```json
{
    "repair_request_id": 1,
    "product_id": 5,
    "quantity": 2,
    "priority": "high",
    "note": "Pneus à changer d'urgence",
    "unit_price": 150.00
}
```

**Réponse (201) :**
```json
{
    "message": "Produit ajouté à la demande de réparation avec succès",
    "data": {
        // Structure complète du produit créé
    }
}
```

### PATCH /repair-request-products/{id}/invoice
Facturer un produit

**Permissions :** admin, bureau_staff

**Réponse (200) :**
```json
{
    "message": "Produit facturé avec succès",
    "data": {
        "is_invoiced": true,
        "invoiced_at": "2025-09-03 14:30:00",
        "invoiced_by": 2
    }
}
```

### PATCH /repair-request-products/{id}/complete
Marquer un produit comme terminé

**Permissions :** admin, bureau_staff, mécanicien assigné

**Paramètres optionnels :**
```json
{
    "completion_note": "Installation terminée sans problème"
}
```

**Réponse (200) :**
```json
{
    "message": "Produit marqué comme terminé avec succès",
    "data": {
        "is_completed": true,
        "completed_at": "2025-09-03 16:45:00",
        "completed_by": 3
    }
}
```

### PATCH /repair-request-products/{id}/approve
Approuver un produit

**Permissions :** admin, bureau_staff

**Réponse (200) :**
```json
{
    "message": "Produit approuvé avec succès",
    "data": {
        "is_approved": true,
        "approved_at": "2025-09-03 17:00:00"
    }
}
```

### PATCH /repair-request-products/{id}/revert-invoice
Annuler la facturation

**Permissions :** admin

### PATCH /repair-request-products/{id}/revert-completion
Annuler la completion

**Permissions :** admin

### GET /repair-request-products/statistics
Statistiques des produits de demandes

**Permissions :** admin, bureau_staff

**Réponse (200) :**
```json
{
    "message": "Statistiques récupérées avec succès",
    "data": {
        "total_products": 145,
        "by_status": {
            "pending": 35,
            "invoiced": 28,
            "completed": 45,
            "approved": 37
        },
        "by_priority": {
            "high": 42,
            "medium": 67,
            "low": 36
        },
        "total_value": 18750.50,
        "average_processing_days": 3.2,
        "completion_rate": 85.5
    }
}
```

---

## 📋 Modèles de données

### Utilisateur
```json
{
    "id": "integer",
    "first_name": "string",
    "last_name": "string",
    "full_name": "string (computed)",
    "email": "string (unique)",
    "role": "enum: client|bureau_staff|mechanic|admin",
    "role_label": "string",
    "phone": "string|null",
    "address": "string|null",
    "company": "string|null",
    "is_active": "boolean",
    "email_verified_at": "datetime|null",
    "last_login_at": "datetime|null",
    "deleted_at": "datetime|null",
    "created_at": "datetime",
    "updated_at": "datetime"
}
```

### Pilote
```json
{
    "id": "integer",
    "first_name": "string",
    "last_name": "string",
    "full_name": "string (computed)",
    "date_of_birth": "date",
    "license_number": "string (unique)",
    "license_expiry": "date",
    "phone": "string|null",
    "email": "string|null",
    "address": "string|null",
    "emergency_contact": "string|null",
    "medical_info": "text|null",
    "is_active": "boolean",
    "client_id": "integer (foreign key)",
    "age": "integer (computed)",
    "license_expires_soon": "boolean (computed)",
    "deleted_at": "datetime|null",
    "created_at": "datetime",
    "updated_at": "datetime"
}
```

### Produit
```json
{
    "id": "integer",
    "name": "string",
    "reference": "string (unique)",
    "description": "text|null",
    "category": "string",
    "brand": "string|null",
    "model": "string|null",
    "unity": "enum: Piece|Liters|Hours|Kg",
    "price": "decimal(10,2)",
    "stock_quantity": "integer",
    "min_stock": "integer",
    "max_stock": "integer",
    "is_active": "boolean",
    "stock_status": "string (computed)",
    "needs_restock": "boolean (computed)",
    "deleted_at": "datetime|null",
    "created_at": "datetime",
    "updated_at": "datetime"
}
```

---

## 📊 Codes de statut

### Codes de succès
- **200** : OK - Requête réussie
- **201** : Created - Ressource créée avec succès
- **204** : No Content - Suppression réussie

### Codes d'erreur
- **400** : Bad Request - Requête malformée
- **401** : Unauthorized - Token manquant ou invalide
- **403** : Forbidden - Permissions insuffisantes
- **404** : Not Found - Ressource inexistante
- **422** : Unprocessable Entity - Erreurs de validation
- **500** : Internal Server Error - Erreur serveur

---

## 🔍 Exemples d'utilisation

### Authentification et utilisation basique

```bash
# 1. Connexion
curl -X POST https://api.kartrepair.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password123"
  }'

# Réponse : { "token": "1|abcd1234...", ... }

# 2. Utiliser le token pour accéder aux ressources
curl -X GET https://api.kartrepair.com/api/users \
  -H "Authorization: Bearer 1|abcd1234..." \
  -H "Accept: application/json"

# 3. Créer un produit
curl -X POST https://api.kartrepair.com/api/products \
  -H "Authorization: Bearer 1|abcd1234..." \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Pneu avant Michelin",
    "reference": "MICH-F-001",
    "category": "Pneumatiques",
    "price": 150.00,
    "stock_quantity": 25
  }'
```

### Workflow de réparation complet

```bash
# 1. Créer une demande de réparation
curl -X POST https://api.kartrepair.com/api/repair-requests \
  -H "Authorization: Bearer token..." \
  -d '{
    "title": "Réparation moteur",
    "description": "Surchauffe détectée",
    "priority": "high",
    "kart_id": 1
  }'

# 2. Ajouter des produits à la demande
curl -X POST https://api.kartrepair.com/api/repair-request-products \
  -H "Authorization: Bearer token..." \
  -d '{
    "repair_request_id": 1,
    "product_id": 5,
    "quantity": 2,
    "priority": "high",
    "unit_price": 150.00
  }'

# 3. Démarrer la réparation
curl -X PATCH https://api.kartrepair.com/api/repair-requests/1/start \
  -H "Authorization: Bearer token..." \
  -d '{
    "assigned_to": 3
  }'

# 4. Facturer le produit
curl -X PATCH https://api.kartrepair.com/api/repair-request-products/1/invoice \
  -H "Authorization: Bearer token..."

# 5. Marquer comme terminé
curl -X PATCH https://api.kartrepair.com/api/repair-request-products/1/complete \
  -H "Authorization: Bearer token..."

# 6. Approuver
curl -X PATCH https://api.kartrepair.com/api/repair-request-products/1/approve \
  -H "Authorization: Bearer token..."
```

---

## 📚 Notes importantes

### Permissions et rôles
- **admin** : Accès complet à toutes les fonctionnalités
- **bureau_staff** : Gestion des réparations, produits, clients (pas de gestion utilisateurs admin)
- **mechanic** : Consultation et mise à jour des réparations assignées
- **client** : Consultation de ses propres données (pilotes, karts, demandes)

### Pagination
- Toutes les listes supportent la pagination
- Paramètres : `page`, `per_page` (max 100)
- Réponse inclut `links` et `meta` pour navigation

### Soft Delete
- La plupart des ressources utilisent le soft delete
- Routes `/trashed`, `/restore`, `/force-delete` disponibles
- Seuls les admins peuvent gérer les éléments supprimés

### Validation
- Toutes les entrées sont validées côté serveur
- Messages d'erreur en français
- Codes d'erreur HTTP standards

### Recherche et filtrage
- Paramètres `search` disponibles sur la plupart des listes
- Filtres spécifiques par entité
- Tri configurable avec `sort` et `direction`

---

*Documentation générée automatiquement - Version 1.0.0*
