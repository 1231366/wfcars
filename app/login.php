<?php 
// 1. INICIA A SESSÃO
session_start();

// 2. TENTA DESTRUIR QUALQUER SESSÃO ANTIGA OU FALHA
if (isset($_SESSION['user_id'])) {
    // Se a sessão existir, DESTRÓI-A e força o recarregamento da página de login.
    session_destroy();
    // Redireciona para login.php para garantir que o browser não tem cache.
    header("Location: login.php");
    exit();
}

// O bloco de código de erro
$error_message = '';
if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WFCARS | Login</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --mdb-secondary-rgb: 192, 192, 192;
            --mdb-primary-rgb: 10, 10, 10;
        }
        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: rgb(var(--mdb-primary-rgb)); 
            color: white; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
        }
        .login-container { 
            width: 100%; 
            max-width: 400px; 
            padding: 30px; 
            background-color: rgb(28, 28, 28); 
            border-radius: 8px; 
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5); 
        }
        .form-control { 
            background-color: rgb(var(--mdb-primary-rgb)) !important; 
            color: white !important; 
            border-color: rgba(var(--mdb-secondary-rgb), 0.5) !important; 
        }
        .form-control:focus {
             border-color: rgb(var(--mdb-secondary-rgb)) !important;
             box-shadow: 0 0 0 0.25rem rgba(192, 192, 192, 0.25) !important;
        }
        .btn-primary { 
            background-color: rgb(var(--mdb-secondary-rgb)) !important; 
            color: rgb(var(--mdb-primary-rgb)) !important; 
            font-weight: 700; 
            border: none; 
        }
        .text-highlight { color: rgb(var(--mdb-secondary-rgb)) !important; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="text-center mb-4">WFCARS | <span class="text-highlight">Login</span></h2>
        
        <?php 
        // Mostrar mensagem de erro se existir
        if ($error_message) {
            echo '<div class="alert alert-danger" role="alert" style="background-color: #58151c; color: #ffcccc; border-color: #7b242e;">' . $error_message . '</div>';
        }
        ?>

        <form action="process_login.php" method="POST">
            <div class="form-outline mb-4">
                <input type="text" id="username" name="username" class="form-control" required/>
                <label class="form-label text-highlight" for="username">Utilizador</label>
            </div>
            <div class="form-outline mb-4">
                <input type="password" id="password" name="password" class="form-control" required/>
                <label class="form-label text-highlight" for="password">Password</label>
            </div>
            <button type="submit" class="btn btn-primary btn-block">ENTRAR</button>
        </form>
    </div>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.umd.min.js"></script>
</body>
</html>