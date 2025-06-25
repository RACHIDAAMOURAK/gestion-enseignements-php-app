<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require_once 'config/database.php';
require_once 'classes/Authentication.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = isset($_POST['email']) ? trim($_POST['email']) : "";
    
    if (!empty($email)) {
        $database = new Database();
        $db = $database->getConnection();
        $auth = new Authentication($db);
        
        // Vérifier si l'email existe dans la base de données
        $result = $auth->checkEmailExists($email);
        
        if ($result["success"]) {
            // Générer un token unique
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Sauvegarder le token dans la base de données
            $auth->saveResetToken($email, $token, $expiry);
            
            // Configurer PHPMailer
            $mail = new PHPMailer(true);
            
            try {
                // Configuration du serveur
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'bouibauanaya730@gmail.com'; // Remplacez par votre email Gmail
                $mail->Password = 'xqca njbs nags riel'; // Remplacez par le mot de passe d'application généré
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->CharSet = 'UTF-8';
                
                // Configuration de l'expéditeur
                $mail->setFrom('bouibauanaya730@gmail.com', 'Système de Gestion'); // Même email que Username
                $mail->addAddress($email);
                
                // Contenu
                $mail->isHTML(true);
                $mail->Subject = 'Réinitialisation de votre mot de passe';
                $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/projet_web/reset_password.php?token=" . $token;
                
                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                        <h2 style='color: #1c2841;'>Réinitialisation de mot de passe</h2>
                        <p>Bonjour,</p>
                        <p>Vous avez demandé la réinitialisation de votre mot de passe. Cliquez sur le lien ci-dessous pour procéder :</p>
                        <p style='margin: 20px 0;'>
                            <a href='{$resetLink}' style='background-color: #31b7d1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
                                Réinitialiser mon mot de passe
                            </a>
                        </p>
                        <p>Ou copiez ce lien dans votre navigateur :</p>
                        <p style='color: #666;'>{$resetLink}</p>
                        <p><strong>Ce lien expirera dans 1 heure.</strong></p>
                        <p>Si vous n'avez pas demandé cette réinitialisation, veuillez ignorer cet email.</p>
                        <hr style='border: 1px solid #eee; margin: 20px 0;'>
                        <p style='color: #666; font-size: 12px;'>
                            Cet email a été envoyé automatiquement, merci de ne pas y répondre.
                        </p>
                    </div>
                ";
                
                $mail->send();
                echo json_encode(["success" => true, "message" => "Un email de réinitialisation a été envoyé à votre adresse email."]);
            } catch (Exception $e) {
                error_log("Erreur d'envoi d'email : " . $mail->ErrorInfo);
                echo json_encode(["success" => false, "message" => "Erreur lors de l'envoi de l'email. Veuillez réessayer plus tard."]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "Aucun compte associé à cette adresse email."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Veuillez fournir une adresse email."]);
    }
}
?> 