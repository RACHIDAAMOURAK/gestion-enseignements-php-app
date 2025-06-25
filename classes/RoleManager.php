<?php
class RoleManager {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Récupère toutes les permissions disponibles
     */
    public function getAllPermissions() {
        try {
            $query = "SELECT id, nom, description FROM permissions ORDER BY nom";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des permissions : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les permissions d'un rôle spécifique
     */
    public function getRolePermissions($role) {
        try {
            $query = "SELECT id_permission FROM role_permissions WHERE role = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$role]);
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id_permission');
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des permissions du rôle : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Met à jour les permissions d'un rôle
     */
    public function updateRolePermissions($role, $permissions) {
        try {
            $this->db->beginTransaction();

            // Supprimer les permissions existantes
            $query = "DELETE FROM role_permissions WHERE role = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$role]);

            // Insérer les nouvelles permissions
            if (!empty($permissions)) {
                $query = "INSERT INTO role_permissions (role, id_permission) VALUES (?, ?)";
                $stmt = $this->db->prepare($query);
                foreach ($permissions as $permissionId) {
                    $stmt->execute([$role, $permissionId]);
                }
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur lors de la mise à jour des permissions : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie si un utilisateur a une permission spécifique
     */
    public function hasPermission($userId, $permissionName) {
        try {
            // Vérifier d'abord les permissions basées sur le rôle
            $query = "SELECT COUNT(*) FROM utilisateurs u
                     JOIN role_permissions rp ON u.role = rp.role
                     JOIN permissions p ON rp.id_permission = p.id
                     WHERE u.id = ? AND p.nom = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId, $permissionName]);
            
            if ($stmt->fetchColumn() > 0) {
                return true;
            }

            // Vérifier ensuite les permissions spécifiques à l'utilisateur
            $query = "SELECT COUNT(*) FROM utilisateur_permissions
                     WHERE id_utilisateur = ? AND nom_permission = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId, $permissionName]);
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification des permissions : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère toutes les permissions d'un utilisateur
     */
    public function getUserPermissions($userId) {
        try {
            // Récupérer les permissions basées sur le rôle
            $query = "SELECT DISTINCT p.nom, p.description, 'role' as source FROM utilisateurs u
                     JOIN role_permissions rp ON u.role = rp.role
                     JOIN permissions p ON rp.id_permission = p.id
                     WHERE u.id = ?
                     UNION
                     SELECT up.nom_permission as nom, p.description, 'specific' as source 
                     FROM utilisateur_permissions up
                     LEFT JOIN permissions p ON up.nom_permission = p.nom
                     WHERE up.id_utilisateur = ?
                     ORDER BY nom";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId, $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des permissions de l'utilisateur : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ajoute une permission spécifique à un utilisateur
     */
    public function addUserPermission($userId, $permissionName) {
        try {
            $query = "INSERT INTO utilisateur_permissions (id_utilisateur, nom_permission) VALUES (?, ?)";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$userId, $permissionName]);
        } catch (PDOException $e) {
            error_log("Erreur lors de l'ajout de la permission utilisateur : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime une permission spécifique d'un utilisateur
     */
    public function removeUserPermission($userId, $permissionName) {
        try {
            $query = "DELETE FROM utilisateur_permissions WHERE id_utilisateur = ? AND nom_permission = ?";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$userId, $permissionName]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de la permission utilisateur : " . $e->getMessage());
            return false;
        }
    }
}