# 🎓 Gestionnaire de Stages - Département MMI

Bienvenue sur le projet **Gestionnaire de Stages**, une application web dédiée à la centralisation, au suivi pédagogique et à la validation des stages pour le département MMI de l'Université Gustave Eiffel.

## 📋 Description du Projet
Cette plateforme permet aux étudiants, enseignants et responsables de promotion de collaborer efficacement sur le cycle de vie d'un stage, de la recherche d'entreprise jusqu'à la soutenance finale.

## 🚀 Fonctionnalités
- **Espace Étudiant :** Déclaration de démarches, suivi des candidatures et dépôt de convention.
- **Espace Enseignant :** Suivi des étudiants, planification des jurys de soutenance et saisie des notes.
- **Espace Responsable (MMI1, MMI2, MMI3) :** Validation des conventions, gestion des offres de stages et pilotage des alertes terrain.
- **Catalogue d'Offres :** Dépôt et consultation d'offres de stages avec filtrage par promotion.
- **Mode Sombre/Clair :** Interface utilisateur flexible avec mémorisation des préférences.

## 🛠 Technologies
- **Frontend :** HTML5, CSS3, Bootstrap 5, JavaScript.
- **Backend :** PHP 8 (Architecture structurée).
- **Base de Données :** MySQL (Connexion via PDO pour une sécurité renforcée).
- **Icônes :** Bootstrap Icons.

## 📂 Structure du projet
```text
/
├── images/               # Ressources graphiques (logos, photos)
├── php/                  # Logique serveur (connexion BDD, actions)
├── enseignant/           # Pages de l'espace enseignant
├── etudiant/             # Pages de l'espace étudiant
├── style.css             # Feuille de style personnalisée
├── index.html            # Page d'accueil et connexion
└── README.md             # Documentation du projet

```

## ⚙️ Installation

1. **Serveur local :** Utilisez un environnement comme XAMPP ou WAMP.
2. **Base de données :** Importez le script SQL dans `phpMyAdmin`.
3. **Configuration :** Adaptez le fichier `php/config.php` avec vos paramètres de base de données :

```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'sae-201-203');
   define('DB_USER', 'root');
   define('DB_PASS', '');

```

## 👥 Équipe de développement

Projet réalisé par :

* **Tom Pelloile**
* **Robin Maréchal**
* **Emerick Angel**

---

*Université Gustave Eiffel - Département MMI 2026*
