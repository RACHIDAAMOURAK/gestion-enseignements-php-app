<?php
// vacataire/enregistrer_vacataire.php

function genererMotDePasse($longueur = 10) {
    $chiffres = '0123456789';
    $majuscules = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $minuscules = 'abcdefghijklmnopqrstuvwxyz';
    $speciaux = '!@#$%^&*()_+-=';
    $tous = $chiffres . $majuscules . $minuscules . $speciaux;

    // Garantir au moins un de chaque
    $motdepasse = $chiffres[rand(0, strlen($chiffres)-1)];
    $motdepasse .= $majuscules[rand(0, strlen($majuscules)-1)];
    $motdepasse .= $minuscules[rand(0, strlen($minuscules)-1)];
    $motdepasse .= $speciaux[rand(0, strlen($speciaux)-1)];

    // Compléter avec des caractères aléatoires
    for ($i = 4; $i < $longueur; $i++) {
        $motdepasse .= $tous[rand(0, strlen($tous)-1)];
    }

    // Mélanger le mot de passe pour plus de sécurité
    return str_shuffle($motdepasse);
}

function genererNomUtilisateurUnique($pdo, $nom) {
    // Nettoyer et mettre en majuscules le nom
    $nom_base = strtoupper(str_replace(' ', '', $nom));
    $nom_utilisateur = $nom_base;
    $compteur = 1;

    // Vérifier si le nom d'utilisateur existe déjà
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE nom_utilisateur = ?");
    
    while (true) {
        $stmt->execute([$nom_utilisateur]);
        if ($stmt->fetchColumn() == 0) {
            return $nom_utilisateur;
        }
        // Si le nom existe, ajouter un numéro
        $nom_utilisateur = $nom_base . $compteur;
        $compteur++;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $specialite = $_POST['specialite'];
    $id_departement = $_POST['id_departement'];
    
    // Générer le mot de passe
    $mot_de_passe_clair = genererMotDePasse(10);
    $mot_de_passe = password_hash($mot_de_passe_clair, PASSWORD_DEFAULT);

    $role = 'vacataire';
    
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=projet_web', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Générer un nom d'utilisateur unique
        $nom_utilisateur = genererNomUtilisateurUnique($pdo, $nom);
        
        // Générer l'email
        $email = strtolower($prenom . '.' . $nom) . '@etu.uae.ac.ma';
        $email = str_replace(' ', '', $email);

        $sql = "INSERT INTO utilisateurs (nom_utilisateur, nom, prenom, email, specialite, id_departement, mot_de_passe, role, date_creation, actif)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 1)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nom_utilisateur, $nom, $prenom, $email, $specialite, $id_departement, $mot_de_passe, $role]);

        // Rediriger vers la page de création avec message de succès et les informations de connexion
        header("Location: creer_vacataire.php?success=1&username=" . urlencode($nom_utilisateur) . "&email=" . urlencode($email) . "&password=" . urlencode($mot_de_passe_clair));
        exit;
    } catch (PDOException $e) {
        die("Erreur : " . $e->getMessage());
    }
} else {
    echo "Méthode non autorisée.";
}
?> 