<?php
class UserManager {
    private $conn;
    private $usersPerPage = 10; // Nombre d'utilisateurs par page
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Récupérer tous les utilisateurs avec pagination
    public function getAllUsers($page = 1, $search = '', $roleFilter = '', $departmentFilter = '') {
        $offset = ($page - 1) * $this->usersPerPage;
        
        $query = "SELECT u.*, d.nom as nom_departement 
                 FROM utilisateurs u 
                 LEFT JOIN departements d ON u.id_departement = d.id 
                 WHERE 1=1";
        
        $params = [];
        
        // Ajouter les filtres si présents
        if (!empty($search)) {
            $query .= " AND (u.nom_utilisateur LIKE ? OR u.email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if (!empty($roleFilter)) {
            $query .= " AND u.role = ?";
            $params[] = $roleFilter;
        }
        
        if (!empty($departmentFilter)) {
            $query .= " AND u.id_departement = ?";
            $params[] = $departmentFilter;
        }
        
        $query .= " ORDER BY u.date_creation DESC LIMIT " . (int)$this->usersPerPage . " OFFSET " . (int)$offset;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Compter le nombre total d'utilisateurs (pour la pagination)
    public function getTotalUsers($search = '', $roleFilter = '', $departmentFilter = '') {
        $query = "SELECT COUNT(*) as total FROM utilisateurs u WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $query .= " AND (u.nom_utilisateur LIKE ? OR u.email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if (!empty($roleFilter)) {
            $query .= " AND u.role = ?";
            $params[] = $roleFilter;
        }
        
        if (!empty($departmentFilter)) {
            $query .= " AND u.id_departement = ?";
            $params[] = $departmentFilter;
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['total'];
    }
    
    // Obtenir le nombre d'utilisateurs par page
    public function getUsersPerPage() {
        return $this->usersPerPage;
    }
    
    // Récupérer tous les départements
    public function getAllDepartments() {
        $query = "SELECT * FROM departements ORDER BY nom";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Récupérer toutes les filières
    public function getAllFilieres() {
        $query = "SELECT * FROM filieres ORDER BY nom";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Ajouter un nouvel utilisateur
    public function addUser($data) {
        try {
            // Vérifier si l'email existe déjà
            $stmt = $this->conn->prepare("SELECT id FROM utilisateurs WHERE email = ?");
            $stmt->execute([$data['email']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Cet email est déjà utilisé'];
            }

            // Vérifier si le nom d'utilisateur existe déjà
            $stmt = $this->conn->prepare("SELECT id FROM utilisateurs WHERE nom_utilisateur = ?");
            $stmt->execute([$data['username']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Ce nom d\'utilisateur est déjà utilisé'];
            }

            // Hasher le mot de passe
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

            // Récupérer le nom de la spécialité si fourni
            $specialite_nom = null;
            if (!empty($data['specialite_id'])) {
                $stmt = $this->conn->prepare("SELECT nom FROM specialites WHERE id = ?");
                $stmt->execute([$data['specialite_id']]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $specialite_nom = $row['nom'];
                }
            }

            // Insérer l'utilisateur
            $query = "INSERT INTO utilisateurs (nom_utilisateur, email, mot_de_passe, role, id_departement, prenom, nom, id_filiere, specialite) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $data['username'],
                $data['email'],
                $hashedPassword,
                $data['role'],
                $data['department_id'] ?: null,
                $data['prenom'],
                $data['nom'],
                ($data['role'] === 'coordonnateur' && isset($data['filiere_id'])) ? $data['filiere_id'] : null,
                $specialite_nom
            ]);

            return ['success' => true, 'message' => 'Utilisateur ajouté avec succès'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erreur lors de l\'ajout: ' . $e->getMessage()];
        }
    }
    
    // Mettre à jour un utilisateur
    public function updateUser($data) {
        try {
            // Vérifier si l'email existe déjà pour un autre utilisateur
            $stmt = $this->conn->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
            $stmt->execute([$data['email'], $data['user_id']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Cet email est déjà utilisé par un autre utilisateur'];
            }

            // Vérifier si le nom d'utilisateur existe déjà pour un autre utilisateur
            $stmt = $this->conn->prepare("SELECT id FROM utilisateurs WHERE nom_utilisateur = ? AND id != ?");
            $stmt->execute([$data['username'], $data['user_id']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Ce nom d\'utilisateur est déjà utilisé par un autre utilisateur'];
            }

            // Récupérer le nom de la spécialité si fourni
            $specialite_nom = null;
            if (!empty($data['specialite_id'])) {
                $stmt = $this->conn->prepare("SELECT nom FROM specialites WHERE id = ?");
                $stmt->execute([$data['specialite_id']]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $specialite_nom = $row['nom'];
                }
            }

            // Préparer la requête de mise à jour
            $query = "UPDATE utilisateurs SET 
                     nom_utilisateur = ?, 
                     email = ?, 
                     role = ?, 
                     id_departement = ?,
                     id_filiere = ?,
                     specialite = ?";

            $params = [
                $data['username'],
                $data['email'],
                $data['role'],
                $data['department_id'] ?: null,
                ($data['role'] === 'coordonnateur' && isset($data['filiere_id'])) ? $data['filiere_id'] : null,
                $specialite_nom
            ];

            // Ajouter le mot de passe à la mise à jour seulement s'il est fourni
            if (!empty($data['password'])) {
                $query .= ", mot_de_passe = ?";
                $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            $query .= " WHERE id = ?";
            $params[] = $data['user_id'];

            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);

            return ['success' => true, 'message' => 'Utilisateur modifié avec succès'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erreur lors de la modification: ' . $e->getMessage()];
        }
    }
    
    // Supprimer un utilisateur
    public function deleteUser($userId) {
        try {
            $query = "DELETE FROM utilisateurs WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$userId]);
            
            return ['success' => true, 'message' => "Utilisateur supprimé avec succès."];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => "Erreur lors de la suppression : " . $e->getMessage()];
        }
    }
    
    // Activer/désactiver un utilisateur
    public function toggleUserStatus($userId) {
        try {
            $query = "UPDATE utilisateurs SET actif = NOT actif WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$userId]);
            
            return ['success' => true, 'message' => "Statut de l'utilisateur modifié avec succès."];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => "Erreur lors de la modification du statut : " . $e->getMessage()];
        }
    }
    
    // Récupérer un utilisateur par son ID
    public function getUserById($userId) {
        $query = "SELECT * FROM utilisateurs WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getStatistics() {
        try {
            // Get total users count
            $query = "SELECT COUNT(*) as total FROM utilisateurs";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Get active users count
            $query = "SELECT COUNT(*) as active FROM utilisateurs WHERE actif = 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $active = $stmt->fetch(PDO::FETCH_ASSOC)['active'];

            // Get departments count
            $query = "SELECT COUNT(*) as departments FROM departements";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $departments = $stmt->fetch(PDO::FETCH_ASSOC)['departments'];

            return [
                'total_users' => $total,
                'active_users' => $active,
                'inactive_users' => $total - $active,
                'departments_count' => $departments
            ];
        } catch (PDOException $e) {
            return [
                'total_users' => 0,
                'active_users' => 0,
                'inactive_users' => 0,
                'departments_count' => 0
            ];
        }
    }

    /**
     * Met à jour le profil d'un utilisateur
     */
    public function updateUserProfile($userId, $username, $email) {
        try {
            // Vérifier si l'email est déjà utilisé par un autre utilisateur
            $stmt = $this->conn->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
            $stmt->execute([$email, $userId]);
            if ($stmt->rowCount() > 0) {
                return [
                    'success' => false,
                    'message' => 'Cette adresse email est déjà utilisée.'
                ];
            }

            // Mettre à jour le profil
            $stmt = $this->conn->prepare("UPDATE utilisateurs SET nom_utilisateur = ?, email = ? WHERE id = ?");
            $stmt->execute([$username, $email, $userId]);

            return [
                'success' => true,
                'message' => 'Profil mis à jour avec succès.'
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Une erreur est survenue lors de la mise à jour du profil.'
            ];
        }
    }

    /**
     * Change le mot de passe d'un utilisateur
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Vérifier le mot de passe actuel
            $stmt = $this->conn->prepare("SELECT mot_de_passe FROM utilisateurs WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($currentPassword, $user['mot_de_passe'])) {
                return [
                    'success' => false,
                    'message' => 'Le mot de passe actuel est incorrect.'
                ];
            }

            // Mettre à jour le mot de passe
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);

            return [
                'success' => true,
                'message' => 'Mot de passe modifié avec succès.'
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Une erreur est survenue lors du changement de mot de passe.'
            ];
        }
    }

    /**
     * Récupère toutes les spécialités
     * @return array Liste des spécialités
     */
    public function getAllSpecialites() {
        try {
            $query = "SELECT id, nom FROM specialites ORDER BY nom";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des spécialités: " . $e->getMessage());
            return [];
        }
    }
} 