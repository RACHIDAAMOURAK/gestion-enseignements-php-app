<?php
require_once 'includes/config.php';

// Simuler une connexion en tant qu'enseignant
$_SESSION['user_id'] = 11;  // ID de Marie Curie
$_SESSION['nom'] = 'Curie';
$_SESSION['prenom'] = 'Marie';
$_SESSION['role'] = 'enseignant';

// Rediriger vers la page principale
header('Location: index.php');
exit;
?> 