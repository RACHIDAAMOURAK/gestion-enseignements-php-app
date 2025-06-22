<?php

// Classe pour gérer l'authentification
class Authentication {
    private $conn;
    private $table_name = "utilisateurs";
    private $sessions_table = "sessions";
    private $attempts_table = "tentatives_connexion";

    // Propriétés de l'utilisateur
    public $id;
    public $nom_utilisateur;
    public $email;
    public $mot_de_passe;
    public $role;
    public $id_departement;
    public $id_filiere;
    public $actif;

    // Constructor avec connexion à la base de données
    public function __construct($db) {
        $this->conn = $db;
    }

    // Méthode pour authentifier un utilisateur
    public function login($username, $password) {
        // Vérifier si l'utilisateur est bloqué à cause de trop de tentatives
        if ($this->isUserBlocked($_SERVER['REMOTE_ADDR'])) {
            return ["success" => false, "message" => "Trop de tentatives de connexion. Veuillez réessayer plus tard."];
        }

        // Préparer la requête
        $query = "SELECT id, nom_utilisateur, email, mot_de_passe, role, id_departement, id_filiere, actif, prenom, nom 
                  FROM " . $this->table_name . " 
                  WHERE (nom_utilisateur = :username OR email = :email)";
        
        $stmt = $this->conn->prepare($query);
        
        // Nettoyer les données
        $username = htmlspecialchars(strip_tags($username));
        
        // Liaison des paramètres
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":email", $username);
        
        // Exécuter la requête
        $stmt->execute();
        
        // Obtenir le nombre de lignes
        $num = $stmt->rowCount();
        
        // Si l'utilisateur existe
        if ($num > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Vérifier si le compte est actif
            if ($row['actif'] == 0) {
                $this->logLoginAttempt($row['id'], $_SERVER['REMOTE_ADDR'], false);
                return ["success" => false, "message" => "Votre compte est désactivé. Veuillez contacter l'administrateur."];
            }
            
            // Vérifier le mot de passe
            if (password_verify($password, $row['mot_de_passe'])) {
                // Mettre à jour la dernière connexion
                $this->updateLastLogin($row['id']);
                
                // Créer une session
                $session_id = $this->createSession($row['id']);
                
                // Enregistrer la tentative réussie
                $this->logLoginAttempt($row['id'], $_SERVER['REMOTE_ADDR'], true);
                
                // Définir les propriétés de l'objet
                $this->id = $row['id'];
                $this->nom_utilisateur = $row['nom_utilisateur'];
                $this->email = $row['email'];
                $this->role = $row['role'];
                $this->id_departement = $row['id_departement'];
                $this->id_filiere = $row['id_filiere'];
                $this->actif = $row['actif'];
                
                return [
                    "success" => true, 
                    "user" => [
                        "id" => $this->id,
                        "nom_utilisateur" => $this->nom_utilisateur,
                        "email" => $this->email,
                        "role" => $this->role,
                        "id_departement" => $this->id_departement,
                        "id_filiere" => $this->id_filiere,
                        "actif" => $this->actif,
                        "prenom" => $row['prenom'],
                        "nom" => $row['nom']
                    ],
                    "session_id" => $session_id
                ];
            } else {
                // Enregistrer la tentative échouée
                $this->logLoginAttempt($row['id'], $_SERVER['REMOTE_ADDR'], false);
                return ["success" => false, "message" => "Mot de passe incorrect."];
            }
        } else {
            // Utilisateur n'existe pas
            $this->logLoginAttempt(null, $_SERVER['REMOTE_ADDR'], false);
            return ["success" => false, "message" => "Utilisateur non trouvé."];
        }
    }
    
    // Méthode pour créer une session
    private function createSession($user_id) {
        // Générer un ID de session unique
        $session_id = bin2hex(random_bytes(32));
        
        // Préparer la requête
        $query = "INSERT INTO " . $this->sessions_table . " 
                  (id, id_utilisateur, adresse_ip, agent_utilisateur, date_creation, date_expiration) 
                  VALUES (:id, :id_utilisateur, :adresse_ip, :agent_utilisateur, NOW(), DATE_ADD(NOW(), INTERVAL 2 HOUR))";
        
        $stmt = $this->conn->prepare($query);
        
        // Liaison des paramètres
        $stmt->bindParam(":id", $session_id);
        $stmt->bindParam(":id_utilisateur", $user_id);
        $stmt->bindParam(":adresse_ip", $_SERVER['REMOTE_ADDR']);
        $stmt->bindParam(":agent_utilisateur", $_SERVER['HTTP_USER_AGENT']);
        
        // Exécuter la requête
        $stmt->execute();
        
        return $session_id;
    }
    
    // Méthode pour vérifier si une session est valide
    public function validateSession($session_id) {
        // Nettoyer les données
        $session_id = htmlspecialchars(strip_tags($session_id));
        
        // Préparer la requête
        $query = "SELECT s.id, s.id_utilisateur, u.nom_utilisateur, u.email, u.role, u.id_departement, u.id_filiere, u.actif
                  FROM " . $this->sessions_table . " s
                  JOIN " . $this->table_name . " u ON s.id_utilisateur = u.id
                  WHERE s.id = :session_id 
                  AND s.date_expiration > NOW()
                  AND s.adresse_ip = :adresse_ip";
        
        $stmt = $this->conn->prepare($query);
        
        // Liaison des paramètres
        $stmt->bindParam(":session_id", $session_id);
        $stmt->bindParam(":adresse_ip", $_SERVER['REMOTE_ADDR']);
        
        // Exécuter la requête
        $stmt->execute();
        
        // Obtenir le nombre de lignes
        $num = $stmt->rowCount();
        
        // Si la session est valide
        if ($num > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Vérifier si le compte est toujours actif
            if ($row['actif'] == 0) {
                return ["success" => false, "message" => "Votre compte est désactivé."];
            }
            
            // Prolonger la session
            $this->extendSession($session_id);
            
            // Définir les propriétés de l'objet
            $this->id = $row['id_utilisateur'];
            $this->nom_utilisateur = $row['nom_utilisateur'];
            $this->email = $row['email'];
            $this->role = $row['role'];
            $this->id_departement = $row['id_departement'];
            $this->id_filiere = $row['id_filiere'];
            $this->actif = $row['actif'];
            
            return [
                "success" => true, 
                "user" => [
                    "id" => $this->id,
                    "nom_utilisateur" => $this->nom_utilisateur,
                    "email" => $this->email,
                    "role" => $this->role,
                    "id_departement" => $this->id_departement,
                    "id_filiere" => $this->id_filiere
                ]
            ];
        } else {
            return ["success" => false, "message" => "Session invalide ou expirée."];
        }
    }
    
    // Méthode pour prolonger une session
    private function extendSession($session_id) {
        // Préparer la requête
        $query = "UPDATE " . $this->sessions_table . " 
                  SET date_expiration = DATE_ADD(NOW(), INTERVAL 2 HOUR) 
                  WHERE id = :session_id";
        
        $stmt = $this->conn->prepare($query);
        
        // Liaison des paramètres
        $stmt->bindParam(":session_id", $session_id);
        
        // Exécuter la requête
        $stmt->execute();
    }
    
    // Méthode pour déconnecter un utilisateur
    public function logout($session_id) {
        // Nettoyer les données
        $session_id = htmlspecialchars(strip_tags($session_id));
        
        // Préparer la requête
        $query = "DELETE FROM " . $this->sessions_table . " 
                  WHERE id = :session_id";
        
        $stmt = $this->conn->prepare($query);
        
        // Liaison des paramètres
        $stmt->bindParam(":session_id", $session_id);
        
        // Exécuter la requête
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
    
    // Méthode pour mettre à jour la dernière connexion
    private function updateLastLogin($user_id) {
        // Préparer la requête
        $query = "UPDATE " . $this->table_name . " 
                  SET derniere_connexion = NOW() 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Liaison des paramètres
        $stmt->bindParam(":id", $user_id);
        
        // Exécuter la requête
        $stmt->execute();
    }
    
    // Méthode pour enregistrer les tentatives de connexion
    private function logLoginAttempt($user_id, $ip_address, $success) {
        // Préparer la requête
        $query = "INSERT INTO " . $this->attempts_table . " 
                  (id_utilisateur, adresse_ip, date_tentative, succes) 
                  VALUES (:id_utilisateur, :adresse_ip, NOW(), :succes)";
        
        $stmt = $this->conn->prepare($query);
        
        // Liaison des paramètres
        $stmt->bindParam(":id_utilisateur", $user_id);
        $stmt->bindParam(":adresse_ip", $ip_address);
        $stmt->bindParam(":succes", $success, PDO::PARAM_BOOL);
        
        // Exécuter la requête
        $stmt->execute();
    }
    
    // Méthode pour vérifier si l'utilisateur est bloqué
    private function isUserBlocked($ip_address) {
        // Préparer la requête pour compter les tentatives échouées dans les 15 dernières minutes
        $query = "SELECT COUNT(*) as total 
                  FROM " . $this->attempts_table . " 
                  WHERE adresse_ip = :adresse_ip 
                  AND succes = 0 
                  AND date_tentative > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
        
        $stmt = $this->conn->prepare($query);
        
        // Liaison des paramètres
        $stmt->bindParam(":adresse_ip", $ip_address);
        
        // Exécuter la requête
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si plus de 5 tentatives échouées
        if ($row['total'] >= 5) {
            return true;
        }
        
        return false;
    }

    public function checkEmailExists($email) {
        try {
            $query = "SELECT id FROM utilisateurs WHERE email = :email";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":email", $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return ["success" => true];
            }
            return ["success" => false, "message" => "Aucun compte associé à cette adresse email."];
        } catch(PDOException $e) {
            return ["success" => false, "message" => "Erreur lors de la vérification de l'email."];
        }
    }

    public function saveResetToken($email, $token, $expiry) {
        try {
            // Supprimer les anciens tokens non utilisés pour cet email
            $query = "DELETE FROM password_resets WHERE email = :email AND used = 0";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":email", $email);
            $stmt->execute();
            
            // Insérer le nouveau token
            $query = "INSERT INTO password_resets (email, token, expiry) VALUES (:email, :token, :expiry)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":token", $token);
            $stmt->bindParam(":expiry", $expiry);
            $stmt->execute();
            
            return ["success" => true];
        } catch(PDOException $e) {
            return ["success" => false, "message" => "Erreur lors de la sauvegarde du token."];
        }
    }

    public function verifyResetToken($token) {
        try {
            $query = "SELECT email FROM password_resets WHERE token = :token AND expiry > NOW() AND used = 0";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":token", $token);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return ["success" => true, "email" => $row['email']];
            }
            return ["success" => false, "message" => "Token invalide ou expiré."];
        } catch(PDOException $e) {
            return ["success" => false, "message" => "Erreur lors de la vérification du token."];
        }
    }

    public function resetPassword($token, $new_password) {
        try {
            // Vérifier d'abord si le token est valide
            $verify_result = $this->verifyResetToken($token);
            if (!$verify_result["success"]) {
                return $verify_result;
            }
            
            // Hasher le nouveau mot de passe
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Mettre à jour le mot de passe
            $query = "UPDATE utilisateurs SET mot_de_passe = :password WHERE email = :email";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":password", $hashed_password);
            $stmt->bindParam(":email", $verify_result["email"]);
            $stmt->execute();
            
            // Marquer le token comme utilisé
            $query = "UPDATE password_resets SET used = 1 WHERE token = :token";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":token", $token);
            $stmt->execute();
            
            return ["success" => true];
        } catch(PDOException $e) {
            return ["success" => false, "message" => "Erreur lors de la réinitialisation du mot de passe."];
        }
    }
}
?>
