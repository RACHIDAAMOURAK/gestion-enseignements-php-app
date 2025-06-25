<?php
// Protection du fichier et gestion de session
// Démarrer une session si ce n'est pas déjà fait
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Inclusion de la connexion à la base de données
include 'db.php';
$pdo = connectDB();

// Vérification des permissions
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'chef_departement') {
    echo "Accès refusé.";
    exit();
}

// Définir que ce fichier est inclus pour les autres fichiers
define('INCLUDED', true);

$DEFAULT_MAX_HEURES = 192; // Valeur par défaut (par exemple 192h annuelles)

// Si le chef change le max d'heures via le formulaire
if (isset($_POST['update_max_heures'])) {
    $_SESSION['max_heures'] = (int)$_POST['max_heures'];
    $_SESSION['message'] = "Le maximum d'heures a été mis à jour avec succès";
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Fonction pour enregistrer une décision dans le journal
function enregistrerDecision($pdo, $type_entite, $id_entite, $id_utilisateur, $ancien_statut, $nouveau_statut, $commentaire) {
    $stmt = $pdo->prepare("INSERT INTO journal_decisions 
                          (type_entite, id_entite, id_utilisateur_decision, ancien_statut, nouveau_statut, commentaire, date_decision)
                          VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$type_entite, $id_entite, $_SESSION['user_id'], $ancien_statut, $nouveau_statut, $commentaire]);
}
                    // Ajouter cette fonction pour récupérer le nom du département
                    function getNomDepartement($pdo, $id_departement) {
                        $stmt = $pdo->prepare("SELECT nom FROM departements WHERE id = ?");
                        $stmt->execute([$id_departement]);
                        return $stmt->fetchColumn() ?: "Département";  // Valeur par défaut si non trouvé
                    }
// Traitement de la validation comme unité vacante
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'valider_vacante') {
    $id_ue = $_POST['id_ue'];
    $id_filiere = $_POST['id_filiere'];
    $type_cours = $_POST['type_cours'];
    
    // Récupérer les informations de l'UE
    $stmt = $pdo->prepare("SELECT ue.id, ue.code, ue.intitule, ue.specialite, 
                          ue.annee_universitaire, ue.semestre, 
                          f.nom as nom_filiere,
                          CASE ? 
                            WHEN 'CM' THEN ue.volume_horaire_cm
                            WHEN 'TD' THEN ue.volume_horaire_td
                            WHEN 'TP' THEN ue.volume_horaire_tp
                            ELSE 0
                          END as volume_horaire
                          FROM unites_enseignement ue
                          JOIN filieres f ON ue.id_filiere = f.id
                          WHERE ue.id = ? AND f.id = ?");
    $stmt->execute([$type_cours, $id_ue, $id_filiere]);
    $ue_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($ue_info) {
        // Vérifier si cette UE n'est pas déjà enregistrée comme vacante pour cette filière
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM unites_enseignement_vacantes 
                              WHERE id_unite_enseignement = ? AND annee_universitaire = ? 
                              AND semestre = ? AND type_cours = ? AND id_departement = ?
                              AND id_filiere = ?");
        $stmt->execute([$id_ue, $ue_info['annee_universitaire'], $ue_info['semestre'], 
                        $type_cours, $_SESSION['id_departement'], $id_filiere]);
        $exists = $stmt->fetchColumn();
        
        if (!$exists) {
            // Enregistrer dans la table unites_enseignement_vacantes
            $stmt = $pdo->prepare("INSERT INTO unites_enseignement_vacantes 
                                  (id_unite_enseignement, annee_universitaire, semestre, 
                                   type_cours, volume_horaire, date_declaration, id_departement, id_filiere)
                                  VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)");
            $stmt->execute([$id_ue, $ue_info['annee_universitaire'], $ue_info['semestre'], 
                           $type_cours, $ue_info['volume_horaire'], $_SESSION['id_departement'], $id_filiere]);
            //Enregistrer dans la table unites_vacantes_vacataires
                $stmt = $pdo->prepare("INSERT INTO unites_vacantes_vacataires 
                (id_unite_enseignement, annee_universitaire, semestre, 
                 type_cours, volume_horaire, date_declaration, id_departement, id_filiere)
                VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)");
$stmt->execute([$id_ue, $ue_info['annee_universitaire'], $ue_info['semestre'], 
         $type_cours, $ue_info['volume_horaire'], $_SESSION['id_departement'], $id_filiere]);
            
            // Enregistrer dans le journal des décisions
            $commentaire = "Validation comme unité vacante pour la filière " . $ue_info['nom_filiere'];
            enregistrerDecision($pdo, 'unite_vacante', $id_ue, $_SESSION['user_id'], 
                              'non_affecte', 'valide_vacante', $commentaire);
            
            $_SESSION['message'] = "L'unité d'enseignement a été validée comme vacante avec succès.";
        } else {
            $_SESSION['message'] = "Cette unité d'enseignement est déjà enregistrée comme vacante pour cette filière.";
        }
    } else {
        $_SESSION['message'] = "Erreur: Informations sur l'UE introuvables.";
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Récupérer le maximum d'heures actuel (depuis la session ou valeur par défaut)
$max_heures_global = isset($_SESSION['max_heures']) ? $_SESSION['max_heures'] : $DEFAULT_MAX_HEURES;

$id_departement = $_SESSION['id_departement'];

// Fonction pour récupérer toutes les spécialités des enseignants du département
function getAllTeacherSpecialties($pdo, $id_departement) {
    $query = "SELECT DISTINCT specialite FROM utilisateurs 
              WHERE id_departement = ? AND specialite IS NOT NULL";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$id_departement]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Récupération des UE avec affectation et non affectées par filière
function getAllUEs($pdo, $id_departement, $specialite_filter = null) {
    // D'abord, nous récupérons les spécialités disponibles dans le département
    $spec_query = "SELECT DISTINCT specialite FROM utilisateurs 
                  WHERE id_departement = ? AND specialite IS NOT NULL";
    $spec_stmt = $pdo->prepare($spec_query);
    $spec_stmt->execute([$id_departement]);
    $available_specialites = $spec_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Construire la requête principale pour les UE
    $query = "SELECT DISTINCT ue.id as id_ue, ue.code, ue.intitule, ue.specialite, 
              ue.volume_horaire_cm, ue.volume_horaire_td, ue.volume_horaire_tp,
              ue.id_filiere, f.nom as nom_filiere, 
              ue.annee_universitaire, ue.semestre
              FROM unites_enseignement ue
              LEFT JOIN filieres f ON ue.id_filiere = f.id
              WHERE ue.id_departement = ? AND (ue.specialite IS NULL";
    
    // Ajouter les spécialités disponibles si elles existent
    if (!empty($available_specialites)) {
        $placeholders = implode(',', array_fill(0, count($available_specialites), '?'));
        $query .= " OR ue.specialite IN ($placeholders)";
    }
    $query .= ")";
    
    $params = [$id_departement];
    if (!empty($available_specialites)) {
        $params = array_merge($params, $available_specialites);
    }
    
    // Ajouter le filtre de spécialité si présent
    if ($specialite_filter) {
        $query .= " AND (ue.specialite = ? OR ue.specialite IS NULL)";
        $params[] = $specialite_filter;
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $ues = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [];
    
    // Pour chaque UE, déterminer les types de cours et leur statut d'affectation
    foreach ($ues as $ue) {
        $types_cours = [];
        
        // Vérifier CM
        if ($ue['volume_horaire_cm'] > 0) {
            $stmt = $pdo->prepare("SELECT id, id_utilisateur, nom_utilisateur FROM historique_affectations 
                                  WHERE id_unite_enseignement = ? AND id_filiere = ? AND type_cours = 'CM'");
            $stmt->execute([$ue['id_ue'], $ue['id_filiere']]);
            $affectation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Vérifier si cette UE est déjà déclarée vacante pour cette filière
            $stmt_vacant = $pdo->prepare("SELECT COUNT(*) FROM unites_enseignement_vacantes 
                                        WHERE id_unite_enseignement = ? AND annee_universitaire = ? 
                                        AND semestre = ? AND type_cours = 'CM' AND id_departement = ?
                                        AND id_filiere = ?");
            $stmt_vacant->execute([$ue['id_ue'], $ue['annee_universitaire'], $ue['semestre'], 
                                 $id_departement, $ue['id_filiere']]);
            $is_vacant = $stmt_vacant->fetchColumn();
            
            $types_cours[] = [
                'type' => 'CM',
                'volume' => $ue['volume_horaire_cm'],
                'affecte' => $affectation ? true : false,
                'id_affectation' => $affectation ? $affectation['id'] : null,
                'id_utilisateur' => $affectation ? $affectation['id_utilisateur'] : null,
                'nom_utilisateur' => $affectation ? $affectation['nom_utilisateur'] : null,
                'is_vacant' => (bool)$is_vacant
            ];
        }
        
        // Vérifier TD
        if ($ue['volume_horaire_td'] > 0) {
            $stmt = $pdo->prepare("SELECT id, id_utilisateur, nom_utilisateur FROM historique_affectations 
                                  WHERE id_unite_enseignement = ? AND id_filiere = ? AND type_cours = 'TD'");
            $stmt->execute([$ue['id_ue'], $ue['id_filiere']]);
            $affectation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Vérifier si cette UE est déjà déclarée vacante pour cette filière
            $stmt_vacant = $pdo->prepare("SELECT COUNT(*) FROM unites_enseignement_vacantes 
                                        WHERE id_unite_enseignement = ? AND annee_universitaire = ? 
                                        AND semestre = ? AND type_cours = 'TD' AND id_departement = ?
                                        AND id_filiere = ?");
            $stmt_vacant->execute([$ue['id_ue'], $ue['annee_universitaire'], $ue['semestre'], 
                                 $id_departement, $ue['id_filiere']]);
            $is_vacant = $stmt_vacant->fetchColumn();
            
            $types_cours[] = [
                'type' => 'TD',
                'volume' => $ue['volume_horaire_td'],
                'affecte' => $affectation ? true : false,
                'id_affectation' => $affectation ? $affectation['id'] : null,
                'id_utilisateur' => $affectation ? $affectation['id_utilisateur'] : null,
                'nom_utilisateur' => $affectation ? $affectation['nom_utilisateur'] : null,
                'is_vacant' => (bool)$is_vacant
            ];
        }
        
        // Vérifier TP
        if ($ue['volume_horaire_tp'] > 0) {
            $stmt = $pdo->prepare("SELECT id, id_utilisateur, nom_utilisateur FROM historique_affectations 
                                  WHERE id_unite_enseignement = ? AND id_filiere = ? AND type_cours = 'TP'");
            $stmt->execute([$ue['id_ue'], $ue['id_filiere']]);
            $affectation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Vérifier si cette UE est déjà déclarée vacante pour cette filière
            $stmt_vacant = $pdo->prepare("SELECT COUNT(*) FROM unites_enseignement_vacantes 
                                        WHERE id_unite_enseignement = ? AND annee_universitaire = ? 
                                        AND semestre = ? AND type_cours = 'TP' AND id_departement = ?
                                        AND id_filiere = ?");
            $stmt_vacant->execute([$ue['id_ue'], $ue['annee_universitaire'], $ue['semestre'], 
                                 $id_departement, $ue['id_filiere']]);
            $is_vacant = $stmt_vacant->fetchColumn();
            
            $types_cours[] = [
                'type' => 'TP',
                'volume' => $ue['volume_horaire_tp'],
                'affecte' => $affectation ? true : false,
                'id_affectation' => $affectation ? $affectation['id'] : null,
                'id_utilisateur' => $affectation ? $affectation['id_utilisateur'] : null,
                'nom_utilisateur' => $affectation ? $affectation['nom_utilisateur'] : null,
                'is_vacant' => (bool)$is_vacant
            ];
        }
        
        // N'ajouter l'UE au résultat que si elle a des types de cours
        if (!empty($types_cours)) {
            $ue['types_cours'] = $types_cours;
            $result[] = $ue;
        }
    }
    
    return $result;
}

// Récupération de tous les enseignants, même ceux qui ont atteint leur maximum d'heures
function getAllTeachers($pdo, $id_departement, $max_heures_global, $specialite = null) {
    // Inclure tous les enseignants, même ceux sans affectations
    $query = "SELECT u.id, u.nom, u.prenom, u.specialite, u.role, 
              COALESCE(SUM(
                CASE ha.type_cours 
                  WHEN 'CM' THEN ue.volume_horaire_cm
                  WHEN 'TD' THEN ue.volume_horaire_td
                  WHEN 'TP' THEN ue.volume_horaire_tp
                  ELSE 0
                END
              ), 0) as heures_effectuees
              FROM utilisateurs u
              LEFT JOIN historique_affectations ha ON u.id = ha.id_utilisateur
              LEFT JOIN unites_enseignement ue ON ha.id_unite_enseignement = ue.id
              WHERE u.id_departement = ? AND u.role IN ('enseignant', 'coordonnateur')";
    
    if ($specialite) {
        $query .= " AND u.specialite = ?";
        $params = [$id_departement, $specialite];
    } else {
        $params = [$id_departement];
    }
    
    $query .= " GROUP BY u.id ORDER BY u.nom, u.prenom";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $enseignants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Marquer les enseignants qui ont atteint leur maximum d'heures
    foreach ($enseignants as &$enseignant) {
        $enseignant['max_atteint'] = ($enseignant['heures_effectuees'] >= $max_heures_global);
    }
    
    return $enseignants;
}

// Traitement des affectations (nouvelles ou modifications)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && ($_POST['action'] === 'affecter' || $_POST['action'] === 'modifier')) {
    $id_ue = $_POST['id_ue'];
    $id_filiere = $_POST['id_filiere'];
    $id_utilisateur = $_POST['id_utilisateur']; // Peut être vide pour "Non affecté"
    $type_cours = $_POST['type_cours'];
    $commentaire = isset($_POST['commentaire']) ? $_POST['commentaire'] : '';
    $id_affectation = isset($_POST['id_affectation']) ? $_POST['id_affectation'] : null;
    
    // Vérifier si cette UE est déjà déclarée vacante pour ce type de cours et cette filière
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM unites_enseignement_vacantes 
                          WHERE id_unite_enseignement = ? AND type_cours = ? AND id_departement = ?
                          AND id_filiere = ?");
    $stmt->execute([$id_ue, $type_cours, $id_departement, $id_filiere]);
    $is_vacant = $stmt->fetchColumn();
    
    if ($is_vacant) {
        $_SESSION['message'] = "Erreur: Cette unité d'enseignement est déjà déclarée vacante pour ce type de cours et cette filière. Vous ne pouvez pas l'affecter.";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Si on modifie pour rendre "non affecté"
    if ($_POST['action'] === 'modifier' && empty($id_utilisateur) && $id_affectation) {
        // Récupérer l'ancienne affectation pour le journal
        $stmt = $pdo->prepare("SELECT id_utilisateur, nom_utilisateur FROM historique_affectations WHERE id = ?");
        $stmt->execute([$id_affectation]);
        $old_affectation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Supprimer l'affectation existante
        $stmt = $pdo->prepare("DELETE FROM historique_affectations WHERE id = ?");
        $stmt->execute([$id_affectation]);
        
        // Mettre à jour les voeux éventuels
        $stmt = $pdo->prepare("UPDATE voeux_professeurs SET statut = 'en attente' 
                              WHERE id_ue = ? AND id_filiere = ? AND type_ue = ?");
        $stmt->execute([$id_ue, $id_filiere, $type_cours]);
        
        // Enregistrer dans le journal des décisions
        $commentaire_journal = "Suppression de l'affectation de " . $old_affectation['nom_utilisateur'] . " pour " . $type_cours;
        enregistrerDecision($pdo, 'affectation', $id_affectation, $_SESSION['user_id'], 
                          'affecte', 'non_affecte', $commentaire_journal);
        
        $_SESSION['message'] = "L'affectation a été supprimée. Le cours est maintenant sans affectation.";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Pour les autres cas où un enseignant est sélectionné
    if (!empty($id_utilisateur)) {
        // Récupérer les informations nécessaires
        $stmt = $pdo->prepare("SELECT u.id as id_utilisateur, u.nom, u.prenom, u.role, u.specialite as specialite_prof,
                              ue.id as id_unite_enseignement, ue.code, ue.intitule, ue.specialite as specialite_ue,
                              ue.volume_horaire_cm, ue.volume_horaire_td, ue.volume_horaire_tp,
                              f.id as id_filiere, f.nom as nom_filiere,
                              ue.annee_universitaire, ue.semestre
                              FROM utilisateurs u
                              CROSS JOIN unites_enseignement ue
                              LEFT JOIN filieres f ON ue.id_filiere = f.id
                              WHERE u.id = ? AND ue.id = ? AND ue.id_filiere = ?");
        $stmt->execute([$id_utilisateur, $id_ue, $id_filiere]);
        $infos = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($infos) {
            // Vérifier si les spécialités correspondent - Blocage si non correspondance
            if ($infos['specialite_prof'] !== $infos['specialite_ue'] && !empty($infos['specialite_ue'])) {
                $_SESSION['message'] = "Erreur: La spécialité de l'enseignant ne correspond pas à celle de l'UE. L'affectation n'a pas été effectuée.";
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            
            // Déterminer le volume horaire en fonction du type
            $volume_field = "volume_horaire_" . strtolower($type_cours);
            $volume_horaire = $infos[$volume_field] ?? 0;
            
            // Si c'est une modification, on récupère l'ancien enseignant pour ajuster ses heures
            $ancien_utilisateur_id = null;
            if ($_POST['action'] === 'modifier' && $id_affectation) {
                $stmt = $pdo->prepare("SELECT id_utilisateur, nom_utilisateur FROM historique_affectations WHERE id = ?");
                $stmt->execute([$id_affectation]);
                $ancien_affectation = $stmt->fetch(PDO::FETCH_ASSOC);
                $ancien_utilisateur_id = $ancien_affectation['id_utilisateur'];
            }
            
            // Vérifier si l'enseignant a assez d'heures disponibles (sauf s'il est déjà affecté)
            if ($ancien_utilisateur_id != $id_utilisateur) {
                $stmt = $pdo->prepare("SELECT COALESCE(SUM(
                    CASE type_cours 
                      WHEN 'CM' THEN (SELECT volume_horaire_cm FROM unites_enseignement WHERE id = id_unite_enseignement)
                      WHEN 'TD' THEN (SELECT volume_horaire_td FROM unites_enseignement WHERE id = id_unite_enseignement)
                      WHEN 'TP' THEN (SELECT volume_horaire_tp FROM unites_enseignement WHERE id = id_unite_enseignement)
                      ELSE 0
                    END
                  ), 0) as heures_effectuees
                  FROM historique_affectations 
                  WHERE id_utilisateur = ?");
                $stmt->execute([$id_utilisateur]);
                $heures_effectuees = $stmt->fetchColumn();
                
                // Utiliser le maximum d'heures global au lieu de la valeur de la base de données
                if (($heures_effectuees + $volume_horaire) > $max_heures_global) {
                    $_SESSION['message'] = "Attention: L'enseignant dépassera son maximum d'heures ({$max_heures_global}h). Affectation créée quand même.";
                }
            }
            
            // Action selon si c'est une création ou modification
            if ($_POST['action'] === 'affecter') {
                // Vérifier si cette affectation existe déjà pour cette UE, filière et type de cours
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM historique_affectations 
                                      WHERE id_unite_enseignement = ? AND id_filiere = ? AND type_cours = ?");
                $stmt->execute([$id_ue, $id_filiere, $type_cours]);
                $exists = (bool)$stmt->fetchColumn();
                
                if (!$exists) {
                    // Enregistrer dans la table historique_affectations

$nom_departement = getNomDepartement($pdo, $id_departement);

                    $stmt = $pdo->prepare("INSERT INTO historique_affectations 
                                         (id_utilisateur, nom_utilisateur, role, 
                                          id_unite_enseignement, code_ue, intitule_ue, 
                                          id_filiere, nom_filiere, id_departement, nom_departement, 
                                          annee_universitaire, semestre, type_cours, volume_horaire, 
                                          commentaire_chef, statut, date_affectation)
                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'affecté', NOW())");
                    $stmt->execute([
                        $infos['id_utilisateur'],
                        $infos['nom'] . ' ' . $infos['prenom'],
                        $infos['role'],
                        $infos['id_unite_enseignement'],
                        $infos['code'],
                        $infos['intitule'],
                        $infos['id_filiere'],
                        $infos['nom_filiere'],
                        $id_departement,
                        $nom_departement, // Vous devriez remplacer cela par le vrai nom du département
                        $infos['annee_universitaire'],
                        $infos['semestre'],
                        $type_cours,
                        $volume_horaire,
                        $commentaire
                    ]);
                    
                    $id_nouvelle_affectation = $pdo->lastInsertId();
                    
                    // Enregistrer dans le journal des décisions
                    $commentaire_journal = "Nouvelle affectation pour " . $type_cours . " à " . $infos['nom'] . " " . $infos['prenom']."avec un comentaire:". $commentaire;
                    enregistrerDecision($pdo, 'affectation', $id_nouvelle_affectation, $_SESSION['user_id'], 
                                      'non_affecte', 'affecte', $commentaire_journal);
                    
                    // Mettre à jour les voeux
                    $stmt = $pdo->prepare("UPDATE voeux_professeurs SET statut = 'validé' 
                                          WHERE id_ue = ? AND id_filiere = ? AND type_ue = ?");
                    $stmt->execute([$id_ue, $id_filiere, $type_cours]);

                    if (!isset($_SESSION['message'])) {
                        $_SESSION['message'] = "Affectation réussie pour {$type_cours} à {$infos['nom']} {$infos['prenom']}";
                    }
                } else {
                    $_SESSION['message'] = "Erreur: Cette affectation existe déjà";
                }
            } else if ($_POST['action'] === 'modifier' && $id_affectation) {
                // D'abord supprimer l'ancienne affectation
                $stmt = $pdo->prepare("DELETE FROM historique_affectations WHERE id = ?");
                $stmt->execute([$id_affectation]);
                
                // Puis créer une nouvelle avec les informations mises à jour
                $stmt = $pdo->prepare("INSERT INTO historique_affectations 
                                     (id_utilisateur, nom_utilisateur, role, 
                                      id_unite_enseignement, code_ue, intitule_ue, 
                                      id_filiere, nom_filiere, id_departement, nom_departement, 
                                      annee_universitaire, semestre, type_cours, volume_horaire, 
                                      commentaire_chef, statut, date_affectation)
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'affecté', NOW())");
                $stmt->execute([
                    $infos['id_utilisateur'],
                    $infos['nom'] . ' ' . $infos['prenom'],
                    $infos['role'],
                    $infos['id_unite_enseignement'],
                    $infos['code'],
                    $infos['intitule'],
                    $infos['id_filiere'],
                    $infos['nom_filiere'],
                    $id_departement,
                    $nom_departement, // À remplacer par le nom réel du département
                    $infos['annee_universitaire'],
                    $infos['semestre'],
                    $type_cours,
                    $volume_horaire,
                    $commentaire
                ]);
                
                $id_nouvelle_affectation = $pdo->lastInsertId();
                
                // Enregistrer dans le journal des décisions
                $commentaire_journal = "Modification d'affectation de " . $ancien_affectation['nom_utilisateur'] . 
                                      " vers " . $infos['nom'] . " " . $infos['prenom'] . " pour " . $type_cours;
                enregistrerDecision($pdo, 'affectation', $id_nouvelle_affectation, $_SESSION['user_id'], 
                                  'affecte', 'affecte_modifie', $commentaire_journal);
                
                $_SESSION['message'] = "Modification de l'affectation réussie pour {$type_cours} à {$infos['nom']} {$infos['prenom']}";
            }
        } else {
            $_SESSION['message'] = "Erreur: Informations introuvables";
        }
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Récupération de toutes les spécialités des enseignants du département
$specialites_enseignants = getAllTeacherSpecialties($pdo, $id_departement);

// Si un filtre de spécialité est appliqué
$specialite_filter = isset($_GET['specialite']) ? $_GET['specialite'] : null;

// Récupération de toutes les UE (affectées et non affectées) avec filtrage par spécialité
$ues = getAllUEs($pdo, $id_departement, $specialite_filter);

// Récupération de tous les enseignants avec filtrage par spécialité
$tous_enseignants = getAllTeachers($pdo, $id_departement, $max_heures_global, $specialite_filter);

// Récupérer la liste des spécialités disponibles
$stmt = $pdo->prepare("SELECT DISTINCT specialite FROM utilisateurs WHERE id_departement = ? AND specialite IS NOT NULL");
$stmt->execute([$id_departement]);
$specialites = $stmt->fetchAll(PDO::FETCH_COLUMN);
include 'header.php' ;
?>


<div class="main-container">
    <div class="content">
<!-- Contenu spécifique à la page -->
<div class="content-wrapper">
    <h2>Affectation des Enseignants et Validation des Unités Vacantes</h2>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert <?= strpos($_SESSION['message'], 'Erreur') !== false ? 'alert-danger' : 'alert-success' ?>" id="alertMessage">
            <i class="fas <?= strpos($_SESSION['message'], 'Erreur') !== false ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i>
            <?= $_SESSION['message']; unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>

<!-- CSS spécifique à cette page -->
<style>
    :root {
        --primary-bg: #1B2438;
        --secondary-bg: #1F2B47;
        --accent-color: #31B7D1;
        --text-color: #FFFFFF;
        --text-muted: #7086AB;
        --border-color: #2A3854;
        --success-color: #31B7D1; /* Bleu turquoise */
        --warning-color: #FFA726; /* Orange */
        --danger-color: #EF5350; /* Rouge */
        --info-color: #5C6BC0; /* Bleu indigo */
                --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                --border-radius: 8px;
                --transition: all 0.3s ease;
    }
    
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
                font-family: 'Roboto', Arial, sans-serif;
        background-color: var(--primary-bg);
        color: var(--text-color);
                line-height: 1.6;
    }
    
    /* Content wrapper */
    .content-wrapper {
                padding: 1.5rem;
        flex: 1;
                animation: fadeIn 0.3s ease;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-10px); }
                to { opacity: 1; transform: translateY(0); }
    }
    
    h2 {
        color: var(--text-color);
                margin-bottom: 1.5rem;
                font-size: 1.75rem;
                font-weight: 600;
                position: relative;
                padding-bottom: 0.5rem;
                letter-spacing: 0.5px;
            }
            
            h2:after {
                content: '';
                position: absolute;
                left: 0;
                bottom: 0;
                height: 3px;
                width: 50px;
                background: var(--accent-color);
                border-radius: 3px;
            }
            

            table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            background: var(--secondary-bg);
        }

        th, td {
            border: 1px solid var(--border-color);
            padding: 12px;
            text-align: left;
            color: var(--text-color);
        }

        th {
            background-color: var(--primary-bg);
            color: var(--text-muted);
        }

        

        tr:hover {
            background-color: rgba(49, 183, 209, 0.1);
        }
            /* Search and filter components */
            .search-wrapper,
            .filter-container,
            .filter-section {
                margin-bottom: 1.5rem;
        background-color: var(--secondary-bg);
                padding: 1.25rem;
                border-radius: var(--border-radius);
        border: 1px solid var(--border-color);
                box-shadow: var(--shadow);
                transition: var(--transition);
            }
            
            .search-wrapper:hover,
            .filter-container:hover,
            .filter-section:hover {
                box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
                transform: translateY(-2px);
            }
            
            .filter-container,
            .filter-section {
                display: flex;
                flex-wrap: wrap;
                gap: 1rem;
            }
            
            .filter-item {
                flex: 1;
                min-width: 200px;
            }
            
            .filter-item label {
                display: block;
                margin-bottom: 0.5rem;
                color: var(--text-color);
                font-weight: 500;
                font-size: 0.95rem;
            }
            
            /* Form elements */
            input[type="text"],
            select,
            input[type="number"],
            textarea {
        width: 100%;
                padding: 0.75rem 1rem;
                background-color: rgba(27, 36, 56, 0.8);
        border: 1px solid var(--border-color);
                border-radius: var(--border-radius);
        color: var(--text-color);
                margin-bottom: 1rem;
                font-size: 0.95rem;
                transition: var(--transition);
    }
    
            input[type="text"]:focus,
            select:focus,
            input[type="number"]:focus,
            textarea:focus {
        outline: none;
        border-color: var(--accent-color);
                box-shadow: 0 0 0 3px rgba(49, 183, 209, 0.2);
            }
            
            select option {
        background-color: var(--secondary-bg);
                color: var(--text-color);
                padding: 0.5rem;
    }
    
            textarea {
                resize: vertical;
                min-height: 100px;
    }
    
            /* Button styles */
    .action-buttons {
        display: flex;
                gap: 0.5rem;
                flex-wrap: wrap;
    }
    
    .btn {
                padding: 0.75rem 1.25rem;
                border-radius: var(--border-radius);
        cursor: pointer;
                font-weight: 600;
                transition: var(--transition);
        border: 1px solid transparent;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                font-size: 0.95rem;
                letter-spacing: 0.3px;
            }
            
            .btn i {
                font-size: 1rem;
    }
    
    .btn:hover {
        transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }
    
    .btn-primary, .affecter-btn {
                background-color: rgba(49, 183, 209, 0.15);
        color: var(--accent-color);
        border-color: var(--accent-color);
    }
    
    .btn-primary:hover, .affecter-btn:hover {
                background-color: rgba(49, 183, 209, 0.25);
                box-shadow: 0 4px 8px rgba(49, 183, 209, 0.2);
    }
    
    .btn-danger, .cancel-btn {
                background-color: rgba(239, 83, 80, 0.15);
        color: var(--danger-color);
        border-color: var(--danger-color);
    }
    
    .btn-danger:hover, .cancel-btn:hover {
                background-color: rgba(239, 83, 80, 0.25);
                box-shadow: 0 4px 8px rgba(239, 83, 80, 0.2);
    }
    
    .btn-warning, .modifier-btn {
                background-color: rgba(255, 167, 38, 0.15);
        color: var(--warning-color);
        border-color: var(--warning-color);
    }
    
    .btn-warning:hover, .modifier-btn:hover {
                background-color: rgba(255, 167, 38, 0.25);
                box-shadow: 0 4px 8px rgba(255, 167, 38, 0.2);
    }
    
    .vacante-btn, .valider-vacante-btn {
                background-color: rgba(92, 107, 192, 0.15);
        color: var(--info-color);
        border-color: var(--info-color);
    }
    
    .vacante-btn:hover, .valider-vacante-btn:hover {
                background-color: rgba(92, 107, 192, 0.25);
                box-shadow: 0 4px 8px rgba(92, 107, 192, 0.2);
            }
            
            .btn-sm {
                padding: 0.5rem 0.75rem;
                font-size: 0.85rem;
            }
            
            /* Modal styles */
    .modal, .confirmation-modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
                background-color: rgba(0, 0, 0, 0.5);
                backdrop-filter: blur(3px);
                animation: fadeIn 0.3s ease;
    }
    
    .modal-content, .confirmation-content {
        background-color: var(--secondary-bg);
                margin: 10% auto;
                padding: 1.5rem;
        border: 1px solid var(--border-color);
                border-radius: var(--border-radius);
                width: 90%;
        max-width: 500px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
                animation: slideIn 0.3s ease;
            }
            
            @keyframes slideIn {
                from { transform: translateY(-30px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            
            .modal-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 1.25rem;
                padding-bottom: 0.75rem;
                border-bottom: 1px solid var(--border-color);
            }
            
            .modal-title {
                color: var(--accent-color);
                font-size: 1.25rem;
                font-weight: 600;
    }
    
    .close-modal {
        color: var(--text-muted);
                font-size: 1.5rem;
                font-weight: 600;
        cursor: pointer;
                transition: var(--transition);
    }
    
    .close-modal:hover {
        color: var(--accent-color);
               
            }
            
            .modal-body {
                margin-bottom: 1.25rem;
            }
            
            .modal-footer {
                display: flex;
                justify-content: flex-end;
                gap: 0.75rem;
                padding-top: 0.75rem;
                border-top: 1px solid var(--border-color);
            }
            
            /* Alert styles */
    .alert {
                padding: 1rem 1.5rem;
                margin-bottom: 1.5rem;
                border-radius: var(--border-radius);
        display: flex;
        align-items: center;
                gap: 0.75rem;
        border-left: 4px solid transparent;
                animation: fadeIn 0.3s ease forwards;
                box-shadow: var(--shadow);
    }
    
    .alert-success {
                background-color: rgba(49, 183, 209, 0.15);
        border-left-color: var(--accent-color);
                color: var(--accent-color);
    }
    
    .alert-danger, .alert-error {
                background-color: rgba(239, 83, 80, 0.15);
        border-left-color: var(--danger-color);
                color: var(--danger-color);
    }
    
    .confirmation-buttons {
        display: flex;
        justify-content: space-between;
                margin-top: 1.5rem;
            }
            
            /* Layout components */
    .flex-container {
        display: flex;
        flex-wrap: wrap;
                gap: 1.5rem;
                margin-bottom: 1.5rem;
    }
    
    .column {
        flex: 1;
                min-width: 300px;
    }
    
    .card {
        background-color: var(--secondary-bg);
                border-radius: var(--border-radius);
                padding: 1.5rem;
                margin-bottom: 1.25rem;
        border: 1px solid var(--border-color);
                transition: var(--transition);
                box-shadow: var(--shadow);
    }
    
    .card:hover {
                transform: translateY(-3px);
                box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
            }
            
            .card-header {
                margin-bottom: 1rem;
                padding-bottom: 0.75rem;
                border-bottom: 1px solid var(--border-color);
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .card-title {
                color: var(--accent-color);
                font-size: 1.1rem;
                font-weight: 600;
    }
    
   
    
    .max-hours-form {
        display: flex;
        align-items: center;
                gap: 0.75rem;
                flex-wrap: wrap;
            }
            
            .max-hours-form input[type="number"] {
                max-width: 120px;
                margin-bottom: 0;
    }
    
    .max-hours-badge {
                background: rgba(255, 167, 38, 0.15);
        color: var(--warning-color);
                padding: 0.35rem 0.75rem;
                border-radius: 50px;
                font-weight: 600;
                font-size: 0.9rem;
        border: 1px solid var(--warning-color);
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
    }
    
    .ue-title {
                font-weight: 600;
                font-size: 1.05rem;
                margin-bottom: 0.75rem;
        color: var(--accent-color);
    }
    
    .enseignant-item {
                padding: 0.75rem;
        border-bottom: 1px solid var(--border-color);
                transition: var(--transition);
            }
            
            .enseignant-item:hover {
                background-color: rgba(49, 183, 209, 0.05);
            }
            
            .enseignant-item:last-child {
                border-bottom: none;
            }
            
            /* Badge styles */
            .badge {
                display: inline-flex;
                align-items: center;
                padding: 0.35rem 0.75rem;
                border-radius: 50px;
                font-size: 0.8rem;
                font-weight: 600;
                margin-right: 0.5rem;
    }
    
    .type-badge {
                background: rgba(49, 183, 209, 0.15);
        color: var(--accent-color);
                border: 1px solid var(--accent-color);
    }
    
    .specialite-badge {
                background: rgba(92, 107, 192, 0.15);
        color: var(--info-color);
        border: 1px solid var(--info-color);
    }
    
    .filiere-badge {
                background: rgba(255, 167, 38, 0.15);
        color: var(--warning-color);
        border: 1px solid var(--warning-color);
    }
    
    .hours-info {
                background: rgba(49, 183, 209, 0.15);
        color: var(--accent-color);
        border: 1px solid var(--accent-color);
    }
    
            .filter-active {
                background-color: rgba(49, 183, 209, 0.25);
                border: 2px solid var(--accent-color);
            }
            
            /* Responsive adjustments */
            @media (max-width: 992px) {
                .filter-container,
                .filter-section {
                    flex-direction: column;
                }
                
                .filter-item {
                    min-width: 100%;
                }
                
                .max-hours-form {
                    flex-direction: column;
                    align-items: flex-start;
                }
                
                .max-hours-form input[type="number"] {
                    max-width: 100%;
                    margin-bottom: 1rem;
                }
            }
            
            @media (max-width: 768px) {
                .content-wrapper {
                    padding: 1rem;
                }
                
                .action-buttons {
                    flex-direction: column;
                }
                
                .action-buttons .btn {
                    width: 100%;
                }
                
                .table-container {
                    padding: 1rem;
                }
                
                th, td {
                    padding: 0.75rem;
                }
    }

/* Ajout pour la nouvelle disposition */
.enseignants-table-container {
    width: 100%;
    margin-bottom: 2rem;
}
.enseignants-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--secondary-bg);
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--shadow);
}
.enseignants-table th, .enseignants-table td {
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
    color: var(--text-color);
}
.enseignants-table th {
    background: rgba(49, 183, 209, 0.1);
    color: var(--text-muted);
    text-transform: uppercase;
    font-size: 0.95rem;
}
.enseignants-table tr:last-child td {
    border-bottom: none;
}
.enseignants-table tr:hover {
    background: rgba(49, 183, 209, 0.05);
}

.ues-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    margin-top: 2rem;
    width: 100%;
    max-width: 100%;
}
.ue-card {
    background: var(--secondary-bg);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
    padding: 1.5rem;
    flex: 1 1 350px;
    min-width: 320px;
    max-width: 100%;
    margin-bottom: 1.5rem;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
}
.ue-title {
    font-weight: 600;
    font-size: 1.08rem;
    margin-bottom: 0.5rem;
    color: var(--accent-color);
}
.ue-info-block {
    margin-bottom: 1rem;
    color: var(--text-muted);
    font-size: 0.97rem;
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
}
.ue-info-label {
    font-weight: 500;
    color: var(--text-muted);
    margin-right: 0.3rem;
}
.types-cours-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.type-cours-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.5rem 0.2rem 0.5rem 0;
    border-radius: 6px;
    transition: background 0.15s;
}
.type-cours-row:hover {
    background: rgba(49,183,209,0.07);
}
.type-cours-label {
    font-weight: 500;
    color: var(--text-color);
    font-size: 1rem;
}
.type-cours-actions {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}
.flat-action-btn {
    background: none;
    border: none;
    color: var(--text-muted);
    font-weight: 600;
    font-size: 0.97rem;
    padding: 0.35rem 0.9rem;
    border-radius: 6px;
    cursor: pointer;
    transition: color 0.18s, background 0.18s;
    outline: none;
    margin-left: 0.1rem;
}
.flat-action-btn:disabled {
    color: #555a6a;
    background: none;
    cursor: not-allowed;
    opacity: 0.7;
}
.flat-action-btn:not(:disabled):hover {
    color: var(--accent-color);
    background: rgba(49,183,209,0.10);
}
.flat-action-btn:not(:disabled):active {
    color: #fff;
    background: var(--accent-color);
}
.flat-action-btn.warning:not(:disabled):hover {
    color: #FFA726;
    background: rgba(255,167,38,0.10);
}
.flat-action-btn.warning:not(:disabled):active {
    color: #fff;
    background: #FFA726;
}
@media (max-width: 900px) {
    .ues-grid {
        flex-direction: column;
        gap: 1rem;
    }
    .ue-card {
        max-width: 100%;
        min-width: 0;
    }
}

/* Bouton principal dans les modals */
.affecter-btn, .modifier-btn, .confirm-btn ,.cancel-btn{
    background: var(--accent-color);
    color: var(--text-color);
    border: none;
    border-radius: 6px;
    padding: 0.65rem 1.5rem;
    font-weight: 600;
    font-size: 1rem;
    box-shadow: 0 2px 8px rgba(49,183,209,0.10);
    transition: background 0.18s, color 0.18s, box-shadow 0.18s;
    cursor: pointer;
}
.affecter-btn:hover, .modifier-btn:hover, .confirm-btn:hover,.cancel-btn:hover {
    background: #3ee0ff;
    color: var(--primary-bg);
    box-shadow: 0 4px 16px rgba(49,183,209,0.18);
}
.affecter-btn:active, .modifier-btn:active, .confirm-btn:active,.cancel-btn:active  {
    background: #229ab3;
    color: #fff;
}


</style>


<?php if (isset($_SESSION['message'])): ?>
    <div id="alert-message" class="alert <?= strpos($_SESSION['message'], 'Erreur') !== false ? 'alert-error' : 'alert-success' ?>">
        <?php 
        echo $_SESSION['message']; 
        unset($_SESSION['message']);
        ?>
    </div>
<?php endif; ?>

<div class="filter-section">
    <form method="get" action="">
        <select name="specialite" onchange="this.form.submit()" class="<?= $specialite_filter ? 'filter-active' : '' ?>">
            <option value="">Toutes les spécialités</option>
            <?php foreach ($specialites as $specialite): ?>
                <option value="<?= htmlspecialchars($specialite) ?>" <?= $specialite_filter === $specialite ? 'selected' : '' ?>>
                    <?= htmlspecialchars($specialite) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
    
    <!-- Formulaire pour définir le maximum d'heures global -->
    <form method="post" action="" class="max-hours-form">
        <label for="max_heures">Maximum d'heures par enseignant:</label>
        <input type="number" name="max_heures" id="max_heures" min="1" value="<?= $max_heures_global ?>" required>
        <input type="hidden" name="update_max_heures" value="1">
        <button type="submit" class="affecter-btn">Mettre à jour</button>
        <div class="max-hours-badge">Maximum actuel: <?= $max_heures_global ?>h</div>
    </form>
</div>

<!-- Nouvelle disposition : tableau enseignants -->
<div class="enseignants-table-container">
    <h3>Enseignants disponibles <?= $specialite_filter ? "- Spécialité: $specialite_filter" : "" ?></h3>
    <?php if (empty($tous_enseignants)): ?>
        <div class="card">
            <p>Aucun enseignant disponible<?= $specialite_filter ? " pour la spécialité $specialite_filter" : "" ?>.</p>
        </div>
    <?php else: ?>
        <table class="enseignants-table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Spécialité</th>
                    <th>Rôle</th>
                    <th>Heures effectuées</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tous_enseignants as $enseignant): ?>
                    <tr class="<?= $enseignant['max_atteint'] ? 'max-atteint' : '' ?>">
                        <td><?= htmlspecialchars($enseignant['nom']) ?></td>
                        <td><?= htmlspecialchars($enseignant['prenom']) ?></td>
                        <td><?= htmlspecialchars($enseignant['specialite']) ?></td>
                        <td><?= htmlspecialchars($enseignant['role']) ?></td>
                        <td><?= $enseignant['heures_effectuees'] ?>h / <?= $max_heures_global ?>h</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Nouvelle disposition : cartes UE -->
<h3 style="margin-top:2.5rem;">Unités d'Enseignement <?= $specialite_filter ? "- Spécialité: $specialite_filter" : "" ?></h3>
<div class="ues-grid">
    <?php if (empty($ues)): ?>
        <div class="card">
            <p>Aucune UE disponible<?= $specialite_filter ? " pour la spécialité $specialite_filter" : "" ?>.</p>
        </div>
    <?php else: ?>
        <?php foreach ($ues as $ue): ?>
            <div class="ue-card">
                <div class="ue-title">
                    <?= htmlspecialchars($ue['code'] . ' - ' . $ue['intitule']) ?>
                </div>
                <div class="ue-info-block">
                    <span><span class="ue-info-label">Filière :</span><?= htmlspecialchars($ue['nom_filiere']) ?></span>
                    <?php if (!empty($ue['specialite'])): ?>
                        <span><span class="ue-info-label">Spécialité :</span><?= htmlspecialchars($ue['specialite']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="ue-info-block" style="margin-top:-0.7rem; margin-bottom:0.7rem;">
                    <span><span class="ue-info-label">Année :</span><?= htmlspecialchars($ue['annee_universitaire']) ?></span>
                    <span><span class="ue-info-label">Semestre :</span><?= htmlspecialchars($ue['semestre']) ?></span>
                </div>
                <div class="types-cours-list">
                    <?php foreach ($ue['types_cours'] as $type_cours): ?>
                        <div class="type-cours-row">
                            <span class="type-cours-label">
                                <?= $type_cours['type'] ?> : <?= $type_cours['volume'] ?>h
                            </span>
                            <span style="flex: 1; text-align: center; color: #31B7D1; margin-top: 10px;">
                                <?php if ($type_cours['affecte']): ?>
                                    Affecté à: <strong><?= htmlspecialchars($type_cours['nom_utilisateur']) ?></strong>
                                <?php endif; ?>
                                <?php if ($type_cours['is_vacant']): ?>
                                    Validé comme vacante
                                <?php endif; ?>
                            </span>
                            <span class="type-cours-actions">
                                <?php if ($type_cours['is_vacant']): ?>
                                    <button type="button" class="flat-action-btn warning" disabled>
                                        Déjà validé
                                    </button>
                                <?php elseif ($type_cours['affecte']): ?>
                                    <button type="button" class="flat-action-btn" 
                                            onclick="openModifierModal(<?= $ue['id_ue'] ?>, <?= $ue['id_filiere'] ?>, '<?= $type_cours['type'] ?>', <?= $type_cours['id_affectation'] ?>, '<?= $ue['specialite'] ?>')">
                                        Modifier
                                    </button>
                                    <button type="button" class="flat-action-btn warning" disabled>
                                        Valider vacante
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="flat-action-btn" 
                                            onclick="openAffecterModal(<?= $ue['id_ue'] ?>, <?= $ue['id_filiere'] ?>, '<?= $type_cours['type'] ?>', '<?= $ue['specialite'] ?>')">
                                        Affecter
                                    </button>
                                    <button type="button" class="flat-action-btn warning" 
                                            onclick="openConfirmationModal(<?= $ue['id_ue'] ?>, <?= $ue['id_filiere'] ?>, '<?= $type_cours['type'] ?>')">
                                        Valider vacante
                                    </button>
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal pour affecter -->
<div id="affecterModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal('affecterModal')">&times;</span>
        <h3>Affecter un enseignant</h3>
        <form id="affecterForm" method="post" action="">
            <input type="hidden" name="action" value="affecter">
            <input type="hidden" name="id_ue" id="aff_id_ue">
            <input type="hidden" name="id_filiere" id="aff_id_filiere">
            <input type="hidden" name="type_cours" id="aff_type_cours">
            
            <div>
                <label for="id_utilisateur">Sélectionner un enseignant:</label>
                <select name="id_utilisateur" id="aff_id_utilisateur" required>
                    <option value="" class="non-affecte-option">--- Non affecté ---</option>
                    <?php foreach ($tous_enseignants as $enseignant): ?>
                        <option value="<?= $enseignant['id'] ?>" 
                            data-specialite="<?= htmlspecialchars($enseignant['specialite']) ?>"
                            <?= $enseignant['max_atteint'] ? 'data-max-atteint="1"' : '' ?>>
                            <?= htmlspecialchars($enseignant['nom'] . ' ' . $enseignant['prenom']) ?> 
                            (<?= $enseignant['heures_effectuees'] ?>h / <?= $max_heures_global ?>h)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="commentaire">Commentaire (optionnel):</label>
                <textarea name="commentaire" id="aff_commentaire"></textarea>
            </div>
            
            <div id="aff_warning" class="alert alert-error" style="display: none;"></div>
            
            <div style="display:flex; gap:0.75rem; justify-content:flex-end;">
                <button type="button" class="cancel-btn" onclick="closeModal('affecterModal')">Annuler</button>
                <button type="submit" class="affecter-btn">Affecter</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal pour modifier -->
<div id="modifierModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal('modifierModal')">&times;</span>
        <h3>Modifier une affectation</h3>
        <form id="modifierForm" method="post" action="">
            <input type="hidden" name="action" value="modifier">
            <input type="hidden" name="id_ue" id="mod_id_ue">
            <input type="hidden" name="id_filiere" id="mod_id_filiere">
            <input type="hidden" name="type_cours" id="mod_type_cours">
            <input type="hidden" name="id_affectation" id="mod_id_affectation">
            
            <div>
                <label for="id_utilisateur">Sélectionner un enseignant:</label>
                <select name="id_utilisateur" id="mod_id_utilisateur">
                    <option value="" class="non-affecte-option">--- Non affecté ---</option>
                    <?php foreach ($tous_enseignants as $enseignant): ?>
                        <option value="<?= $enseignant['id'] ?>" 
                            data-specialite="<?= htmlspecialchars($enseignant['specialite']) ?>"
                            <?= $enseignant['max_atteint'] ? 'data-max-atteint="1"' : '' ?>>
                            <?= htmlspecialchars($enseignant['nom'] . ' ' . $enseignant['prenom']) ?> 
                            (<?= $enseignant['heures_effectuees'] ?>h / <?= $max_heures_global ?>h)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="commentaire">Commentaire (optionnel):</label>
                <textarea name="commentaire" id="mod_commentaire"></textarea>
            </div>
            
            <div id="mod_warning" class="alert alert-error" style="display: none;"></div>
            
            <div style="display:flex; gap:0.75rem; justify-content:flex-end;">
                <button type="button" class="cancel-btn" onclick="closeModal('modifierModal')">Annuler</button>
                <button type="submit" class="modifier-btn">Modifier</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de confirmation pour valider comme unité vacante -->
<div id="confirmationModal" class="confirmation-modal">
    <div class="confirmation-content">
        <span class="close-modal" onclick="closeModal('confirmationModal')">&times;</span>
        <h3>Confirmer la validation comme unité vacante</h3>
        <p>Êtes-vous sûr de vouloir valider cette unité d'enseignement comme vacante pour cette filière ?</p>
        <p>Une fois validée, vous ne pourrez plus l'affecter à un enseignant pour cette filière.</p>
        
        <form id="validerVacanteForm" method="post" action="">
            <input type="hidden" name="action" value="valider_vacante">
            <input type="hidden" name="id_ue" id="conf_id_ue">
            <input type="hidden" name="id_filiere" id="conf_id_filiere">
            <input type="hidden" name="type_cours" id="conf_type_cours">
            
            <div class="confirmation-buttons">
                <button type="button" class="cancel-btn" onclick="closeModal('confirmationModal')">Annuler</button>
                <button type="submit" class="confirm-btn">Confirmer</button>
            </div>
        </form>
    </div>
</div>
<script>
        // Fonction pour ouvrir le modal d'affectation
        function openAffecterModal(id_ue, id_filiere, type_cours, specialite_ue) {
            document.getElementById('aff_id_ue').value = id_ue;
            document.getElementById('aff_id_filiere').value = id_filiere;
            document.getElementById('aff_type_cours').value = type_cours;
            
            // Réinitialiser les avertissements
            document.getElementById('aff_warning').style.display = 'none';
            document.getElementById('aff_warning').textContent = '';
            
            // Afficher le modal
            document.getElementById('affecterModal').style.display = 'block';
            
            // Ajouter l'événement de changement pour vérifier la compatibilité des spécialités
            document.getElementById('aff_id_utilisateur').onchange = function() {
                checkSpecialiteCompatibility(this, specialite_ue, 'aff_warning');
            };
        }

        // Fonction pour ouvrir le modal de modification
        function openModifierModal(id_ue, id_filiere, type_cours, id_affectation, specialite_ue) {
            document.getElementById('mod_id_ue').value = id_ue;
            document.getElementById('mod_id_filiere').value = id_filiere;
            document.getElementById('mod_type_cours').value = type_cours;
            document.getElementById('mod_id_affectation').value = id_affectation;
            
            // Réinitialiser les avertissements
            document.getElementById('mod_warning').style.display = 'none';
            document.getElementById('mod_warning').textContent = '';
            
            // Afficher le modal
            document.getElementById('modifierModal').style.display = 'block';
            
            // Ajouter l'événement de changement pour vérifier la compatibilité des spécialités
            document.getElementById('mod_id_utilisateur').onchange = function() {
                checkSpecialiteCompatibility(this, specialite_ue, 'mod_warning');
            };
        }

        // Fonction pour ouvrir le modal de confirmation pour valider comme unité vacante
        function openConfirmationModal(id_ue, id_filiere, type_cours) {
            document.getElementById('conf_id_ue').value = id_ue;
            document.getElementById('conf_id_filiere').value = id_filiere;
            document.getElementById('conf_type_cours').value = type_cours;
            
            // Afficher le modal
            document.getElementById('confirmationModal').style.display = 'block';
        }

        // Fonction pour fermer les modals
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Fonction pour vérifier la compatibilité des spécialités
        function checkSpecialiteCompatibility(selectElement, specialite_ue, warningElementId) {
            // Si l'option "Non affecté" est sélectionnée, on n'affiche pas d'avertissement
            // et on s'assure que le bouton est activé
            if (!selectElement.value) {
                document.getElementById(warningElementId).style.display = 'none';
                selectElement.form.querySelector('button[type="submit"]').disabled = false;
                return;
            }
            
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            const enseignantSpecialite = selectedOption.getAttribute('data-specialite');
            const maxAtteint = selectedOption.getAttribute('data-max-atteint') === '1';
            
            // Vérifier la compatibilité des spécialités
            if (specialite_ue && enseignantSpecialite && specialite_ue !== enseignantSpecialite) {
                document.getElementById(warningElementId).textContent = 
                    "Attention: La spécialité de l'enseignant ne correspond pas à celle de l'UE. L'affectation ne sera pas possible.";
                document.getElementById(warningElementId).style.display = 'block';
                // Désactiver le bouton de soumission
                selectElement.form.querySelector('button[type="submit"]').disabled = true;
            } 
            // Avertissement si l'enseignant a atteint son maximum d'heures
            else if (maxAtteint) {
                document.getElementById(warningElementId).textContent = 
                    "Attention: L'enseignant a atteint son maximum d'heures. L'affectation sera quand même possible.";
                document.getElementById(warningElementId).style.display = 'block';
                // Activer le bouton de soumission car c'est juste un avertissement
                selectElement.form.querySelector('button[type="submit"]').disabled = false;
            } else {
                document.getElementById(warningElementId).style.display = 'none';
                // Activer le bouton de soumission
                selectElement.form.querySelector('button[type="submit"]').disabled = false;
            }
        }

        // Fermer tous les messages d'alerte après 5 secondes
        window.onload = function() {
            const alertMessages = document.querySelectorAll('.alert');
            alertMessages.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 5000);
            });
        };

        // Fermer les modals si l'utilisateur clique en dehors de la modal
        window.onclick = function(event) {
            const affecterModal = document.getElementById('affecterModal');
            const modifierModal = document.getElementById('modifierModal');
            const confirmationModal = document.getElementById('confirmationModal');
            
            if (event.target === affecterModal) {
                affecterModal.style.display = 'none';
            }
            
            if (event.target === modifierModal) {
                modifierModal.style.display = 'none';
            }
            
            if (event.target === confirmationModal) {
                confirmationModal.style.display = 'none';
            }
        };
        </script>


<?php
// Inclure le footer
include 'footer_chef.php';
?> 