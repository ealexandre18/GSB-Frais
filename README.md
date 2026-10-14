# 💼 GSB Frais — Gestion des Frais de Déplacement
> **Laboratoire Galaxy Swiss Bourdin (GSB)**  
> **BTS SIO SLAM • Atelier de Professionnalisation**  
> **Étudiant :** ALEXANDRE Ewan (Groupe 1)  
> **Enseignante :** Mme BEAUVALLET  
> **Année scolaire :** 2026 - 2027

---

## 📌 Présentation du Projet

Le projet **GSB Frais** a pour objectif la modernisation, l'extension fonctionnelle et la sécurisation de l'application web de gestion des frais de déplacement des visiteurs médicaux du laboratoire **Galaxy Swiss Bourdin**.

L'application a été structurée sous la forme d'un bundle modulaire **Symfony** (`GsbFraisBundle`), exploitant une base de données relationnelle **PostgreSQL** (hébergée sur Supabase en production/développement, compatible local) et un moteur de templates **Twig**.

---

## 📦 Livrables Officiels (Téléchargement à la carte)

Tous les livrables sont disponibles en téléchargement direct et individuel ci-dessous :

| Livrable | Description | Format & Taille | Téléchargement direct |
| :--- | :--- | :---: | :---: |
| 📁 **Code source Applicatif** | Archive ZIP du dossier `GsbFraisBundle` débarrassé de tout document annexe | **ZIP (~45 Ko)** | [📥 Télécharger GsbFraisBundle.zip](livrables/GsbFraisBundle.zip) |
| 📑 **Compte Rendu de Réalisation** | Dossier technique complet (architecture MVC, rôle comptable, PDO, maquettes) | **PDF (18 pages)** | [📥 Télécharger le Compte Rendu (PDF)](livrables/Compte_rendu_GSB_ALEXANDRE_EWAN_ATELIER.pdf) |
| 📖 **Notice Utilisateur** | Guide utilisateur pas à pas pour les visiteurs et les comptables | **PDF (12 pages)** | [📥 Télécharger la Notice (PDF)](livrables/Notice_Utilisateur_GSB_ATELIER.pdf) |
| 📋 **Cahier des Charges** | Spécifications fonctionnelles et contraintes organisationnelles GSB | **PDF (9 pages)** | [📥 Télécharger le Cahier des Charges (PDF)](livrables/Cahier_des_charges_GSB.pdf) |

> 💡 **Navigation directe dans les sources :** Vous pouvez également explorer directement l'arborescence des fichiers du bundle sans télécharger l'archive en ouvrant le dossier [📂 `GsbFraisBundle/`](GsbFraisBundle/).

---

## 🏛️ Structure du Bundle Symfony (`GsbFraisBundle`)

Le dossier `GsbFraisBundle` est prêt à être intégré dans un projet Symfony (dossier `src/Pg/GsbFraisBundle/`) :

```text
GsbFraisBundle/
├── Controller/               # Contrôleurs applicatifs
│   ├── AccueilController.php     # Tableau de bord et redirection selon rôle
│   ├── ConnexionController.php   # Authentification visiteur et comptable
│   ├── GererFraisController.php  # Saisie et modification des fiches de frais
│   ├── SuiviFraisController.php  # Consultation de l'historique des fiches
│   ├── ValiderFraisController.php# Validation et mise en paiement (rôle comptable)
│   └── include/                  # Fonctions utilitaires métier (fct.inc.php)
├── DependencyInjection/      # Configuration d'injection de dépendances Symfony
├── Resources/
│   ├── config/               # Routage (routing.yml) et services (services.yml)
│   ├── public/               # Feuilles de style CSS, images, logos
│   └── views/                # Gabarits Twig (vues visiteur, vues comptable, base)
├── services/
│   └── PdoGsb.php            # Service singleton PDO d'accès aux données PostgreSQL
├── Tests/                    # Tests unitaires
└── PgGsbFraisBundle.php      # Déclaration de la classe Bundle Symfony
```

---

## 🛠️ Stack Technique

* **Langage :** PHP 7.x / 8.x
* **Framework :** Symfony (Bundle Architecture)
* **Moteur de gabarit :** Twig
* **Base de données :** PostgreSQL (Supabase / local)
* **Accès aux données :** Requêtes préparées PDO via le service `PdoGsb`
* **Interface graphique :** HTML5, CSS3, Bootstrap responsive

---

## 👥 Comptes de Démonstration / Évaluation

Pour tester l'application sur un environnement de test :

| Profil | Identifiant | Mot de passe | Droits & Accès |
| :--- | :--- | :--- | :--- |
| **Visiteur Médical** | `dandre` | `oppg5` | Saisie de frais, suivi des fiches personnelles |
| **Comptable** | `comptable1` | `secret` | Validation de fiches, report de frais, mise en paiement |

---

## 🚀 Intégration dans Symfony

1. Cloner ou télécharger ce dépôt.
2. Copier le dossier `GsbFraisBundle` dans `src/Pg/GsbFraisBundle`.
3. Enregistrer le bundle dans `app/AppKernel.php` :
   ```php
   new Pg\GsbFraisBundle\PgGsbFraisBundle(),
   ```
4. Importer les routes dans `app/config/routing.yml` :
   ```yaml
   pg_gsb_frais:
       resource: "@PgGsbFraisBundle/Resources/config/routing.yml"
       prefix:   /
   ```
5. Renseigner les accès PostgreSQL dans `app/config/parameters.yml` ou `services.yml`.
