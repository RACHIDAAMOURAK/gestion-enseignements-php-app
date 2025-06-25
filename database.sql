-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mer. 25 juin 2025 à 10:58
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
-- Base de données : `projet_web`
--

-- --------------------------------------------------------

--
-- Structure de la table `charges_horaires`
--

CREATE TABLE `charges_horaires` (
  `id` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `annee_universitaire` varchar(10) NOT NULL,
  `total_heures_cm` float DEFAULT 0,
  `total_heures_td` float DEFAULT 0,
  `total_heures_tp` float DEFAULT 0,
  `total_heures` float DEFAULT 0,
  `minimum_requis` float DEFAULT 192,
  `statut` enum('suffisant','insuffisant') GENERATED ALWAYS AS (if(`total_heures` >= `minimum_requis`,'suffisant','insuffisant')) STORED,
  `date_mise_a_jour` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `departements`
--

CREATE TABLE `departements` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `departements`
--

INSERT INTO `departements` (`id`, `nom`, `description`, `date_creation`) VALUES
(1, 'Informatique', 'Département des sciences informatiques ', '2020-08-31 22:00:00'),
(2, 'Mathématiques', 'Département de mathématiques appliquées', '2019-01-14 22:00:00'),
(3, 'Physique', 'Département de physique fondamentale', '2018-06-09 22:00:00');

-- --------------------------------------------------------

--
-- Structure de la table `emplois_temps`
--

CREATE TABLE `emplois_temps` (
  `id` int(11) NOT NULL,
  `id_filiere` int(11) NOT NULL,
  `semestre` int(11) NOT NULL,
  `annee_universitaire` varchar(10) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `fichier_path` varchar(255) DEFAULT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_modification` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `statut` varchar(20) NOT NULL DEFAULT 'actif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `emplois_temps`
--

INSERT INTO `emplois_temps` (`id`, `id_filiere`, `semestre`, `annee_universitaire`, `date_debut`, `date_fin`, `fichier_path`, `date_creation`, `date_modification`, `statut`) VALUES
(21, 21, 1, '2025-2026', '2025-05-26', '2025-06-21', '', '2025-06-06 17:06:47', '2025-06-06 17:06:47', 'actif'),
(22, 20, 1, '2026-2027', '2025-06-09', '2025-07-06', '', '2025-06-06 17:14:20', '2025-06-06 21:47:05', 'actif'),
(23, 20, 2, '2025-2026', '2025-06-02', '2025-07-06', '', '2025-06-06 20:57:04', '2025-06-06 20:57:04', 'actif'),
(25, 20, 4, '2025-2026', '2025-05-26', '2025-06-21', '', '2025-06-06 21:02:32', '2025-06-06 21:02:32', 'inactif'),
(26, 20, 5, '2026-2027', '2025-05-26', '2025-07-06', '', '2025-06-06 21:05:14', '2025-06-06 21:05:14', 'actif'),
(29, 20, 3, '2026-2027', '2025-05-26', '2025-07-06', '', '2025-06-09 20:44:58', '2025-06-09 20:44:58', 'actif'),
(30, 20, 6, '2025-2026', '2025-05-26', '2025-07-05', '', '2025-06-09 22:03:08', '2025-06-09 22:03:08', 'actif');

-- --------------------------------------------------------

--
-- Structure de la table `etudiants`
--

CREATE TABLE `etudiants` (
  `id` int(11) NOT NULL,
  `numero_etudiant` varchar(50) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `id_filiere` int(11) DEFAULT NULL,
  `annee_universitaire` varchar(10) NOT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `etudiants`
--

INSERT INTO `etudiants` (`id`, `numero_etudiant`, `nom`, `prenom`, `email`, `id_filiere`, `annee_universitaire`, `date_creation`) VALUES
(22, 'ET001', 'Nom1', 'Prenom1', 'et001@univ.com', NULL, '2024/2025', '2025-05-18 22:00:00'),
(23, 'ET002', 'Nom2', 'Prenom2', 'et002@univ.com', NULL, '2024/2025', '2025-05-18 22:00:00'),
(24, 'ET003', 'Nom3', 'Prenom3', 'et003@univ.com', NULL, '2024/2025', '2025-05-18 22:00:00'),
(25, 'ET004', 'Nom4', 'Prenom4', 'et004@univ.com', NULL, '2024/2025', '2025-05-18 22:00:00'),
(26, 'ET005', 'Nom5', 'Prenom5', 'et005@univ.com', NULL, '2024/2025', '2025-05-18 22:00:00'),
(27, 'ET006', 'Nom6', 'Prenom6', 'et006@univ.com', NULL, '2024/2025', '2025-05-18 22:00:00'),
(28, 'ET007', 'Nom7', 'Prenom7', 'et007@univ.com', NULL, '2024/2025', '2025-05-18 22:00:00');

-- --------------------------------------------------------

--
-- Structure de la table `fichiers_notes`
--

CREATE TABLE `fichiers_notes` (
  `id` int(11) NOT NULL,
  `id_unite_enseignement` int(11) NOT NULL,
  `id_enseignant` int(11) NOT NULL,
  `type_session` enum('normale','rattrapage') NOT NULL,
  `nom_fichier` varchar(255) NOT NULL,
  `chemin_fichier` varchar(255) NOT NULL,
  `date_upload` datetime DEFAULT current_timestamp(),
  `statut` enum('traite','non_traite') DEFAULT 'non_traite'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `fichiers_notes`
--

INSERT INTO `fichiers_notes` (`id`, `id_unite_enseignement`, `id_enseignant`, `type_session`, `nom_fichier`, `chemin_fichier`, `date_upload`, `statut`) VALUES
(51, 79, 1, 'rattrapage', 'Classeur1.xlsx', '6845cba5cc217_notes_79_rattrapage.xlsx', '2025-06-08 18:43:02', 'traite'),
(54, 12, 1, 'normale', 'Classeur1.xlsx', '6845cf611d14e_notes_12_normale.xlsx', '2025-06-08 18:58:57', 'traite'),
(55, 11, 88, 'normale', 'Classeur1.xlsx', '6847338fe20cc_notes_11_normale.xlsx', '2025-06-09 20:18:40', 'traite'),
(56, 1244, 102, 'normale', 'Classeur1.xlsx', '1749499189_102_1244.xlsx', '2025-06-09 20:59:49', 'traite'),
(57, 1244, 102, 'rattrapage', 'Classeur1.xlsx', '1749500859_102_1244.xlsx', '2025-06-09 21:27:39', 'traite'),
(58, 87, 87, 'normale', 'Classeur1 (1).xlsx', '684749cd64272_notes_87_normale.xlsx', '2025-06-09 21:53:33', 'traite');

-- --------------------------------------------------------

--
-- Structure de la table `filieres`
--

CREATE TABLE `filieres` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `id_specialite` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `id_coordonnateur` int(11) DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_departement` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `filieres`
--

INSERT INTO `filieres` (`id`, `nom`, `id_specialite`, `description`, `id_coordonnateur`, `date_creation`, `id_departement`) VALUES
(20, 'Génie Informatique (GI)', 1, 'Formation en ingénierie logicielle et systèmes informatiques ', 68, '2023-08-31 23:00:00', 1),
(21, 'Ingénierie de Données (ID)', 4, 'Big Data, analytics et gestion des données massives', 68, '2023-08-31 23:00:00', 1),
(22, 'Transformation Digitale et IA (TDIA)', 16, 'Digitalisation et systèmes intelligents', 68, '2023-12-31 23:00:00', 1),
(23, 'Génie Civil (GC)', 23, 'Mathématiques appliquées à la construction et génie civil', NULL, '2023-08-31 23:00:00', 2),
(24, 'Génie de l\'Eau et Environnement (GEE)', 24, 'Modélisation mathématique des systèmes hydriques', NULL, '2023-08-31 23:00:00', 2),
(25, 'Génie Energétique Renouvelable (GER)', 26, 'Physique appliquée aux énergies vertes', NULL, '2023-08-31 23:00:00', 3);

-- --------------------------------------------------------

--
-- Structure de la table `groupes`
--

CREATE TABLE `groupes` (
  `id` int(11) NOT NULL,
  `type` enum('TD','TP') NOT NULL,
  `numero` int(11) NOT NULL,
  `id_unite_enseignement` int(11) NOT NULL,
  `id_filiere` int(11) NOT NULL,
  `effectif` int(11) DEFAULT 0,
  `annee_universitaire` varchar(10) NOT NULL,
  `semestre` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `groupes`
--

INSERT INTO `groupes` (`id`, `type`, `numero`, `id_unite_enseignement`, `id_filiere`, `effectif`, `annee_universitaire`, `semestre`) VALUES
(108, 'TD', 1, 10, 1, 20, '2025', 2),
(109, 'TP', 2, 12, 4, 20, '2025', 2),
(127, 'TD', 1, 74, 20, 3, '2025', 1),
(128, 'TP', 2, 125, 21, 20, '2025', 1),
(129, 'TD', 2, 79, 20, 12, '2025', 2),
(130, 'TP', 1, 6, 20, 20, '2025', 1),
(131, 'TD', 2, 1247, 20, 20, '2025', 1);

-- --------------------------------------------------------

--
-- Structure de la table `historique_affectations`
--

CREATE TABLE `historique_affectations` (
  `id` int(11) NOT NULL,
  `id_utilisateur` int(11) DEFAULT NULL,
  `nom_utilisateur` varchar(100) DEFAULT NULL,
  `role` enum('enseignant','coordonnateur','vacataire') NOT NULL,
  `id_unite_enseignement` int(11) DEFAULT NULL,
  `code_ue` varchar(20) DEFAULT NULL,
  `intitule_ue` varchar(100) DEFAULT NULL,
  `id_filiere` int(11) DEFAULT NULL,
  `nom_filiere` varchar(100) DEFAULT NULL,
  `id_departement` int(11) DEFAULT NULL,
  `nom_departement` varchar(100) DEFAULT NULL,
  `annee_universitaire` varchar(10) NOT NULL,
  `semestre` int(11) NOT NULL,
  `type_cours` enum('CM','TD','TP') NOT NULL,
  `volume_horaire` float NOT NULL,
  `statut` varchar(20) DEFAULT NULL,
  `date_affectation` timestamp NOT NULL DEFAULT current_timestamp(),
  `commentaire_chef` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `historique_affectations`
--

INSERT INTO `historique_affectations` (`id`, `id_utilisateur`, `nom_utilisateur`, `role`, `id_unite_enseignement`, `code_ue`, `intitule_ue`, `id_filiere`, `nom_filiere`, `id_departement`, `nom_departement`, `annee_universitaire`, `semestre`, `type_cours`, `volume_horaire`, `statut`, `date_affectation`, `commentaire_chef`) VALUES
(233, 3, ' Pierre', 'enseignant', 3, 'INFO201', 'mecanoique de fluide', 2, 'mecanique', 1, 'Informatique', '2024-2025', 1, 'TP', 20, 'affecté', '2025-04-27 15:11:46', 'fffffffffffffffffffffffffffffffffff'),
(238, 1, ' Jean', 'enseignant', 4, 'MATH101', 'programmationc++', 1, ' Informatique', 1, 'Informatique', '2025-2026', 1, 'CM', 30, 'validé', '2025-04-19 20:34:00', 'Importé depuis Excel'),
(241, 3, ' Pierre', 'enseignant', 7, 'RES101', 'psycopatre', 4, 'eau et environmment', 1, 'Informatique', '2025-2026', 1, 'TD', 20, 'validé', '2025-04-17 16:15:00', 'Importé depuis Excel'),
(242, 1, ' Jean', 'enseignant', 2, 'INFO102', 'analyse1', 2, 'mecanique', 1, 'Informatique', '2025-2026', 1, 'CM', 24, 'validé', '2025-04-17 16:05:00', 'Importé depuis Excel'),
(258, 1, 'Durand Jean', 'enseignant', 7, 'AL123', 'AAAB', 1, 'Génie Logiciel', 1, 'Informatique', '2023-2024', 1, 'CM', 13, 'affecté', '2025-05-20 15:27:36', ''),
(265, 1, 'Durand Jean', 'enseignant', 3, 'AB123', '12', 1, 'Génie Logiciel', 1, 'Informatique', '2024-2025', 4, 'TP', 5, 'affecté', '2025-06-05 14:31:21', ''),
(266, 1, 'Durand Jean', 'enseignant', 12, 'KK555', 'PRAGRAMMATION C++', 4, 'geora', 1, 'Informatique', '2024-2025', 1, 'CM', 45, 'affecté', '2025-06-05 15:20:58', ''),
(267, 1, 'Durand Jean', 'enseignant', 79, 'M1116', 'Culture and Art skills', 20, 'Génie Informatique (GI)', 1, 'Informatique', '2024-2025', 1, 'CM', 26, 'affecté', '2025-06-06 16:22:40', ''),
(273, 88, 'Sofia Sofi', 'enseignant', 11, 'DA123', 'Analyse de donnee1', 21, 'Ingénierie de Données (ID)', 1, 'Informatique', '2025-2026', 1, 'CM', 20, 'validé', '2025-06-09 18:56:00', 'Importé depuis Excel'),
(274, 1, 'Durand Jean', 'enseignant', 79, 'M1116', 'Culture and Art skills', 20, 'Génie Informatique (GI)', 1, 'Informatique', '2025-2026', 1, 'CM', 26, 'validé', '2025-06-06 16:22:00', 'Importé depuis Excel'),
(275, 87, 'Smlali Hanane', 'enseignant', 87, 'M126', 'Prompt ingeniering for developpers', 20, 'Génie Informatique (GI)', 1, 'Informatique', '2024-2025', 2, 'CM', 26, 'affecté', '2025-06-09 19:48:23', ''),
(277, 1, 'Durand Jean', 'enseignant', 75, 'MM112', 'Langage C avancé et structures de données', 20, 'Génie Informatique (GI)', 1, 'Informatique', '2024-2025', 1, 'CM', 26, 'affecté', '2025-06-09 20:37:02', 'yrureuieuuee'),
(278, 1, 'Durand Jean', 'enseignant', 75, 'MM112', 'Langage C avancé et structures de données', 20, 'Génie Informatique (GI)', 1, 'Informatique', '2025-2026', 1, 'CM', 26, 'validé', '2025-06-09 20:37:00', 'Importé depuis Excel'),
(279, 88, 'Sofia Sofi', 'enseignant', 11, 'DA123', 'Analyse de donnee1', 21, 'Ingénierie de Données (ID)', 1, 'Informatique', '2025-2026', 1, 'CM', 20, 'validé', '2025-06-09 18:56:00', 'Importé depuis Excel'),
(280, 1, 'Durand Jean', 'enseignant', 79, 'M1116', 'Culture and Art skills', 20, 'Génie Informatique (GI)', 1, 'Informatique', '2025-2026', 1, 'CM', 26, 'validé', '2025-06-06 16:22:00', 'Importé depuis Excel'),
(281, 1, 'Durand Jean', 'enseignant', 79, 'M1116', 'Culture and Art skills', 20, 'Génie Informatique (GI)', 1, 'Informatique', '2025-2026', 1, 'CM', 26, 'validé', '2025-06-06 16:22:00', 'Importé depuis Excel');

-- --------------------------------------------------------

--
-- Structure de la table `historique_affectations_vacataire`
--

CREATE TABLE `historique_affectations_vacataire` (
  `id` int(11) NOT NULL,
  `id_vacataire` int(11) NOT NULL,
  `id_unite_enseignement` int(11) NOT NULL,
  `type_cours` enum('CM','TD','TP') NOT NULL,
  `date_affectation` datetime NOT NULL,
  `id_coordonnateur` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `commentaire` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `historique_affectations_vacataire`
--

INSERT INTO `historique_affectations_vacataire` (`id`, `id_vacataire`, `id_unite_enseignement`, `type_cours`, `date_affectation`, `id_coordonnateur`, `action`, `commentaire`) VALUES
(6, 79, 6, 'CM', '2025-06-05 14:25:02', 68, 'affectation', 'REAG'),
(7, 80, 12, 'CM', '2025-06-05 16:33:24', 68, 'affectation', 'POUR S1'),
(8, 102, 1244, 'CM', '2025-06-09 20:54:30', 82, 'affectation', 'pour S1'),
(9, 104, 6, 'CM', '2025-06-09 22:04:44', 82, 'affectation', 'pour s1');

-- --------------------------------------------------------

--
-- Structure de la table `journal_decisions`
--

CREATE TABLE `journal_decisions` (
  `id` int(11) NOT NULL,
  `type_entite` varchar(255) NOT NULL,
  `id_entite` int(11) NOT NULL,
  `id_utilisateur_decision` int(11) NOT NULL,
  `ancien_statut` varchar(50) DEFAULT NULL,
  `nouveau_statut` varchar(50) DEFAULT NULL,
  `commentaire` text DEFAULT NULL,
  `date_decision` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `journal_decisions`
--

INSERT INTO `journal_decisions` (`id`, `type_entite`, `id_entite`, `id_utilisateur_decision`, `ancien_statut`, `nouveau_statut`, `commentaire`, `date_decision`) VALUES
(1, 'affectation', 258, 4, 'non_affecte', 'affecte', 'Nouvelle affectation pour CM à Durand Jeanavec un comentaire:', '2025-05-20 18:27:36'),
(2, 'voeux_professeurs', 89, 76, 'en_attente', 'validé', 'ruuut', '2025-05-20 20:47:31'),
(3, 'voeux_professeurs', 89, 76, 'validé', 'en_attente', '', '2025-05-20 20:48:20'),
(4, 'unite_vacante', 3, 4, 'non_affecte', 'valide_vacante', 'Validation comme unité vacante pour la filière Réseaux et Sécurité', '2025-05-20 20:51:31'),
(5, 'unite_vacante', 3, 4, 'non_affecte', 'valide_vacante', 'Validation comme unité vacante pour la filière Génie Logiciel', '2025-05-20 20:52:54'),
(6, 'affectation', 260, 4, 'non_affecte', 'affecte', 'Nouvelle affectation pour TP à MAMI Maavec un comentaire:tttttttttttttttttttt', '2025-05-20 20:55:05'),
(7, 'affectation', 261, 76, 'non_affecte', 'affecte', 'Nouvelle affectation pour TD à Durand Jeanavec un comentaire:', '2025-06-01 13:20:38'),
(8, 'affectation', 261, 76, 'affecte', 'non_affecte', 'Suppression de l\'affectation de Durand Jean pour TD', '2025-06-01 13:20:49'),
(9, 'unite_vacante', 3, 4, 'non_affecte', 'valide_vacante', 'Validation comme unité vacante pour la filière Génie Logiciel', '2025-06-01 15:15:57'),
(10, 'unite_vacante', 6, 4, 'non_affecte', 'valide_vacante', 'Validation comme unité vacante pour la filière Génie Logiciel', '2025-06-01 15:31:51'),
(11, 'unite_vacante', 6, 4, 'non_affecte', 'valide_vacante', 'Validation comme unité vacante pour la filière geora', '2025-06-01 16:22:41'),
(12, 'unite_vacante', 3, 4, 'non_affecte', 'valide_vacante', 'Validation comme unité vacante pour la filière maath appliqué', '2025-06-01 17:32:52'),
(13, 'affectation', 262, 4, 'affecte', 'affecte_modifie', 'Modification d\'affectation de MAMI Ma vers Durand Jean pour TP', '2025-06-01 18:00:16'),
(14, 'unite_vacante', 3, 4, 'non_affecte', 'valide_vacante', 'Validation comme unité vacante pour la filière Réseaux et Sécurité', '2025-06-01 18:00:28'),
(15, 'voeux_professeurs', 89, 4, 'en_attente', 'validé', '', '2025-06-02 10:10:25'),
(16, 'voeux_professeurs', 89, 4, 'validé', 'en_attente', '', '2025-06-02 10:10:33'),
(17, 'affectation', 264, 4, 'non_affecte', 'affecte', 'Nouvelle affectation pour CM à Durand Jeanavec un comentaire:', '2025-06-02 10:12:51'),
(18, 'affectation', 264, 4, 'affecte', 'non_affecte', 'Suppression de l\'affectation de Durand Jean pour CM', '2025-06-02 10:13:47'),
(19, 'affectation', 265, 4, 'non_affecte', 'affecte', 'Nouvelle affectation pour TP à Durand Jeanavec un comentaire:', '2025-06-05 15:31:21'),
(20, 'affectation', 266, 4, 'non_affecte', 'affecte', 'Nouvelle affectation pour CM à Durand Jeanavec un comentaire:', '2025-06-05 16:20:58'),
(21, 'unite_vacante', 3, 4, 'non_affecte', 'valide_vacante', 'Validation comme unité vacante pour la filière maath appliqué', '2025-06-05 16:31:27'),
(22, 'unite_vacante', 12, 4, 'non_affecte', 'valide_vacante', 'Validation comme unité vacante pour la filière geora', '2025-06-05 16:31:46'),
(23, 'affectation', 267, 4, 'non_affecte', 'affecte', 'Nouvelle affectation pour CM à Durand Jeanavec un comentaire:', '2025-06-06 17:22:40'),
(24, 'voeux_professeurs', 102, 76, 'en_attente', 'validé', 'Demande acceptée conformément aux besoins pédagogiques du semestre.', '2025-06-07 23:14:24'),
(25, 'voeux_professeurs', 103, 76, 'en_attente', 'validé', 'Demande acceptée conformément aux besoins pédagogiques du semestre.', '2025-06-07 23:14:53'),
(26, 'voeux_professeurs', 103, 76, 'validé', 'validé', 'Demande acceptée conformément aux besoins .', '2025-06-07 23:15:23'),
(27, 'voeux_professeurs', 103, 76, 'validé', 'validé', 'Demande acceptée ', '2025-06-07 23:15:41'),
(28, 'unite_vacante', 6, 76, 'non_affecte', 'valide_vacante', 'Validation comme unité vacante pour la filière Génie Informatique (GI)', '2025-06-07 23:35:12'),
(29, 'unite_vacante', 1244, 76, 'non_affecte', 'valide_vacante', 'Validation comme unité vacante pour la filière Transformation Digitale et IA (TDIA)', '2025-06-07 23:35:29'),
(30, 'unite_vacante', 1244, 76, 'non_affecte', 'valide_vacante', 'Validation comme unité vacante pour la filière Transformation Digitale et IA (TDIA)', '2025-06-07 23:35:37'),
(31, 'unite_vacante', 1243, 76, 'non_affecte', 'valide_vacante', 'Validation comme unité vacante pour la filière Génie Informatique (GI)', '2025-06-07 23:35:46'),
(32, 'unite_vacante', 1243, 76, 'non_affecte', 'valide_vacante', 'Validation comme unité vacante pour la filière Génie Informatique (GI)', '2025-06-07 23:36:00'),
(33, 'unite_vacante', 11, 76, 'non_affecte', 'valide_vacante', 'Validation comme unité vacante pour la filière Ingénierie de Données (ID)', '2025-06-07 23:38:09'),
(34, 'unite_vacante', 1241, 76, 'non_affecte', 'valide_vacante', 'Validation comme unité vacante pour la filière Génie Informatique (GI)', '2025-06-07 23:40:59'),
(35, 'voeux_professeurs', 94, 76, 'en_attente', 'validé', 'trtteyyyzyeyye', '2025-06-09 19:52:59'),
(36, 'voeux_professeurs', 94, 76, 'validé', 'rejeté', '', '2025-06-09 19:53:16'),
(37, 'affectation', 271, 76, 'non_affecte', 'affecte', 'Nouvelle affectation pour CM à Durand Jeanavec un comentaire:yryyyeyyryfyrfyyfy', '2025-06-09 19:56:14'),
(38, 'affectation', 272, 76, 'affecte', 'affecte_modifie', 'Modification d\'affectation de Durand Jean vers Sofia Sofi pour CM', '2025-06-09 19:56:45'),
(39, 'unite_vacante', 3, 76, 'non_affecte', 'valide_vacante', 'Validation comme unité vacante pour la filière Génie Informatique (GI)', '2025-06-09 19:57:08'),
(40, 'affectation', 275, 76, 'non_affecte', 'affecte', 'Nouvelle affectation pour CM à Smlali Hananeavec un comentaire:', '2025-06-09 20:48:23'),
(41, 'voeux_professeurs', 104, 76, 'en_attente', 'validé', 'etyeuzuuuzuuz', '2025-06-09 21:33:44'),
(42, 'voeux_professeurs', 104, 76, 'validé', 'rejeté', '', '2025-06-09 21:34:13'),
(43, 'affectation', 277, 76, 'non_affecte', 'affecte', 'Nouvelle affectation pour CM à Durand Jeanavec un comentaire:yrureuieuuee', '2025-06-09 21:37:02'),
(44, 'affectation', 272, 76, 'affecte', 'non_affecte', 'Suppression de l\'affectation de Sofia Sofi pour CM', '2025-06-09 21:37:25'),
(45, 'unite_vacante', 6, 76, 'non_affecte', 'valide_vacante', 'Validation comme unité vacante pour la filière Génie Informatique (GI)', '2025-06-09 21:38:19');

-- --------------------------------------------------------

--
-- Structure de la table `journal_import_export`
--

CREATE TABLE `journal_import_export` (
  `id` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `type_operation` enum('import','export') NOT NULL,
  `type_donnees` varchar(50) NOT NULL,
  `nom_fichier` varchar(255) NOT NULL,
  `statut` enum('succes','echec') NOT NULL,
  `message` text DEFAULT NULL,
  `date_operation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notes`
--

CREATE TABLE `notes` (
  `id` int(11) NOT NULL,
  `id_unite_enseignement` int(11) NOT NULL,
  `id_etudiant` int(11) NOT NULL,
  `type_session` enum('normale','rattrapage') NOT NULL,
  `note` float NOT NULL,
  `date_soumission` datetime DEFAULT current_timestamp(),
  `id_enseignant` int(11) NOT NULL,
  `statut` enum('soumise','validee','rejetee') DEFAULT 'soumise',
  `commentaire` text DEFAULT NULL,
  `fichier_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `notes`
--

INSERT INTO `notes` (`id`, `id_unite_enseignement`, `id_etudiant`, `type_session`, `note`, `date_soumission`, `id_enseignant`, `statut`, `commentaire`, `fichier_path`) VALUES
(134, 79, 22, 'rattrapage', 16.4, '2025-06-08 18:43:02', 1, 'soumise', NULL, '6845cba5cc217_notes_79_rattrapage.xlsx'),
(135, 79, 23, 'rattrapage', 20, '2025-06-08 18:43:02', 1, 'soumise', NULL, '6845cba5cc217_notes_79_rattrapage.xlsx'),
(136, 79, 24, 'rattrapage', 19, '2025-06-08 18:43:02', 1, 'soumise', NULL, '6845cba5cc217_notes_79_rattrapage.xlsx'),
(137, 79, 25, 'rattrapage', 8.9, '2025-06-08 18:43:02', 1, 'soumise', NULL, '6845cba5cc217_notes_79_rattrapage.xlsx'),
(138, 79, 26, 'rattrapage', 4.9, '2025-06-08 18:43:02', 1, 'soumise', NULL, '6845cba5cc217_notes_79_rattrapage.xlsx'),
(139, 79, 27, 'rattrapage', 20, '2025-06-08 18:43:02', 1, 'soumise', NULL, '6845cba5cc217_notes_79_rattrapage.xlsx'),
(140, 79, 28, 'rattrapage', 16, '2025-06-08 18:43:02', 1, 'soumise', NULL, '6845cba5cc217_notes_79_rattrapage.xlsx'),
(155, 12, 22, 'normale', 16.4, '2025-06-08 18:58:57', 1, 'soumise', NULL, '6845cf611d14e_notes_12_normale.xlsx'),
(156, 12, 23, 'normale', 20, '2025-06-08 18:58:57', 1, 'soumise', NULL, '6845cf611d14e_notes_12_normale.xlsx'),
(157, 12, 24, 'normale', 19, '2025-06-08 18:58:57', 1, 'soumise', NULL, '6845cf611d14e_notes_12_normale.xlsx'),
(158, 12, 25, 'normale', 8.9, '2025-06-08 18:58:57', 1, 'soumise', NULL, '6845cf611d14e_notes_12_normale.xlsx'),
(159, 12, 26, 'normale', 4.9, '2025-06-08 18:58:57', 1, 'soumise', NULL, '6845cf611d14e_notes_12_normale.xlsx'),
(160, 12, 27, 'normale', 20, '2025-06-08 18:58:57', 1, 'soumise', NULL, '6845cf611d14e_notes_12_normale.xlsx'),
(161, 12, 28, 'normale', 16, '2025-06-08 18:58:57', 1, 'soumise', NULL, '6845cf611d14e_notes_12_normale.xlsx'),
(162, 11, 22, 'normale', 16.4, '2025-06-09 20:18:40', 88, 'soumise', NULL, '6847338fe20cc_notes_11_normale.xlsx'),
(163, 11, 23, 'normale', 20, '2025-06-09 20:18:40', 88, 'soumise', NULL, '6847338fe20cc_notes_11_normale.xlsx'),
(164, 11, 24, 'normale', 19, '2025-06-09 20:18:40', 88, 'soumise', NULL, '6847338fe20cc_notes_11_normale.xlsx'),
(165, 11, 25, 'normale', 8.9, '2025-06-09 20:18:40', 88, 'soumise', NULL, '6847338fe20cc_notes_11_normale.xlsx'),
(166, 11, 26, 'normale', 4.9, '2025-06-09 20:18:40', 88, 'soumise', NULL, '6847338fe20cc_notes_11_normale.xlsx'),
(167, 11, 27, 'normale', 20, '2025-06-09 20:18:40', 88, 'soumise', NULL, '6847338fe20cc_notes_11_normale.xlsx'),
(168, 11, 28, 'normale', 16, '2025-06-09 20:18:40', 88, 'soumise', NULL, '6847338fe20cc_notes_11_normale.xlsx'),
(169, 1244, 22, 'normale', 16.4, '2025-06-09 20:59:49', 102, 'soumise', NULL, '1749499189_102_1244.xlsx'),
(170, 1244, 23, 'normale', 20, '2025-06-09 20:59:49', 102, 'soumise', NULL, '1749499189_102_1244.xlsx'),
(171, 1244, 24, 'normale', 19, '2025-06-09 20:59:49', 102, 'soumise', NULL, '1749499189_102_1244.xlsx'),
(172, 1244, 25, 'normale', 8.9, '2025-06-09 20:59:49', 102, 'soumise', NULL, '1749499189_102_1244.xlsx'),
(173, 1244, 26, 'normale', 4.9, '2025-06-09 20:59:49', 102, 'soumise', NULL, '1749499189_102_1244.xlsx'),
(174, 1244, 27, 'normale', 20, '2025-06-09 20:59:49', 102, 'soumise', NULL, '1749499189_102_1244.xlsx'),
(175, 1244, 28, 'normale', 16, '2025-06-09 20:59:49', 102, 'soumise', NULL, '1749499189_102_1244.xlsx'),
(176, 1244, 22, 'rattrapage', 16.4, '2025-06-09 21:27:39', 102, 'soumise', NULL, '1749500859_102_1244.xlsx'),
(177, 1244, 23, 'rattrapage', 20, '2025-06-09 21:27:39', 102, 'soumise', NULL, '1749500859_102_1244.xlsx'),
(178, 1244, 24, 'rattrapage', 19, '2025-06-09 21:27:39', 102, 'soumise', NULL, '1749500859_102_1244.xlsx'),
(179, 1244, 25, 'rattrapage', 8.9, '2025-06-09 21:27:39', 102, 'soumise', NULL, '1749500859_102_1244.xlsx'),
(180, 1244, 26, 'rattrapage', 4.9, '2025-06-09 21:27:39', 102, 'soumise', NULL, '1749500859_102_1244.xlsx'),
(181, 1244, 27, 'rattrapage', 20, '2025-06-09 21:27:39', 102, 'soumise', NULL, '1749500859_102_1244.xlsx'),
(182, 1244, 28, 'rattrapage', 16, '2025-06-09 21:27:39', 102, 'soumise', NULL, '1749500859_102_1244.xlsx'),
(183, 87, 22, 'normale', 16.4, '2025-06-09 21:53:33', 87, 'soumise', NULL, '684749cd64272_notes_87_normale.xlsx'),
(184, 87, 23, 'normale', 20, '2025-06-09 21:53:33', 87, 'soumise', NULL, '684749cd64272_notes_87_normale.xlsx'),
(185, 87, 24, 'normale', 19, '2025-06-09 21:53:33', 87, 'soumise', NULL, '684749cd64272_notes_87_normale.xlsx'),
(186, 87, 25, 'normale', 8.9, '2025-06-09 21:53:33', 87, 'soumise', NULL, '684749cd64272_notes_87_normale.xlsx'),
(187, 87, 26, 'normale', 4.9, '2025-06-09 21:53:33', 87, 'soumise', NULL, '684749cd64272_notes_87_normale.xlsx'),
(188, 87, 27, 'normale', 20, '2025-06-09 21:53:33', 87, 'soumise', NULL, '684749cd64272_notes_87_normale.xlsx'),
(189, 87, 28, 'normale', 16, '2025-06-09 21:53:33', 87, 'soumise', NULL, '684749cd64272_notes_87_normale.xlsx');

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `titre` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','error','success') DEFAULT 'info',
  `statut` enum('non_lu','lu') DEFAULT 'non_lu',
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_lecture` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id`, `id_utilisateur`, `titre`, `message`, `type`, `statut`, `date_creation`, `date_lecture`) VALUES
(1, 1, 'Heures insuffisantes', 'Attention: Votre charge horaire actuelle est de 20h, ce qui est inférieur au minimum requis de 200h.', 'warning', 'lu', '2025-04-27 13:38:24', '2025-05-20 16:10:41'),
(2, 1, 'Heures insuffisantes', 'Attention: Votre charge horaire actuelle est de 24h, ce qui est inférieur au minimum requis de 200h.', 'warning', 'lu', '2025-04-27 13:38:24', '2025-06-09 19:16:13'),
(3, 1, 'informative', 'vos modules sont prets', 'info', 'lu', '2025-04-27 13:38:24', '2025-06-09 19:16:13'),
(16, 1, 'succes', 'mis a jour des notes effectues avec succes', 'success', 'lu', '2025-05-20 17:14:27', '2025-06-09 19:16:13'),
(17, 1, 'erreur', 'un probleme a ete detecte', 'warning', 'lu', '2025-05-20 17:14:39', '2025-06-09 19:16:13'),
(18, 1, 'des nouvelles a propos des modules', 'Un nouveau module a été ajouté à votre département.', 'info', 'lu', '2025-05-20 18:13:28', '2025-06-07 03:42:27'),
(19, 1, 'des nouvelles a propos de vos voeux', 'Un rappel : veuillez finaliser vos vœux avant la date limite.', 'info', 'lu', '2025-06-06 18:40:33', '2025-06-07 03:42:27'),
(20, 1, 'erreur ', 'Le fichier joint dépasse la taille maximale autorisée.', 'error', 'lu', '2025-06-06 18:40:57', '2025-06-09 19:16:13'),
(21, 1, 'mis a jour avec succes', 'Votre profil a été mis à jour avec succès.', 'success', 'lu', '2025-06-06 20:50:52', '2025-06-09 19:16:13'),
(22, 1, 'a propos de liste des modules', 'La liste des modules disponibles a été actualisée', 'info', 'lu', '2025-06-07 03:42:19', '2025-06-09 19:16:13'),
(23, 1, 'Charge horaire insuffisante', 'Votre charge horaire actuelle (54h) est inférieure au minimum requis (192h). Il vous manque 138h.', 'warning', 'lu', '2025-06-07 13:15:19', '2025-06-09 19:16:13'),
(24, 1, 'Heures insuffisantes', 'Attention: Votre charge horaire actuelle est de 22h, ce qui est inférieur au minimum requis de 50h.', 'warning', 'lu', '2025-06-07 14:13:37', '2025-06-09 19:16:13'),
(25, 87, 'Charge horaire insuffisante', 'Votre charge horaire actuelle (0h) est inférieure au minimum requis (192h). Il vous manque 192h.', 'warning', 'non_lu', '2025-06-07 15:27:58', NULL),
(26, 88, 'Charge horaire insuffisante', 'Votre charge horaire actuelle (0h) est inférieure au minimum requis (192h). Il vous manque 192h.', 'warning', 'lu', '2025-06-07 15:38:35', '2025-06-07 15:42:38'),
(27, 88, 'Heures insuffisantes', 'Attention: Votre charge horaire actuelle est de 15h, ce qui est inférieur au minimum requis de 50h.', 'warning', 'lu', '2025-06-07 15:41:46', '2025-06-07 15:42:36'),
(28, 87, 'informative', 'vos modules sont prets', 'info', 'non_lu', '2025-06-07 16:39:10', NULL),
(36, 1, 'succes', 'mis a jour des notes effectues avec succes', 'success', 'lu', '2025-06-07 21:25:11', '2025-06-07 21:30:22'),
(37, 1, 'erreur', 'un probleme a ete detecte', 'error', 'lu', '2025-06-08 17:37:19', '2025-06-09 19:16:13'),
(38, 1, 'Charge horaire insuffisante', 'Votre charge horaire actuelle (54h) est inférieure au minimum requis (192h). Il vous manque 138h.', 'warning', 'lu', '2025-06-09 18:07:28', '2025-06-09 19:16:13'),
(39, 1, 'Charge horaire insuffisante', 'Votre charge horaire actuelle (54h) est inférieure au minimum requis (192h). Il vous manque 138h.', 'warning', 'lu', '2025-06-09 18:07:40', '2025-06-09 19:16:13'),
(40, 1, 'Charge horaire insuffisante', 'Votre charge horaire actuelle (54h) est inférieure au minimum requis (192h). Il vous manque 138h.', 'warning', 'lu', '2025-06-09 18:08:46', '2025-06-09 19:16:10'),
(41, 1, 'Charge horaire insuffisante', 'Votre charge horaire actuelle (80h) est inférieure au minimum requis (192h). Il vous manque 112h.', 'warning', 'lu', '2025-06-09 19:05:17', '2025-06-09 19:16:08'),
(42, 1, 'Heures insuffisantes', 'Attention: Votre charge horaire actuelle est de 42h, ce qui est inférieur au minimum requis de 192h.', 'warning', 'lu', '2025-06-09 19:06:38', '2025-06-09 19:16:05'),
(43, 1, 'Charge horaire insuffisante', 'Votre charge horaire actuelle (80h) est inférieure au minimum requis (192h). Il vous manque 112h.', 'warning', 'lu', '2025-06-09 19:17:53', '2025-06-09 20:52:25'),
(44, 1, 'Charge horaire insuffisante', 'Votre charge horaire actuelle (158h) est inférieure au minimum requis (192h). Il vous manque 34h.', 'warning', 'lu', '2025-06-09 20:48:08', '2025-06-09 20:52:23'),
(45, 1, 'Heures insuffisantes', 'Attention: Votre charge horaire actuelle est de 58h, ce qui est inférieur au minimum requis de 192h.', 'warning', 'lu', '2025-06-09 20:51:47', '2025-06-09 20:52:22');

-- --------------------------------------------------------

--
-- Structure de la table `notifications_coordonnateur`
--

CREATE TABLE `notifications_coordonnateur` (
  `id` int(11) NOT NULL,
  `id_utilisateur` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `notifications_coordonnateur`
--

INSERT INTO `notifications_coordonnateur` (`id`, `id_utilisateur`, `message`, `is_read`, `created_at`) VALUES
(1, 68, 'Vous devez affecter les unités d\'enseignement vacantes aux vacataires existants.', 1, '2025-06-05 22:07:49'),
(2, 68, 'Ceci est une notification d\'exemple pour le coordinateur.', 1, '2025-06-05 22:11:29'),
(3, 68, 'Ceci est une notification d\'exemple pour le coordinateur.', 1, '2025-06-05 22:18:10'),
(4, 68, 'Ceci est une notification d\'exemple pour le coordinateur.', 1, '2025-06-05 22:18:10'),
(5, 68, 'Envoyez les emplois du temps aux enseignants et vacataires.', 1, '2025-06-05 22:19:35');

-- --------------------------------------------------------

--
-- Structure de la table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expiry` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `used` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expiry`, `created_at`, `used`) VALUES
(6, 'rachida.amourak@etu.uae.ac.ma', '70317868d800e9172c164262c24f01ceed62f9387a8094ab74c781a9dd68ba89', '2025-06-09 21:43:04', '2025-06-09 18:43:04', 1),
(8, 'rachida.amourak@etu.uae.ac.ma', '327b31a49189b3a2a86e2157e8414fbd7782c8c13e3ab2914dca11216d28b672', '2025-06-09 23:23:32', '2025-06-09 20:23:32', 1);

-- --------------------------------------------------------

--
-- Structure de la table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `permissions`
--

INSERT INTO `permissions` (`id`, `nom`, `description`) VALUES
(54, 'view_dashboard', 'Accéder au tableau de bord'),
(55, 'manage_profile', 'Gérer son profil personnel'),
(56, 'manage_users', 'Gérer les utilisateurs'),
(57, 'manage_roles', 'Gérer les rôles et permissions'),
(58, 'manage_departments', 'Gérer les départements'),
(59, 'view_department', 'Voir les informations du département'),
(60, 'manage_department_users', 'Gérer les membres du département'),
(61, 'manage_department_courses', 'Gérer les cours du département'),
(62, 'create_course', 'Créer un nouveau cours'),
(63, 'edit_course', 'Modifier un cours'),
(64, 'delete_course', 'Supprimer un cours'),
(65, 'view_course', 'Voir les détails d\'un cours'),
(66, 'manage_schedule', 'Gérer les emplois du temps'),
(67, 'view_schedule', 'Voir les emplois du temps'),
(68, 'manage_absences', 'Gérer les absences'),
(69, 'view_absences', 'Voir les absences'),
(70, 'report_absence', 'Signaler une absence');

-- --------------------------------------------------------

--
-- Structure de la table `rapport_charge_departement`
--

CREATE TABLE `rapport_charge_departement` (
  `id` int(11) NOT NULL,
  `id_departement` int(11) NOT NULL,
  `annee_universitaire` varchar(10) NOT NULL,
  `semestre` int(11) NOT NULL,
  `total_heures_cm` float DEFAULT 0,
  `total_heures_td` float DEFAULT 0,
  `total_heures_tp` float DEFAULT 0,
  `total_heures` float DEFAULT 0,
  `nombre_enseignants` int(11) DEFAULT 0,
  `nombre_vacataires` int(11) DEFAULT 0,
  `date_generation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `rapport_charge_departement`
--

INSERT INTO `rapport_charge_departement` (`id`, `id_departement`, `annee_universitaire`, `semestre`, `total_heures_cm`, `total_heures_td`, `total_heures_tp`, `total_heures`, `nombre_enseignants`, `nombre_vacataires`, `date_generation`) VALUES
(1, 1, '2024-2025', 4, NULL, NULL, NULL, NULL, 0, 0, '2025-05-16 11:50:32'),
(2, 1, '2022-2023', 4, NULL, NULL, NULL, NULL, 0, 0, '2025-05-16 11:53:31'),
(3, 1, '2023-2024', 4, NULL, NULL, NULL, NULL, 0, 0, '2025-05-16 11:53:36'),
(4, 1, '2024-2025', 1, 97, 0, 20, 117, 2, 2, '2025-06-09 20:46:33'),
(5, 1, '2023-2024', 1, 13, 0, 0, 13, 1, 2, '2025-06-02 09:14:25'),
(6, 1, '2024-2025', 2, NULL, NULL, NULL, NULL, 0, 2, '2025-06-07 21:42:27');

-- --------------------------------------------------------

--
-- Structure de la table `reinitialisation_mot_de_passe`
--

CREATE TABLE `reinitialisation_mot_de_passe` (
  `id` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `date_expiration` datetime NOT NULL,
  `utilise` tinyint(1) DEFAULT 0,
  `date_utilisation` datetime DEFAULT NULL,
  `date_creation` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role` enum('admin','chef_departement','coordonnateur','enseignant','vacataire') NOT NULL,
  `id_permission` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `role_permissions`
--

INSERT INTO `role_permissions` (`role`, `id_permission`) VALUES
('admin', 54),
('admin', 55),
('admin', 56),
('admin', 57),
('admin', 58),
('admin', 59),
('admin', 60),
('admin', 61),
('admin', 62),
('admin', 63),
('admin', 64),
('admin', 65),
('admin', 66),
('admin', 67),
('admin', 68),
('admin', 69),
('admin', 70),
('chef_departement', 54),
('chef_departement', 55),
('chef_departement', 59),
('chef_departement', 60),
('chef_departement', 61),
('chef_departement', 62),
('chef_departement', 63),
('chef_departement', 64),
('chef_departement', 65),
('chef_departement', 66),
('chef_departement', 67),
('chef_departement', 68),
('chef_departement', 69),
('coordonnateur', 54),
('coordonnateur', 55),
('coordonnateur', 59),
('coordonnateur', 61),
('coordonnateur', 62),
('coordonnateur', 63),
('coordonnateur', 65),
('coordonnateur', 66),
('coordonnateur', 67),
('coordonnateur', 68),
('coordonnateur', 69),
('enseignant', 54),
('enseignant', 55),
('enseignant', 59),
('enseignant', 65),
('enseignant', 67),
('enseignant', 69),
('enseignant', 70),
('vacataire', 54),
('vacataire', 55),
('vacataire', 65),
('vacataire', 67),
('vacataire', 69),
('vacataire', 70);

-- --------------------------------------------------------

--
-- Structure de la table `seances`
--

CREATE TABLE `seances` (
  `id` int(11) NOT NULL,
  `id_unite_enseignement` int(11) NOT NULL,
  `id_groupe` int(11) DEFAULT NULL,
  `type` enum('CM','TD','TP') NOT NULL,
  `id_enseignant` int(11) DEFAULT NULL,
  `jour` enum('Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi') NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `salle` varchar(50) DEFAULT NULL,
  `id_emploi_temps` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `seances`
--

INSERT INTO `seances` (`id`, `id_unite_enseignement`, `id_groupe`, `type`, `id_enseignant`, `jour`, `heure_debut`, `heure_fin`, `salle`, `id_emploi_temps`) VALUES
(23, 75, NULL, 'CM', NULL, 'Lundi', '08:00:00', '10:00:00', 'salle 8', 21),
(26, 130, 128, 'TP', NULL, 'Lundi', '14:00:00', '16:00:00', 'Salle 2', 21),
(27, 258, 128, 'TP', NULL, 'Lundi', '10:00:00', '12:00:00', 'salle 6', 21),
(28, 258, NULL, 'CM', NULL, 'Mardi', '14:00:00', '16:00:00', 'salle 8', 21),
(29, 85, 127, 'TD', NULL, 'Mardi', '14:00:00', '16:00:00', 'salle 2', 26),
(30, 85, NULL, 'CM', NULL, 'Mardi', '08:00:00', '10:00:00', 'salle 4', 22),
(32, 85, 129, 'TD', NULL, 'Lundi', '10:00:00', '12:00:00', 'salle 10', 29),
(33, 87, 130, 'TP', NULL, 'Mercredi', '14:00:00', '16:00:00', 'salle 2', 29),
(34, 87, NULL, 'CM', NULL, 'Lundi', '14:00:00', '16:00:00', 'salle 20', 29),
(35, 88, NULL, 'CM', NULL, 'Vendredi', '08:00:00', '10:00:00', 'salle 4', 29);

-- --------------------------------------------------------

--
-- Structure de la table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `adresse_ip` varchar(45) DEFAULT NULL,
  `agent_utilisateur` text DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_expiration` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `sessions`
--

INSERT INTO `sessions` (`id`, `id_utilisateur`, `adresse_ip`, `agent_utilisateur`, `date_creation`, `date_expiration`) VALUES
('017950c547408965ddfc06076031d081d3abb89d20578c87d48db9deea531c99', 67, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 20:13:10', '2025-06-01 22:13:10'),
('02007c92a3597f3faff4ef833b42e177ab86c46d309dc1deba8d4916b3a1dc89', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 18:09:07', '2025-05-19 20:09:07'),
('02e30bf20ab9aec9a2f8418bd4dc787642db7c60bcd85ac572a5e73881726e37', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-06 16:17:00', '2025-06-06 18:17:00'),
('0323c403418d9063517447d243cdd4266317badf32b59ab06d55e3b59b981340', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-23 12:52:48', '2025-04-23 14:52:48'),
('05304bd828ee4862b8fd645c9126a207a4e5af831647029e537c3e0312ce6de9', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 18:05:02', '2025-05-19 20:05:02'),
('05f7c092d1404f824e42f9cbdf2ba663ead40c50675d50a5e63f370dddcc4c06', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 15:43:59', '2025-05-19 17:43:59'),
('06317aebce04dbc0d093aaa8bd19a95a8c9d1ba81ea5e52861b7f586c1d1efc6', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-16 19:45:52', '2025-04-16 21:45:52'),
('0873ac3f698d7b2b5d7a7a988627eeed9fc9fd74476a966eb18b9540205c5d73', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-29 23:06:44', '2025-04-30 01:06:44'),
('09bc8b193db1f4dca2150dfb653bd1580c04f741976fa58eaecc7260e40e4ed9', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-16 19:21:22', '2025-04-16 21:21:22'),
('09dd19c26ab37e15ee4680d57098f92bef0664114e66d4aa881a24a2af1f67df', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-07 13:15:19', '2025-06-07 15:15:19'),
('1050c4ddbba982a03ccb96b6d2514220f7526613f714e36ddb2fec519b1ac63a', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-15 15:01:10', '2025-05-15 17:01:10'),
('152b8b06bbbd3cc612c9412af6d36128312a9d8c7b7622d01ff689662efa3ff9', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 22:01:05', '2025-05-28 00:01:05'),
('17546aaf5f4a06995dfac8fcc2d4cb8ffa64776b92b2285c1f9878f88c5368a8', 76, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-09 20:31:21', '2025-06-09 22:31:21'),
('1aaabebb1e605d6179f9df1500593ad7c799b1009acbd03f479a343caa5d3611', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-23 19:19:22', '2025-05-23 21:19:22'),
('1b4328da2488614b5f8c821ce5dd52e73b002f8c185df9cea965c4c304e54435', 82, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-06 20:38:11', '2025-06-06 22:38:11'),
('202389d2135d81a3dfd20535addf6007f1413607cf2d6f5a5ac6810cf9634d62', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-17 13:53:31', '2025-04-17 15:53:31'),
('20a9666d1b8fa9c974e2d683c5608eb27265b216c3c1e6053b02ca7653e4f36a', 67, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 16:34:01', '2025-06-01 18:34:01'),
('218641818c9191e7a7aed74dd3ac2c32287a6e7c674430038ae2b0a3b3de060c', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-05 17:31:01', '2025-06-05 19:31:01'),
('255db6b1814f31dc0dfc270d37a4443588bb79ca3b82dd57560c1e9aebe4599e', 67, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-02 09:16:13', '2025-06-02 11:16:13'),
('258b044daae29221a5c7eab753a20c89ba2752850fb08632e920e07fc429f820', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 13:14:16', '2025-05-19 15:14:16'),
('28d93cf34490b401edc72a17cc0396fdbe102f8719479823e77b702bdc837e81', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 15:13:15', '2025-05-19 17:13:15'),
('29c16e693d38d83de10c54798c9f5e919f65843b3152328bbde5a0f8e207524b', 74, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 18:08:44', '2025-05-19 20:08:44'),
('2c2eb4ce44bb3cc902c9f0de195bcb2cf3bccebfed2cf609aad7e969ba4d33c2', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 12:49:46', '2025-05-19 14:49:46'),
('2c7ef2e7a4e14f490f248bccb268ec9acf83fc05428c2858e879efbbc9058aad', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-29 21:03:29', '2025-04-29 23:03:29'),
('2cef97f2a3afb6ff23bd4665e105187af1416d87cf863885a3a8c3a7392a4b1a', 87, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-07 16:39:10', '2025-06-07 18:39:10'),
('2e531a85b1485a1570ecb277322fa72c0204c8682a3e1e82d511f7fb735a5222', 78, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 13:17:56', '2025-06-01 15:17:56'),
('2ede6e02635b7cabff0a47c2e805388cacdf75c8e751366d350864caf926682f', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-30 01:33:36', '2025-04-30 03:33:36'),
('2f6b1e4c969f2e034669f2e5b954a85c24827ebc87bbe558b37445ad229d8f3f', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 14:29:36', '2025-06-01 16:29:36'),
('30020dfdcc15995a26c81dc23ecb9b370f130df53fa418cb683398e17950472f', 67, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-02 22:49:38', '2025-06-03 00:49:38'),
('3164f37c2609dc58cb0dbfc9017b8e4e5d95b7734f4a32c6848f4c8340779e3d', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-23 18:55:23', '2025-05-23 20:55:23'),
('333046a264ee9698f0c035c15d086cca56ce74942e302d2ff049da96c3a56314', 75, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 14:11:30', '2025-06-01 16:11:30'),
('333c572b030d6198152d146cce5def35469464d64493ed7ad20ca72cd5fcc44f', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 15:22:23', '2025-06-01 17:22:23'),
('37ba11135cc2e5ae40ea7c272f42b8536abbdbfedbf40a863e030d83c23596de', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-04 13:58:05', '2025-05-04 15:58:05'),
('38d551714cf6dfe508ea137038c2a7a21be6f519266a7142b35747f8f3e4e356', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-15 15:05:14', '2025-05-15 17:05:14'),
('39ac43d90f84f0bbbff6ee86b3404fe2d7b1aeeba5a24a696203b0b1214083db', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-04-30 14:01:34', '2025-04-30 16:01:34'),
('39f66ecd096faeaef2c228802a2b8924d147fadc075e2c6e687bbafdfd080293', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-05 13:14:58', '2025-06-05 15:14:58'),
('3a1238d3a3dd6fb0f0d3093b0c0750683b69aa6205aa2e2e33d9c712c20c131a', 78, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 13:05:45', '2025-06-01 15:05:45'),
('3c098cd11e6f7905609c6ad93d199e930120c2d661726dc233a7a61f8a03482c', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-23 19:04:50', '2025-05-23 21:04:50'),
('3c4a9b80edde98c5aacf2a0a0e1bd54c213f0b665fc05ee3310c8c2c89d214a1', 76, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-07 20:58:06', '2025-06-07 22:58:06'),
('3c87c619c7f6eb360fefc1fe5af8d2bd0b2bc8f347af6e30edd31553acf43764', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-16 11:21:44', '2025-05-16 13:21:44'),
('3cc68745661d17107db94235d80c5f0beab65c84665b940522612ca4601bec83', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-20 11:28:39', '2025-05-20 13:28:39'),
('3dfb16d11b6f91cfbabdb1c9d11ee227947d7f2bc4e51a64a317adf59a11f1c3', 76, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-07 16:27:42', '2025-06-07 18:27:42'),
('3e627914d947bb2e8415f0f1dc83b4205d3fae1d1e89a20f76d4d2c3af3a079e', 75, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 14:14:11', '2025-06-01 16:14:11'),
('3f9857069fd5a3f61f6eca2271415ed57447818a2699a405ade0d83bc59f62b0', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-29 23:04:01', '2025-04-30 01:04:01'),
('433ca94a46b6cc5d9bb90e1a37ef2d6dad239fcc7a65e0c31408f7600eecaa8c', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-05 23:52:02', '2025-06-06 01:52:02'),
('43b246e2211f86fefdbd70666207c974bcbdfeb93c412c7d05e08182aed13fed', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-16 11:56:34', '2025-05-16 13:56:34'),
('458b3217eff3cdd8d7496d59daae43ddf317a518647c9deae70ed5feda895cef', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-29 13:58:32', '2025-04-29 15:58:32'),
('45c262f3d7b700640e4e72ab2135bf1576a998f9ad80bfc5fe420c7daa421a30', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 20:12:18', '2025-06-01 22:12:18'),
('46eeb5abbde737629b417c0ca2223a6fb5f3789e63d0472da79c66de97dfaa44', 75, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 14:13:40', '2025-06-01 16:13:40'),
('496fad036589bf0a9b49870f5caf1b5efc6458a03d9bd5f92b0b2291c17a0799', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 20:59:58', '2025-05-27 22:59:58'),
('4b4c9cd7b1b02007c89b2d95bd9f483de5150d461e1d8b3d4d8769cb4e0593e9', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-02 12:43:09', '2025-05-02 14:43:09'),
('4bff6ccdb242ff88335e180ce6908e50f602a25d1178c940e450c66fd8f5c109', 67, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 15:24:25', '2025-06-01 17:24:25'),
('4d746a86ecf08c24ab36860acb682e095ee457444b1d4d527c0ea195a436ecec', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 15:22:42', '2025-05-19 17:22:42'),
('4e22ca2968d4cda17df8b228d53fc68950f447ce89622c9025c08bc14c30f475', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-16 19:23:29', '2025-04-16 21:23:29'),
('50e4244da5a3f29f7abe8969816242576adafe87eca84b50068645ca0cd005f1', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 17:29:03', '2025-05-19 19:29:03'),
('51e3aa5ff3eefc216e8861a1e4cf63ec4109a2cb30fc67ed7acca89332cfdd9c', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-24 21:45:52', '2025-05-24 23:45:52'),
('5365a727931901b0efc08cd1c6cb4b736f466947fdaf7d01dad36fabf8431b84', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-04 19:01:32', '2025-05-04 21:01:32'),
('549eaf47fdeca9c64cb9e3c42050e8f7a2350352047e5fdc1fe370110fceebae', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-02 20:44:45', '2025-05-02 22:44:45'),
('5609921caf198d51e6fff1520651623fd3ee387169e998692aaf9fdc8ce3cdf5', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 15:40:31', '2025-05-19 17:40:31'),
('56ecbcb20828706899f1713882b56691f869f3faff4a28df403ebeb7293eb115', 76, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-07 21:11:16', '2025-06-07 23:11:16'),
('573ead92da21d7ab010d18cdf8e088eb302227650ab5f3152ee440ec777f09ec', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-08 17:37:18', '2025-06-08 19:37:18'),
('577814415e68d8833f5f1f323eb23ad9905faf1676dc6d4e3a74eba5684f7967', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-29 23:22:35', '2025-04-30 01:22:35'),
('58149e4d077d8038e024d28742a565c5f2230d7e4283bc59e8bdbf315f44f041', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-02 09:08:23', '2025-06-02 11:08:23'),
('5d55bfa335e2e85408d08b129ba05bbcd3808e0437f38c9aceeba2fc7a0dbcbb', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-04 21:58:14', '2025-05-04 23:58:14'),
('61214fb990a097d9ce4942ce5f8d7f3dd3c681777c12073741b3cd47ea61a246', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-06 11:36:56', '2025-06-06 13:36:56'),
('65342804066f069802b5692e2e07ce4e6fd2344a1db902ebbbd635c4acb05b3f', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 14:15:59', '2025-05-19 16:15:59'),
('65b5105b67de9af167b5619a71e1796cd25bc35f01b385cd80eee2d24a95399b', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-23 19:01:06', '2025-05-23 21:01:06'),
('67746bbe44062dd33331e9c9e09e40eefd0a2bd4e2003572fe692fc3859c1300', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 15:44:42', '2025-05-19 17:44:42'),
('6963b34563bc21ba29af0ee7d19715491d73c5f5a78cd8b9ee043767bda0cd16', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-19 19:21:21', '2025-04-19 21:21:21'),
('69e2a1ce8423fc8dd6d66528a05080ee4734b2779c0e07f9f9a62edf677b1cf9', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-19 12:34:24', '2025-04-19 14:34:24'),
('70d3fdb0e93d073b82091f57a1813e6a1f05ccb4bc7ebd8b66caafd62cb5597b', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-16 19:23:43', '2025-04-16 21:23:43'),
('7178b0cba1bc1b10e3af168f8225150f1848a451acaf4e29ae37bfd0612d9db8', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 20:15:49', '2025-05-27 22:15:49'),
('71d5cd405d90774dc7d007078e363e23e33609b7864c0a40c89a74f098901bfb', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 16:59:56', '2025-06-01 18:59:56'),
('7532a798f554a5f5efa8f2955275594e7251c6a7a59a9124b7b3772198b20e7b', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-02 21:05:13', '2025-05-02 23:05:13'),
('761e39595e5278ae53de18bd45936939d6410c4b1de1ca5fa6533b984e2f6312', 76, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-09 18:49:04', '2025-06-09 20:49:04'),
('7b92ebca293ebb75c1d26956408ddf05e1ef799600005c75845fc57d9e6f4be2', 76, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-09 19:47:44', '2025-06-09 21:47:44'),
('7c1788e6f5114ded721505f457af86a64638e4a7e9fa1bf873cfaa495f7c8c46', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-20 11:31:42', '2025-05-20 13:31:42'),
('7f06c63aea7f592584badfc768ef0bda9e9226618d01ceb1744b92b93b17e58f', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-23 19:05:28', '2025-05-23 21:05:28'),
('7f47c4db284f633be65e946055dcdfc7cf0bec068d9dfb24be7fc1a6ce5f98cb', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-04 14:35:47', '2025-05-04 16:35:47'),
('8317fc670b4deae854099a0c6067e8832ad9da5f581df6363d947e011f465382', 67, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 14:18:25', '2025-06-01 16:18:25'),
('84e3e3c92da6cd95cbf99cdee99b09e6492b8da1cd1618c3fe906a74893ff737', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 23:08:33', '2025-05-28 01:08:33'),
('84f00823624af706f7b81ba9807423572695c36addb45c77b134acb8ba83d32c', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 17:53:55', '2025-05-19 19:53:55'),
('87fa4357a573d9e1d5ca57e8dcbe3c2c6cb3e3f7e62b1aa6a47ee90a1fa0ec46', 102, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-09 20:26:38', '2025-06-09 22:26:38'),
('95cd74eb3ddc453e8dae499f862a9333bd2c5392407f9f680e6a958e13586c4b', 67, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 16:46:44', '2025-06-01 18:46:44'),
('97c3c80e940194a38d9349cc20abbd62971cedc07eed09f6ab08fc507a4b5c1c', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 20:18:44', '2025-05-27 22:18:44'),
('986b5ebdcc447aaa89f21b53b9d4e60ea31ddf573699c36e3fd0f7adf0b2c190', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-05 20:57:14', '2025-06-05 22:57:14'),
('98d57a3a8bb87ad1deb35eb9dd640098d502f656fbf0475a1393bff6f0abb40f', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-16 11:57:22', '2025-05-16 13:57:22'),
('99fbaca38c0b27c7c0271e15f08062f2da4814678114ae64f21836458aab84b1', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-05 15:19:43', '2025-06-05 17:19:43'),
('9c3d67b097c9828978d5ab9c6062bb9ce03d531404ff88b398c720ab6ad82949', 82, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-11 13:24:37', '2025-06-11 15:24:37'),
('9cbef9e34238799590f312b5286627b1213f347afc2095adf29027650364a496', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-04-30 22:38:47', '2025-05-01 00:38:47'),
('9d32c6b9f434ca43635526c8932f7a9f5cc9806aeae3bd0f88267b3de18d40c3', 102, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-09 19:59:23', '2025-06-09 21:59:23'),
('9ee391234c7441cd5103025b536127e92e58a25a2354657c17604caaa4aac4f3', 76, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-07 21:34:11', '2025-06-07 23:34:11'),
('a54f50c5b3443c8e97e1dd86d043019bbbba0b918230a7ca62ce87adff429cfd', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 20:12:14', '2025-06-01 22:12:14'),
('a6f71dde3c37b23d39046f0630eb3bc68677bb83b3d22ad7bc3bd63c8d86487e', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-06 18:46:21', '2025-06-06 20:46:21'),
('a82fd623d0fed4e23a99dcb1a838872a360606d472904b364a93d3d6e9d17e49', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 20:16:09', '2025-05-27 22:16:09'),
('a86e74ebf7fd47d5b16926ce4269d58e551ad22addb62130696290b432c8e53f', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-16 19:19:41', '2025-04-16 21:19:41'),
('ab2daeba2a019559d5cd29f4f22c854d8a9af21361bc8ee851067fb4db6b6801', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 23:11:25', '2025-05-28 01:11:25'),
('aba57d147ff61c9ebe447de84eabe531b6463f718596d4ce69a068bc20286fb0', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-15 15:09:10', '2025-05-15 17:09:10'),
('ad93bc0cf0e88a9e04ff24a1baec4b52a92bec79da2269f23b7087371c7c6127', 67, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 17:01:28', '2025-06-01 19:01:28'),
('ae8083ec1cca5dfd6ad095b22c75e791f6fc8ddc7ad9c5dc1fcda9a4d1fe5eb7', 76, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-07 16:05:29', '2025-06-07 18:05:29'),
('aef37d25523f9a1192f74fc9f365946cf487904b7ddf9bac45b99492aa312396', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-15 14:58:39', '2025-05-15 16:58:39'),
('b5a49d011da2b6753f185ac19e32341449e835500153cf023f1cc52e8814b0cd', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-23 18:59:14', '2025-05-23 20:59:14'),
('b710a4925ba9c8390677f9390e6eda9358d1e2f484ae50ff1453ec5bc83fafe1', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 14:15:30', '2025-05-19 16:15:30'),
('b9b9e3422ce09d6a1b55a3022a326d96cdeb816a6fb131ddd1937902a9536a89', 76, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-09 18:48:33', '2025-06-09 20:48:33'),
('bd027c63cf9e74cd5dad5a480c16cf6601391d18c0059851c928d663a3851686', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 14:15:41', '2025-06-01 16:15:41'),
('c02440b96453f930df0251eb6f0949b2478da4d594d93638c17525aab26ee3f5', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 10:32:41', '2025-06-01 12:32:41'),
('c03d9199c3ea21042f27b29ec1b832fa5f721edad861981b1cf932ffb5a53dff', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-16 22:24:16', '2025-04-17 00:24:16'),
('c17598e6c712e25d8f615372ab420d73c57051d5533f1507f69888d064530446', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 23:14:33', '2025-05-28 01:14:33'),
('c296962d1268bc341ad47c9a506c17bd354ff827b0d6794e528e2f0aa1118b80', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 20:08:15', '2025-05-27 22:08:15'),
('c9cba047941070739b1c820816037eb8f1cc723890b21084ad7617813743ab4f', 81, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-06 16:26:35', '2025-06-06 18:26:35'),
('cb4a54d5d257982ff56372bb46a672f4bdbf5e5b60f2db48d217f46ab7474bf7', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-05 14:30:14', '2025-06-05 16:30:14'),
('cd9e51a75202fbde2f04f33704ca34e4e5345f20eeea7a7a168dff9f7c1ee9a6', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-29 23:03:49', '2025-04-30 01:03:49'),
('cfaee0d7001eb084f9c7585bb109c01d3b97ed71d703a4337745ee6d3c2c2435', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-02 20:45:51', '2025-05-02 22:45:51'),
('d3563c32b5150997666b68e4afccc27382872b184d91dca6000d9b040085f476', 76, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-07 20:39:53', '2025-06-07 22:39:53'),
('d514600bbd4b95440f85ab246dd2855532fbb0c46a02eda5d1442d3d0ee28451', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 21:05:04', '2025-05-27 23:05:04'),
('d83a4c1707bdfbc87cb12067f17f3e83a414e8d6450ec4e298fb04e6d86d92cf', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-16 19:23:38', '2025-04-16 21:23:38'),
('d866319115e009321b02f06743d92dd8b27741b1b22555b58f783420a5ae921d', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-04-30 15:08:14', '2025-04-30 17:08:14'),
('d87a82ccadc42fddf1cbc2457d5af6f35ab9c81b2fb7df2c116d768e7845cc80', 74, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 18:05:27', '2025-05-19 20:05:27'),
('d96c48cebbd245ebbfa4c50512c1a8744cd1babb01edc8dd6316836580c39314', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 17:37:35', '2025-05-19 19:37:35'),
('dacea400f79207dc000877d021c740946fdf3fcfd767572f8002957e79e555e2', 67, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 15:02:57', '2025-06-01 17:02:57'),
('ddaae490d87adebf432652da940a5ac0dfa3feefe85b35fefc2ba8768b42631e', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-15 14:54:41', '2025-05-15 16:54:41'),
('df01b199b490eb1acf5abce7c0454fdfb8d612d0f04cd9ec6224a3b1af111815', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 20:38:25', '2025-05-27 22:38:25'),
('df301ccbe9b3f15395bcd87c85b47df76ebdbce4c01ee28614ba4c43f6914e9d', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 09:29:10', '2025-05-19 11:29:10'),
('e68dfe20074e567d0737a2555f925ee2120e5c0c586699463a6734978346143b', 67, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-11 13:24:20', '2025-06-11 15:24:20'),
('e86e827d0a1bb6458fa767c3a29e800ace0ec31ec570965ed1037d2c7190f092', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-18 21:46:22', '2025-04-18 23:46:22'),
('ea22313b3aa08cba5a27ccc47718c343d462b8ff5efa5aa782dda0976427ed40', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-04-30 11:11:31', '2025-04-30 13:11:31'),
('ed9c48da76b6c5cafbce4008b6248cf3e103fb1e00e0d44ecad73499ae6881b4', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-16 20:36:21', '2025-04-16 22:36:21'),
('efd9d0bcf7d6b37d06e9026e4608c0792e40b656179f2368d53872125c0161db', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-24 21:44:38', '2025-05-24 23:44:38'),
('f256a126992c0298b277de9df494e056000d1091e64ffaabc22b2b8eb1678e47', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 16:31:54', '2025-06-01 18:31:54'),
('f5b361e37e6aef693d98fbf84e4711c53a40e9db9bd460f7319a602d9598f9c6', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 20:34:46', '2025-05-27 22:34:46'),
('f5c87617505c8a8a1bc4a5779cd3c499e6e6b944738347d562acb0a3c4297fac', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 23:12:26', '2025-05-28 01:12:26'),
('f95e9b3e3e5a1b560ad6c0e0333735861d4222d012c55bc52947178b575dae05', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 20:17:27', '2025-05-27 22:17:27'),
('fbd61994c1a55dcb325bd8e968edacc24fdc8bcc29ee63e5a86665452dc9cebe', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-05 18:49:59', '2025-06-05 20:49:59'),
('fc583a7bad67c3bc2bb891d5d064af26014350c013ebee0ddde302b45a631d31', 102, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-09 20:26:02', '2025-06-09 22:26:02'),
('fe243bc860f2d8034a174c4570850b7d5c53ff9e7effa747085db5464c22a9dd', 67, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-09 19:57:05', '2025-06-09 21:57:05'),
('feb84f8d6b3775f3fa6cee135c6acdaa250417eb3a87e110903137538b5d8371', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-05 15:30:51', '2025-06-05 17:30:51'),
('fee81aab27925475dbfe1f82bcc02fe190b8069ae75c342cb2cb15375e33ed42', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 15:19:26', '2025-05-19 17:19:26');

-- --------------------------------------------------------

--
-- Structure de la table `specialites`
--

CREATE TABLE `specialites` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `id_departement` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `specialites`
--

INSERT INTO `specialites` (`id`, `nom`, `id_departement`, `description`, `date_creation`) VALUES
(1, 'Informatique Appliquée', 1, 'Spécialité axée sur le développement logiciel et les systèmes informatiques.', '2025-04-29 22:00:00'),
(2, 'physique', 1, NULL, '2025-04-12 19:37:54'),
(3, 'mecanique', 1, NULL, '2025-04-12 19:40:10'),
(4, 'informatique', 1, NULL, '2025-04-12 19:00:05'),
(15, 'Informatique théorique', 1, 'Fondements mathématiques de l\'informatique', '2025-06-06 01:07:38'),
(16, 'Intelligence artificielle', 1, 'Apprentissage automatique et systèmes intelligents', '2025-06-06 01:07:38'),
(17, 'Réseaux et sécurité', 1, 'Architecture réseau et cybersécurité', '2025-06-06 01:07:38'),
(18, 'Développement logiciel', 1, 'Conception et programmation d\'applications', '2025-06-06 01:07:38'),
(19, 'Algèbre', 2, 'Structures algébriques et théorie des groupes', '2025-06-06 01:07:38'),
(20, 'Analyse numérique', 2, 'Méthodes numériques pour la résolution de problèmes', '2025-06-06 01:07:38'),
(21, 'Probabilités et statistiques', 2, 'Théorie des probabilités et analyse statistique', '2025-06-06 01:07:38'),
(22, 'Mathématiques appliquées', 2, 'Applications des mathématiques à d\'autres domaines', '2025-06-06 01:07:38'),
(23, 'Mécanique quantique', 3, 'Physique à l\'échelle atomique et subatomique', '2025-06-06 01:07:38'),
(24, 'Physique des particules', 3, 'Étude des constituants fondamentaux de la matière', '2025-06-06 01:07:38'),
(25, 'Astrophysique', 3, 'Physique appliquée à l\'astronomie', '2025-06-06 01:07:38'),
(26, 'Physique des matériaux', 3, 'Propriétés et comportement des matériaux', '2025-06-06 01:07:38'),
(27, 'culture générale', 1, 'vise a etudier culture et diversite des sciences humaines', '2025-06-07 12:53:40'),
(29, 'Langues étrangeres', 3, 'apprendre a communiquer avec plusieurs langues', '2025-06-07 13:39:33'),
(30, 'Management et Entrepreunariat', 3, NULL, '2025-06-07 14:15:08');

-- --------------------------------------------------------

--
-- Structure de la table `tentatives_connexion`
--

CREATE TABLE `tentatives_connexion` (
  `id` int(11) NOT NULL,
  `id_utilisateur` int(11) DEFAULT NULL,
  `adresse_ip` varchar(45) NOT NULL,
  `date_tentative` timestamp NOT NULL DEFAULT current_timestamp(),
  `succes` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `tentatives_connexion`
--

INSERT INTO `tentatives_connexion` (`id`, `id_utilisateur`, `adresse_ip`, `date_tentative`, `succes`) VALUES
(1, NULL, '::1', '2025-04-16 19:08:17', 0),
(2, NULL, '::1', '2025-04-16 19:14:50', 0),
(3, NULL, '::1', '2025-04-16 19:15:08', 0),
(4, NULL, '::1', '2025-04-16 19:19:26', 0),
(5, 2, '::1', '2025-04-16 19:19:41', 1),
(6, 2, '::1', '2025-04-16 19:21:22', 1),
(7, 2, '::1', '2025-04-16 19:23:29', 1),
(8, 2, '::1', '2025-04-16 19:23:38', 1),
(9, 2, '::1', '2025-04-16 19:23:43', 1),
(10, 2, '::1', '2025-04-16 19:45:52', 1),
(11, 2, '::1', '2025-04-16 20:36:21', 1),
(12, 2, '::1', '2025-04-16 20:38:28', 1),
(13, 2, '::1', '2025-04-16 20:47:44', 1),
(14, 2, '::1', '2025-04-16 20:58:30', 1),
(15, 2, '::1', '2025-04-16 21:08:18', 1),
(16, NULL, '::1', '2025-04-16 21:11:53', 0),
(17, NULL, '::1', '2025-04-16 21:12:16', 0),
(18, NULL, '::1', '2025-04-16 21:12:27', 0),
(19, 2, '::1', '2025-04-16 21:12:30', 1),
(20, NULL, '::1', '2025-04-16 21:13:19', 1),
(21, 2, '::1', '2025-04-16 21:13:36', 1),
(22, 2, '::1', '2025-04-16 22:11:45', 1),
(23, 2, '::1', '2025-04-16 22:24:16', 1),
(24, 2, '::1', '2025-04-17 12:26:04', 1),
(25, 2, '::1', '2025-04-17 13:53:31', 1),
(26, 2, '::1', '2025-04-18 21:46:22', 1),
(27, 2, '::1', '2025-04-19 12:34:24', 1),
(28, NULL, '::1', '2025-04-19 19:20:16', 1),
(29, 2, '::1', '2025-04-19 19:21:21', 1),
(30, 2, '::1', '2025-04-23 12:51:58', 1),
(31, 2, '::1', '2025-04-23 12:52:48', 1),
(32, 2, '::1', '2025-04-29 13:58:32', 1),
(33, 2, '::1', '2025-04-29 19:10:44', 1),
(34, 2, '::1', '2025-04-29 19:11:46', 1),
(35, 2, '::1', '2025-04-29 21:03:19', 0),
(36, 2, '::1', '2025-04-29 21:03:29', 1),
(37, 2, '::1', '2025-04-29 23:03:49', 1),
(38, 2, '::1', '2025-04-29 23:04:01', 1),
(39, 2, '::1', '2025-04-29 23:06:44', 1),
(40, 2, '::1', '2025-04-29 23:06:44', 1),
(41, 2, '::1', '2025-04-29 23:22:35', 1),
(42, 2, '::1', '2025-04-30 00:18:50', 1),
(43, 2, '::1', '2025-04-30 01:33:36', 1),
(44, 2, '::1', '2025-04-30 11:11:31', 1),
(45, 2, '::1', '2025-04-30 14:01:34', 1),
(46, 2, '::1', '2025-04-30 15:08:14', 1),
(47, 2, '::1', '2025-04-30 16:55:58', 1),
(48, NULL, '::1', '2025-04-30 16:58:28', 1),
(49, 2, '::1', '2025-04-30 17:02:25', 1),
(50, NULL, '::1', '2025-04-30 17:03:36', 1),
(51, NULL, '::1', '2025-04-30 17:06:10', 1),
(52, NULL, '::1', '2025-04-30 17:10:13', 1),
(53, NULL, '::1', '2025-04-30 17:11:18', 1),
(54, 2, '::1', '2025-04-30 22:38:47', 1),
(55, 2, '::1', '2025-05-01 07:41:00', 1),
(56, 2, '::1', '2025-05-02 12:43:09', 1),
(57, 2, '::1', '2025-05-02 15:03:09', 1),
(58, NULL, '::1', '2025-05-02 15:07:01', 1),
(59, NULL, '::1', '2025-05-02 15:57:11', 1),
(60, NULL, '::1', '2025-05-02 15:57:55', 1),
(61, NULL, '::1', '2025-05-02 15:59:58', 0),
(62, NULL, '::1', '2025-05-02 16:00:36', 0),
(63, NULL, '::1', '2025-05-02 16:00:47', 0),
(64, 2, '::1', '2025-05-02 16:00:55', 1),
(65, NULL, '::1', '2025-05-02 16:01:38', 1),
(66, NULL, '::1', '2025-05-02 16:02:08', 1),
(67, 2, '::1', '2025-05-02 16:02:53', 1),
(68, NULL, '::1', '2025-05-02 16:04:27', 1),
(69, 2, '::1', '2025-05-02 16:04:46', 1),
(70, NULL, '::1', '2025-05-02 16:05:09', 1),
(71, 2, '::1', '2025-05-02 20:44:45', 1),
(72, 2, '::1', '2025-05-02 20:45:51', 1),
(73, NULL, '::1', '2025-05-02 21:05:09', 0),
(74, 2, '::1', '2025-05-02 21:05:13', 1),
(75, 2, '::1', '2025-05-02 21:12:21', 1),
(76, 2, '::1', '2025-05-03 15:18:26', 1),
(77, NULL, '::1', '2025-05-03 15:19:45', 1),
(78, 2, '::1', '2025-05-03 16:18:44', 1),
(79, NULL, '::1', '2025-05-03 16:20:35', 1),
(80, 2, '::1', '2025-05-04 13:58:05', 1),
(81, NULL, '::1', '2025-05-04 13:58:57', 1),
(82, 2, '::1', '2025-05-04 14:35:47', 1),
(83, NULL, '::1', '2025-05-04 14:36:16', 1),
(84, 2, '::1', '2025-05-04 19:01:32', 1),
(85, 2, '::1', '2025-05-04 21:58:14', 1),
(86, NULL, '::1', '2025-05-04 21:58:45', 1),
(87, NULL, '::1', '2025-05-04 22:28:53', 1),
(88, 2, '::1', '2025-05-15 14:54:41', 1),
(89, NULL, '::1', '2025-05-15 14:56:41', 1),
(90, 2, '::1', '2025-05-15 14:58:39', 1),
(91, NULL, '::1', '2025-05-15 15:00:54', 1),
(92, 2, '::1', '2025-05-15 15:01:10', 1),
(93, NULL, '::1', '2025-05-15 15:01:41', 1),
(94, 2, '::1', '2025-05-15 15:05:14', 1),
(95, NULL, '::1', '2025-05-15 15:05:55', 1),
(96, 2, '::1', '2025-05-15 15:09:10', 1),
(97, NULL, '::1', '2025-05-15 15:09:44', 1),
(98, 2, '::1', '2025-05-16 11:21:44', 1),
(99, NULL, '::1', '2025-05-16 11:22:37', 1),
(100, 2, '::1', '2025-05-16 11:56:34', 1),
(101, NULL, '::1', '2025-05-16 11:57:06', 0),
(102, 2, '::1', '2025-05-16 11:57:22', 1),
(103, NULL, '::1', '2025-05-16 11:58:10', 1),
(104, 2, '::1', '2025-05-19 09:29:10', 1),
(105, NULL, '::1', '2025-05-19 09:29:38', 1),
(106, 2, '::1', '2025-05-19 12:49:46', 1),
(107, NULL, '::1', '2025-05-19 13:05:30', 1),
(108, 2, '::1', '2025-05-19 13:14:16', 1),
(109, 2, '::1', '2025-05-19 14:15:30', 1),
(110, 68, '::1', '2025-05-19 14:15:59', 1),
(111, 68, '::1', '2025-05-19 15:13:15', 1),
(112, 2, '::1', '2025-05-19 15:19:26', 1),
(113, NULL, '::1', '2025-05-19 15:20:04', 1),
(114, 2, '::1', '2025-05-19 15:22:42', 1),
(115, NULL, '::1', '2025-05-19 15:31:33', 1),
(116, NULL, '::1', '2025-05-19 15:32:36', 1),
(117, 68, '::1', '2025-05-19 15:40:31', 1),
(118, 68, '::1', '2025-05-19 15:43:59', 1),
(119, 2, '::1', '2025-05-19 15:44:42', 1),
(120, NULL, '::1', '2025-05-19 15:46:27', 1),
(121, 2, '::1', '2025-05-19 17:29:03', 1),
(122, NULL, '::1', '2025-05-19 17:29:43', 1),
(123, 2, '::1', '2025-05-19 17:37:35', 1),
(124, NULL, '::1', '2025-05-19 17:38:31', 1),
(125, NULL, '::1', '2025-05-19 17:39:09', 1),
(126, NULL, '::1', '2025-05-19 17:40:45', 1),
(127, NULL, '::1', '2025-05-19 17:47:05', 1),
(128, NULL, '::1', '2025-05-19 17:48:00', 1),
(129, NULL, '::1', '2025-05-19 17:48:54', 1),
(130, NULL, '::1', '2025-05-19 17:49:34', 1),
(131, NULL, '::1', '2025-05-19 17:53:47', 1),
(132, 68, '::1', '2025-05-19 17:53:55', 1),
(133, NULL, '::1', '2025-05-19 17:55:16', 1),
(134, NULL, '::1', '2025-05-19 17:56:19', 1),
(135, NULL, '::1', '2025-05-19 18:01:08', 1),
(136, NULL, '::1', '2025-05-19 18:03:41', 1),
(137, NULL, '::1', '2025-05-19 18:03:58', 1),
(138, NULL, '::1', '2025-05-19 18:04:36', 1),
(139, NULL, '::1', '2025-05-19 18:04:54', 0),
(140, NULL, '::1', '2025-05-19 18:04:59', 0),
(141, 2, '::1', '2025-05-19 18:05:02', 1),
(142, 74, '::1', '2025-05-19 18:05:27', 1),
(143, 74, '::1', '2025-05-19 18:08:44', 1),
(144, 68, '::1', '2025-05-19 18:09:07', 1),
(145, NULL, '::1', '2025-05-19 18:27:56', 1),
(146, 2, '::1', '2025-05-20 11:28:39', 1),
(147, 68, '::1', '2025-05-20 11:31:42', 1),
(148, NULL, '::1', '2025-05-20 11:40:34', 0),
(149, 2, '::1', '2025-05-23 18:55:15', 1),
(150, 68, '::1', '2025-05-23 18:55:23', 1),
(151, NULL, '::1', '2025-05-23 18:57:23', 0),
(152, NULL, '::1', '2025-05-23 18:57:28', 0),
(153, 2, '::1', '2025-05-23 18:57:33', 1),
(154, 77, '::1', '2025-05-23 18:59:14', 1),
(155, 2, '::1', '2025-05-23 19:00:29', 1),
(156, 1, '::1', '2025-05-23 19:01:06', 1),
(157, 77, '::1', '2025-05-23 19:04:50', 1),
(158, 77, '::1', '2025-05-23 19:05:28', 1),
(159, 2, '::1', '2025-05-23 19:05:36', 1),
(160, 68, '::1', '2025-05-23 19:06:15', 1),
(161, 77, '::1', '2025-05-23 19:19:22', 1),
(162, 2, '::1', '2025-05-23 19:21:43', 1),
(163, 1, '::1', '2025-05-23 19:22:32', 1),
(164, 2, '::1', '2025-05-24 21:41:24', 1),
(165, 68, '::1', '2025-05-24 21:44:38', 1),
(166, 2, '::1', '2025-05-24 21:45:52', 1),
(167, 2, '::1', '2025-05-27 20:07:44', 1),
(168, 68, '::1', '2025-05-27 20:08:15', 1),
(169, 68, '::1', '2025-05-27 20:15:49', 1),
(170, 68, '::1', '2025-05-27 20:16:09', 1),
(171, 68, '::1', '2025-05-27 20:17:27', 1),
(172, 68, '::1', '2025-05-27 20:18:44', 1),
(173, 2, '::1', '2025-05-27 20:23:19', 1),
(174, 68, '::1', '2025-05-27 20:34:46', 1),
(175, 2, '::1', '2025-05-27 20:37:48', 1),
(176, 68, '::1', '2025-05-27 20:38:25', 1),
(177, 2, '::1', '2025-05-27 20:51:03', 1),
(178, 77, '::1', '2025-05-27 20:59:58', 1),
(179, 2, '::1', '2025-05-27 21:04:57', 1),
(180, 68, '::1', '2025-05-27 21:05:04', 1),
(181, 2, '::1', '2025-05-27 21:41:27', 1),
(182, 68, '::1', '2025-05-27 22:01:05', 1),
(183, 68, '::1', '2025-05-27 22:01:38', 1),
(184, 68, '::1', '2025-05-27 23:07:19', 1),
(185, 77, '::1', '2025-05-27 23:08:33', 1),
(186, 77, '::1', '2025-05-27 23:11:25', 1),
(187, 77, '::1', '2025-05-27 23:12:26', 1),
(188, 68, '::1', '2025-05-27 23:14:33', 1),
(189, 77, '::1', '2025-06-01 10:32:41', 1),
(190, 2, '::1', '2025-06-01 13:05:16', 1),
(191, 78, '::1', '2025-06-01 13:05:45', 1),
(192, 2, '::1', '2025-06-01 13:17:06', 1),
(193, 78, '::1', '2025-06-01 13:17:56', 1),
(194, 2, '::1', '2025-06-01 14:10:51', 1),
(195, 75, '::1', '2025-06-01 14:11:30', 1),
(196, 75, '::1', '2025-06-01 14:13:40', 1),
(197, 75, '::1', '2025-06-01 14:14:11', 1),
(198, 68, '::1', '2025-06-01 14:15:14', 1),
(199, 77, '::1', '2025-06-01 14:15:41', 1),
(200, 68, '::1', '2025-06-01 14:17:13', 1),
(201, 2, '::1', '2025-06-01 14:18:05', 1),
(202, 67, '::1', '2025-06-01 14:18:25', 1),
(203, 77, '::1', '2025-06-01 14:29:36', 1),
(204, 68, '::1', '2025-06-01 14:34:12', 1),
(205, 68, '::1', '2025-06-01 14:57:11', 1),
(206, 67, '::1', '2025-06-01 15:02:57', 1),
(207, 77, '::1', '2025-06-01 15:22:23', 1),
(208, 67, '::1', '2025-06-01 15:24:25', 1),
(209, 77, '::1', '2025-06-01 16:31:54', 1),
(210, 68, '::1', '2025-06-01 16:33:06', 1),
(211, 67, '::1', '2025-06-01 16:34:01', 1),
(212, 67, '::1', '2025-06-01 16:46:44', 1),
(213, 77, '::1', '2025-06-01 16:59:56', 1),
(214, 68, '::1', '2025-06-01 17:01:06', 1),
(215, 67, '::1', '2025-06-01 17:01:28', 1),
(216, 2, '::1', '2025-06-01 20:11:37', 1),
(217, 1, '::1', '2025-06-01 20:12:14', 1),
(218, 1, '::1', '2025-06-01 20:12:18', 1),
(219, 67, '::1', '2025-06-01 20:13:10', 1),
(220, 77, '::1', '2025-06-02 09:08:23', 1),
(221, 67, '::1', '2025-06-02 09:16:13', 1),
(222, 67, '::1', '2025-06-02 22:49:38', 1),
(223, 68, '::1', '2025-06-05 13:06:10', 1),
(224, 68, '::1', '2025-06-05 13:14:58', 1),
(225, 68, '::1', '2025-06-05 13:56:22', 1),
(226, 77, '::1', '2025-06-05 14:30:14', 1),
(227, 68, '::1', '2025-06-05 14:32:03', 1),
(228, 77, '::1', '2025-06-05 15:19:43', 1),
(229, 68, '::1', '2025-06-05 15:21:45', 1),
(230, 77, '::1', '2025-06-05 15:30:51', 1),
(231, 68, '::1', '2025-06-05 15:32:33', 1),
(232, 68, '::1', '2025-06-05 17:20:54', 1),
(233, 2, '::1', '2025-06-05 17:30:16', 1),
(234, 68, '::1', '2025-06-05 17:31:01', 1),
(235, 68, '::1', '2025-06-05 18:49:59', 1),
(236, 68, '::1', '2025-06-05 20:57:14', 1),
(237, 68, '::1', '2025-06-05 23:37:20', 1),
(238, 68, '::1', '2025-06-05 23:52:02', 1),
(239, 68, '::1', '2025-06-06 11:36:56', 1),
(240, 68, '::1', '2025-06-06 15:32:05', 1),
(241, 2, '::1', '2025-06-06 16:01:38', 0),
(242, 2, '::1', '2025-06-06 16:01:47', 1),
(243, 81, '::1', '2025-06-06 16:03:28', 1),
(244, 68, '::1', '2025-06-06 16:09:17', 1),
(245, 2, '::1', '2025-06-06 16:11:46', 1),
(246, 82, '::1', '2025-06-06 16:13:09', 1),
(247, 68, '::1', '2025-06-06 16:14:53', 1),
(248, 77, '::1', '2025-06-06 16:17:00', 1),
(249, 68, '::1', '2025-06-06 16:23:58', 1),
(250, 81, '::1', '2025-06-06 16:26:35', 1),
(251, 81, '::1', '2025-06-06 18:32:51', 1),
(252, 2, '::1', '2025-06-06 18:33:29', 1),
(253, 83, '::1', '2025-06-06 18:34:44', 1),
(254, 2, '::1', '2025-06-06 18:35:00', 1),
(255, NULL, '::1', '2025-06-06 18:36:14', 1),
(256, 1, '::1', '2025-06-06 18:40:04', 1),
(257, NULL, '::1', '2025-06-06 18:45:29', 1),
(258, 77, '::1', '2025-06-06 18:46:21', 1),
(259, 2, '::1', '2025-06-06 18:46:27', 1),
(260, 85, '::1', '2025-06-06 18:48:12', 1),
(261, 68, '::1', '2025-06-06 18:57:05', 1),
(262, 81, '::1', '2025-06-06 18:57:58', 1),
(263, 82, '::1', '2025-06-06 18:59:22', 1),
(264, 81, '::1', '2025-06-06 20:21:25', 1),
(265, 83, '::1', '2025-06-06 20:33:04', 1),
(266, 82, '::1', '2025-06-06 20:33:32', 1),
(267, 82, '::1', '2025-06-06 20:38:11', 1),
(268, 82, '::1', '2025-06-06 20:38:18', 1),
(269, 1, '::1', '2025-06-06 20:49:42', 1),
(270, 83, '::1', '2025-06-06 20:56:31', 1),
(271, 1, '::1', '2025-06-07 13:15:19', 1),
(272, 2, '::1', '2025-06-07 15:20:02', 1),
(273, 87, '::1', '2025-06-07 15:27:58', 1),
(274, 2, '::1', '2025-06-07 15:33:25', 1),
(275, 88, '::1', '2025-06-07 15:38:35', 1),
(276, 76, '::1', '2025-06-07 15:43:16', 0),
(277, 76, '::1', '2025-06-07 15:43:21', 0),
(278, 76, '::1', '2025-06-07 15:43:39', 0),
(279, 2, '::1', '2025-06-07 15:43:50', 1),
(280, 76, '::1', '2025-06-07 15:43:58', 0),
(281, 2, '::1', '2025-06-07 15:58:53', 1),
(282, 76, '::1', '2025-06-07 16:05:29', 1),
(283, 2, '::1', '2025-06-07 16:19:55', 1),
(284, 76, '::1', '2025-06-07 16:27:42', 1),
(285, 87, '::1', '2025-06-07 16:39:10', 1),
(286, 76, '::1', '2025-06-07 20:39:53', 1),
(287, 2, '::1', '2025-06-07 20:43:20', 1),
(288, 88, '::1', '2025-06-07 20:44:24', 1),
(289, 2, '::1', '2025-06-07 20:54:53', 1),
(290, 87, '::1', '2025-06-07 20:56:58', 1),
(291, 76, '::1', '2025-06-07 20:58:06', 1),
(292, 87, '::1', '2025-06-07 21:06:15', 0),
(293, 87, '::1', '2025-06-07 21:06:35', 1),
(294, 76, '::1', '2025-06-07 21:11:16', 1),
(295, 1, '::1', '2025-06-07 21:25:11', 1),
(296, 76, '::1', '2025-06-07 21:34:11', 1),
(297, NULL, '::1', '2025-06-08 13:02:40', 0),
(298, 85, '::1', '2025-06-08 13:03:01', 1),
(299, 2, '::1', '2025-06-08 13:03:27', 1),
(300, 91, '::1', '2025-06-08 13:10:41', 1),
(301, 92, '::1', '2025-06-08 13:11:38', 1),
(302, 91, '::1', '2025-06-08 13:12:48', 1),
(303, 93, '::1', '2025-06-08 13:13:04', 1),
(304, 94, '::1', '2025-06-08 13:13:46', 1),
(305, 2, '::1', '2025-06-08 13:15:07', 1),
(306, 95, '::1', '2025-06-08 13:15:32', 1),
(307, 94, '::1', '2025-06-08 13:20:20', 1),
(308, 81, '::1', '2025-06-08 17:31:48', 1),
(309, 82, '::1', '2025-06-08 17:35:23', 1),
(310, 1, '::1', '2025-06-08 17:37:18', 1),
(311, 1, '::1', '2025-06-09 18:07:28', 1),
(312, 2, '::1', '2025-06-09 18:13:47', 1),
(313, 2, '::1', '2025-06-09 18:15:51', 1),
(314, 2, '::1', '2025-06-09 18:23:24', 1),
(315, 2, '::1', '2025-06-09 18:33:13', 1),
(316, 81, '::1', '2025-06-09 18:45:14', 1),
(317, 76, '::1', '2025-06-09 18:48:33', 1),
(318, 76, '::1', '2025-06-09 18:49:04', 1),
(319, 1, '::1', '2025-06-09 19:05:17', 1),
(320, 81, '::1', '2025-06-09 19:19:33', 0),
(321, 82, '::1', '2025-06-09 19:19:40', 1),
(322, 83, '::1', '2025-06-09 19:21:44', 1),
(323, 82, '::1', '2025-06-09 19:21:58', 1),
(324, 76, '::1', '2025-06-09 19:47:44', 1),
(325, 82, '::1', '2025-06-09 19:49:04', 1),
(326, 2, '::1', '2025-06-09 19:56:10', 1),
(327, 67, '::1', '2025-06-09 19:57:05', 1),
(328, 81, '::1', '2025-06-09 19:57:20', 0),
(329, 2, '::1', '2025-06-09 19:57:45', 1),
(330, 102, '::1', '2025-06-09 19:59:23', 1),
(331, 2, '::1', '2025-06-09 20:11:37', 1),
(332, 2, '::1', '2025-06-09 20:19:01', 1),
(333, 81, '::1', '2025-06-09 20:25:15', 0),
(334, 81, '::1', '2025-06-09 20:25:19', 0),
(335, 81, '::1', '2025-06-09 20:25:28', 1),
(336, 81, '::1', '2025-06-09 20:25:39', 1),
(337, 102, '::1', '2025-06-09 20:26:02', 1),
(338, 102, '::1', '2025-06-09 20:26:38', 1),
(339, 76, '::1', '2025-06-09 20:31:21', 1),
(340, 1, '::1', '2025-06-09 20:48:08', 1),
(341, 82, '::1', '2025-06-09 20:54:53', 0),
(342, 82, '::1', '2025-06-09 20:55:02', 0),
(343, 82, '::1', '2025-06-09 20:55:40', 0),
(344, 82, '::1', '2025-06-09 20:55:47', 0),
(345, 2, '::1', '2025-06-09 20:55:54', 1),
(346, 82, '::1', '2025-06-09 20:56:38', 1),
(347, 82, '::1', '2025-06-09 20:56:58', 1),
(348, 81, '::1', '2025-06-11 13:24:06', 0),
(349, NULL, '::1', '2025-06-11 13:24:12', 0),
(350, 67, '::1', '2025-06-11 13:24:20', 1),
(351, 82, '::1', '2025-06-11 13:24:37', 1);

-- --------------------------------------------------------

--
-- Structure de la table `unites_enseignement`
--

CREATE TABLE `unites_enseignement` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `intitule` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `specialite` varchar(50) NOT NULL,
  `id_filiere` int(11) DEFAULT NULL,
  `id_departement` int(11) NOT NULL,
  `semestre` int(11) NOT NULL,
  `volume_horaire_cm` float DEFAULT 0,
  `volume_horaire_td` float DEFAULT 0,
  `volume_horaire_tp` float DEFAULT 0,
  `credits` int(11) NOT NULL,
  `annee_universitaire` varchar(10) NOT NULL,
  `id_responsable` int(11) DEFAULT NULL,
  `statut` enum('disponible','affecte','vacant') DEFAULT 'disponible',
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_modification` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `unites_enseignement`
--

INSERT INTO `unites_enseignement` (`id`, `code`, `intitule`, `description`, `specialite`, `id_filiere`, `id_departement`, `semestre`, `volume_horaire_cm`, `volume_horaire_td`, `volume_horaire_tp`, `credits`, `annee_universitaire`, `id_responsable`, `statut`, `date_creation`, `date_modification`) VALUES
(6, 'AB117', 'algebre lineiare', 'xwcvb', 'Mathématiques appliquées', 20, 1, 1, 14, 4, 6, 12, '2024-2025', NULL, 'disponible', '2025-05-19 14:39:29', '2025-06-09 20:28:52'),
(10, 'AZ123', 'CHIMIE', 'AZZEE', 'informatique', NULL, 3, 1, 3, 3, 3, 12, '2023-2024', 1, 'disponible', '2025-05-27 23:05:19', '2025-05-27 23:05:19'),
(11, 'DA123', 'Analyse de donnee1', 'data', 'informatique', 21, 1, 1, 20, 20, 20, 4, '2025-2026', NULL, 'disponible', '2025-06-05 14:17:40', '2025-06-07 17:11:35'),
(12, 'KK555', 'PRAGRAMMATION C++', 'INFORMATIqua', 'Informatique Appliquée', 21, 1, 1, 45, 45, 21, 3, '2024-2025', 3, 'disponible', '2025-06-05 16:19:25', '2025-06-07 23:39:45'),
(74, 'M111', 'Architecture des ordinateurs', NULL, 'Développement logiciel', 20, 1, 1, 26, 16, 16, 6, '2024-2025', NULL, 'disponible', '2025-06-06 15:50:11', '2025-06-06 15:50:11'),
(75, 'MM112', 'Langage C avancé et structures de données', NULL, 'informatique', 20, 1, 1, 26, 16, 16, 6, '2024-2025', NULL, 'disponible', '2025-06-06 15:52:35', '2025-06-07 16:37:55'),
(76, 'MM113', 'Recherche opérationnelle et théorie des graphes', NULL, 'Informatique théorique', 20, 1, 1, 26, 16, 16, 6, '2024-2025', NULL, 'disponible', '2025-06-06 15:56:16', '2025-06-06 15:56:16'),
(77, 'MM114', 'Systèmes d\'Information et Bases de Données Relationnelles', NULL, 'Développement logiciel', 20, 1, 1, 26, 16, 16, 6, '2024-2025', NULL, 'disponible', '2025-06-06 15:57:17', '2025-06-06 15:57:17'),
(78, 'MM115', 'Réseaux informatiques', NULL, 'Réseaux et sécurité', 20, 1, 1, 26, 16, 16, 6, '2024-2025', NULL, 'disponible', '2025-06-06 15:58:09', '2025-06-06 15:58:09'),
(79, 'M1116', 'Culture and Art skills', NULL, 'culture générale', 20, 1, 1, 26, 16, 16, 6, '2024-2025', NULL, 'disponible', '2025-06-06 16:00:31', '2025-06-07 14:58:18'),
(80, 'MM171', 'Langues Etrangéres (Français)', NULL, 'Langues étrangeres', 20, 1, 1, 26, 16, 16, 6, '2024-2025', NULL, 'disponible', '2025-06-06 16:01:39', '2025-06-07 16:01:44'),
(81, 'MM172', 'Langues Etrangéres (Anglais)', '', 'Langues étrangeres', 20, 1, 1, 26, 6, 3, 3, '2024-2025', NULL, 'disponible', '2025-06-06 16:15:32', '2025-06-07 16:01:44'),
(82, 'MM121', 'Architecture Logicielle et UML', '', 'Développement logiciel', 20, 1, 2, 26, 17, 10, 6, '2024-2025', NULL, 'disponible', '2025-06-06 16:15:32', '2025-06-06 16:36:00'),
(83, 'M122', 'Web1 : Technologies de Web et PHP5', NULL, 'Développement logiciel', 20, 1, 2, 26, 10, 16, 6, '2024-2025', NULL, 'disponible', '2025-06-06 16:15:32', '2025-06-06 16:15:32'),
(84, 'M123', 'Programmation Orientée Objet C++', NULL, 'informatique', 20, 1, 2, 26, 16, 10, 6, '2024-2025', NULL, 'disponible', '2025-06-06 16:15:32', '2025-06-07 16:37:55'),
(85, 'M124', 'Linux et programmation systéme', NULL, 'Développement logiciel', 20, 1, 2, 26, 16, 10, 6, '2024-2025', NULL, 'disponible', '2025-06-06 16:15:32', '2025-06-06 16:15:32'),
(86, 'M125', 'Algorithmique Avancée et complexité', NULL, 'Informatique théorique', 20, 1, 2, 26, 26, 4, 6, '2024-2025', NULL, 'disponible', '2025-06-06 16:15:32', '2025-06-06 16:15:32'),
(87, 'M126', 'Prompt ingeniering for developpers', NULL, 'Intelligence artificielle', 20, 1, 2, 26, 26, 6, 6, '2024-2025', NULL, 'disponible', '2025-06-06 16:15:32', '2025-06-06 16:15:32'),
(88, 'M127.1', 'Langues,Communication et TIC -fr', NULL, 'Langues étrangeres', 20, 1, 2, 20, 6, 3, 6, '2024-2025', NULL, 'disponible', '2025-06-06 16:15:32', '2025-06-07 16:04:40'),
(89, 'M127.2', 'Langues,Communication et TIC- Ang', NULL, 'Langues étrangeres', 20, 1, 2, 20, 6, 3, 6, '2024-2025', NULL, 'disponible', '2025-06-06 16:15:32', '2025-06-07 16:05:13'),
(90, 'MM234', 'Algèbre 1', 'F', 'Mathématiques appliquées', 21, 1, 1, 23, 15, 20, 3, '2024-2025', 75, 'disponible', '2025-06-06 17:04:31', '2025-06-07 15:32:09'),
(91, 'MM346', 'Analyse', '', 'Algèbre', 21, 1, 1, 26, 12, 12, 0, '2024-2025', NULL, 'disponible', '2025-06-06 17:33:54', '2025-06-06 17:33:54'),
(92, 'MM122', 'Web1 : Technologies de Web et PHP5', NULL, 'Développement logiciel', 20, 1, 2, 26, 10, 16, 6, '2024-2025', NULL, 'disponible', '2025-06-06 17:54:02', '2025-06-06 17:54:02'),
(93, 'MM123', 'Programmation Orientée Objet C++', NULL, 'informatique', 21, 1, 2, 26, 16, 10, 6, '2024-2025', NULL, 'disponible', '2025-06-06 17:54:02', '2025-06-07 16:37:55'),
(94, 'MM124', 'Linux et programmation systéme', NULL, 'Développement logiciel', 20, 1, 2, 26, 16, 10, 6, '2024-2025', NULL, 'disponible', '2025-06-06 17:54:02', '2025-06-06 17:54:02'),
(95, 'MM125', 'Algorithmique Avancée et complexité', NULL, 'Informatique théorique', 20, 1, 2, 26, 26, 4, 6, '2024-2025', NULL, 'disponible', '2025-06-06 17:54:02', '2025-06-06 17:54:02'),
(96, 'MM126', 'Prompt ingeniering for developpers', NULL, 'Intelligence artificielle', 20, 1, 2, 26, 26, 6, 6, '2024-2025', NULL, 'disponible', '2025-06-06 17:54:02', '2025-06-06 17:54:02'),
(111, 'MM31', 'Python pour les sciences de données', NULL, 'Intelligence artificielle', 20, 1, 3, 28, 0, 36, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:14:45', '2025-06-06 18:14:45'),
(112, 'MM32', 'Programmation Java Avancée', NULL, 'informatique', 20, 1, 3, 24, 8, 32, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:14:45', '2025-06-07 16:37:55'),
(113, 'MM33.1', 'Langues et Communication -FR', NULL, 'Langues étrangeres', 20, 1, 3, 21, 0, 0, 2, '2024-2025', NULL, 'disponible', '2025-06-06 18:14:45', '2025-06-07 15:46:24'),
(114, 'MM33.2', 'Langues et Communication- Ang', NULL, 'Langues étrangeres', 20, 1, 3, 21, 10, 0, 2, '2024-2025', NULL, 'disponible', '2025-06-06 18:14:45', '2025-06-07 16:01:44'),
(115, 'MM33.3', 'Langues et Communication- Espagnol', NULL, 'Langues étrangeres', 20, 1, 3, 21, 10, 0, 2, '2024-2025', NULL, 'disponible', '2025-06-06 18:14:45', '2025-06-07 16:01:44'),
(116, 'MM34', 'Linux et programmation système', NULL, 'Développement logiciel', 20, 1, 3, 21, 16, 27, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:14:45', '2025-06-06 18:14:45'),
(117, 'MM35', 'Administration des Bases de données Avancées', NULL, 'Développement logiciel', 20, 1, 3, 26, 4, 34, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:14:45', '2025-06-06 18:14:45'),
(118, 'MM36', 'Administration réseaux et systèmes', NULL, 'Réseaux et sécurité', 20, 1, 3, 27, 15, 22, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:14:45', '2025-06-06 18:14:45'),
(119, 'MM41', 'Entreprenariat 2 - Contrôle gestion', NULL, 'Informatique Appliquée', 20, 1, 4, 21, 18, 0, 3, '2024-2025', NULL, 'disponible', '2025-06-06 18:14:45', '2025-06-06 18:14:45'),
(120, 'MM42', 'Entreprenariat 2 -Marketing fondamental', NULL, 'Informatique Appliquée', 20, 1, 4, 25, 0, 0, 3, '2024-2025', NULL, 'disponible', '2025-06-06 18:14:45', '2025-06-06 18:14:45'),
(121, 'MM426', 'Machine Learning', NULL, 'Intelligence artificielle', 20, 1, 4, 21, 20, 23, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:14:45', '2025-06-06 18:14:45'),
(122, 'MM431', 'Gestion de projet', NULL, 'Développement logiciel', 20, 1, 4, 16, 6, 16, 3, '2024-2025', NULL, 'disponible', '2025-06-06 18:14:45', '2025-06-06 18:14:45'),
(123, 'MM432', 'Génie logiciel', NULL, 'Développement logiciel', 20, 1, 4, 12, 6, 0, 3, '2024-2025', NULL, 'disponible', '2025-06-06 18:14:45', '2025-06-06 18:14:45'),
(124, 'MM441', 'Crypto-systèmes', NULL, 'Réseaux et sécurité', 20, 1, 4, 15, 10, 4, 3, '2024-2025', NULL, 'disponible', '2025-06-06 18:14:45', '2025-06-06 18:14:45'),
(125, 'DD131', 'Théorie des langages et compilation', NULL, 'Informatique théorique', 21, 1, 1, 26, 18, 8, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:21:11', '2025-06-06 18:21:11'),
(126, 'DD112', 'Systèmes d\'Information et Bases de Données', NULL, 'Développement logiciel', 21, 1, 1, 26, 10, 16, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:21:11', '2025-06-06 18:21:11'),
(127, 'DD113', 'Structure de données et Algorithmique avancée', NULL, 'Informatique théorique', 21, 1, 1, 26, 16, 10, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:21:11', '2025-06-06 18:21:11'),
(128, 'DD114', 'Architecture d\'entreprise et transformation digitale', NULL, 'Intelligence artificielle', 21, 1, 1, 26, 6, 20, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:21:11', '2025-06-06 18:21:11'),
(129, 'DD115', 'Architecture des ordinateurs et systèmes d\'exploitation', NULL, 'Développement logiciel', 21, 1, 1, 26, 10, 16, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:21:11', '2025-06-06 18:21:11'),
(130, 'DD116', 'Langues Etrangéres Français', NULL, 'Langues étrangeres', 21, 1, 1, 44, 12, 6, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:21:11', '2025-06-07 16:06:08'),
(257, 'M192', 'Programmation Orientée Objet Java', NULL, 'informatique', 21, 1, 2, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:45:22', '2025-06-07 16:37:55'),
(258, 'M127', 'Programmation Python / Programmation fonctionnelle', NULL, 'Intelligence artificielle', 21, 1, 2, 36, 0, 28, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:45:22', '2025-06-06 18:45:22'),
(259, 'M121', 'Développement Web', NULL, 'Développement logiciel', 21, 1, 2, 24, 0, 40, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:45:22', '2025-06-06 18:45:22'),
(260, 'M155', 'Gestion de projets informatiques', NULL, 'Développement logiciel', 21, 1, 2, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:45:22', '2025-06-06 18:45:22'),
(261, 'M103', 'Industrie de numérique', NULL, 'Intelligence artificielle', 21, 1, 2, 24, 20, 20, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:45:22', '2025-06-06 18:45:22'),
(262, 'M224', 'Langues Etrangéres (Anglais /Français)', NULL, 'Langues étrangeres', 21, 1, 2, 44, 12, 6, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:45:22', '2025-06-07 16:01:44'),
(263, 'M166', 'marketing et management pour les technologies de l\'information', NULL, 'Informatique Appliquée', 21, 1, 2, 24, 30, 10, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:45:22', '2025-06-06 18:45:22'),
(264, 'M234', 'Cloud Computing', NULL, 'Réseaux et sécurité', 21, 1, 3, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:45:22', '2025-06-06 18:45:22'),
(265, 'M232', 'Cartographie des systèmes d\'information', NULL, 'Intelligence artificielle', 21, 1, 3, 24, 20, 20, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:45:22', '2025-06-06 18:45:22'),
(266, 'M235', 'Bases de l\'Intelligence Artificielle', NULL, 'Intelligence artificielle', 21, 1, 3, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:45:22', '2025-06-06 18:45:22'),
(267, 'M236', 'Architecture logiciel et UML', NULL, 'Développement logiciel', 21, 1, 3, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:45:22', '2025-06-06 18:45:22'),
(268, 'M233', 'Communication Professionnelle et Soft Skills-2', NULL, 'Langues étrangeres', 21, 1, 3, 30, 30, 0, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:45:22', '2025-06-07 15:46:24'),
(269, 'M231', 'Gestion de projets digitaux', NULL, 'Intelligence artificielle', 21, 1, 3, 24, 30, 10, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:45:22', '2025-06-06 18:45:22'),
(270, 'M243', 'Applications de l\'Intelligence Artificielle', NULL, 'Intelligence artificielle', 21, 1, 4, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:45:22', '2025-06-06 18:45:22'),
(271, 'M245', 'Ingestion et stockage de données', NULL, 'Intelligence artificielle', 21, 1, 4, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:45:22', '2025-06-06 18:45:22'),
(272, 'M242', 'Big Data', NULL, 'Intelligence artificielle', 21, 1, 4, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:45:22', '2025-06-06 18:45:22'),
(273, 'MM244', 'Droit et sécurité des données', NULL, 'Réseaux et sécurité', 21, 1, 4, 24, 20, 20, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:45:22', '2025-06-06 18:45:22'),
(274, 'M241', 'Cyber Security', NULL, 'Réseaux et sécurité', 21, 1, 4, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:45:22', '2025-06-06 18:45:22'),
(275, 'M246', 'Entreprenariat', NULL, 'Management et Entrepreunariat', 21, 1, 4, 24, 20, 10, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:45:22', '2025-06-07 16:21:13'),
(276, 'MM351', 'La veille Stratégique, Scientifique et Technologique', NULL, 'Intelligence artificielle', 21, 1, 5, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:45:22', '2025-06-06 18:45:22'),
(277, 'M353', 'Gouvernance et Urbanisation des SI', NULL, 'Intelligence artificielle', 21, 1, 5, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:45:22', '2025-06-06 18:45:22'),
(278, 'M352', 'DevOps', NULL, 'Développement logiciel', 21, 1, 5, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:45:22', '2025-06-06 18:45:22'),
(279, 'M355', 'Innovation Engineering & Digitalisation', NULL, 'Intelligence artificielle', 21, 1, 5, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:45:22', '2025-06-06 18:45:22'),
(280, 'M354', 'Web Marketing et CRM', NULL, 'Informatique Appliquée', 21, 1, 5, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:45:22', '2025-06-06 18:45:22'),
(281, 'M356', 'Business English', NULL, 'Langues étrangeres', 21, 1, 5, 14, 20, 0, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:45:22', '2025-06-07 16:12:37'),
(814, 'MI199', 'Programmation Orientée Objet Java', NULL, 'informatique', 22, 1, 2, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:55:55', '2025-06-07 16:37:55'),
(815, 'MI188', 'Programmation Python / Programmation fonctionnelle', NULL, 'informatique', 20, 1, 2, 36, 0, 28, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:55:55', '2025-06-07 17:03:58'),
(816, 'MI141', 'Développement Web', NULL, 'Développement logiciel', 21, 1, 2, 24, 0, 40, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:55:55', '2025-06-06 18:55:55'),
(817, 'MI555', 'Gestion de projets informatiques', NULL, 'Développement logiciel', 21, 1, 2, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:55:55', '2025-06-06 18:55:55'),
(818, 'MI100', 'Industrie de numérique', NULL, 'Intelligence artificielle', 21, 1, 2, 24, 20, 20, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:55:55', '2025-06-06 18:55:55'),
(819, 'MI220', 'Langues Etrangéres (Anglais /Français)', NULL, 'Langues étrangeres', 21, 1, 2, 44, 12, 6, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:55:55', '2025-06-07 16:01:44'),
(820, 'MI966', 'marketing et management pour les technologies de l\'information', NULL, 'Informatique Appliquée', 21, 1, 2, 24, 30, 10, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:55:55', '2025-06-06 18:55:55'),
(821, 'MI294', 'Cloud Computing', NULL, 'Réseaux et sécurité', 21, 1, 3, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:55:55', '2025-06-06 18:55:55'),
(822, 'MI232', 'Cartographie des systèmes d\'information', NULL, 'Intelligence artificielle', 21, 1, 3, 24, 20, 20, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:55:55', '2025-06-06 18:55:55'),
(823, 'MI235', 'Bases de l\'Intelligence Artificielle', NULL, 'Intelligence artificielle', 21, 1, 3, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:55:55', '2025-06-06 18:55:55'),
(824, 'MI296', 'Architecture logiciel et UML', NULL, 'Développement logiciel', 21, 1, 3, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:55:55', '2025-06-06 18:55:55'),
(825, 'MI233', 'Communication Professionnelle et Soft Skills-2', NULL, 'Langues étrangeres', 21, 1, 3, 30, 30, 0, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:55:55', '2025-06-07 15:46:24'),
(826, 'MI231', 'Gestion de projets digitaux', NULL, 'Intelligence artificielle', 21, 1, 3, 24, 30, 10, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:55:55', '2025-06-06 18:55:55'),
(827, 'MI243', 'Applications de l\'Intelligence Artificielle', NULL, 'Intelligence artificielle', 21, 1, 4, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:55:55', '2025-06-06 18:55:55'),
(828, 'MI245', 'Ingestion et stockage de données', NULL, 'Intelligence artificielle', 21, 1, 4, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:55:55', '2025-06-06 18:55:55'),
(829, 'MI242', 'Big Data', NULL, 'Intelligence artificielle', 21, 1, 4, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:55:55', '2025-06-06 18:55:55'),
(830, 'MI244', 'Droit et sécurité des données', NULL, 'Réseaux et sécurité', 21, 1, 4, 24, 20, 20, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:55:55', '2025-06-06 18:55:55'),
(831, 'MI241', 'Cyber Security', NULL, 'Réseaux et sécurité', 21, 1, 4, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:55:55', '2025-06-06 18:55:55'),
(832, 'MI246', 'Entreprenariat', NULL, 'Management et Entrepreunariat', 22, 1, 4, 24, 20, 10, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:55:55', '2025-06-07 16:21:13'),
(833, 'MI351', 'La veille Stratégique, Scientifique et Technologique', NULL, 'Intelligence artificielle', 21, 1, 5, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:55:55', '2025-06-06 18:55:55'),
(834, 'MI353', 'Gouvernance et Urbanisation des SI', NULL, 'Intelligence artificielle', 21, 1, 5, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:55:55', '2025-06-06 18:55:55'),
(835, 'MI352', 'DevOps', NULL, 'Développement logiciel', 21, 1, 5, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:55:55', '2025-06-06 18:55:55'),
(836, 'MI355', 'Innovation Engineering & Digitalisation', NULL, 'Intelligence artificielle', 21, 1, 5, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:55:55', '2025-06-06 18:55:55'),
(837, 'MI354', 'Web Marketing et CRM', NULL, 'Informatique Appliquée', 21, 1, 5, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:55:55', '2025-06-06 18:55:55'),
(838, 'MI356', 'Business English', NULL, 'Langues étrangeres', 21, 1, 5, 14, 20, 0, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:55:55', '2025-06-07 16:12:37'),
(996, 'ID111', 'Analyse numérique matricielle', NULL, 'Analyse numérique', 22, 1, 1, 0, 0, 0, 0, '2024-2025', NULL, 'disponible', '2025-06-06 18:58:50', '2025-06-06 18:58:50'),
(997, 'ID112', 'statistique inférentielle', NULL, 'Probabilités et statistiques', 22, 1, 1, 0, 0, 0, 0, '2024-2025', NULL, 'disponible', '2025-06-06 18:58:50', '2025-06-06 18:58:50'),
(998, 'ID129', 'Théorie des langages et compilation', NULL, 'Informatique théorique', 22, 1, 1, 0, 0, 0, 0, '2024-2025', NULL, 'disponible', '2025-06-06 18:58:50', '2025-06-06 18:58:50'),
(999, 'ID113', 'Systèmes d\'Information et Bases de données', NULL, 'Développement logiciel', 22, 1, 1, 0, 0, 0, 0, '2024-2025', NULL, 'disponible', '2025-06-06 18:58:50', '2025-06-06 18:58:50'),
(1000, 'ID114', 'Relationnelles', NULL, 'Développement logiciel', 22, 1, 1, 0, 0, 0, 0, '2024-2025', NULL, 'disponible', '2025-06-06 18:58:50', '2025-06-06 18:58:50'),
(1001, 'ID115', 'Architectures des ordinateurs et systèmes d\'exploitation', NULL, 'Développement logiciel', 22, 1, 1, 0, 0, 0, 0, '2024-2025', NULL, 'disponible', '2025-06-06 18:58:50', '2025-06-06 18:58:50'),
(1002, 'ID116', 'Structure de données et algorithmique avancée', NULL, 'Informatique théorique', 22, 1, 1, 0, 0, 0, 0, '2024-2025', NULL, 'disponible', '2025-06-06 18:58:50', '2025-06-06 18:58:50'),
(1003, 'ID117', 'Anglais', NULL, 'Langues étrangeres', 22, 1, 1, 0, 0, 0, 0, '2024-2025', NULL, 'disponible', '2025-06-06 18:58:50', '2025-06-07 15:46:24'),
(1004, 'ID107', 'Français', NULL, 'Langues étrangeres', 22, 1, 1, 0, 0, 0, 0, '2024-2025', NULL, 'disponible', '2025-06-06 18:58:50', '2025-06-07 15:46:24'),
(1005, 'ID196', 'Programmation Python Bases du Web', NULL, 'Développement logiciel', 22, 1, 2, 0, 0, 0, 0, '2024-2025', NULL, 'disponible', '2025-06-06 18:58:50', '2025-06-06 18:58:50'),
(1006, 'ID177', 'Data mining', NULL, 'Intelligence artificielle', 22, 1, 2, 0, 0, 0, 0, '2024-2025', NULL, 'disponible', '2025-06-06 18:58:50', '2025-06-06 18:58:50'),
(1007, 'ID125', 'Statistique en grande dimension', NULL, 'Probabilités et statistiques', 22, 1, 2, 0, 0, 0, 0, '2024-2025', NULL, 'disponible', '2025-06-06 18:58:50', '2025-06-06 18:58:50'),
(1008, 'ID999', 'Programmation orientée objet java', NULL, 'Développement logiciel', 22, 1, 2, 0, 0, 0, 0, '2024-2025', NULL, 'disponible', '2025-06-06 18:58:50', '2025-06-06 18:58:50'),
(1009, 'ID122', 'Administration et optimisation des bases de données', NULL, 'Développement logiciel', 22, 1, 2, 0, 0, 0, 0, '2024-2025', NULL, 'disponible', '2025-06-06 18:58:50', '2025-06-06 18:58:50'),
(1010, 'ID123', 'Communication Professionnelle II: Anglais', NULL, 'Langues étrangeres', 22, 1, 2, 0, 0, 0, 0, '2024-2025', NULL, 'disponible', '2025-06-06 18:58:50', '2025-06-07 15:46:24'),
(1011, 'ID133', 'Communication Professionnelle II: Espagnol', NULL, 'Langues étrangeres', 22, 1, 2, 0, 0, 0, 0, '2024-2025', NULL, 'disponible', '2025-06-06 18:58:50', '2025-06-07 15:46:24'),
(1012, 'ID124', 'Entreprenariat -I-', NULL, 'Informatique Appliquée', 22, 1, 2, 0, 0, 0, 0, '2024-2025', NULL, 'disponible', '2025-06-06 18:58:50', '2025-06-06 18:58:50'),
(1013, 'ID313', 'Inteligence Artificielle I: Maching Learning', NULL, 'Intelligence artificielle', 22, 1, 3, 24, 10, 24, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:58:50', '2025-06-06 18:58:50'),
(1014, 'ID319', 'Modélisation stochastique', NULL, 'Probabilités et statistiques', 22, 1, 3, 13, 10, 7, 3, '2024-2025', NULL, 'disponible', '2025-06-06 18:58:50', '2025-06-06 18:58:50'),
(1015, 'ID315', 'Technique Mathématiques d\'Optimisation', NULL, 'Mathématiques appliquées', 22, 1, 3, 13, 10, 7, 3, '2024-2025', NULL, 'disponible', '2025-06-06 18:58:50', '2025-06-06 18:58:50'),
(1016, 'ID303', 'Architecture Logicielle et UML', NULL, 'Développement logiciel', 22, 1, 3, 24, 10, 24, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:58:50', '2025-06-06 18:58:50'),
(1017, 'ID300', 'Fondements du Big Data', NULL, 'Intelligence artificielle', 22, 1, 3, 24, 10, 24, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:58:50', '2025-06-06 18:58:50'),
(1018, 'ID000', 'Français', NULL, 'Langues étrangeres', 22, 1, 3, 12, 10, 0, 2, '2024-2025', NULL, 'disponible', '2025-06-06 18:58:50', '2025-06-07 15:46:24'),
(1019, 'ID014', 'Anglais', NULL, 'Langues étrangeres', 22, 1, 3, 12, 10, 0, 2, '2024-2025', NULL, 'disponible', '2025-06-06 18:58:50', '2025-06-07 15:46:24'),
(1020, 'ID318', 'SoftSkills', NULL, 'Informatique Appliquée', 22, 1, 3, 10, 10, 0, 2, '2024-2025', NULL, 'disponible', '2025-06-06 18:58:50', '2025-06-06 18:58:50'),
(1021, 'ID711', 'Bases de données Avancées', NULL, 'Développement logiciel', 22, 1, 3, 24, 10, 24, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:58:50', '2025-06-06 18:58:50'),
(1022, 'ID333', 'Big Data Avancée', NULL, 'Intelligence artificielle', 22, 1, 4, 24, 10, 24, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:58:50', '2025-06-06 18:58:50'),
(1023, 'ID310', 'Inelligence artificielle-II- Deep Learning', NULL, 'Intelligence artificielle', 22, 1, 4, 24, 8, 32, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:58:50', '2025-06-06 18:58:50'),
(1024, 'ID444', 'Data Werhaus et Data Lake', NULL, 'Intelligence artificielle', 22, 1, 4, 21, 0, 21, 4, '2024-2025', NULL, 'disponible', '2025-06-06 18:58:50', '2025-06-06 18:58:50'),
(1025, 'ID222', 'Applicataions Web avancées avec Java et spring', NULL, 'Développement logiciel', 22, 1, 4, 21, 16, 27, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:58:50', '2025-06-06 18:58:50'),
(1026, 'ID666', 'TAL', NULL, 'Intelligence artificielle', 22, 1, 4, 26, 4, 34, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:58:50', '2025-06-06 18:58:50'),
(1027, 'ID883', 'Entreprenariat-II', NULL, 'Informatique Appliquée', 22, 1, 4, 27, 15, 22, 6, '2024-2025', NULL, 'disponible', '2025-06-06 18:58:50', '2025-06-06 18:58:50'),
(1035, 'TD122', 'Programmation Orientée Objet Java', NULL, 'informatique', 20, 1, 2, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 19:03:34', '2025-06-07 16:37:55'),
(1036, 'TD127', 'Programmation Python / Programmation fonctionnelle', NULL, 'informatique', 21, 1, 2, 36, 0, 28, 6, '2024-2025', NULL, 'disponible', '2025-06-06 19:03:34', '2025-06-07 17:03:58'),
(1037, 'TD121', 'Développement Web', NULL, 'Développement logiciel', 21, 1, 2, 24, 0, 40, 6, '2024-2025', NULL, 'disponible', '2025-06-06 19:03:34', '2025-06-06 19:03:34'),
(1038, 'TD125', 'Gestion de projets informatiques', NULL, 'Développement logiciel', 21, 1, 2, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 19:03:34', '2025-06-06 19:03:34'),
(1039, 'TD123', 'Industrie de numérique', NULL, 'Intelligence artificielle', 21, 1, 2, 24, 20, 20, 6, '2024-2025', NULL, 'disponible', '2025-06-06 19:03:34', '2025-06-06 19:03:34'),
(1040, 'TD124', 'Langues Etrangéres (Anglais /Français)', NULL, 'Langues étrangeres', 21, 1, 2, 44, 12, 6, 6, '2024-2025', NULL, 'disponible', '2025-06-06 19:03:34', '2025-06-07 16:01:44'),
(1042, 'TD241', 'Cyber Security', NULL, 'Réseaux et sécurité', 21, 1, 4, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 19:06:46', '2025-06-06 19:06:46'),
(1043, 'TD246', 'Entreprenariat', NULL, 'Management et Entrepreunariat', 20, 1, 1, 24, 20, 10, 6, '2024-2025', NULL, 'disponible', '2025-06-06 19:06:46', '2025-06-07 16:21:13'),
(1044, 'TD351', 'La veille Stratégique, Scientifique et Technologique', NULL, 'Intelligence artificielle', 21, 1, 5, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 19:06:46', '2025-06-06 19:06:46'),
(1045, 'TD353', 'Gouvernance et Urbanisation des SI', NULL, 'Intelligence artificielle', 21, 1, 5, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 19:06:46', '2025-06-06 19:06:46'),
(1046, 'TD352', 'DevOps', NULL, 'Développement logiciel', 21, 1, 5, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 19:06:46', '2025-06-06 19:06:46'),
(1047, 'TD355', 'Innovation Engineering & Digitalisation', NULL, 'Intelligence artificielle', 21, 1, 5, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 19:06:46', '2025-06-06 19:06:46'),
(1048, 'TD354', 'Web Marketing et CRM', NULL, 'Informatique Appliquée', 21, 1, 5, 24, 10, 30, 6, '2024-2025', NULL, 'disponible', '2025-06-06 19:06:46', '2025-06-06 19:06:46'),
(1049, 'TD356', 'Business English', NULL, 'Langues étrangeres', 21, 1, 5, 14, 20, 0, 6, '2024-2025', NULL, 'disponible', '2025-06-06 19:06:46', '2025-06-07 16:12:37'),
(1050, 'GC111', 'Mécanique des solides', NULL, 'Mécanique', 24, 3, 1, 30, 20, 10, 6, '2024-2025', NULL, 'disponible', '2025-06-06 19:13:37', '2025-06-06 19:13:37'),
(1051, 'GC112', 'Dessin technique', NULL, 'Génie Civil', 24, 3, 1, 20, 10, 20, 5, '2024-2025', NULL, 'disponible', '2025-06-06 19:13:37', '2025-06-06 19:13:37'),
(1052, 'GC113', 'Matériaux de construction', NULL, 'Génie Civil', 24, 3, 1, 25, 15, 10, 5, '2024-2025', NULL, 'disponible', '2025-06-06 19:13:37', '2025-06-06 19:13:37'),
(1053, 'GC114', 'Mathématiques pour l\'ingénieur', NULL, 'Mathématiques appliquées', 24, 3, 1, 26, 16, 0, 6, '2024-2025', NULL, 'disponible', '2025-06-06 19:13:37', '2025-06-06 19:13:37'),
(1054, 'GC115', 'Langues étrangères - Anglais', NULL, 'Langues étrangeres', 24, 3, 1, 20, 10, 0, 3, '2024-2025', NULL, 'disponible', '2025-06-06 19:13:37', '2025-06-07 16:01:44'),
(1055, 'GC121', 'Résistance des matériaux', NULL, 'Génie Civil', 24, 3, 2, 28, 20, 12, 6, '2024-2025', NULL, 'disponible', '2025-06-06 19:13:37', '2025-06-06 19:13:37'),
(1056, 'GC122', 'Topographie', NULL, 'Génie Civil', 24, 3, 2, 20, 10, 20, 5, '2024-2025', NULL, 'disponible', '2025-06-06 19:13:37', '2025-06-06 19:13:37'),
(1057, 'GC123', 'Mécanique des fluides', NULL, 'Mécanique', 24, 3, 2, 25, 15, 10, 5, '2024-2025', NULL, 'disponible', '2025-06-06 19:13:37', '2025-06-06 19:13:37'),
(1058, 'GC124', 'Géologie appliquée', NULL, 'Génie Civil', 24, 3, 2, 22, 12, 6, 4, '2024-2025', NULL, 'disponible', '2025-06-06 19:13:37', '2025-06-06 19:13:37'),
(1059, 'GC125', 'Projet de construction', NULL, 'Génie Civil', 24, 3, 2, 15, 10, 25, 6, '2024-2025', NULL, 'disponible', '2025-06-06 19:13:37', '2025-06-06 19:13:37'),
(1060, 'GC211', 'Béton armé', NULL, 'Génie Civil', 24, 3, 3, 30, 20, 10, 6, '2024-2025', NULL, 'disponible', '2025-06-06 19:13:37', '2025-06-06 19:13:37'),
(1061, 'GC212', 'Mécanique des sols', NULL, 'Génie Civil', 24, 3, 3, 28, 18, 14, 6, '2024-2025', NULL, 'disponible', '2025-06-06 19:13:37', '2025-06-06 19:13:37'),
(1062, 'GC213', 'Structures métalliques', NULL, 'Génie Civil', 24, 3, 3, 25, 15, 10, 5, '2024-2025', NULL, 'disponible', '2025-06-06 19:13:37', '2025-06-06 19:13:37'),
(1063, 'GC214', 'Hydraulique', NULL, 'Génie Civil', 24, 3, 3, 22, 12, 16, 5, '2024-2025', NULL, 'disponible', '2025-06-06 19:13:37', '2025-06-06 19:13:37'),
(1064, 'GC215', 'Environnement et construction durable', NULL, 'Génie de l\'Eau et Environnement', 24, 3, 3, 20, 10, 10, 4, '2024-2025', NULL, 'disponible', '2025-06-06 19:13:37', '2025-06-06 19:13:37'),
(1065, 'GC311', 'Routes et ouvrages d\'art', NULL, 'Génie Civil', 24, 3, 4, 30, 20, 10, 6, '2024-2025', NULL, 'disponible', '2025-06-06 19:13:37', '2025-06-06 19:13:37'),
(1066, 'GC312', 'Géotechnique', NULL, 'Génie Civil', 24, 3, 4, 28, 18, 14, 6, '2024-2025', NULL, 'disponible', '2025-06-06 19:13:37', '2025-06-06 19:13:37'),
(1067, 'GC313', 'Construction parasismique', NULL, 'Génie Civil', 24, 3, 4, 25, 15, 10, 5, '2024-2025', NULL, 'disponible', '2025-06-06 19:13:37', '2025-06-06 19:13:37'),
(1068, 'GC314', 'Management de projet', NULL, 'Génie Civil', 24, 3, 4, 22, 12, 16, 5, '2024-2025', NULL, 'disponible', '2025-06-06 19:13:37', '2025-06-06 19:13:37'),
(1069, 'GC315', 'Droit et législation', NULL, 'Génie Civil', 24, 3, 4, 20, 10, 10, 4, '2024-2025', NULL, 'disponible', '2025-06-06 19:13:37', '2025-06-06 19:13:37'),
(1070, 'GEE111', 'Hydrologie', NULL, 'Génie de l\'Eau et Environnement', 25, 3, 1, 28, 16, 16, 6, '2024-2025', NULL, 'disponible', '2025-06-06 19:18:17', '2025-06-06 19:18:17'),
(1071, 'GEE112', 'Chimie de l\'eau', NULL, 'Génie de l\'Eau et Environnement', 25, 3, 1, 25, 15, 20, 6, '2024-2025', NULL, 'disponible', '2025-06-06 19:18:17', '2025-06-06 19:18:17'),
(1072, 'GEE113', 'Écologie générale', NULL, 'Génie de l\'Eau et Environnement', 25, 3, 1, 22, 12, 6, 4, '2024-2025', NULL, 'disponible', '2025-06-06 19:18:17', '2025-06-06 19:18:17'),
(1073, 'GEE114', 'Mathématiques appliquées', NULL, 'Mathématiques appliquées', 25, 3, 1, 26, 16, 8, 5, '2024-2025', NULL, 'disponible', '2025-06-06 19:18:17', '2025-06-06 19:18:17'),
(1074, 'GEE115', 'Informatique pour l\'ingénieur', NULL, 'Informatique Appliquée', 25, 3, 1, 20, 10, 20, 5, '2024-2025', NULL, 'disponible', '2025-06-06 19:18:17', '2025-06-06 19:18:17'),
(1075, 'GEE121', 'Hydraulique urbaine', NULL, 'Génie de l\'Eau et Environnement', 25, 3, 2, 28, 16, 16, 6, '2024-2025', NULL, 'disponible', '2025-06-06 19:18:17', '2025-06-06 19:18:17'),
(1076, 'GEE122', 'Traitement des eaux', NULL, 'Génie de l\'Eau et Environnement', 25, 3, 2, 25, 15, 20, 6, '2024-2025', NULL, 'disponible', '2025-06-06 19:18:17', '2025-06-06 19:18:17'),
(1077, 'GEE123', 'Qualité des eaux', NULL, 'Génie de l\'Eau et Environnement', 25, 3, 2, 22, 12, 16, 5, '2024-2025', NULL, 'disponible', '2025-06-06 19:18:17', '2025-06-06 19:18:17'),
(1078, 'GEE124', 'Géologie et hydrogéologie', NULL, 'Génie de l\'Eau et Environnement', 25, 3, 2, 24, 12, 14, 5, '2024-2025', NULL, 'disponible', '2025-06-06 19:18:17', '2025-06-06 19:18:17'),
(1079, 'GEE125', 'Droit de l\'environnement', NULL, 'Génie de l\'Eau et Environnement', 25, 3, 2, 20, 10, 10, 4, '2024-2025', NULL, 'disponible', '2025-06-06 19:18:17', '2025-06-06 19:18:17'),
(1080, 'GEE211', 'Réseaux d\'assainissement', NULL, 'Génie de l\'Eau et Environnement', 25, 3, 3, 30, 15, 15, 6, '2024-2025', NULL, 'disponible', '2025-06-06 19:18:17', '2025-06-06 19:18:17'),
(1081, 'GEE212', 'Gestion des ressources en eau', NULL, 'Génie de l\'Eau et Environnement', 25, 3, 3, 28, 14, 18, 6, '2024-2025', NULL, 'disponible', '2025-06-06 19:18:17', '2025-06-06 19:18:17'),
(1082, 'GEE213', 'Modélisation hydrologique', NULL, 'Génie de l\'Eau et Environnement', 25, 3, 3, 25, 12, 23, 6, '2024-2025', NULL, 'disponible', '2025-06-06 19:18:17', '2025-06-06 19:18:17'),
(1083, 'GEE214', 'Énergie hydraulique', NULL, 'Génie Energétique', 25, 3, 3, 22, 12, 16, 5, '2024-2025', NULL, 'disponible', '2025-06-06 19:18:17', '2025-06-06 19:18:17'),
(1084, 'GEE215', 'Impact environnemental', NULL, 'Génie de l\'Eau et Environnement', 25, 3, 3, 20, 10, 20, 5, '2024-2025', NULL, 'disponible', '2025-06-06 19:18:17', '2025-06-06 19:18:17'),
(1085, 'GEE311', 'Traitement des eaux usées industrielles', NULL, 'Génie de l\'Eau et Environnement', 25, 3, 4, 30, 15, 15, 6, '2024-2025', NULL, 'disponible', '2025-06-06 19:18:17', '2025-06-06 19:18:17'),
(1086, 'GEE312', 'Gestion des déchets', NULL, 'Génie de l\'Eau et Environnement', 25, 3, 4, 28, 14, 18, 6, '2024-2025', NULL, 'disponible', '2025-06-06 19:18:17', '2025-06-06 19:18:17'),
(1087, 'GEE313', 'Économie de l\'eau', NULL, 'Génie de l\'Eau et Environnement', 25, 3, 4, 25, 12, 13, 5, '2024-2025', NULL, 'disponible', '2025-06-06 19:18:17', '2025-06-06 19:18:17'),
(1088, 'GEE314', 'Télédétection en hydrologie', NULL, 'Génie de l\'Eau et Environnement', 25, 3, 4, 22, 12, 16, 5, '2024-2025', NULL, 'disponible', '2025-06-06 19:18:17', '2025-06-06 19:18:17'),
(1089, 'GEE315', 'Projet intégré', NULL, 'Génie de l\'Eau et Environnement', 25, 3, 4, 15, 10, 35, 8, '2024-2025', NULL, 'disponible', '2025-06-06 19:18:17', '2025-06-06 19:18:17'),
(1230, 'EE129', 'PHISIQUE', '43', 'Mécanique quantique', 25, 3, 1, 23, 23, 23, 1, '2023-2024', NULL, 'disponible', '2025-06-06 19:50:26', '2025-06-06 19:50:26'),
(1231, 'M1116_1', 'culture and art skills', 'vise a etudier culture ', 'culture générale', 22, 1, 1, 6, 4, 2, 0, '2024-2025', NULL, 'disponible', '2025-06-07 15:01:07', '2025-06-07 15:01:07'),
(1234, 'M1116.2', 'culture and art skills', 'vise a etudier culture ', 'culture générale', 21, 1, 1, 6, 5, 2, 0, '2024-2025', NULL, 'disponible', '2025-06-07 15:05:32', '2025-06-07 15:05:32'),
(1240, 'M1116.3', 'culture and art skills', 'vise a etudier culture ', 'culture générale', 25, 1, 1, 6, 5, 2, 3, '2024-2025', NULL, 'disponible', '2025-06-07 15:09:04', '2025-06-07 15:13:43'),
(1241, 'TA123', 'Génie logiciel', NULL, 'informatique', 20, 1, 1, 20, 15, 15, 5, '2024-2025', NULL, 'disponible', '2025-06-07 16:53:11', '2025-06-07 16:53:11'),
(1242, 'TA123_1', 'Génie logiciel', '', 'informatique', 21, 1, 2, 10, 20, 15, 8, '2024-2025', NULL, 'disponible', '2025-06-07 16:53:11', '2025-06-08 18:35:00'),
(1244, 'DA1234.2', 'Analyse de donnee1', NULL, 'informatique', 22, 1, 2, 20, 26, 15, 4, '2024-2025', NULL, 'disponible', '2025-06-07 17:15:29', '2025-06-07 17:15:29'),
(1245, 'CV123', 'Art et Culture', 'Art et Culture', 'culture générale', 23, 2, 1, 20, 0, 0, 6, '2024-2025', 55, 'disponible', '2025-06-08 14:22:09', '2025-06-08 14:22:09'),
(1246, 'ZZ234', 'base de donnee', 'base de donnee', 'Intelligence artificielle', 20, 1, 1, 20, 15, 15, 6, '2023-2024', 88, 'disponible', '2025-06-09 20:21:07', '2025-06-09 20:21:07'),
(1247, 'IN123', 'base de donnee1', 'base de donne', 'Intelligence artificielle', 20, 1, 1, 20, 12, 12, 6, '2024-2025', 87, 'disponible', '2025-06-09 20:26:49', '2025-06-09 20:26:49');

-- --------------------------------------------------------

--
-- Structure de la table `unites_enseignement_vacantes`
--

CREATE TABLE `unites_enseignement_vacantes` (
  `id` int(11) NOT NULL,
  `id_unite_enseignement` int(11) NOT NULL,
  `annee_universitaire` varchar(10) NOT NULL,
  `semestre` int(11) NOT NULL,
  `type_cours` enum('CM','TD','TP') NOT NULL,
  `volume_horaire` float NOT NULL,
  `date_declaration` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_departement` int(11) DEFAULT NULL,
  `id_filiere` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `unites_enseignement_vacantes`
--

INSERT INTO `unites_enseignement_vacantes` (`id`, `id_unite_enseignement`, `annee_universitaire`, `semestre`, `type_cours`, `volume_horaire`, `date_declaration`, `id_departement`, `id_filiere`) VALUES
(33, 6, '2024-2025', 1, 'TD', 4, '2025-06-07 21:35:12', 1, 20),
(34, 1244, '2024-2025', 2, 'TP', 15, '2025-06-07 21:35:29', 1, 22),
(35, 1244, '2024-2025', 2, 'TD', 26, '2025-06-07 21:35:37', 1, 22),
(38, 11, '2025-2026', 1, 'TP', 20, '2025-06-07 21:38:09', 1, 21),
(39, 1241, '2024-2025', 1, 'CM', 20, '2025-06-07 21:40:59', 1, 20),
(41, 6, '2024-2025', 1, 'CM', 14, '2025-06-09 20:38:19', 1, 20);

-- --------------------------------------------------------

--
-- Structure de la table `unites_vacantes_vacataires`
--

CREATE TABLE `unites_vacantes_vacataires` (
  `id` int(11) NOT NULL,
  `id_unite_enseignement` int(11) NOT NULL,
  `annee_universitaire` varchar(10) NOT NULL,
  `semestre` int(11) NOT NULL,
  `type_cours` enum('CM','TD','TP') NOT NULL,
  `volume_horaire` float NOT NULL,
  `date_declaration` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_departement` int(11) DEFAULT NULL,
  `id_filiere` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `unites_vacantes_vacataires`
--

INSERT INTO `unites_vacantes_vacataires` (`id`, `id_unite_enseignement`, `annee_universitaire`, `semestre`, `type_cours`, `volume_horaire`, `date_declaration`, `id_departement`, `id_filiere`) VALUES
(9, 6, '2024-2025', 1, 'TD', 4, '2025-06-07 21:35:12', 1, 20),
(10, 1244, '2024-2025', 2, 'TP', 15, '2025-06-07 21:35:29', 1, 22),
(14, 11, '2025-2026', 1, 'TP', 20, '2025-06-07 21:38:09', 1, 21),
(15, 1241, '2024-2025', 1, 'CM', 20, '2025-06-07 21:40:59', 1, 20);

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id` int(11) NOT NULL,
  `nom_utilisateur` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `prenom` varchar(50) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `role` enum('admin','chef_departement','coordonnateur','enseignant','vacataire') NOT NULL,
  `specialite` varchar(100) DEFAULT NULL,
  `id_departement` int(11) DEFAULT NULL,
  `id_filiere` int(11) DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `derniere_connexion` timestamp NULL DEFAULT NULL,
  `actif` tinyint(1) DEFAULT 1,
  `date_changement_mdp` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom_utilisateur`, `email`, `mot_de_passe`, `prenom`, `nom`, `role`, `specialite`, `id_departement`, `id_filiere`, `date_creation`, `date_modification`, `derniere_connexion`, `actif`, `date_changement_mdp`) VALUES
(1, 'jean', 'jean@example.com', '$2y$10$FUVvvOaG9l0/YotMn9N/U.KHbzTed6MvbKUKOwNZYh3RWuciAjID.', 'Jean', 'Durand', 'enseignant', 'informatique', 1, NULL, '2025-05-20 15:08:29', '2025-06-09 20:48:08', '2025-06-09 20:48:08', 1, NULL),
(2, 'admin', 'admin@admin.com', '$2y$10$nquYCPW1QDEZK1UilydOBuITQsxivDsfgqpYTEoBNcI41jlKqAYPS', '', '', 'admin', NULL, NULL, NULL, '2025-04-16 19:19:18', '2025-06-09 20:55:54', '2025-06-09 20:55:54', 1, NULL),
(3, 'pierre', 'pierre@example.com', '$2y$10$xHyvo6oZ9ei5OzHq5p.mc.RPAN9CnKEjXpe5yyFrRZAbmPdC0PwaK', 'Pierre', 'Martin', 'enseignant', 'Réseaux et sécurité', 1, NULL, '2025-05-20 15:08:29', '2025-06-07 16:36:46', NULL, 1, NULL),
(55, 'BOUIBAUAN1', 'ibtissam.bouibauan@etu.uae.ac.ma', '$2y$10$hzGQYkwyBzAh5v7ALbfHR.CQsK4NAMj6UYIgYuYiAQCH/VGTw9x0i', 'IBTISSAM', 'bouibauan', 'vacataire', 'Informatique Appliquée', 3, NULL, '2025-05-03 16:26:31', '2025-05-03 16:26:31', NULL, 1, NULL),
(67, 'AHMED', 'racjid.ahmed@etu.uae.ac.ma', '$2y$10$4TgUrU/XS0JlGDpVZhVYv.IfwNdpcBDdFCjWomhXS.y.N/j1aUtVy', 'racjid', 'ahmed', 'vacataire', NULL, 2, NULL, '2025-05-19 14:07:53', '2025-06-11 13:24:20', '2025-06-11 13:24:20', 1, NULL),
(68, 'ALAMI', 'alam.alami@etu.uae.ac.ma', '$2y$10$RHx9Xp8kT56iozWsM7nMmuSXaMqGzg2FUQ3MR3ivKFaUrPOqPwFqG', 'ALAM', 'ALAMI', 'coordonnateur', 'Informatique Appliquée', NULL, NULL, '2025-05-19 14:15:47', '2025-06-07 16:30:07', '2025-06-06 18:57:05', 1, NULL),
(74, 'alamin', 'amin.lamin@etu.uae.ac.ma', '$2y$10$xNlav4xv0IYhpxqTyDuHoOK7W2UQbkbARcU6pC1X39ti1zgoH2BqW', 'Amin', 'LAMIN', 'enseignant', NULL, NULL, NULL, '2025-05-19 18:05:15', '2025-05-19 18:08:44', '2025-05-19 18:08:44', 1, NULL),
(75, 'RAARA', 'rari.raara@etu.uae.ac.ma', '$2y$10$cPcO72dqMoTHtq1Vbh0Whu3HNwzbBG.tv0UBj/AkR8H6l2IcmTd5S', 'RARI', 'RAARA', 'vacataire', 'Informatique Appliquée', 1, NULL, '2025-05-19 18:17:17', '2025-06-01 14:14:11', '2025-06-01 14:14:11', 1, NULL),
(76, 'SAFAA', 'safsof@2004', '$2y$10$BPttawCe1/TbfSi5uCJrVOBhvGJFrhhzHFmX06uWHignGz6X24os2', 'bif', 'safaa', 'chef_departement', 'Informatique', 1, NULL, '2025-05-20 17:23:24', '2025-06-09 20:31:21', '2025-06-09 20:31:21', 1, NULL),
(77, 'CHEF', 'chef.chef@etu.uae.ac.ma', '$2y$10$joUAGa4GxXo.yQ0wzuicIuhT5/g54scWFDANmy3Lhphx0AV7xL0ju', 'CHEF', '', 'chef_departement', NULL, 1, NULL, '2025-05-23 18:58:44', '2025-06-07 16:08:51', '2025-06-06 18:46:21', 1, NULL),
(78, 'AMINI', 'ayman.amini@etu.uae.ac.ma', '$2y$10$jLqnGyQ32IEM6/4yoYbeBurraUbjn2z7BPKYO7j.pJzFqgQ4V3jsS', 'AMINI', '', 'vacataire', NULL, NULL, NULL, '2025-06-01 13:05:34', '2025-06-01 14:10:08', '2025-06-01 14:10:08', 1, NULL),
(79, 'SALM', 'eer.salm@etu.uae.ac.ma', '$2y$10$N0eL6SO6EmN7vr/u31.vxeNvRmLvLo.dGMU4c5rpuMjzjCrQn/kxO', 'EER', 'Salm', 'vacataire', 'informatique', 2, NULL, '2025-06-05 13:23:50', '2025-06-05 13:23:50', NULL, 1, NULL),
(80, 'LAMIA', 'asmae.lamia@etu.uae.ac.ma', '$2y$10$eMNaHodAGDmMgDb.Nx7YQuovC1XaietoCsfCvI6F2w6P1J10OAh.O', 'ASMAE', 'LAMIA', 'vacataire', 'Informatique Appliquée', 1, NULL, '2025-06-05 15:28:49', '2025-06-05 15:28:49', NULL, 1, NULL),
(81, 'AMOURAK', 'rachida.amourak@etu.uae.ac.ma', '$2y$10$hxfC9lTSG0l9KOp55dXcqOI4j/c6dz/mbfvtREiUZLb2tBjRccU1G', 'Rachida', 'Amourak', 'coordonnateur', 'Informatique Appliquée', 1, 21, '2025-06-06 16:02:33', '2025-06-09 20:25:39', '2025-06-09 20:25:39', 1, NULL),
(82, 'BIFKIOUIN', 'rochdi.bifkiouin@etu.uae.ac.ma', '$2y$10$J4f3ll6B8Cle/QzLYeu8zu0TQwMqbczCg1qpeTz9XX/Rk2iMDhEOe', 'Rochdi', 'Bifkiouin', 'coordonnateur', NULL, 1, 20, '2025-06-06 16:12:36', '2025-06-11 13:24:37', '2025-06-11 13:24:37', 1, NULL),
(83, 'SALOUA', 'salim.saloua@etu.uae.ac.ma', '$2y$10$2MKCrlcyOUxrQRzN8wywAOxBDhQOewW/zOY3sQcvc4OHiC.Aexe7i', 'Salim', 'Saloua', 'coordonnateur', 'Réseaux et sécurité', 3, 23, '2025-06-06 18:34:13', '2025-06-09 19:21:44', '2025-06-09 19:21:44', 1, NULL),
(85, 'AMOURAM', 'slima.amouram@etu.uae.ac.ma', '$2y$10$87VpuLMRmgCIKEkAAZIhquODg1zDF1x6.3TB33Tuh3iRRQBNcwLwO', 'Slima', 'Amouram', 'coordonnateur', 'Développement logiciel', 2, 25, '2025-06-06 18:47:15', '2025-06-08 13:03:01', '2025-06-08 13:03:01', 1, NULL),
(86, 'MOHAMED', 'salim.mohamed@etu.uae.ac.ma', '$2y$10$vIx5owJvpwVig16kupoLBOkdYunmiHbPWnLKEth9MeW4IqtnEBOKy', 'salim', 'mohamed', 'vacataire', 'informatique', 1, NULL, '2025-06-06 20:10:01', '2025-06-06 20:10:01', NULL, 1, NULL),
(87, 'SMLALI', 'hanane.smlali@etu.uae.ac.ma', '$2y$10$NOyURSNZnpBA4YyOkiRsE.60Rjf1Yj9BwxfkagWCOSsp2eHT5NPbW', 'Hanane', 'Smlali', 'enseignant', 'Intelligence artificielle', 1, NULL, '2025-06-07 15:20:46', '2025-06-07 21:06:35', '2025-06-07 21:06:35', 1, NULL),
(88, 'SOFIA', 'sofi.sofia@etu.uae.ac.ma', '$2y$10$kkRkIVkTHgC.UsYOCQ.pfe6bHbxahYsLrgxAJRtFo25CELPZXw/Sy', 'Sofi', 'Sofia', 'enseignant', 'informatique', 1, NULL, '2025-06-07 15:36:16', '2025-06-07 20:44:24', '2025-06-07 20:44:24', 1, NULL),
(89, 'SLAOUI', 'hassan.slaoui@etu.uae.ac.ma', '$2y$10$X7ko9Ov.DvC6Vx118jETGOUsZ68VBXEN.6h7UXztiALuPaS776pH6', 'Hassan', 'Slaoui', 'enseignant', 'Développement logiciel', 1, NULL, '2025-06-07 16:20:29', '2025-06-07 16:23:09', NULL, 1, NULL),
(90, 'SOUANI', 'adam.souani@etu.uae.ac.ma', '$2y$10$xFHG9AD4RySaz3uwV9edOeQJPu51oCeaKModx2Gxac37kIIXs7RcO', 'Adam', 'Souani', 'enseignant', 'Mathématiques appliquées', 1, NULL, '2025-06-07 16:25:31', '2025-06-07 16:26:20', NULL, 1, NULL),
(91, 'KARAM', 'mohamed.karam@etu.uae.ac.ma', '$2y$10$3Sw7URa3SvF5ANUC0q0bAOZ4.hvZIseCjahB1nbE6.nm4PBp8xksi', 'Mohamed', 'Karam', 'coordonnateur', NULL, 1, 20, '2025-06-08 13:04:58', '2025-06-08 13:12:48', '2025-06-08 13:12:48', 1, NULL),
(92, 'SALMI', 'mohamed.salmi@etu.uae.ac.ma', '$2y$10$oNxBcBElHGcCog1YHGf8b.ZejlG/hMYFZB5If5pn4PezCBwMHN/9C', 'Mohamed', 'Salmi', 'coordonnateur', NULL, 1, 21, '2025-06-08 13:06:15', '2025-06-08 13:11:38', '2025-06-08 13:11:38', 1, NULL),
(93, 'SALOY', 'mohamed.saloy@etu.uae.ac.ma', '$2y$10$1QpbwY9SghlhzbivyEGNHu.JDtHQ/8ZLVQmQxUUbVYx5.qOxKDyVG', 'Mohamed', 'Saloy', 'coordonnateur', NULL, 1, 22, '2025-06-08 13:06:49', '2025-06-08 13:13:04', '2025-06-08 13:13:04', 1, NULL),
(94, 'TORYA', 'mohamed.torya@etu.uae.ac.ma', '$2y$10$zT4qIswGgc2R8n4aADogyemZqgj.iwi6pRQHDToBED49ciTzCIKYW', 'Mohamed', 'Torya', 'coordonnateur', NULL, 2, 23, '2025-06-08 13:07:20', '2025-06-08 13:20:20', '2025-06-08 13:20:20', 1, NULL),
(95, 'KRIM', 'mohamed.krim@etu.uae.ac.ma', '$2y$10$ZFpOZG/ge6iDv8P3NRn9leVNSNwSvr3YS5W.CRNHKZaWsntps02QG', 'Mohamed', 'Krim', 'coordonnateur', NULL, 2, 24, '2025-06-08 13:07:46', '2025-06-08 13:15:32', '2025-06-08 13:15:32', 1, NULL),
(96, 'HASSNA', 'mohamed.hassna@etu.uae.ac.ma', '$2y$10$QL.ZB/xulmeCBnJNPHyXkeQXckOZvaeDwvo13IJXhTSfbir6HBIe.', 'Mohamed', 'Hassna', 'coordonnateur', NULL, 3, 25, '2025-06-08 13:08:10', '2025-06-09 18:17:16', NULL, 1, NULL),
(97, 'BOUIBAUAN', 'aya.bouibauan@etu.uae.ac.ma', '$2y$10$5GomdayXpawEqcrsFLL8/exxdYtb5Jeo4T3CRQbQ4KKes9v3vUVYi', 'Aya', 'Bouibauan', 'chef_departement', NULL, 3, NULL, '2025-06-09 18:16:32', '2025-06-09 18:16:59', NULL, 1, NULL),
(99, 'MOURABIT', 'ahlam.mourabit@etu.uae.ac.ma', '$2y$10$PgFLm5eAGDrN77P3Jj61kuyDHgm/9HtBz8TKmvXz8N9VL85dIVp8u', 'Ahlam', 'Mourabit', 'coordonnateur', NULL, 1, 22, '2025-06-09 18:34:18', '2025-06-09 18:34:44', NULL, 1, NULL),
(102, 'AMOURAK1', 'amourak.amourak@etu.uae.ac.ma', '$2y$10$hovb3BPrfPnSzBOMlHYj8unGvL0TfGqek7Z9w5s.CsX3pFSSQI2Qy', 'amourak', 'amourak', 'vacataire', NULL, 1, NULL, '2025-06-09 19:53:52', '2025-06-09 20:26:38', '2025-06-09 20:26:38', 1, NULL),
(104, 'SALMI1', 'rachida.salmi@etu.uae.ac.ma', '$2y$10$XEKrgJhKkcbyk4zmRJOuFuv7934a5Jw7JR9NQmAOapvaS35gAmBUO', 'rachida', 'salmi', 'vacataire', 'Management et Entrepreunariat', 1, NULL, '2025-06-09 21:04:05', '2025-06-09 21:04:05', NULL, 1, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur_permissions`
--

CREATE TABLE `utilisateur_permissions` (
  `id_utilisateur` int(11) NOT NULL,
  `nom_permission` varchar(100) NOT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur_specialites`
--

CREATE TABLE `utilisateur_specialites` (
  `id_utilisateur` int(11) NOT NULL,
  `id_specialite` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `voeux_professeurs`
--

CREATE TABLE `voeux_professeurs` (
  `id` int(11) NOT NULL,
  `id_utilisateur` int(11) DEFAULT NULL,
  `id_ue` int(11) DEFAULT NULL,
  `priorite` int(11) DEFAULT NULL,
  `commentaire` text DEFAULT NULL,
  `id_filiere` int(11) DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `annee_universitaire` varchar(9) NOT NULL DEFAULT '2023-2024',
  `type_ue` varchar(2) DEFAULT NULL,
  `statut` varchar(20) DEFAULT 'en_attente',
  `commentaire_chef` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `voeux_professeurs`
--

INSERT INTO `voeux_professeurs` (`id`, `id_utilisateur`, `id_ue`, `priorite`, `commentaire`, `id_filiere`, `date_creation`, `annee_universitaire`, `type_ue`, `statut`, `commentaire_chef`) VALUES
(94, 1, 1019, 1, 'Ce module me tient à cœur car il me permet d’initier les étudiants aux bases solides de l’informatique moderne.', 22, '2025-06-07 03:42:19', '2023-2024', 'CM', 'rejeté', ''),
(98, 88, 1036, 13, 'Je souhaite enseigner ce module car il correspond parfaitement à ma spécialité et à mon expérience professionnelle.', 21, '2025-06-07 20:47:13', '2023-2024', 'CM', 'en_attente', NULL),
(99, 88, 112, 15, 'Je souhaite enseigner ce module car il correspond parfaitement à ma spécialité et à mon expérience professionnelle.', 20, '2025-06-07 20:47:37', '2023-2024', 'CM', 'en_attente', NULL),
(100, 88, 75, 1, 'J’ai déjà assuré ce module plusieurs fois et je dispose des ressources pédagogiques nécessaires pour le mener à bien.', 20, '2025-06-07 20:52:57', '2023-2024', 'TD', 'en_attente', NULL),
(101, 87, 272, 1, 'Ce module me tient à cœur car il me permet d’initier les étudiants aux bases solides de l’informatique moderne.ca sera une chance', 21, '2025-06-07 20:57:57', '2023-2024', 'CM', 'en_attente', NULL),
(102, 87, 269, 4, 'Ce module représente une opportunité d’appliquer des approches pédagogiques innovantes.', 21, '2025-06-07 21:10:22', '2023-2024', 'CM', 'validé', 'Demande acceptée conformément aux besoins pédagogiques du semestre.'),
(103, 87, 828, 5, 'Ce module est essentiel pour la filière et je suis prêt à l’assurer avec rigueur.et je dispose des ressources pédagogiques ', 21, '2025-06-07 21:10:22', '2023-2024', 'CM', 'validé', 'Demande acceptée '),
(104, 1, 11, 34, 'DGGFHHFYYF', 21, '2025-06-09 19:06:38', '2023-2024', 'CM', 'en attente', ''),
(105, 1, 84, 1235, 'DGGHD', 20, '2025-06-09 20:51:47', '2023-2024', 'CM', 'en_attente', NULL);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `charges_horaires`
--
ALTER TABLE `charges_horaires`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_utilisateur` (`id_utilisateur`,`annee_universitaire`),
  ADD KEY `idx_charges_horaires_statut` (`statut`);

--
-- Index pour la table `departements`
--
ALTER TABLE `departements`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `emplois_temps`
--
ALTER TABLE `emplois_temps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_filiere` (`id_filiere`);

--
-- Index pour la table `etudiants`
--
ALTER TABLE `etudiants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_etudiant` (`numero_etudiant`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_etudiants_filiere` (`id_filiere`);

--
-- Index pour la table `fichiers_notes`
--
ALTER TABLE `fichiers_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_unite_enseignement` (`id_unite_enseignement`),
  ADD KEY `id_enseignant` (`id_enseignant`);

--
-- Index pour la table `filieres`
--
ALTER TABLE `filieres`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_filieres_specialite` (`id_specialite`),
  ADD KEY `idx_filieres_coordonnateur` (`id_coordonnateur`);

--
-- Index pour la table `groupes`
--
ALTER TABLE `groupes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `type` (`type`,`numero`,`id_unite_enseignement`,`annee_universitaire`,`semestre`),
  ADD KEY `idx_groupes_ue` (`id_unite_enseignement`);

--
-- Index pour la table `historique_affectations`
--
ALTER TABLE `historique_affectations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_unite_enseignement` (`id_unite_enseignement`),
  ADD KEY `id_utilisateur` (`id_utilisateur`,`annee_universitaire`),
  ADD KEY `id_departement` (`id_departement`,`annee_universitaire`),
  ADD KEY `id_filiere` (`id_filiere`,`annee_universitaire`),
  ADD KEY `idx_historique_affectations_annee` (`annee_universitaire`);

--
-- Index pour la table `historique_affectations_vacataire`
--
ALTER TABLE `historique_affectations_vacataire`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_vacataire` (`id_vacataire`),
  ADD KEY `id_unite_enseignement` (`id_unite_enseignement`),
  ADD KEY `id_coordonnateur` (`id_coordonnateur`);

--
-- Index pour la table `journal_decisions`
--
ALTER TABLE `journal_decisions`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `journal_import_export`
--
ALTER TABLE `journal_import_export`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_unite_enseignement` (`id_unite_enseignement`,`id_etudiant`,`type_session`),
  ADD KEY `id_enseignant` (`id_enseignant`),
  ADD KEY `idx_notes_ue` (`id_unite_enseignement`),
  ADD KEY `idx_notes_etudiant` (`id_etudiant`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_utilisateur` (`id_utilisateur`,`statut`);

--
-- Index pour la table `notifications_coordonnateur`
--
ALTER TABLE `notifications_coordonnateur`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`),
  ADD KEY `token` (`token`);

--
-- Index pour la table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nom` (`nom`);

--
-- Index pour la table `rapport_charge_departement`
--
ALTER TABLE `rapport_charge_departement`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_departement` (`id_departement`,`annee_universitaire`,`semestre`);

--
-- Index pour la table `reinitialisation_mot_de_passe`
--
ALTER TABLE `reinitialisation_mot_de_passe`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token_unique` (`token`),
  ADD KEY `idx_reinit_mdp_token` (`token`),
  ADD KEY `idx_reinit_mdp_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role`,`id_permission`),
  ADD KEY `id_permission` (`id_permission`);

--
-- Index pour la table `seances`
--
ALTER TABLE `seances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_groupe` (`id_groupe`),
  ADD KEY `id_enseignant` (`id_enseignant`),
  ADD KEY `idx_seances_ue` (`id_unite_enseignement`),
  ADD KEY `idx_seances_emploi_temps` (`id_emploi_temps`);

--
-- Index pour la table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `specialites`
--
ALTER TABLE `specialites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nom` (`nom`),
  ADD KEY `idx_specialites_departement` (`id_departement`);

--
-- Index pour la table `tentatives_connexion`
--
ALTER TABLE `tentatives_connexion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `unites_enseignement`
--
ALTER TABLE `unites_enseignement`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_ue_filiere` (`id_filiere`),
  ADD KEY `idx_ue_departement` (`id_departement`),
  ADD KEY `idx_ue_responsable` (`id_responsable`);

--
-- Index pour la table `unites_enseignement_vacantes`
--
ALTER TABLE `unites_enseignement_vacantes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_id_departement` (`id_departement`),
  ADD KEY `fk_uevacantes_filiere` (`id_filiere`),
  ADD KEY `id_unite_enseignement` (`id_unite_enseignement`,`annee_universitaire`,`semestre`,`type_cours`) USING BTREE;

--
-- Index pour la table `unites_vacantes_vacataires`
--
ALTER TABLE `unites_vacantes_vacataires`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_id_departement_vacataires` (`id_departement`),
  ADD KEY `fk_uevacataires_filiere` (`id_filiere`),
  ADD KEY `id_unite_enseignement` (`id_unite_enseignement`,`annee_universitaire`,`semestre`,`type_cours`) USING BTREE;

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nom_utilisateur` (`nom_utilisateur`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_utilisateurs_departement` (`id_departement`),
  ADD KEY `idx_utilisateurs_filiere` (`id_filiere`);

--
-- Index pour la table `utilisateur_permissions`
--
ALTER TABLE `utilisateur_permissions`
  ADD PRIMARY KEY (`id_utilisateur`,`nom_permission`);

--
-- Index pour la table `utilisateur_specialites`
--
ALTER TABLE `utilisateur_specialites`
  ADD PRIMARY KEY (`id_utilisateur`,`id_specialite`),
  ADD KEY `id_specialite` (`id_specialite`);

--
-- Index pour la table `voeux_professeurs`
--
ALTER TABLE `voeux_professeurs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_ue` (`id_ue`),
  ADD KEY `id_filiere` (`id_filiere`),
  ADD KEY `idx_voeux_professeurs_utilisateur` (`id_utilisateur`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `charges_horaires`
--
ALTER TABLE `charges_horaires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `departements`
--
ALTER TABLE `departements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT pour la table `emplois_temps`
--
ALTER TABLE `emplois_temps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT pour la table `etudiants`
--
ALTER TABLE `etudiants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT pour la table `fichiers_notes`
--
ALTER TABLE `fichiers_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT pour la table `filieres`
--
ALTER TABLE `filieres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT pour la table `groupes`
--
ALTER TABLE `groupes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=132;

--
-- AUTO_INCREMENT pour la table `historique_affectations`
--
ALTER TABLE `historique_affectations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=282;

--
-- AUTO_INCREMENT pour la table `historique_affectations_vacataire`
--
ALTER TABLE `historique_affectations_vacataire`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `journal_decisions`
--
ALTER TABLE `journal_decisions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT pour la table `journal_import_export`
--
ALTER TABLE `journal_import_export`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `notes`
--
ALTER TABLE `notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=190;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT pour la table `notifications_coordonnateur`
--
ALTER TABLE `notifications_coordonnateur`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT pour la table `rapport_charge_departement`
--
ALTER TABLE `rapport_charge_departement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `reinitialisation_mot_de_passe`
--
ALTER TABLE `reinitialisation_mot_de_passe`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `seances`
--
ALTER TABLE `seances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT pour la table `specialites`
--
ALTER TABLE `specialites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT pour la table `tentatives_connexion`
--
ALTER TABLE `tentatives_connexion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=352;

--
-- AUTO_INCREMENT pour la table `unites_enseignement`
--
ALTER TABLE `unites_enseignement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1249;

--
-- AUTO_INCREMENT pour la table `unites_enseignement_vacantes`
--
ALTER TABLE `unites_enseignement_vacantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT pour la table `unites_vacantes_vacataires`
--
ALTER TABLE `unites_vacantes_vacataires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT pour la table `voeux_professeurs`
--
ALTER TABLE `voeux_professeurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `charges_horaires`
--
ALTER TABLE `charges_horaires`
  ADD CONSTRAINT `charges_horaires_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `emplois_temps`
--
ALTER TABLE `emplois_temps`
  ADD CONSTRAINT `emplois_temps_ibfk_1` FOREIGN KEY (`id_filiere`) REFERENCES `filieres` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `etudiants`
--
ALTER TABLE `etudiants`
  ADD CONSTRAINT `etudiants_ibfk_1` FOREIGN KEY (`id_filiere`) REFERENCES `filieres` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `fichiers_notes`
--
ALTER TABLE `fichiers_notes`
  ADD CONSTRAINT `fichiers_notes_ibfk_1` FOREIGN KEY (`id_unite_enseignement`) REFERENCES `unites_enseignement` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fichiers_notes_ibfk_2` FOREIGN KEY (`id_enseignant`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `filieres`
--
ALTER TABLE `filieres`
  ADD CONSTRAINT `filieres_ibfk_1` FOREIGN KEY (`id_specialite`) REFERENCES `specialites` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_coordonnateur` FOREIGN KEY (`id_coordonnateur`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `groupes`
--
ALTER TABLE `groupes`
  ADD CONSTRAINT `groupes_ibfk_1` FOREIGN KEY (`id_unite_enseignement`) REFERENCES `unites_enseignement` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `historique_affectations_vacataire`
--
ALTER TABLE `historique_affectations_vacataire`
  ADD CONSTRAINT `historique_affectations_vacataire_ibfk_1` FOREIGN KEY (`id_vacataire`) REFERENCES `utilisateurs` (`id`),
  ADD CONSTRAINT `historique_affectations_vacataire_ibfk_2` FOREIGN KEY (`id_unite_enseignement`) REFERENCES `unites_enseignement` (`id`),
  ADD CONSTRAINT `historique_affectations_vacataire_ibfk_3` FOREIGN KEY (`id_coordonnateur`) REFERENCES `utilisateurs` (`id`);

--
-- Contraintes pour la table `journal_import_export`
--
ALTER TABLE `journal_import_export`
  ADD CONSTRAINT `journal_import_export_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `notes`
--
ALTER TABLE `notes`
  ADD CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`id_unite_enseignement`) REFERENCES `unites_enseignement` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notes_ibfk_2` FOREIGN KEY (`id_etudiant`) REFERENCES `etudiants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notes_ibfk_3` FOREIGN KEY (`id_enseignant`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `rapport_charge_departement`
--
ALTER TABLE `rapport_charge_departement`
  ADD CONSTRAINT `rapport_charge_departement_ibfk_1` FOREIGN KEY (`id_departement`) REFERENCES `departements` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `reinitialisation_mot_de_passe`
--
ALTER TABLE `reinitialisation_mot_de_passe`
  ADD CONSTRAINT `reinitialisation_mot_de_passe_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`id_permission`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `seances`
--
ALTER TABLE `seances`
  ADD CONSTRAINT `seances_ibfk_1` FOREIGN KEY (`id_unite_enseignement`) REFERENCES `unites_enseignement` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `seances_ibfk_2` FOREIGN KEY (`id_groupe`) REFERENCES `groupes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `seances_ibfk_3` FOREIGN KEY (`id_enseignant`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `seances_ibfk_4` FOREIGN KEY (`id_emploi_temps`) REFERENCES `emplois_temps` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `specialites`
--
ALTER TABLE `specialites`
  ADD CONSTRAINT `specialites_ibfk_1` FOREIGN KEY (`id_departement`) REFERENCES `departements` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `tentatives_connexion`
--
ALTER TABLE `tentatives_connexion`
  ADD CONSTRAINT `tentatives_connexion_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `unites_enseignement`
--
ALTER TABLE `unites_enseignement`
  ADD CONSTRAINT `unites_enseignement_ibfk_1` FOREIGN KEY (`id_filiere`) REFERENCES `filieres` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `unites_enseignement_ibfk_2` FOREIGN KEY (`id_departement`) REFERENCES `departements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `unites_enseignement_ibfk_3` FOREIGN KEY (`id_responsable`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `unites_enseignement_vacantes`
--
ALTER TABLE `unites_enseignement_vacantes`
  ADD CONSTRAINT `fk_id_departement` FOREIGN KEY (`id_departement`) REFERENCES `departements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_uevacantes_filiere` FOREIGN KEY (`id_filiere`) REFERENCES `filieres` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `unites_enseignement_vacantes_ibfk_1` FOREIGN KEY (`id_unite_enseignement`) REFERENCES `unites_enseignement` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `unites_vacantes_vacataires`
--
ALTER TABLE `unites_vacantes_vacataires`
  ADD CONSTRAINT `fk_id_departement_vacataires` FOREIGN KEY (`id_departement`) REFERENCES `departements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_uevacataires_filiere` FOREIGN KEY (`id_filiere`) REFERENCES `filieres` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_uevacataires_unite` FOREIGN KEY (`id_unite_enseignement`) REFERENCES `unites_enseignement` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD CONSTRAINT `utilisateurs_ibfk_1` FOREIGN KEY (`id_departement`) REFERENCES `departements` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `utilisateurs_ibfk_2` FOREIGN KEY (`id_filiere`) REFERENCES `filieres` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `utilisateur_permissions`
--
ALTER TABLE `utilisateur_permissions`
  ADD CONSTRAINT `utilisateur_permissions_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `utilisateur_specialites`
--
ALTER TABLE `utilisateur_specialites`
  ADD CONSTRAINT `utilisateur_specialites_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `utilisateur_specialites_ibfk_2` FOREIGN KEY (`id_specialite`) REFERENCES `specialites` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `voeux_professeurs`
--
ALTER TABLE `voeux_professeurs`
  ADD CONSTRAINT `voeux_professeurs_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `voeux_professeurs_ibfk_3` FOREIGN KEY (`id_ue`) REFERENCES `unites_enseignement` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `voeux_professeurs_ibfk_4` FOREIGN KEY (`id_filiere`) REFERENCES `filieres` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
