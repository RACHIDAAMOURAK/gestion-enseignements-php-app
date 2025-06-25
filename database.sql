-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 05, 2025 at 02:45 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `projet_web`
--

-- --------------------------------------------------------

--
-- Table structure for table `charges_horaires`
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
-- Table structure for table `departements`
--

CREATE TABLE `departements` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departements`
--

INSERT INTO `departements` (`id`, `nom`, `description`, `date_creation`) VALUES
(1, 'Informatique', 'Département des sciences informatiques', '2020-08-31 22:00:00'),
(2, 'Mathématiques', 'Département de mathématiques appliquées', '2019-01-14 22:00:00'),
(3, 'Physique', 'Département de physique fondamentale', '2018-06-09 22:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `emplois_temps`
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
-- Dumping data for table `emplois_temps`
--

INSERT INTO `emplois_temps` (`id`, `id_filiere`, `semestre`, `annee_universitaire`, `date_debut`, `date_fin`, `fichier_path`, `date_creation`, `date_modification`, `statut`) VALUES
(7, 2, 2, '2026-2027', '2025-04-28', '2025-06-07', '', '2025-05-27 23:50:40', '2025-05-28 00:03:02', 'actif');

-- --------------------------------------------------------

--
-- Table structure for table `etudiants`
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
-- Dumping data for table `etudiants`
--

INSERT INTO `etudiants` (`id`, `numero_etudiant`, `nom`, `prenom`, `email`, `id_filiere`, `annee_universitaire`, `date_creation`) VALUES
(22, 'ET001', 'Nom1', 'Prenom1', 'et001@univ.com', 1, '2024/2025', '2025-05-18 22:00:00'),
(23, 'ET002', 'Nom2', 'Prenom2', 'et002@univ.com', 1, '2024/2025', '2025-05-18 22:00:00'),
(24, 'ET003', 'Nom3', 'Prenom3', 'et003@univ.com', 1, '2024/2025', '2025-05-18 22:00:00'),
(25, 'ET004', 'Nom4', 'Prenom4', 'et004@univ.com', 1, '2024/2025', '2025-05-18 22:00:00'),
(26, 'ET005', 'Nom5', 'Prenom5', 'et005@univ.com', 1, '2024/2025', '2025-05-18 22:00:00'),
(27, 'ET006', 'Nom6', 'Prenom6', 'et006@univ.com', 1, '2024/2025', '2025-05-18 22:00:00'),
(28, 'ET007', 'Nom7', 'Prenom7', 'et007@univ.com', 1, '2024/2025', '2025-05-18 22:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `fichiers_notes`
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
-- Dumping data for table `fichiers_notes`
--

INSERT INTO `fichiers_notes` (`id`, `id_unite_enseignement`, `id_enseignant`, `type_session`, `nom_fichier`, `chemin_fichier`, `date_upload`, `statut`) VALUES
(46, 3, 67, 'normale', 'Classeur1 (7).xlsx', '1748910143_67_3.xlsx', '2025-06-03 01:22:23', 'traite'),
(47, 3, 67, 'normale', 'Classeur1 (7).xlsx', '1748910200_67_3.xlsx', '2025-06-03 01:23:20', 'traite');

-- --------------------------------------------------------

--
-- Table structure for table `filieres`
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
-- Dumping data for table `filieres`
--

INSERT INTO `filieres` (`id`, `nom`, `id_specialite`, `description`, `id_coordonnateur`, `date_creation`, `id_departement`) VALUES
(1, 'Génie Logiciel', NULL, 'Filière axée sur la conception et le développement de logiciels.', NULL, '2025-04-16 22:00:00', 1),
(2, 'Réseaux et Sécurité', NULL, 'Filière axée sur les réseaux informatiques et la cybersécurité.', NULL, '2025-04-16 22:00:00', 1),
(4, 'geora', 4, 'gfrt', NULL, '2025-05-20 16:38:01', 1),
(5, 'maath appliqué', NULL, '', NULL, '2025-05-19 09:26:12', 1),
(6, 'GENIE MECANOQUE', NULL, 'qsdfghnj', NULL, '2025-05-19 09:26:37', 1),
(7, 'GENIE MECANOQUE', NULL, '', NULL, '2025-05-24 21:42:31', 3);

-- --------------------------------------------------------

--
-- Table structure for table `groupes`
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
-- Dumping data for table `groupes`
--

INSERT INTO `groupes` (`id`, `type`, `numero`, `id_unite_enseignement`, `id_filiere`, `effectif`, `annee_universitaire`, `semestre`) VALUES
(101, 'TD', 1, 3, 3, 25, '2024-2025', 2),
(105, 'TP', 1, 7, 1, 34, '2023', 1),
(106, 'TD', 3, 3, 2, 11, '2023', 3),
(107, 'TD', 1, 7, 1, 12, '2024', 6);

-- --------------------------------------------------------

--
-- Table structure for table `historique_affectations`
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
-- Dumping data for table `historique_affectations`
--

INSERT INTO `historique_affectations` (`id`, `id_utilisateur`, `nom_utilisateur`, `role`, `id_unite_enseignement`, `code_ue`, `intitule_ue`, `id_filiere`, `nom_filiere`, `id_departement`, `nom_departement`, `annee_universitaire`, `semestre`, `type_cours`, `volume_horaire`, `statut`, `date_affectation`, `commentaire_chef`) VALUES
(233, 3, ' Pierre', 'enseignant', 3, 'INFO201', 'mecanoique de fluide', 2, 'mecanique', 1, 'Informatique', '2024-2025', 1, 'TP', 20, 'affecté', '2025-04-27 15:11:46', 'fffffffffffffffffffffffffffffffffff'),
(238, 1, ' Jean', 'enseignant', 4, 'MATH101', 'programmationc++', 1, ' Informatique', 1, 'Informatique', '2025-2026', 1, 'CM', 30, 'validé', '2025-04-19 20:34:00', 'Importé depuis Excel'),
(241, 3, ' Pierre', 'enseignant', 7, 'RES101', 'psycopatre', 4, 'eau et environmment', 1, 'Informatique', '2025-2026', 1, 'TD', 20, 'validé', '2025-04-17 16:15:00', 'Importé depuis Excel'),
(242, 1, ' Jean', 'enseignant', 2, 'INFO102', 'analyse1', 2, 'mecanique', 1, 'Informatique', '2025-2026', 1, 'CM', 24, 'validé', '2025-04-17 16:05:00', 'Importé depuis Excel'),
(258, 1, 'Durand Jean', 'enseignant', 7, 'AL123', 'AAAB', 1, 'Génie Logiciel', 1, 'Informatique', '2023-2024', 1, 'CM', 13, 'affecté', '2025-05-20 15:27:36', '');

-- --------------------------------------------------------

--
-- Table structure for table `historique_affectations_vacataire`
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
-- Dumping data for table `historique_affectations_vacataire`
--

INSERT INTO `historique_affectations_vacataire` (`id`, `id_vacataire`, `id_unite_enseignement`, `type_cours`, `date_affectation`, `id_coordonnateur`, `action`, `commentaire`) VALUES
(3, 67, 7, 'CM', '2025-06-01 16:01:56', 68, 'affectation', ''),
(4, 67, 3, 'TD', '2025-06-01 17:33:31', 68, 'affectation', ''),
(5, 67, 3, 'CM', '2025-06-01 18:01:20', 68, 'affectation', '');

-- --------------------------------------------------------

--
-- Table structure for table `journal_decisions`
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
-- Dumping data for table `journal_decisions`
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
(18, 'affectation', 264, 4, 'affecte', 'non_affecte', 'Suppression de l\'affectation de Durand Jean pour CM', '2025-06-02 10:13:47');

-- --------------------------------------------------------

--
-- Table structure for table `journal_import_export`
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
-- Table structure for table `notes`
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
-- Dumping data for table `notes`
--

INSERT INTO `notes` (`id`, `id_unite_enseignement`, `id_etudiant`, `type_session`, `note`, `date_soumission`, `id_enseignant`, `statut`, `commentaire`, `fichier_path`) VALUES
(106, 3, 22, 'normale', 14, '2025-06-03 01:23:20', 67, 'soumise', NULL, '1748910200_67_3.xlsx'),
(107, 3, 23, 'normale', 13.2, '2025-06-03 01:23:20', 67, 'soumise', NULL, '1748910200_67_3.xlsx'),
(108, 3, 24, 'normale', 9.5, '2025-06-03 01:23:20', 67, 'soumise', NULL, '1748910200_67_3.xlsx'),
(109, 3, 25, 'normale', 8.9, '2025-06-03 01:23:20', 67, 'soumise', NULL, '1748910200_67_3.xlsx'),
(110, 3, 26, 'normale', 4.9, '2025-06-03 01:23:20', 67, 'soumise', NULL, '1748910200_67_3.xlsx'),
(111, 3, 27, 'normale', 20, '2025-06-03 01:23:20', 67, 'soumise', NULL, '1748910200_67_3.xlsx'),
(112, 3, 28, 'normale', 16, '2025-06-03 01:23:20', 67, 'soumise', NULL, '1748910200_67_3.xlsx');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
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
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `id_utilisateur`, `titre`, `message`, `type`, `statut`, `date_creation`, `date_lecture`) VALUES
(1, 1, 'Heures insuffisantes', 'Attention: Votre charge horaire actuelle est de 20h, ce qui est inférieur au minimum requis de 200h.', 'warning', 'lu', '2025-04-27 13:38:24', '2025-05-20 16:10:41'),
(2, 3, 'Heures insuffisantes', 'Attention: Votre charge horaire actuelle est de 24h, ce qui est inférieur au minimum requis de 200h.', 'warning', 'non_lu', '2025-04-27 13:38:24', NULL),
(3, 3, 'Heures insuffisantes', 'Attention: Votre charge horaire actuelle est de 24h, ce qui est inférieur au minimum requis de 200h.', 'warning', 'non_lu', '2025-04-27 13:38:24', NULL),
(14, 1, 'Heures insuffisantes', 'Attention: Votre charge horaire actuelle est de 5h, ce qui est inférieur au minimum requis de 200h.', 'warning', 'lu', '2025-05-20 16:11:14', '2025-05-20 16:11:29'),
(15, 1, 'Heures insuffisantes', 'Attention: Votre charge horaire actuelle est de 10h, ce qui est inférieur au minimum requis de 200h.', 'warning', 'lu', '2025-05-20 17:13:06', '2025-05-20 17:13:47'),
(16, 1, 'Heures insuffisantes', 'Attention: Votre charge horaire actuelle est de 20h, ce qui est inférieur au minimum requis de 200h.', 'warning', 'lu', '2025-05-20 17:14:27', '2025-05-20 17:14:47'),
(17, 1, 'Heures insuffisantes', 'Attention: Votre charge horaire actuelle est de 25h, ce qui est inférieur au minimum requis de 200h.', 'warning', 'lu', '2025-05-20 17:14:39', '2025-05-20 17:14:46'),
(18, 1, 'Heures insuffisantes', 'Attention: Votre charge horaire actuelle est de 30h, ce qui est inférieur au minimum requis de 200h.', 'warning', 'non_lu', '2025-05-20 18:13:28', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
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
-- Table structure for table `rapport_charge_departement`
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
-- Dumping data for table `rapport_charge_departement`
--

INSERT INTO `rapport_charge_departement` (`id`, `id_departement`, `annee_universitaire`, `semestre`, `total_heures_cm`, `total_heures_td`, `total_heures_tp`, `total_heures`, `nombre_enseignants`, `nombre_vacataires`, `date_generation`) VALUES
(1, 1, '2024-2025', 4, NULL, NULL, NULL, NULL, 0, 0, '2025-05-16 11:50:32'),
(2, 1, '2022-2023', 4, NULL, NULL, NULL, NULL, 0, 0, '2025-05-16 11:53:31'),
(3, 1, '2023-2024', 4, NULL, NULL, NULL, NULL, 0, 0, '2025-05-16 11:53:36'),
(4, 1, '2024-2025', 1, 0, 0, 25, 25, 2, 2, '2025-06-01 13:04:48'),
(5, 1, '2023-2024', 1, 13, 0, 0, 13, 1, 2, '2025-06-02 09:14:25');

-- --------------------------------------------------------

--
-- Table structure for table `reinitialisation_mot_de_passe`
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
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role` enum('admin','chef_departement','coordonnateur','enseignant','vacataire') NOT NULL,
  `id_permission` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_permissions`
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
-- Table structure for table `seances`
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

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
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
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `id_utilisateur`, `adresse_ip`, `agent_utilisateur`, `date_creation`, `date_expiration`) VALUES
('017950c547408965ddfc06076031d081d3abb89d20578c87d48db9deea531c99', 67, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 20:13:10', '2025-06-01 22:13:10'),
('02007c92a3597f3faff4ef833b42e177ab86c46d309dc1deba8d4916b3a1dc89', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 18:09:07', '2025-05-19 20:09:07'),
('0323c403418d9063517447d243cdd4266317badf32b59ab06d55e3b59b981340', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-23 12:52:48', '2025-04-23 14:52:48'),
('05304bd828ee4862b8fd645c9126a207a4e5af831647029e537c3e0312ce6de9', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 18:05:02', '2025-05-19 20:05:02'),
('05f7c092d1404f824e42f9cbdf2ba663ead40c50675d50a5e63f370dddcc4c06', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 15:43:59', '2025-05-19 17:43:59'),
('06317aebce04dbc0d093aaa8bd19a95a8c9d1ba81ea5e52861b7f586c1d1efc6', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-16 19:45:52', '2025-04-16 21:45:52'),
('0873ac3f698d7b2b5d7a7a988627eeed9fc9fd74476a966eb18b9540205c5d73', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-29 23:06:44', '2025-04-30 01:06:44'),
('09bc8b193db1f4dca2150dfb653bd1580c04f741976fa58eaecc7260e40e4ed9', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-16 19:21:22', '2025-04-16 21:21:22'),
('103cba8082b201dc2ca075af36929340c7f7ea7480e236888d54cf1109e8ac19', 72, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 17:47:05', '2025-05-19 19:47:05'),
('1050c4ddbba982a03ccb96b6d2514220f7526613f714e36ddb2fec519b1ac63a', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-15 15:01:10', '2025-05-15 17:01:10'),
('152b8b06bbbd3cc612c9412af6d36128312a9d8c7b7622d01ff689662efa3ff9', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 22:01:05', '2025-05-28 00:01:05'),
('1aaabebb1e605d6179f9df1500593ad7c799b1009acbd03f479a343caa5d3611', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-23 19:19:22', '2025-05-23 21:19:22'),
('202389d2135d81a3dfd20535addf6007f1413607cf2d6f5a5ac6810cf9634d62', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-17 13:53:31', '2025-04-17 15:53:31'),
('20a9666d1b8fa9c974e2d683c5608eb27265b216c3c1e6053b02ca7653e4f36a', 67, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 16:34:01', '2025-06-01 18:34:01'),
('255db6b1814f31dc0dfc270d37a4443588bb79ca3b82dd57560c1e9aebe4599e', 67, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-02 09:16:13', '2025-06-02 11:16:13'),
('258b044daae29221a5c7eab753a20c89ba2752850fb08632e920e07fc429f820', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 13:14:16', '2025-05-19 15:14:16'),
('28d93cf34490b401edc72a17cc0396fdbe102f8719479823e77b702bdc837e81', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 15:13:15', '2025-05-19 17:13:15'),
('29c16e693d38d83de10c54798c9f5e919f65843b3152328bbde5a0f8e207524b', 74, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 18:08:44', '2025-05-19 20:08:44'),
('2bce25435dc17e73a0dfff0dc93877f5026e4734612155eaabfbb5d5c1d326bf', 72, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 18:03:58', '2025-05-19 20:03:58'),
('2c2eb4ce44bb3cc902c9f0de195bcb2cf3bccebfed2cf609aad7e969ba4d33c2', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 12:49:46', '2025-05-19 14:49:46'),
('2c7ef2e7a4e14f490f248bccb268ec9acf83fc05428c2858e879efbbc9058aad', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-29 21:03:29', '2025-04-29 23:03:29'),
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
('3a1238d3a3dd6fb0f0d3093b0c0750683b69aa6205aa2e2e33d9c712c20c131a', 78, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 13:05:45', '2025-06-01 15:05:45'),
('3c098cd11e6f7905609c6ad93d199e930120c2d661726dc233a7a61f8a03482c', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-23 19:04:50', '2025-05-23 21:04:50'),
('3c87c619c7f6eb360fefc1fe5af8d2bd0b2bc8f347af6e30edd31553acf43764', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-16 11:21:44', '2025-05-16 13:21:44'),
('3cc68745661d17107db94235d80c5f0beab65c84665b940522612ca4601bec83', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-20 11:28:39', '2025-05-20 13:28:39'),
('3e627914d947bb2e8415f0f1dc83b4205d3fae1d1e89a20f76d4d2c3af3a079e', 75, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 14:14:11', '2025-06-01 16:14:11'),
('3f9857069fd5a3f61f6eca2271415ed57447818a2699a405ade0d83bc59f62b0', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-29 23:04:01', '2025-04-30 01:04:01'),
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
('5177645bef7510aee0a188528685a6dac8b2fa8ac4971e3e3c24448822f8ba49', 72, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 18:27:56', '2025-05-19 20:27:56'),
('51e3aa5ff3eefc216e8861a1e4cf63ec4109a2cb30fc67ed7acca89332cfdd9c', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-24 21:45:52', '2025-05-24 23:45:52'),
('5365a727931901b0efc08cd1c6cb4b736f466947fdaf7d01dad36fabf8431b84', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-04 19:01:32', '2025-05-04 21:01:32'),
('549eaf47fdeca9c64cb9e3c42050e8f7a2350352047e5fdc1fe370110fceebae', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-02 20:44:45', '2025-05-02 22:44:45'),
('5609921caf198d51e6fff1520651623fd3ee387169e998692aaf9fdc8ce3cdf5', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 15:40:31', '2025-05-19 17:40:31'),
('577814415e68d8833f5f1f323eb23ad9905faf1676dc6d4e3a74eba5684f7967', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-29 23:22:35', '2025-04-30 01:22:35'),
('58149e4d077d8038e024d28742a565c5f2230d7e4283bc59e8bdbf315f44f041', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-02 09:08:23', '2025-06-02 11:08:23'),
('5d55bfa335e2e85408d08b129ba05bbcd3808e0437f38c9aceeba2fc7a0dbcbb', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-04 21:58:14', '2025-05-04 23:58:14'),
('5f41c972a23b97321bbf19819a494b2b9c745bf861c2872e73347b00271b85bd', 72, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 15:46:27', '2025-05-19 17:46:27'),
('65342804066f069802b5692e2e07ce4e6fd2344a1db902ebbbd635c4acb05b3f', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 14:15:59', '2025-05-19 16:15:59'),
('65b5105b67de9af167b5619a71e1796cd25bc35f01b385cd80eee2d24a95399b', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-23 19:01:06', '2025-05-23 21:01:06'),
('67746bbe44062dd33331e9c9e09e40eefd0a2bd4e2003572fe692fc3859c1300', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 15:44:42', '2025-05-19 17:44:42'),
('6963b34563bc21ba29af0ee7d19715491d73c5f5a78cd8b9ee043767bda0cd16', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-19 19:21:21', '2025-04-19 21:21:21'),
('69e2a1ce8423fc8dd6d66528a05080ee4734b2779c0e07f9f9a62edf677b1cf9', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-19 12:34:24', '2025-04-19 14:34:24'),
('70d3fdb0e93d073b82091f57a1813e6a1f05ccb4bc7ebd8b66caafd62cb5597b', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-16 19:23:43', '2025-04-16 21:23:43'),
('7178b0cba1bc1b10e3af168f8225150f1848a451acaf4e29ae37bfd0612d9db8', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 20:15:49', '2025-05-27 22:15:49'),
('71d5cd405d90774dc7d007078e363e23e33609b7864c0a40c89a74f098901bfb', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 16:59:56', '2025-06-01 18:59:56'),
('7532a798f554a5f5efa8f2955275594e7251c6a7a59a9124b7b3772198b20e7b', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-02 21:05:13', '2025-05-02 23:05:13'),
('7c1788e6f5114ded721505f457af86a64638e4a7e9fa1bf873cfaa495f7c8c46', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-20 11:31:42', '2025-05-20 13:31:42'),
('7f06c63aea7f592584badfc768ef0bda9e9226618d01ceb1744b92b93b17e58f', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-23 19:05:28', '2025-05-23 21:05:28'),
('7f47c4db284f633be65e946055dcdfc7cf0bec068d9dfb24be7fc1a6ce5f98cb', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-04 14:35:47', '2025-05-04 16:35:47'),
('8256d0695bfc1459109d4c6f214392b06a91689a2405af4cf336b55e77553ed5', 72, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 18:04:36', '2025-05-19 20:04:36'),
('8317fc670b4deae854099a0c6067e8832ad9da5f581df6363d947e011f465382', 67, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 14:18:25', '2025-06-01 16:18:25'),
('84dc89ee1b9a704449935483a36cdd1b3599934122dd65c0f3ea402ff3513f4a', 72, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 17:39:09', '2025-05-19 19:39:09'),
('84e3e3c92da6cd95cbf99cdee99b09e6492b8da1cd1618c3fe906a74893ff737', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 23:08:33', '2025-05-28 01:08:33'),
('84f00823624af706f7b81ba9807423572695c36addb45c77b134acb8ba83d32c', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 17:53:55', '2025-05-19 19:53:55'),
('95cd74eb3ddc453e8dae499f862a9333bd2c5392407f9f680e6a958e13586c4b', 67, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 16:46:44', '2025-06-01 18:46:44'),
('9612ddb2cf735a9829893ed0a40ebe91c65970dc27a3506cdb79b42a06177502', 72, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 17:40:45', '2025-05-19 19:40:45'),
('96fec6b25de0fb330cfeaf15fb7c5f3be269580e160701e2bf0253b0f37f2886', 72, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 17:55:16', '2025-05-19 19:55:16'),
('97c3c80e940194a38d9349cc20abbd62971cedc07eed09f6ab08fc507a4b5c1c', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 20:18:44', '2025-05-27 22:18:44'),
('98d57a3a8bb87ad1deb35eb9dd640098d502f656fbf0475a1393bff6f0abb40f', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-16 11:57:22', '2025-05-16 13:57:22'),
('9cbef9e34238799590f312b5286627b1213f347afc2095adf29027650364a496', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-04-30 22:38:47', '2025-05-01 00:38:47'),
('9fe72948ed6513d6d205baa0eff1182e41e8ef4a0158cceada7656d83aced518', 72, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 17:53:47', '2025-05-19 19:53:47'),
('a54f50c5b3443c8e97e1dd86d043019bbbba0b918230a7ca62ce87adff429cfd', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 20:12:14', '2025-06-01 22:12:14'),
('a5e536f3ef2dd30246c9046f972cb09909e67a1c2f67d2880372405661940593', 72, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 17:38:31', '2025-05-19 19:38:31'),
('a801ce6f3a41e063a4c5bdd1074d1fd5850b9a9d72a052eb7c6e911faa6f291f', 72, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 17:48:54', '2025-05-19 19:48:54'),
('a82fd623d0fed4e23a99dcb1a838872a360606d472904b364a93d3d6e9d17e49', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 20:16:09', '2025-05-27 22:16:09'),
('a86e74ebf7fd47d5b16926ce4269d58e551ad22addb62130696290b432c8e53f', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-16 19:19:41', '2025-04-16 21:19:41'),
('ab2daeba2a019559d5cd29f4f22c854d8a9af21361bc8ee851067fb4db6b6801', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 23:11:25', '2025-05-28 01:11:25'),
('aba57d147ff61c9ebe447de84eabe531b6463f718596d4ce69a068bc20286fb0', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-15 15:09:10', '2025-05-15 17:09:10'),
('ad93bc0cf0e88a9e04ff24a1baec4b52a92bec79da2269f23b7087371c7c6127', 67, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 17:01:28', '2025-06-01 19:01:28'),
('aef37d25523f9a1192f74fc9f365946cf487904b7ddf9bac45b99492aa312396', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-15 14:58:39', '2025-05-15 16:58:39'),
('b5a49d011da2b6753f185ac19e32341449e835500153cf023f1cc52e8814b0cd', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-23 18:59:14', '2025-05-23 20:59:14'),
('b6101fa4e3a7efbd9757f850c70507661ee416d69a88d7208922404c8e38341d', 72, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 17:48:00', '2025-05-19 19:48:00'),
('b710a4925ba9c8390677f9390e6eda9358d1e2f484ae50ff1453ec5bc83fafe1', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 14:15:30', '2025-05-19 16:15:30'),
('bd027c63cf9e74cd5dad5a480c16cf6601391d18c0059851c928d663a3851686', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 14:15:41', '2025-06-01 16:15:41'),
('c02440b96453f930df0251eb6f0949b2478da4d594d93638c17525aab26ee3f5', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 10:32:41', '2025-06-01 12:32:41'),
('c03d9199c3ea21042f27b29ec1b832fa5f721edad861981b1cf932ffb5a53dff', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-16 22:24:16', '2025-04-17 00:24:16'),
('c17598e6c712e25d8f615372ab420d73c57051d5533f1507f69888d064530446', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 23:14:33', '2025-05-28 01:14:33'),
('c296962d1268bc341ad47c9a506c17bd354ff827b0d6794e528e2f0aa1118b80', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 20:08:15', '2025-05-27 22:08:15'),
('cd9e51a75202fbde2f04f33704ca34e4e5345f20eeea7a7a168dff9f7c1ee9a6', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-29 23:03:49', '2025-04-30 01:03:49'),
('cfaee0d7001eb084f9c7585bb109c01d3b97ed71d703a4337745ee6d3c2c2435', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-02 20:45:51', '2025-05-02 22:45:51'),
('d514600bbd4b95440f85ab246dd2855532fbb0c46a02eda5d1442d3d0ee28451', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 21:05:04', '2025-05-27 23:05:04'),
('d535077feb4844d5d741a58c90eb461b1bb57e916fa65d88698e592b49258ec8', 72, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 17:56:19', '2025-05-19 19:56:19'),
('d83a4c1707bdfbc87cb12067f17f3e83a414e8d6450ec4e298fb04e6d86d92cf', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-16 19:23:38', '2025-04-16 21:23:38'),
('d866319115e009321b02f06743d92dd8b27741b1b22555b58f783420a5ae921d', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-04-30 15:08:14', '2025-04-30 17:08:14'),
('d87a82ccadc42fddf1cbc2457d5af6f35ab9c81b2fb7df2c116d768e7845cc80', 74, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 18:05:27', '2025-05-19 20:05:27'),
('d96c48cebbd245ebbfa4c50512c1a8744cd1babb01edc8dd6316836580c39314', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 17:37:35', '2025-05-19 19:37:35'),
('dacea400f79207dc000877d021c740946fdf3fcfd767572f8002957e79e555e2', 67, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 15:02:57', '2025-06-01 17:02:57'),
('ddaae490d87adebf432652da940a5ac0dfa3feefe85b35fefc2ba8768b42631e', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-15 14:54:41', '2025-05-15 16:54:41'),
('ded264fe4fa6b7883348937d1d77949099466fa3e8b1952630c782e808e51601', 72, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 17:49:34', '2025-05-19 19:49:34'),
('df01b199b490eb1acf5abce7c0454fdfb8d612d0f04cd9ec6224a3b1af111815', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 20:38:25', '2025-05-27 22:38:25'),
('df301ccbe9b3f15395bcd87c85b47df76ebdbce4c01ee28614ba4c43f6914e9d', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 09:29:10', '2025-05-19 11:29:10'),
('e86e827d0a1bb6458fa767c3a29e800ace0ec31ec570965ed1037d2c7190f092', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-18 21:46:22', '2025-04-18 23:46:22'),
('ea22313b3aa08cba5a27ccc47718c343d462b8ff5efa5aa782dda0976427ed40', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-04-30 11:11:31', '2025-04-30 13:11:31'),
('ed9c48da76b6c5cafbce4008b6248cf3e103fb1e00e0d44ecad73499ae6881b4', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-16 20:36:21', '2025-04-16 22:36:21'),
('efd9d0bcf7d6b37d06e9026e4608c0792e40b656179f2368d53872125c0161db', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-24 21:44:38', '2025-05-24 23:44:38'),
('f07b47fe4d345ef3baba5db32c87893fbdcc47e0ef1fade7c196428c251b86dc', 72, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 18:01:08', '2025-05-19 20:01:08'),
('f0d823fccadac9de5057fd9989f1a44d62d943e54467a3651e088d801229720b', 72, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 18:03:41', '2025-05-19 20:03:41'),
('f256a126992c0298b277de9df494e056000d1091e64ffaabc22b2b8eb1678e47', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-06-01 16:31:54', '2025-06-01 18:31:54'),
('f5b361e37e6aef693d98fbf84e4711c53a40e9db9bd460f7319a602d9598f9c6', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 20:34:46', '2025-05-27 22:34:46'),
('f5c87617505c8a8a1bc4a5779cd3c499e6e6b944738347d562acb0a3c4297fac', 77, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 23:12:26', '2025-05-28 01:12:26'),
('f95e9b3e3e5a1b560ad6c0e0333735861d4222d012c55bc52947178b575dae05', 68, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-27 20:17:27', '2025-05-27 22:17:27'),
('fee81aab27925475dbfe1f82bcc02fe190b8069ae75c342cb2cb15375e33ed42', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '2025-05-19 15:19:26', '2025-05-19 17:19:26');

-- --------------------------------------------------------

--
-- Table structure for table `specialites`
--

CREATE TABLE `specialites` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `id_departement` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `specialites`
--

INSERT INTO `specialites` (`id`, `nom`, `id_departement`, `description`, `date_creation`) VALUES
(1, 'Informatique Appliquée', 1, 'Spécialité axée sur le développement logiciel et les systèmes informatiques.', '2025-04-29 22:00:00'),
(2, 'physique', 1, NULL, '2025-04-12 19:37:54'),
(3, 'mecanique', 1, NULL, '2025-04-12 19:40:10'),
(4, 'informatique', 1, NULL, '2025-04-12 19:00:05');

-- --------------------------------------------------------

--
-- Table structure for table `tentatives_connexion`
--

CREATE TABLE `tentatives_connexion` (
  `id` int(11) NOT NULL,
  `id_utilisateur` int(11) DEFAULT NULL,
  `adresse_ip` varchar(45) NOT NULL,
  `date_tentative` timestamp NOT NULL DEFAULT current_timestamp(),
  `succes` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tentatives_connexion`
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
(120, 72, '::1', '2025-05-19 15:46:27', 1),
(121, 2, '::1', '2025-05-19 17:29:03', 1),
(122, NULL, '::1', '2025-05-19 17:29:43', 1),
(123, 2, '::1', '2025-05-19 17:37:35', 1),
(124, 72, '::1', '2025-05-19 17:38:31', 1),
(125, 72, '::1', '2025-05-19 17:39:09', 1),
(126, 72, '::1', '2025-05-19 17:40:45', 1),
(127, 72, '::1', '2025-05-19 17:47:05', 1),
(128, 72, '::1', '2025-05-19 17:48:00', 1),
(129, 72, '::1', '2025-05-19 17:48:54', 1),
(130, 72, '::1', '2025-05-19 17:49:34', 1),
(131, 72, '::1', '2025-05-19 17:53:47', 1),
(132, 68, '::1', '2025-05-19 17:53:55', 1),
(133, 72, '::1', '2025-05-19 17:55:16', 1),
(134, 72, '::1', '2025-05-19 17:56:19', 1),
(135, 72, '::1', '2025-05-19 18:01:08', 1),
(136, 72, '::1', '2025-05-19 18:03:41', 1),
(137, 72, '::1', '2025-05-19 18:03:58', 1),
(138, 72, '::1', '2025-05-19 18:04:36', 1),
(139, NULL, '::1', '2025-05-19 18:04:54', 0),
(140, NULL, '::1', '2025-05-19 18:04:59', 0),
(141, 2, '::1', '2025-05-19 18:05:02', 1),
(142, 74, '::1', '2025-05-19 18:05:27', 1),
(143, 74, '::1', '2025-05-19 18:08:44', 1),
(144, 68, '::1', '2025-05-19 18:09:07', 1),
(145, 72, '::1', '2025-05-19 18:27:56', 1),
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
(222, 67, '::1', '2025-06-02 22:49:38', 1);

-- --------------------------------------------------------

--
-- Table structure for table `ue_filiere`
--

CREATE TABLE `ue_filiere` (
  `id` int(11) NOT NULL,
  `id_ue` int(11) NOT NULL,
  `id_filiere` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ue_filiere`
--

INSERT INTO `ue_filiere` (`id`, `id_ue`, `id_filiere`) VALUES
(1, 3, 1),
(88, 3, 2),
(89, 3, 4),
(90, 3, 5),
(96, 6, 1),
(97, 6, 4),
(5, 7, 1),
(92, 7, 2),
(93, 7, 4),
(91, 7, 5),
(6, 8, 1),
(94, 8, 2),
(95, 8, 4),
(98, 10, 1);

-- --------------------------------------------------------

--
-- Table structure for table `unites_enseignement`
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
-- Dumping data for table `unites_enseignement`
--

INSERT INTO `unites_enseignement` (`id`, `code`, `intitule`, `description`, `specialite`, `id_filiere`, `id_departement`, `semestre`, `volume_horaire_cm`, `volume_horaire_td`, `volume_horaire_tp`, `credits`, `annee_universitaire`, `id_responsable`, `statut`, `date_creation`, `date_modification`) VALUES
(3, 'AB123', '12', 'XSQCDVFB', 'Informatique Appliquée', NULL, 1, 4, 10, 5, 5, 9, '2024-2025', NULL, 'vacant', '2025-04-30 23:50:45', '2025-05-20 21:10:01'),
(6, 'AB111', 'algEBRE', 'xwcvb', 'Informatique Appliquée', NULL, 1, 1, 14, 4, 6, 12, '2023-2024', NULL, 'disponible', '2025-05-19 14:39:29', '2025-05-19 14:39:29'),
(7, 'AL123', 'AAAB', 'SDDFBG', 'Informatique Appliquée', NULL, 2, 1, 13, 2, 5, 12, '2023-2024', NULL, 'disponible', '2025-05-19 14:39:56', '2025-05-19 14:39:56'),
(8, 'AZ127', 'ABC', 'ACV', 'Informatique Appliquée', NULL, 2, 1, 12, 12, 12, 2, '2025-2026', 74, 'disponible', '2025-05-19 20:10:29', '2025-05-27 23:04:07'),
(10, 'AZ123', 'CHIMIE', 'AZZEE', 'informatique', NULL, 3, 1, 3, 3, 3, 12, '2023-2024', 1, 'disponible', '2025-05-27 23:05:19', '2025-05-27 23:05:19');

-- --------------------------------------------------------

--
-- Table structure for table `unites_enseignement_vacantes`
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
-- Dumping data for table `unites_enseignement_vacantes`
--

INSERT INTO `unites_enseignement_vacantes` (`id`, `id_unite_enseignement`, `annee_universitaire`, `semestre`, `type_cours`, `volume_horaire`, `date_declaration`, `id_departement`, `id_filiere`) VALUES
(13, 7, '2024-2025', 1, 'TD', 20, '2025-04-18 19:55:30', 1, 2),
(14, 6, '2024-2025', 2, 'CM', 24, '2025-04-19 17:06:52', 1, 4),
(15, 6, '2024-2025', 1, 'TP', 20, '2025-04-19 18:26:19', 1, 4),
(16, 7, '2024-2025', 1, 'CM', 30, '2025-04-19 18:32:08', 1, 2),
(23, 6, '2024-2025', 1, 'TD', 20, '2025-05-16 17:28:12', 1, 4),
(24, 3, '2024-2025', 4, 'CM', 10, '2025-05-20 17:51:31', 1, 2),
(25, 3, '2024-2025', 4, 'CM', 10, '2025-05-20 17:52:54', 1, 1),
(26, 3, '2024-2025', 4, 'TD', 5, '2025-06-01 14:15:57', 1, 1),
(27, 6, '2023-2024', 1, 'CM', 14, '2025-06-01 14:31:51', 1, 1),
(28, 6, '2023-2024', 1, 'TD', 4, '2025-06-01 15:22:41', 1, 4),
(29, 3, '2024-2025', 4, 'CM', 10, '2025-06-01 16:32:52', 1, 5),
(30, 3, '2024-2025', 4, 'TD', 5, '2025-06-01 17:00:28', 1, 2);

-- --------------------------------------------------------

--
-- Table structure for table `unites_vacantes_vacataires`
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
-- Dumping data for table `unites_vacantes_vacataires`
--

INSERT INTO `unites_vacantes_vacataires` (`id`, `id_unite_enseignement`, `annee_universitaire`, `semestre`, `type_cours`, `volume_horaire`, `date_declaration`, `id_departement`, `id_filiere`) VALUES
(4, 6, '2023-2024', 1, 'TD', 4, '2025-06-01 15:22:41', 1, 4);

-- --------------------------------------------------------

--
-- Table structure for table `utilisateurs`
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
  `date_changement_mdp` datetime DEFAULT NULL,
  `reset_token` VARCHAR(64) NULL,
  `reset_token_expiry` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom_utilisateur`, `email`, `mot_de_passe`, `prenom`, `nom`, `role`, `specialite`, `id_departement`, `id_filiere`, `date_creation`, `date_modification`, `derniere_connexion`, `actif`, `date_changement_mdp`, `reset_token`, `reset_token_expiry`) VALUES
(1, 'jean', 'jean@example.com', '$2y$10$FUVvvOaG9l0/YotMn9N/U.KHbzTed6MvbKUKOwNZYh3RWuciAjID.', 'Jean', 'Durand', 'enseignant', 'Informatique Appliquée', 1, NULL, '2025-05-20 15:08:29', '2025-06-01 20:12:18', '2025-06-01 20:12:18', 1, NULL, NULL, NULL),
(2, 'admin', 'admin@admin.com', '$2y$10$xHyvo6oZ9ei5OzHq5p.mc.RPAN9CnKEjXpe5yyFrRZAbmPdC0PwaK', '', '', 'admin', NULL, NULL, NULL, '2025-04-16 19:19:18', '2025-06-01 20:11:37', '2025-06-01 20:11:37', 1, NULL, NULL, NULL),
(3, 'pierre', 'pierre@example.com', '$2y$10$xHyvo6oZ9ei5OzHq5p.mc.RPAN9CnKEjXpe5yyFrRZAbmPdC0PwaK', 'Pierre', 'Martin', 'enseignant', 'chi', 1, NULL, '2025-05-20 15:08:29', '2025-05-20 17:17:55', NULL, 1, NULL, NULL, NULL),
(55, 'BOUIBAUAN1', 'ibtissam.bouibauan@etu.uae.ac.ma', '$2y$10$hzGQYkwyBzAh5v7ALbfHR.CQsK4NAMj6UYIgYuYiAQCH/VGTw9x0i', 'IBTISSAM', 'bouibauan', 'vacataire', 'Informatique Appliquée', 3, NULL, '2025-05-03 16:26:31', '2025-05-03 16:26:31', NULL, 1, NULL, NULL, NULL),
(67, 'AHMED', 'racjid.ahmed@etu.uae.ac.ma', '$2y$10$EvisvckfK/8NyFMznUjH1u/iHNYmHIsdU.CkvePKEM5VPUF0hv2w.', 'racjid', 'ahmed', 'vacataire', 'Informatique Appliquée', 2, NULL, '2025-05-19 14:07:53', '2025-06-03 00:36:29', '2025-06-03 00:36:29', 1, NULL, NULL, NULL),
(68, 'ALAMI', 'alam.alami@etu.uae.ac.ma', '$2y$10$23ZrPwlgHZu9gGWrQkEgvOv4usmZCx/Da46ww8NYwuk/rjh1SROaa', 'ALAM', 'ALAMI', 'coordonnateur', NULL, NULL, NULL, '2025-05-19 14:15:47', '2025-06-01 17:01:06', '2025-06-01 17:01:06', 1, NULL, NULL, NULL),
(72, 'mmami', 'ma.mami@etu.uae.ac.ma', '$2y$10$vOMXB9xTyoCiM8U8H/ODYunP0B3ztOgZ/eEOL99XL0S79B97sNp4.', 'Ma', 'MAMI', 'enseignant', 'Informatique', 1, NULL, '2025-05-19 15:46:13', '2025-05-20 17:54:38', '2025-05-19 18:27:56', 1, NULL, NULL, NULL),
(74, 'alamin', 'amin.lamin@etu.uae.ac.ma', '$2y$10$xNlav4xv0IYhpxqTyDuHoOK7W2UQbkbARcU6pC1X39ti1zgoH2BqW', 'Amin', 'LAMIN', 'enseignant', NULL, NULL, NULL, '2025-05-19 18:05:15', '2025-05-19 18:08:44', '2025-05-19 18:08:44', 1, NULL, NULL, NULL),
(75, 'RAARA', 'rari.raara@etu.uae.ac.ma', '$2y$10$cPcO72dqMoTHtq1Vbh0Whu3HNwzbBG.tv0UBj/AkR8H6l2IcmTd5S', 'RARI', 'RAARA', 'vacataire', 'Informatique Appliquée', 1, NULL, '2025-05-19 18:17:17', '2025-06-01 14:14:11', '2025-06-01 14:14:11', 1, NULL, NULL, NULL),
(76, 'arir', '', '', 'rora', '', 'chef_departement', 'Informatique', 1, NULL, '2025-05-20 17:23:24', '2025-05-20 17:28:47', NULL, 1, NULL, NULL, NULL),
(77, 'CHEF', 'chef.chef@etu.uae.ac.ma', '$2y$10$joUAGa4GxXo.yQ0wzuicIuhT5/g54scWFDANmy3Lhphx0AV7xL0ju', 'CHEF', '', 'chef_departement', NULL, NULL, NULL, '2025-05-23 18:58:44', '2025-06-02 09:08:23', '2025-06-02 09:08:23', 1, NULL, NULL, NULL),
(78, 'AMINI', 'ayman.amini@etu.uae.ac.ma', '$2y$10$jLqnGyQ32IEM6/4yoYbeBurraUbjn2z7BPKYO7j.pJzFqgQ4V3jsS', 'AMINI', '', 'vacataire', NULL, NULL, NULL, '2025-06-01 13:05:34', '2025-06-01 14:10:08', '2025-06-01 14:10:08', 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `utilisateur_permissions`
--

CREATE TABLE `utilisateur_permissions` (
  `id_utilisateur` int(11) NOT NULL,
  `nom_permission` varchar(100) NOT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `utilisateur_specialites`
--

CREATE TABLE `utilisateur_specialites` (
  `id_utilisateur` int(11) NOT NULL,
  `id_specialite` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `voeux_professeurs`
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
-- Dumping data for table `voeux_professeurs`
--

INSERT INTO `voeux_professeurs` (`id`, `id_utilisateur`, `id_ue`, `priorite`, `commentaire`, `id_filiere`, `date_creation`, `annee_universitaire`, `type_ue`, `statut`, `commentaire_chef`) VALUES
(89, 1, 3, 1, 'teyyyyyyyyyy', 1, '2025-05-20 17:13:06', '2023-2024', 'CM', 'en_attente', ''),
(90, 1, 3, 3, 'sssssssssssssssssssssssssssssssssssssssss', 4, '2025-05-20 17:14:27', '2023-2024', 'CM', 'en attente', NULL),
(91, 1, 3, 4, 'DTTTTTTTTTTTTTTT', 5, '2025-05-20 17:14:39', '2023-2024', 'TD', 'en_attente', NULL),
(92, 3, 3, 1, NULL, 5, '2025-05-20 17:50:12', '2023-2024', NULL, 'en_attente', NULL),
(93, 1, 3, 67, 'YYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYY', 4, '2025-05-20 18:13:28', '2023-2024', 'TD', 'en_attente', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `charges_horaires`
--
ALTER TABLE `charges_horaires`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_utilisateur` (`id_utilisateur`,`annee_universitaire`),
  ADD KEY `idx_charges_horaires_statut` (`statut`);

--
-- Indexes for table `departements`
--
ALTER TABLE `departements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `emplois_temps`
--
ALTER TABLE `emplois_temps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_filiere` (`id_filiere`);

--
-- Indexes for table `etudiants`
--
ALTER TABLE `etudiants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_etudiant` (`numero_etudiant`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_etudiants_filiere` (`id_filiere`);

--
-- Indexes for table `fichiers_notes`
--
ALTER TABLE `fichiers_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_unite_enseignement` (`id_unite_enseignement`),
  ADD KEY `id_enseignant` (`id_enseignant`);

--
-- Indexes for table `filieres`
--
ALTER TABLE `filieres`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_filieres_specialite` (`id_specialite`),
  ADD KEY `idx_filieres_coordonnateur` (`id_coordonnateur`);

--
-- Indexes for table `groupes`
--
ALTER TABLE `groupes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `type` (`type`,`numero`,`id_unite_enseignement`,`annee_universitaire`,`semestre`),
  ADD KEY `idx_groupes_ue` (`id_unite_enseignement`);

--
-- Indexes for table `historique_affectations`
--
ALTER TABLE `historique_affectations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_unite_enseignement` (`id_unite_enseignement`),
  ADD KEY `id_utilisateur` (`id_utilisateur`,`annee_universitaire`),
  ADD KEY `id_departement` (`id_departement`,`annee_universitaire`),
  ADD KEY `id_filiere` (`id_filiere`,`annee_universitaire`),
  ADD KEY `idx_historique_affectations_annee` (`annee_universitaire`);

--
-- Indexes for table `historique_affectations_vacataire`
--
ALTER TABLE `historique_affectations_vacataire`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_vacataire` (`id_vacataire`),
  ADD KEY `id_unite_enseignement` (`id_unite_enseignement`),
  ADD KEY `id_coordonnateur` (`id_coordonnateur`);

--
-- Indexes for table `journal_decisions`
--
ALTER TABLE `journal_decisions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `journal_import_export`
--
ALTER TABLE `journal_import_export`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_utilisateur` (`id_utilisateur`);

--
-- Indexes for table `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_unite_enseignement` (`id_unite_enseignement`,`id_etudiant`,`type_session`),
  ADD KEY `id_enseignant` (`id_enseignant`),
  ADD KEY `idx_notes_ue` (`id_unite_enseignement`),
  ADD KEY `idx_notes_etudiant` (`id_etudiant`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_utilisateur` (`id_utilisateur`,`statut`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nom` (`nom`);

--
-- Indexes for table `rapport_charge_departement`
--
ALTER TABLE `rapport_charge_departement`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_departement` (`id_departement`,`annee_universitaire`,`semestre`);

--
-- Indexes for table `reinitialisation_mot_de_passe`
--
ALTER TABLE `reinitialisation_mot_de_passe`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token_unique` (`token`),
  ADD KEY `idx_reinit_mdp_token` (`token`),
  ADD KEY `idx_reinit_mdp_utilisateur` (`id_utilisateur`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role`,`id_permission`),
  ADD KEY `id_permission` (`id_permission`);

--
-- Indexes for table `seances`
--
ALTER TABLE `seances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_groupe` (`id_groupe`),
  ADD KEY `id_enseignant` (`id_enseignant`),
  ADD KEY `idx_seances_ue` (`id_unite_enseignement`),
  ADD KEY `idx_seances_emploi_temps` (`id_emploi_temps`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_utilisateur` (`id_utilisateur`);

--
-- Indexes for table `specialites`
--
ALTER TABLE `specialites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nom` (`nom`),
  ADD KEY `idx_specialites_departement` (`id_departement`);

--
-- Indexes for table `tentatives_connexion`
--
ALTER TABLE `tentatives_connexion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_utilisateur` (`id_utilisateur`);

--
-- Indexes for table `ue_filiere`
--
ALTER TABLE `ue_filiere`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_ue_filiere` (`id_ue`,`id_filiere`),
  ADD KEY `id_filiere` (`id_filiere`);

--
-- Indexes for table `unites_enseignement`
--
ALTER TABLE `unites_enseignement`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_ue_filiere` (`id_filiere`),
  ADD KEY `idx_ue_departement` (`id_departement`),
  ADD KEY `idx_ue_responsable` (`id_responsable`);

--
-- Indexes for table `unites_enseignement_vacantes`
--
ALTER TABLE `unites_enseignement_vacantes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_id_departement` (`id_departement`),
  ADD KEY `fk_uevacantes_filiere` (`id_filiere`),
  ADD KEY `id_unite_enseignement` (`id_unite_enseignement`,`annee_universitaire`,`semestre`,`type_cours`) USING BTREE;

--
-- Indexes for table `unites_vacantes_vacataires`
--
ALTER TABLE `unites_vacantes_vacataires`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_id_departement_vacataires` (`id_departement`),
  ADD KEY `fk_uevacataires_filiere` (`id_filiere`),
  ADD KEY `id_unite_enseignement` (`id_unite_enseignement`,`annee_universitaire`,`semestre`,`type_cours`) USING BTREE;

--
-- Indexes for table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nom_utilisateur` (`nom_utilisateur`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_utilisateurs_departement` (`id_departement`),
  ADD KEY `idx_utilisateurs_filiere` (`id_filiere`);

--
-- Indexes for table `utilisateur_permissions`
--
ALTER TABLE `utilisateur_permissions`
  ADD PRIMARY KEY (`id_utilisateur`,`nom_permission`);

--
-- Indexes for table `utilisateur_specialites`
--
ALTER TABLE `utilisateur_specialites`
  ADD PRIMARY KEY (`id_utilisateur`,`id_specialite`),
  ADD KEY `id_specialite` (`id_specialite`);

--
-- Indexes for table `voeux_professeurs`
--
ALTER TABLE `voeux_professeurs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_ue` (`id_ue`),
  ADD KEY `id_filiere` (`id_filiere`),
  ADD KEY `idx_voeux_professeurs_utilisateur` (`id_utilisateur`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `charges_horaires`
--
ALTER TABLE `charges_horaires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departements`
--
ALTER TABLE `departements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `emplois_temps`
--
ALTER TABLE `emplois_temps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `etudiants`
--
ALTER TABLE `etudiants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `fichiers_notes`
--
ALTER TABLE `fichiers_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `filieres`
--
ALTER TABLE `filieres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `groupes`
--
ALTER TABLE `groupes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT for table `historique_affectations`
--
ALTER TABLE `historique_affectations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=265;

--
-- AUTO_INCREMENT for table `historique_affectations_vacataire`
--
ALTER TABLE `historique_affectations_vacataire`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `journal_decisions`
--
ALTER TABLE `journal_decisions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `journal_import_export`
--
ALTER TABLE `journal_import_export`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notes`
--
ALTER TABLE `notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `rapport_charge_departement`
--
ALTER TABLE `rapport_charge_departement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reinitialisation_mot_de_passe`
--
ALTER TABLE `reinitialisation_mot_de_passe`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seances`
--
ALTER TABLE `seances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `specialites`
--
ALTER TABLE `specialites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tentatives_connexion`
--
ALTER TABLE `tentatives_connexion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=223;

--
-- AUTO_INCREMENT for table `ue_filiere`
--
ALTER TABLE `ue_filiere`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `unites_enseignement`
--
ALTER TABLE `unites_enseignement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `unites_enseignement_vacantes`
--
ALTER TABLE `unites_enseignement_vacantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `unites_vacantes_vacataires`
--
ALTER TABLE `unites_vacantes_vacataires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `voeux_professeurs`
--
ALTER TABLE `voeux_professeurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `charges_horaires`
--
ALTER TABLE `charges_horaires`
  ADD CONSTRAINT `charges_horaires_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `emplois_temps`
--
ALTER TABLE `emplois_temps`
  ADD CONSTRAINT `emplois_temps_ibfk_1` FOREIGN KEY (`id_filiere`) REFERENCES `filieres` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `etudiants`
--
ALTER TABLE `etudiants`
  ADD CONSTRAINT `etudiants_ibfk_1` FOREIGN KEY (`id_filiere`) REFERENCES `filieres` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `fichiers_notes`
--
ALTER TABLE `fichiers_notes`
  ADD CONSTRAINT `fichiers_notes_ibfk_1` FOREIGN KEY (`id_unite_enseignement`) REFERENCES `unites_enseignement` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fichiers_notes_ibfk_2` FOREIGN KEY (`id_enseignant`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `filieres`
--
ALTER TABLE `filieres`
  ADD CONSTRAINT `filieres_ibfk_1` FOREIGN KEY (`id_specialite`) REFERENCES `specialites` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_coordonnateur` FOREIGN KEY (`id_coordonnateur`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `groupes`
--
ALTER TABLE `groupes`
  ADD CONSTRAINT `groupes_ibfk_1` FOREIGN KEY (`id_unite_enseignement`) REFERENCES `unites_enseignement` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `historique_affectations_vacataire`
--
ALTER TABLE `historique_affectations_vacataire`
  ADD CONSTRAINT `historique_affectations_vacataire_ibfk_1` FOREIGN KEY (`id_vacataire`) REFERENCES `utilisateurs` (`id`),
  ADD CONSTRAINT `historique_affectations_vacataire_ibfk_2` FOREIGN KEY (`id_unite_enseignement`) REFERENCES `unites_enseignement` (`id`),
  ADD CONSTRAINT `historique_affectations_vacataire_ibfk_3` FOREIGN KEY (`id_coordonnateur`) REFERENCES `utilisateurs` (`id`);

--
-- Constraints for table `journal_import_export`
--
ALTER TABLE `journal_import_export`
  ADD CONSTRAINT `journal_import_export_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notes`
--
ALTER TABLE `notes`
  ADD CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`id_unite_enseignement`) REFERENCES `unites_enseignement` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notes_ibfk_2` FOREIGN KEY (`id_etudiant`) REFERENCES `etudiants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notes_ibfk_3` FOREIGN KEY (`id_enseignant`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rapport_charge_departement`
--
ALTER TABLE `rapport_charge_departement`
  ADD CONSTRAINT `rapport_charge_departement_ibfk_1` FOREIGN KEY (`id_departement`) REFERENCES `departements` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reinitialisation_mot_de_passe`
--
ALTER TABLE `reinitialisation_mot_de_passe`
  ADD CONSTRAINT `reinitialisation_mot_de_passe_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`id_permission`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seances`
--
ALTER TABLE `seances`
  ADD CONSTRAINT `seances_ibfk_1` FOREIGN KEY (`id_unite_enseignement`) REFERENCES `unites_enseignement` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `seances_ibfk_2` FOREIGN KEY (`id_groupe`) REFERENCES `groupes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `seances_ibfk_3` FOREIGN KEY (`id_enseignant`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `seances_ibfk_4` FOREIGN KEY (`id_emploi_temps`) REFERENCES `emplois_temps` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `specialites`
--
ALTER TABLE `specialites`
  ADD CONSTRAINT `specialites_ibfk_1` FOREIGN KEY (`id_departement`) REFERENCES `departements` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tentatives_connexion`
--
ALTER TABLE `tentatives_connexion`
  ADD CONSTRAINT `tentatives_connexion_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ue_filiere`
--
ALTER TABLE `ue_filiere`
  ADD CONSTRAINT `ue_filiere_ibfk_1` FOREIGN KEY (`id_ue`) REFERENCES `unites_enseignement` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ue_filiere_ibfk_2` FOREIGN KEY (`id_filiere`) REFERENCES `filieres` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `unites_enseignement`
--
ALTER TABLE `unites_enseignement`
  ADD CONSTRAINT `unites_enseignement_ibfk_1` FOREIGN KEY (`id_filiere`) REFERENCES `filieres` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `unites_enseignement_ibfk_2` FOREIGN KEY (`id_departement`) REFERENCES `departements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `unites_enseignement_ibfk_3` FOREIGN KEY (`id_responsable`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `unites_enseignement_vacantes`
--
ALTER TABLE `unites_enseignement_vacantes`
  ADD CONSTRAINT `fk_id_departement` FOREIGN KEY (`id_departement`) REFERENCES `departements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_uevacantes_filiere` FOREIGN KEY (`id_filiere`) REFERENCES `filieres` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `unites_enseignement_vacantes_ibfk_1` FOREIGN KEY (`id_unite_enseignement`) REFERENCES `unites_enseignement` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `unites_vacantes_vacataires`
--
ALTER TABLE `unites_vacantes_vacataires`
  ADD CONSTRAINT `fk_id_departement_vacataires` FOREIGN KEY (`id_departement`) REFERENCES `departements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_uevacataires_filiere` FOREIGN KEY (`id_filiere`) REFERENCES `filieres` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_uevacataires_unite` FOREIGN KEY (`id_unite_enseignement`) REFERENCES `unites_enseignement` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD CONSTRAINT `utilisateurs_ibfk_1` FOREIGN KEY (`id_departement`) REFERENCES `departements` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `utilisateurs_ibfk_2` FOREIGN KEY (`id_filiere`) REFERENCES `filieres` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `utilisateur_permissions`
--
ALTER TABLE `utilisateur_permissions`
  ADD CONSTRAINT `utilisateur_permissions_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `utilisateur_specialites`
--
ALTER TABLE `utilisateur_specialites`
  ADD CONSTRAINT `utilisateur_specialites_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `utilisateur_specialites_ibfk_2` FOREIGN KEY (`id_specialite`) REFERENCES `specialites` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `voeux_professeurs`
--
ALTER TABLE `voeux_professeurs`
  ADD CONSTRAINT `voeux_professeurs_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `voeux_professeurs_ibfk_3` FOREIGN KEY (`id_ue`) REFERENCES `unites_enseignement` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `voeux_professeurs_ibfk_4` FOREIGN KEY (`id_filiere`) REFERENCES `filieres` (`id`) ON DELETE CASCADE;

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expiry` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `email` (`email`),
  KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
