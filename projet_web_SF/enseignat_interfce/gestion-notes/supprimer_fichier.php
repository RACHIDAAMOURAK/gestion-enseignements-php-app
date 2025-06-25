<?php
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_fichier'])) {
    try {
        $id_fichier = $_POST['id_fichier'];

        // Récupérer les informations du fichier
        $stmt = $conn->prepare("
            SELECT chemin_fichier, id_unite_enseignement, type_session
            FROM fichiers_notes 
            WHERE id = ?");
        $stmt->execute([$id_fichier]);
        $fichier = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fichier) {
            throw new Exception("Fichier non trouvé");
        }

        // Début de la transaction
        $conn->beginTransaction();

        // Supprimer les notes associées
        $stmt = $conn->prepare("
            DELETE FROM notes 
            WHERE id_unite_enseignement = ? 
            AND type_session = ? 
            AND fichier_path = ?");
        $stmt->execute([
            $fichier['id_unite_enseignement'],
            $fichier['type_session'],
            $fichier['chemin_fichier']
        ]);

        // Supprimer l'entrée dans fichiers_notes
        $stmt = $conn->prepare("DELETE FROM fichiers_notes WHERE id = ?");
        $stmt->execute([$id_fichier]);

        // Supprimer le fichier physique
        $chemin_complet = __DIR__ . '/uploads/' . $fichier['chemin_fichier'];
        if (file_exists($chemin_complet)) {
            unlink($chemin_complet);
        }

        // Valider la transaction
        $conn->commit();

        header('Location: index.php?success=1&message=' . urlencode('Fichier supprimé avec succès'));
        exit;

    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        header('Location: index.php?error=' . urlencode($e->getMessage()));
        exit;
    }
} else {
    header('Location: index.php?error=' . urlencode('Requête invalide'));
    exit;
}
?> 