-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mer. 27 mai 2026 à 16:24
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `sae-201-203`
--

-- --------------------------------------------------------

--
-- Structure de la table `admin`
--

CREATE TABLE `admin` (
  `id_admin` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `prenom` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `tel` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `admin`
--

INSERT INTO `admin` (`id_admin`, `nom`, `prenom`, `email`, `password`, `tel`) VALUES
(1, 'Admin', 'Admin', 'admin@gmail.com', '$2y$10$iPf3G9r44p4o5kuKjgZuBOu9Kz2RF4w6fkSwIwziX4E3YobteJQK2', '0606060606');

-- --------------------------------------------------------

--
-- Structure de la table `enseignant`
--

CREATE TABLE `enseignant` (
  `id_enseignant` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(100) NOT NULL,
  `valide` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `enseignant`
--

INSERT INTO `enseignant` (`id_enseignant`, `nom`, `prenom`, `email`, `password`, `role`, `valide`) VALUES
(1, 'enseignant', 'enseignant', 'enseignant@gmail.com', '$2y$10$RJlK4Lkr99YoIyL7GFFgoe6T./fCUOZywKseKCdXKki.l.pi4.vrq', 'Jury', 1),
(4, 'enseignant2', 'enseignant2', 'enseignant2@gmail.com', '$2y$10$vdMiX1xom0.3khPT8Qt4DuS2D8WQdLL0vrjjL.LREkM3GMM8VMjXi', 'Responsable-Stage-MMI1', 1);

-- --------------------------------------------------------

--
-- Structure de la table `entreprise`
--

CREATE TABLE `entreprise` (
  `id_entreprise` int(11) NOT NULL,
  `nom_societe` varchar(150) NOT NULL,
  `adresse_siege` text NOT NULL,
  `tel_contact` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `etudiant`
--

CREATE TABLE `etudiant` (
  `id_etudiant` int(11) NOT NULL,
  `matricule` varchar(50) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `tel` varchar(20) DEFAULT NULL,
  `date_naiss` date DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `promo` varchar(50) DEFAULT NULL,
  `gp_td` varchar(10) DEFAULT NULL,
  `gp_tp` varchar(10) DEFAULT NULL,
  `id_offre` int(11) DEFAULT NULL,
  `valide` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `etudiant`
--

INSERT INTO `etudiant` (`id_etudiant`, `matricule`, `nom`, `prenom`, `email`, `password`, `tel`, `date_naiss`, `adresse`, `promo`, `gp_td`, `gp_tp`, `id_offre`, `valide`) VALUES
(1, '00000000', 'etudiant1', 'etudiant1', 'etudiant1@gmail.com', '$2y$10$Xz1rzspUhLVhvSFbaMcmPeUL/5oaNtRRO6savz3GWq6tX/iurrwd2', '', '2026-05-07', 'Meaux', 'MMI 1', '1', 'a', NULL, 1),
(2, '00000002', 'etudiant2', 'etudiant2', 'etudiant2@gmail.com', '$2y$10$H6LeKSpBVpWsfzjIF/3dguASi.dXFX6e8vBLZllBAAONV2lFfRCIG', '0695100250', '2026-05-07', 'Meaux', 'MMI 2', '1', 'a', NULL, 1),
(3, '00000003', 'etudiant3', 'etudiant3', 'etudiant3@gmail.com', '$2y$10$3FMik/i9W6E4FXHHB6109.IkeqEzMRuwbjO3CUqCpAibsW6WPtL4q', '', '2026-05-07', 'Meaux', 'MMI 3', '1', 'a', NULL, 1);

-- --------------------------------------------------------

--
-- Structure de la table `historique`
--

CREATE TABLE `historique` (
  `id_recherche` int(11) NOT NULL,
  `entreprise_cible` varchar(150) NOT NULL,
  `date_contact` date NOT NULL,
  `type_action` varchar(50) DEFAULT NULL,
  `reponse` text DEFAULT NULL,
  `id_etudiant` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `historique`
--

INSERT INTO `historique` (`id_recherche`, `entreprise_cible`, `date_contact`, `type_action`, `reponse`, `id_etudiant`) VALUES
(5, 'Entreprise Partenaire (Offre N°1)', '2026-05-27', 'Candidature Intranet', 'En attente', 2),
(6, 'Entreprise Partenaire (Offre N°1)', '2026-05-27', 'Candidature Intranet', 'En attente', 2);

-- --------------------------------------------------------

--
-- Structure de la table `offre`
--

CREATE TABLE `offre` (
  `id_offre` int(11) NOT NULL,
  `intitule` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `competences` text DEFAULT NULL,
  `duree` varchar(50) DEFAULT NULL,
  `lieu` varchar(150) DEFAULT NULL,
  `remuneration` decimal(10,2) DEFAULT NULL,
  `promotion_visee` enum('MMI1','MMI2','MMI3') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `offre`
--

INSERT INTO `offre` (`id_offre`, `intitule`, `description`, `competences`, `duree`, `lieu`, `remuneration`, `promotion_visee`) VALUES
(1, 'Stage développement', 'Stage dans lequel l\'étudiant devra réaliser un site web avec base de données.', 'Langages JavaScript, HTML5, CSS3, PHP et SQL.', '60', 'IUT Gustave Eiffel Meaux', 0.00, 'MMI2');

-- --------------------------------------------------------

--
-- Structure de la table `responsable_de_stage`
--

CREATE TABLE `responsable_de_stage` (
  `id_responsable` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `tel` varchar(20) DEFAULT NULL,
  `email_pro` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `id_entreprise` int(11) NOT NULL,
  `valide` tinyint(1) DEFAULT 0,
  `grade` enum('MMI1','MMI2','MMI3') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `soutenance`
--

CREATE TABLE `soutenance` (
  `id_soutenance` int(11) NOT NULL,
  `date` date NOT NULL,
  `heure` time NOT NULL,
  `salle` varchar(50) DEFAULT NULL,
  `id_etudiant` int(11) NOT NULL,
  `id_enseignant_1` int(11) NOT NULL,
  `id_enseignant_2` int(11) NOT NULL,
  `note_rapport` decimal(5,2) DEFAULT NULL,
  `note_oral` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `soutenance`
--

INSERT INTO `soutenance` (`id_soutenance`, `date`, `heure`, `salle`, `id_etudiant`, `id_enseignant_1`, `id_enseignant_2`, `note_rapport`, `note_oral`) VALUES
(1, '2026-05-29', '10:45:00', 'B316', 2, 1, 4, 15.00, 16.00),
(2, '2026-05-31', '15:50:00', 'B316', 1, 1, 4, 17.00, 5.00);

-- --------------------------------------------------------

--
-- Structure de la table `stage`
--

CREATE TABLE `stage` (
  `id_stage` int(11) NOT NULL,
  `num_convention` varchar(100) NOT NULL,
  `sujet` text NOT NULL,
  `date_deb` date NOT NULL,
  `date_fin` date NOT NULL,
  `date_visite` date DEFAULT NULL,
  `etat_validation` varchar(50) DEFAULT NULL,
  `id_enseignant` int(11) NOT NULL,
  `id_etudiant` int(11) NOT NULL,
  `id_offre` int(11) DEFAULT NULL,
  `id_responsable` int(11) NOT NULL,
  `id_entreprise` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `enseignant`
--
ALTER TABLE `enseignant`
  ADD PRIMARY KEY (`id_enseignant`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `entreprise`
--
ALTER TABLE `entreprise`
  ADD PRIMARY KEY (`id_entreprise`);

--
-- Index pour la table `etudiant`
--
ALTER TABLE `etudiant`
  ADD PRIMARY KEY (`id_etudiant`),
  ADD UNIQUE KEY `matricule` (`matricule`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_etudiant_offre` (`id_offre`);

--
-- Index pour la table `historique`
--
ALTER TABLE `historique`
  ADD PRIMARY KEY (`id_recherche`),
  ADD KEY `fk_historique_etudiant` (`id_etudiant`);

--
-- Index pour la table `offre`
--
ALTER TABLE `offre`
  ADD PRIMARY KEY (`id_offre`);

--
-- Index pour la table `responsable_de_stage`
--
ALTER TABLE `responsable_de_stage`
  ADD PRIMARY KEY (`id_responsable`),
  ADD UNIQUE KEY `email_pro` (`email_pro`),
  ADD KEY `fk_maitre_entreprise` (`id_entreprise`);

--
-- Index pour la table `soutenance`
--
ALTER TABLE `soutenance`
  ADD PRIMARY KEY (`id_soutenance`),
  ADD UNIQUE KEY `id_etudiant` (`id_etudiant`),
  ADD KEY `fk_soutenance_enseignant1` (`id_enseignant_1`),
  ADD KEY `fk_soutenance_enseignant2` (`id_enseignant_2`);

--
-- Index pour la table `stage`
--
ALTER TABLE `stage`
  ADD PRIMARY KEY (`id_stage`),
  ADD UNIQUE KEY `num_convention` (`num_convention`),
  ADD UNIQUE KEY `id_offre` (`id_offre`),
  ADD KEY `fk_stage_enseignant` (`id_enseignant`),
  ADD KEY `fk_stage_etudiant` (`id_etudiant`),
  ADD KEY `fk_stage_maitre` (`id_responsable`),
  ADD KEY `fk_stage_entreprise` (`id_entreprise`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `enseignant`
--
ALTER TABLE `enseignant`
  MODIFY `id_enseignant` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `entreprise`
--
ALTER TABLE `entreprise`
  MODIFY `id_entreprise` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `etudiant`
--
ALTER TABLE `etudiant`
  MODIFY `id_etudiant` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `historique`
--
ALTER TABLE `historique`
  MODIFY `id_recherche` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `offre`
--
ALTER TABLE `offre`
  MODIFY `id_offre` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `responsable_de_stage`
--
ALTER TABLE `responsable_de_stage`
  MODIFY `id_responsable` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `soutenance`
--
ALTER TABLE `soutenance`
  MODIFY `id_soutenance` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `stage`
--
ALTER TABLE `stage`
  MODIFY `id_stage` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `etudiant`
--
ALTER TABLE `etudiant`
  ADD CONSTRAINT `fk_etudiant_offre` FOREIGN KEY (`id_offre`) REFERENCES `offre` (`id_offre`);

--
-- Contraintes pour la table `historique`
--
ALTER TABLE `historique`
  ADD CONSTRAINT `fk_historique_etudiant` FOREIGN KEY (`id_etudiant`) REFERENCES `etudiant` (`id_etudiant`);

--
-- Contraintes pour la table `responsable_de_stage`
--
ALTER TABLE `responsable_de_stage`
  ADD CONSTRAINT `fk_maitre_entreprise` FOREIGN KEY (`id_entreprise`) REFERENCES `entreprise` (`id_entreprise`);

--
-- Contraintes pour la table `soutenance`
--
ALTER TABLE `soutenance`
  ADD CONSTRAINT `fk_soutenance_enseignant1` FOREIGN KEY (`id_enseignant_1`) REFERENCES `enseignant` (`id_enseignant`),
  ADD CONSTRAINT `fk_soutenance_enseignant2` FOREIGN KEY (`id_enseignant_2`) REFERENCES `enseignant` (`id_enseignant`),
  ADD CONSTRAINT `fk_soutenance_etudiant` FOREIGN KEY (`id_etudiant`) REFERENCES `etudiant` (`id_etudiant`);

--
-- Contraintes pour la table `stage`
--
ALTER TABLE `stage`
  ADD CONSTRAINT `fk_stage_enseignant` FOREIGN KEY (`id_enseignant`) REFERENCES `enseignant` (`id_enseignant`),
  ADD CONSTRAINT `fk_stage_entreprise` FOREIGN KEY (`id_entreprise`) REFERENCES `entreprise` (`id_entreprise`),
  ADD CONSTRAINT `fk_stage_etudiant` FOREIGN KEY (`id_etudiant`) REFERENCES `etudiant` (`id_etudiant`),
  ADD CONSTRAINT `fk_stage_offre` FOREIGN KEY (`id_offre`) REFERENCES `offre` (`id_offre`),
  ADD CONSTRAINT `fk_stage_responsable` FOREIGN KEY (`id_responsable`) REFERENCES `responsable_de_stage` (`id_responsable`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
