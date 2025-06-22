<?php
$error_message = '';
$success_message = '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            height: 100vh;
            margin: 0;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .forgot-container {
            width: 400px;
            padding: 30px;
            background-color: #1c2841;
            border-radius: 20px;
            box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.15);
        }
        
        h3 {
            color: white;
            text-align: center;
            margin-bottom: 25px;
        }
        
        .form-control {
            background-color: transparent;
            border: 1px solid #3a4c6d;
            color: white;
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 10px;
        }
        
        .form-control::placeholder {
            color: #7086ab;
        }
        
        .form-control:focus {
            background-color: transparent;
            color: white;
            border-color: #31b7d1;
            box-shadow: none;
        }
        
        .btn-submit {
            background-color: #31b7d1;
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 10px;
            font-weight: 500;
            margin-top: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-submit:hover {
            background-color: rgb(171, 226, 237);
            transform: translateY(-2px);
        }
        
        .alert {
            margin-bottom: 20px;
        }
        
        .back-to-login {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-to-login a {
            color: #31b7d1;
            text-decoration: none;
        }
        
        .back-to-login a:hover {
            color: rgb(171, 226, 237);
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <h3>Mot de passe oublié</h3>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <form id="forgotPasswordForm">
            <div class="mb-3">
                <input type="email" class="form-control" id="email" name="email" placeholder="Entrez votre adresse email" required>
            </div>
            <button type="submit" class="btn-submit">
                <i class="fas fa-paper-plane"></i>
                Envoyer le lien de réinitialisation
            </button>
        </form>
        
        <div class="back-to-login">
            <a href="login.php">
                <i class="fas fa-arrow-left"></i>
                Retour à la connexion
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            
            fetch('send_reset_email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'email=' + encodeURIComponent(email)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success';
                    alertDiv.role = 'alert';
                    alertDiv.textContent = data.message;
                    
                    const existingAlert = document.querySelector('.alert');
                    if (existingAlert) {
                        existingAlert.remove();
                    }
                    
                    document.querySelector('form').insertAdjacentElement('beforebegin', alertDiv);
                    document.getElementById('email').value = '';
                } else {
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger';
                    alertDiv.role = 'alert';
                    alertDiv.textContent = data.message;
                    
                    const existingAlert = document.querySelector('.alert');
                    if (existingAlert) {
                        existingAlert.remove();
                    }
                    
                    document.querySelector('form').insertAdjacentElement('beforebegin', alertDiv);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger';
                alertDiv.role = 'alert';
                alertDiv.textContent = 'Une erreur est survenue. Veuillez réessayer.';
                
                const existingAlert = document.querySelector('.alert');
                if (existingAlert) {
                    existingAlert.remove();
                }
                
                document.querySelector('form').insertAdjacentElement('beforebegin', alertDiv);
            });
        });
    </script>
</body>
</html>