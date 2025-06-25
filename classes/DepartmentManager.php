<?php
class DepartmentManager {
    private $conn;
    private $departsPerPage = 10;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Récupérer tous les départements avec pagination
    public function getAllDepartments($page = 1, $search = '') {
        $offset = ($page - 1) * $this->departsPerPage;
        
        $query = "SELECT d.*, 
                    (SELECT COUNT(*) FROM utilisateurs u WHERE u.id_departement = d.id) as nombre_utilisateurs,
                    (SELECT COUNT(*) FROM filieres f WHERE f.id_departement = d.id) as nombre_filieres
                 FROM departements d
                 WHERE 1=1";
        
        $params = [];
        if (!empty($search)) {
            $query .= " AND d.nom LIKE ?";
            $params[] = "%$search%";
        }
        
        $query .= " ORDER BY d.nom ASC LIMIT " . (int)$this->departsPerPage . " OFFSET " . (int)$offset;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Compter le nombre total de départements
    public function getTotalDepartments($search = '') {
        $query = "SELECT COUNT(*) as total FROM departements WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $query .= " AND nom LIKE ?";
            $params[] = "%$search%";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
    
    // Ajouter un département
    public function addDepartment($nom, $description) {
        try {
            $query = "INSERT INTO departements (nom, description) VALUES (?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$nom, $description]);
            return ['success' => true, 'message' => 'Département ajouté avec succès'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erreur lors de l\'ajout: ' . $e->getMessage()];
        }
    }
    
    // Mettre à jour un département
    public function updateDepartment($id, $nom, $description) {
        try {
            $query = "UPDATE departements SET nom = ?, description = ? WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$nom, $description, $id]);
            return ['success' => true, 'message' => 'Département mis à jour avec succès'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()];
        }
    }
    
    // Supprimer un département
    public function deleteDepartment($id) {
        try {
            // Vérifier s'il y a des utilisateurs ou des filières associés
            $query = "SELECT 
                        (SELECT COUNT(*) FROM utilisateurs WHERE id_departement = ?) as users,
                        (SELECT COUNT(*) FROM filieres WHERE id_departement = ?) as programs";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id, $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['users'] > 0 || $result['programs'] > 0) {
                return ['success' => false, 'message' => 'Impossible de supprimer: le département contient des utilisateurs ou des filières'];
            }
            
            $query = "DELETE FROM departements WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            return ['success' => true, 'message' => 'Département supprimé avec succès'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()];
        }
    }
    
    // Récupérer un département par ID
    public function getDepartmentById($id) {
        $query = "SELECT * FROM departements WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Récupérer les filières d'un département
    public function getDepartmentPrograms($departmentId) {
        $query = "SELECT * FROM filieres WHERE id_departement = ? ORDER BY nom";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$departmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Ajouter une filière
    public function addProgram($nom, $description, $departmentId) {
        try {
            $query = "INSERT INTO filieres (nom, description, id_departement) VALUES (?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$nom, $description, $departmentId]);
            return ['success' => true, 'message' => 'Filière ajoutée avec succès'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erreur lors de l\'ajout: ' . $e->getMessage()];
        }
    }
    
    // Mettre à jour une filière
    public function updateProgram($id, $nom, $description) {
        try {
            $query = "UPDATE filieres SET nom = ?, description = ? WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$nom, $description, $id]);
            return ['success' => true, 'message' => 'Filière mise à jour avec succès'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()];
        }
    }
    
    // Supprimer une filière
    public function deleteProgram($id) {
        try {
            $query = "DELETE FROM filieres WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            return ['success' => true, 'message' => 'Filière supprimée avec succès'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()];
        }
    }
    
    // Obtenir les statistiques des départements
    public function getDepartmentStatistics() {
        $query = "SELECT 
                    COUNT(*) as total_departments,
                    (SELECT COUNT(*) FROM filieres) as total_programs,
                    (SELECT COUNT(*) FROM utilisateurs WHERE role = 'chef_departement') as total_heads
                 FROM departements";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
} 