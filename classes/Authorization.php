<?php 
// Classe pour gérer les autorisations
class Authorization {
    private $conn;
    private $permissions_table = "role_permissions";
    private $user_permissions_table = "utilisateur_permissions";
    
    // Constructor avec connexion à la base de données
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Vérifier si un rôle a une permission spécifique
    public function hasPermission($role, $permission_name) {
        // Préparer la requête
        $query = "SELECT COUNT(*) as total 
                  FROM " . $this->permissions_table . " rp
                  JOIN permissions p ON rp.id_permission = p.id
                  WHERE rp.role = :role 
                  AND p.nom = :permission_name";
        
        $stmt = $this->conn->prepare($query);
        
        // Nettoyer les données
        $role = htmlspecialchars(strip_tags($role));
        $permission_name = htmlspecialchars(strip_tags($permission_name));
        
        // Liaison des paramètres
        $stmt->bindParam(":role", $role);
        $stmt->bindParam(":permission_name", $permission_name);
        
        // Exécuter la requête
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si la permission existe
        if ($row['total'] > 0) {
            return true;
        }
        
        return false;
    }
    
    // Vérifier si un utilisateur a une permission spécifique
    public function hasUserPermission($user_id, $permission_name) {
        // Préparer la requête
        $query = "SELECT COUNT(*) as total 
                  FROM " . $this->user_permissions_table . " 
                  WHERE id_utilisateur = :user_id 
                  AND nom_permission = :permission_name";
        
        $stmt = $this->conn->prepare($query);
        
        // Nettoyer les données
        $user_id = htmlspecialchars(strip_tags($user_id));
        $permission_name = htmlspecialchars(strip_tags($permission_name));
        
        // Liaison des paramètres
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":permission_name", $permission_name);
        
        // Exécuter la requête
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si la permission existe
        if ($row['total'] > 0) {
            return true;
        }
        
        return false;
    }
    
    // Vérifier si un utilisateur a accès à une fonctionnalité
    public function checkAccess($user_id, $role, $permission_name) {
        // Vérifier les permissions spécifiques à l'utilisateur d'abord
        if ($this->hasUserPermission($user_id, $permission_name)) {
            return true;
        }
        
        // Ensuite vérifier les permissions basées sur le rôle
        if ($this->hasPermission($role, $permission_name)) {
            return true;
        }
        
        return false;
    }
}
?>
