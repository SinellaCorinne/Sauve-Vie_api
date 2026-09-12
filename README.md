# Blood Donor — API Backend

API REST Laravel pour la mise en relation urgente de donneurs de sang et d'hôpitaux.

## Stack

- **Laravel 11** + **PHP 8.2+**
- **PostgreSQL** (production) / SQLite (dev local)
- **Laravel Sanctum** — authentification par token Bearer
- Déployable sur **Railway**

---

## Installation locale

```bash
git clone <repo-url> blood-donor
cd blood-donor

composer install

cp .env.example .env
# Éditer .env avec tes valeurs (voir section Configuration)

php artisan key:generate
php artisan migrate --seed

php artisan serve
```

L'API est disponible sur `http://localhost:8000/api`.

**Comptes de test créés par le seeder :**

| Rôle    | Email               | Mot de passe |
|---------|---------------------|--------------|
| Donneur | donor@test.com      | password     |
| Hôpital | hospital@test.com   | password     |

---

## Configuration

Copier `.env.example` en `.env` et renseigner :

```env
# Dev local (SQLite)
DB_CONNECTION=sqlite

# Production (PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=blood_donor
DB_USERNAME=postgres
DB_PASSWORD=secret
```

---

## Authentification

Toutes les routes (sauf register/login) nécessitent un token Sanctum dans le header :

```
Authorization: Bearer <token>
```

Le token est retourné à l'inscription et à la connexion.

---

## Endpoints

### Auth

| Méthode | Endpoint                     | Description                        | Auth |
|---------|------------------------------|------------------------------------|------|
| POST    | /api/auth/register/donor     | Inscription donneur                | Non  |
| POST    | /api/auth/register/hospital  | Inscription hôpital                | Non  |
| POST    | /api/auth/login              | Connexion (donneur ou hôpital)     | Non  |
| POST    | /api/auth/logout             | Déconnexion                        | Oui  |
| GET     | /api/auth/me                 | Utilisateur connecté               | Oui  |

**Inscription donneur** — `POST /api/auth/register/donor`
```json
{
  "name": "Ahmed Bensalem",
  "email": "ahmed@example.com",
  "password": "password",
  "password_confirmation": "password",
  "blood_type": "O-",
  "phone": "0555123456",
  "city": "Alger",
  "last_donation_date": "2026-01-15"
}
```

**Inscription hôpital** — `POST /api/auth/register/hospital`
```json
{
  "name": "CHU Mustapha",
  "email": "chu@example.com",
  "password": "password",
  "password_confirmation": "password",
  "institution_name": "CHU Mustapha Bacha",
  "city": "Alger",
  "address": "1 Place du 1er Mai, Alger",
  "phone": "021739301"
}
```

---

### Donneur

| Méthode | Endpoint                        | Description                              |
|---------|---------------------------------|------------------------------------------|
| GET     | /api/donor/profile              | Profil du donneur connecté               |
| PATCH   | /api/donor/profile              | Mise à jour du profil                    |
| PATCH   | /api/donor/availability         | Basculer disponibilité (on/off)          |
| GET     | /api/donor/history              | Historique des réponses/dons             |
| GET     | /api/donor/compatible-requests  | Demandes ouvertes compatibles avec son groupe |

---

### Hôpital

| Méthode | Endpoint                        | Description                              |
|---------|---------------------------------|------------------------------------------|
| GET     | /api/hospital/profile           | Profil de l'hôpital connecté             |
| PATCH   | /api/hospital/profile           | Mise à jour du profil                    |
| GET     | /api/hospital/blood-requests    | Toutes les demandes de l'hôpital (tous statuts) |

---

### Demandes de sang

| Méthode | Endpoint                                | Description                              |
|---------|-----------------------------------------|------------------------------------------|
| GET     | /api/blood-requests                     | Liste des demandes ouvertes (avec filtres) |
| POST    | /api/blood-requests                     | Créer une demande (hôpital)              |
| GET     | /api/blood-requests/{id}                | Détail d'une demande                     |
| PATCH   | /api/blood-requests/{id}                | Modifier une demande ouverte             |
| DELETE  | /api/blood-requests/{id}                | Supprimer une demande ouverte            |
| PATCH   | /api/blood-requests/{id}/fulfill        | Clôturer comme satisfaite               |
| PATCH   | /api/blood-requests/{id}/expire         | Marquer comme expirée                    |

**Créer une demande** — `POST /api/blood-requests`
```json
{
  "blood_type": "O-",
  "urgency_level": "critical",
  "description": "Patient polytraumatisé, besoin immédiat.",
  "expires_at": "2026-09-13T12:00:00Z"
}
```

**Filtres disponibles sur GET /api/blood-requests :**
```
?blood_type=O-
?city=Alger
?urgency_level=critical
```

---

### Matching

| Méthode | Endpoint                                | Description                              |
|---------|-----------------------------------------|------------------------------------------|
| GET     | /api/blood-requests/{id}/matching       | Donneurs compatibles pour une demande (hôpital propriétaire) |

Filtres optionnels : `?city=Alger`

**Réponse :**
```json
{
  "blood_request": { "id": 1, "blood_type": "O-", "urgency_level": "critical" },
  "compatible_types": ["O-"],
  "donors": {
    "data": [
      {
        "id": 5,
        "name": "Ahmed Bensalem",
        "blood_type": "O-",
        "city": "Alger",
        "phone": "0555123456",
        "last_donation_date": null,
        "has_responded": false
      }
    ]
  }
}
```

---

### Réponses des donneurs

| Méthode | Endpoint                                          | Description                                    |
|---------|---------------------------------------------------|------------------------------------------------|
| POST    | /api/blood-requests/{id}/respond                  | Signaler sa disponibilité (donneur)            |
| DELETE  | /api/blood-requests/{id}/respond                  | Annuler sa réponse (si pending)               |
| GET     | /api/blood-requests/{id}/responses                | Lister les réponses (hôpital propriétaire)    |
| PATCH   | /api/blood-requests/{id}/responses/{responseId}   | Confirmer ou décliner un donneur              |

**Répondre à une demande** — `POST /api/blood-requests/{id}/respond`
```json
{
  "note": "Disponible immédiatement, je suis à Alger centre."
}
```

**Confirmer/décliner un donneur** — `PATCH /api/blood-requests/{id}/responses/{responseId}`
```json
{
  "status": "confirmed"
}
```
> Quand le statut passe à `confirmed`, la `last_donation_date` du donneur est automatiquement mise à jour à aujourd'hui.

---

## Règles métier

**Compatibilité ABO + Rhésus**

| Groupe recherché | Donneurs acceptés              |
|-----------------|-------------------------------|
| A+              | A+, A-, O+, O-                |
| A-              | A-, O-                        |
| B+              | B+, B-, O+, O-                |
| B-              | B-, O-                        |
| AB+             | Tous (receveur universel)      |
| AB-             | A-, B-, AB-, O-               |
| O+              | O+, O-                        |
| O-              | O- uniquement                 |

**Éligibilité d'un donneur**
- `is_available = true`
- `last_donation_date` est null **ou** date de plus de 3 mois

**Cycle de vie d'une demande**
```
open ──→ fulfilled  (clôturée par l'hôpital)
     └──→ expired   (délai dépassé)
```

---

## Structure du projet

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── DonorController.php
│   │   ├── HospitalController.php
│   │   ├── BloodRequestController.php
│   │   ├── MatchingController.php
│   │   └── DonorResponseController.php
│   └── Requests/
│       ├── RegisterDonorRequest.php
│       ├── RegisterHospitalRequest.php
│       ├── StoreBloodRequestRequest.php
│       ├── UpdateDonorRequest.php
│       └── UpdateHospitalRequest.php
├── Models/
│   ├── User.php
│   ├── Hospital.php
│   ├── BloodRequest.php
│   └── DonorResponse.php
└── Policies/
    └── BloodRequestPolicy.php

database/
├── migrations/
│   ├── ..._create_users_table.php
│   ├── ..._create_hospitals_table.php
│   ├── ..._create_blood_requests_table.php
│   └── ..._create_donor_responses_table.php
├── factories/        (UserFactory, HospitalFactory, BloodRequestFactory, DonorResponseFactory)
└── seeders/          (DatabaseSeeder)

routes/
└── api.php           (25 endpoints)
```

---

## Déploiement Railway

Voir les instructions de déploiement dans la section ci-dessous ou suivre le guide Railway officiel.

1. Pusher le repo sur GitHub
2. Créer un projet Railway depuis le repo
3. Ajouter un plugin **PostgreSQL** dans Railway
4. Copier les variables d'environnement Railway dans les variables du service Laravel
5. Lancer `php artisan migrate --seed` via le terminal Railway

---

## Codes de réponse

| Code | Signification                              |
|------|--------------------------------------------|
| 200  | Succès                                     |
| 201  | Ressource créée                            |
| 401  | Non authentifié                            |
| 403  | Non autorisé (mauvais rôle ou non propriétaire) |
| 404  | Ressource introuvable                      |
| 409  | Conflit (ex: déjà répondu à une demande)  |
| 422  | Données invalides ou règle métier violée   |
