-- Table des filières
CREATE TABLE IF NOT EXISTS filieres (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE
);

-- Insertion des filières
INSERT INTO filieres (id, nom, code) VALUES
(1, 'Informatique', 'INFO');

-- Table des unités d'enseignement
CREATE TABLE IF NOT EXISTS unites_enseignement (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(20) NOT NULL UNIQUE,
    intitule VARCHAR(100) NOT NULL
);

-- Insertion des UEs
INSERT INTO unites_enseignement (id, code, intitule) VALUES
(6, 'IN101', 'Introduction à la Programmation'),
(7, 'MA201', 'Algèbre Linéaire'),
(8, 'PH301', 'Mécanique Quantique');

-- Table ue_filiere
CREATE TABLE IF NOT EXISTS ue_filiere (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_unite_enseignement INT NOT NULL,
    id_filiere INT NOT NULL,
    FOREIGN KEY (id_unite_enseignement) REFERENCES unites_enseignement(id),
    FOREIGN KEY (id_filiere) REFERENCES filieres(id),
    UNIQUE KEY unique_ue_filiere (id_unite_enseignement, id_filiere)
);

-- Insertion des relations UE-Filière
INSERT INTO ue_filiere (id_unite_enseignement, id_filiere) VALUES
(6, 1),  -- IN101 pour la filière INFO
(7, 1),  -- MA201 pour la filière INFO
(8, 1);  -- PH301 pour la filière INFO

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin', 'enseignant', 'etudiant') NOT NULL
);

-- Insertion des enseignants
INSERT INTO utilisateurs (id, nom, prenom, email, role) VALUES
(11, 'Curie', 'Marie', 'marie.curie@univ.fr', 'enseignant'),
(12, 'Turing', 'Alan', 'alan.turing@univ.fr', 'enseignant'),
(13, 'Hopper', 'Grace', 'grace.hopper@univ.fr', 'enseignant');

-- Table des affectations historiques
CREATE TABLE IF NOT EXISTS historique_affectations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_unite_enseignement INT NOT NULL,
    id_utilisateur INT NOT NULL,
    role VARCHAR(20) NOT NULL,
    type_cours ENUM('CM', 'TD', 'TP') NOT NULL,
    date_affectation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_unite_enseignement) REFERENCES unites_enseignement(id),
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id)
);

-- Insertion des affectations
INSERT INTO historique_affectations (id_unite_enseignement, id_utilisateur, role, type_cours) VALUES
(6, 11, 'enseignant', 'CM'),  -- Marie Curie -> IN101
(7, 12, 'enseignant', 'CM'),  -- Alan Turing -> MA201
(8, 13, 'enseignant', 'CM');  -- Grace Hopper -> PH301

-- Table des fichiers de notes
CREATE TABLE IF NOT EXISTS fichiers_notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_unite_enseignement INT NOT NULL,
    id_enseignant INT NOT NULL,
    type_session ENUM('normale', 'rattrapage') NOT NULL,
    nom_fichier VARCHAR(255) NOT NULL,
    chemin_fichier VARCHAR(255) NOT NULL,
    date_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('non_traite', 'traite', 'erreur') DEFAULT 'non_traite',
    FOREIGN KEY (id_unite_enseignement) REFERENCES unites_enseignement(id),
    FOREIGN KEY (id_enseignant) REFERENCES utilisateurs(id)
);

-- Table des notes
CREATE TABLE IF NOT EXISTS notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_unite_enseignement INT NOT NULL,
    id_etudiant INT NOT NULL,
    type_session ENUM('normale', 'rattrapage') NOT NULL,
    note DECIMAL(4,2) NOT NULL,
    date_soumission TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_enseignant INT NOT NULL,
    statut ENUM('soumise', 'validee', 'rejetee') DEFAULT 'soumise',
    fichier_path VARCHAR(255),
    date_modification DATETIME,
    FOREIGN KEY (id_unite_enseignement) REFERENCES unites_enseignement(id),
    FOREIGN KEY (id_enseignant) REFERENCES utilisateurs(id),
    UNIQUE KEY unique_note (id_unite_enseignement, id_etudiant, type_session)
); 