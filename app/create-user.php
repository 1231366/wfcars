<?php
/**
 * Página de criação e edição de utilizadores. Puxa dados existentes (se edit_id presente).
 */
session_start();
include 'db_connect.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$is_editing = false;
$user_data = [];
$form_title = "Criar Novo Utilizador";
$action_url = "process_create_user.php?action=create";

// 1. Lógica para modo de Edição (Se um ID foi passado)
if (isset($_GET['edit_id'])) {
    $edit_id = filter_input(INPUT_GET, 'edit_id', FILTER_VALIDATE_INT);
    if ($edit_id) {
        // Busca os dados do utilizador
        $stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
        $stmt->close();

        if ($user_data) {
            $is_editing = true;
            $form_title = "Editar Utilizador: " . htmlspecialchars($user_data['username']);
            $action_url = "process_create_user.php?action=edit&user_id=" . $user_data['id'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WFCARS | <?php echo $form_title; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.umd.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        /* Bloco de Estilos da Administração */
        :root {
            --mdb-dark-rgb: 28, 28, 28;
            --mdb-secondary-rgb: 192, 192, 192; 
            --mdb-primary-rgb: 10, 10, 10; 

            --sidebar-bg: 26, 26, 26; 
            --link-color: 180, 180, 180; 
            --active-bg: 45, 45, 45; 
            --active-text-color: 255, 255, 255; 
            --active-icon-color: 192, 192, 192; 
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: rgb(var(--mdb-primary-rgb));
        }
        .text-highlight { color: rgb(var(--mdb-secondary-rgb)) !important; }
        .bg-highlight-card { background-color: rgb(var(--mdb-dark-rgb)) !important; }
        
        .sidebar-logo { height: 40px; filter: drop-shadow(0 0 5px rgba(255, 255, 255, 0.2)); }
        .admin-title-desktop { font-size: 3rem; font-weight: 800; }
        
        /* Estilos de Input Limpo e Prateado */
        .form-control {
            background-color: rgb(var(--mdb-primary-rgb)) !important;
            color: white !important;
            border-color: rgba(192, 192, 192, 0.5) !important;
            transition: all 0.3s;
        }
        .form-control:focus {
             border-color: rgb(var(--mdb-secondary-rgb)) !important;
             box-shadow: 0 0 0 0.25rem rgba(192, 192, 192, 0.25) !important;
        }
        .form-label {
            color: rgb(var(--mdb-secondary-rgb)) !important;
            font-weight: 500;
        }
        .form-select {
             background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23C0C0C0' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e") !important;
        }
        
        /* === ESTILOS DO SIDEBAR E GERAL === */
        .sidebar-menu { min-height: 100vh; background-color: rgb(var(--sidebar-bg)) !important; width: 240px; padding-top: 20px; }
        .list-group-item { background-color: transparent !important; border: none !important; border-radius: 8px !important; color: rgb(var(--link-color)) !important; padding: 12px 15px !important; }
        .list-group-item i { color: rgb(var(--link-color)); font-size: 1.1rem; margin-right: 15px; }
        .list-group-item.active { background-color: rgb(var(--active-bg)) !important; color: rgb(var(--active-text-color)) !important; }
        .sidebar-divider { border-top: 1px solid rgba(255, 255, 255, 0.1); margin: 20px 0; }
        .list-group-item.text-danger { color: var(--mdb-danger) !important; }
        
        main { padding: 20px; padding-top: 78px; margin-left: 0 !important; }
        @media (min-width: 992px) { main { margin-left: 240px !important; padding-top: 20px !important; } }
        
        /* Toastr Customização */
        #toast-container > .toast-success { background-color: #1f4420 !important; }
        #toast-container > .toast-error { background-color: #58151c !important; }
    </style>
</head>
<body class="bg-dark text-white">

    <main>
        
        <h1 class="admin-title-desktop mb-5">
            <?php echo $is_editing ? 'Editar' : 'Criar'; ?> <span class="text-highlight">Utilizador</span>
        </h1>
        
        <form action="<?php echo $action_url; ?>" method="POST" class="max-w-xl">
            
            <div class="card bg-highlight-card shadow-lg mb-4">
                <div class="card-header bg-transparent border-bottom border-secondary-subtle">
                    <h5 class="text-white mb-0">Credenciais e Função</h5>
                </div>
                <div class="card-body">
                    
                    <div class="form-outline mb-4">
                        <input type="text" id="username" name="username" class="form-control" value="<?php echo $user_data['username'] ?? ''; ?>" required />
                        <label class="form-label" for="username">Nome de Utilizador</label>
                    </div>
                    
                    <div class="form-outline mb-4">
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo $user_data['email'] ?? ''; ?>" required />
                        <label class="form-label" for="email">Email</label>
                    </div>
                    
                    <div class="form-outline mb-4">
                        <input type="password" id="password" name="password" class="form-control" <?php echo $is_editing ? '' : 'required'; ?> />
                        <label class="form-label" for="password">Password <?php echo $is_editing ? '(Deixe vazio para manter)' : ''; ?></label>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label text-highlight mb-2">Função (Role)</label>
                        <select id="role" name="role" class="form-select text-white" style="background-color: rgb(var(--mdb-primary-rgb)); border-color: rgba(192, 192, 192, 0.5);">
                            <option value="editor" <?php echo ($user_data['role'] ?? 'editor') === 'editor' ? 'selected' : ''; ?>>Editor (Pode criar e gerir anúncios)</option>
                            <option value="viewer" <?php echo ($user_data['role'] ?? '') === 'viewer' ? 'selected' : ''; ?>>Viewer (Só pode ver o dashboard e listagens)</option>
                            <option value="admin" <?php echo ($user_data['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin (Acesso total)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                 <a href="admin-users.php" class="btn btn-lg btn-secondary-subtle me-3">
                    CANCELAR
                </a>
                <button type="submit" class="btn btn-lg btn-rounded text-dark fw-bold" style="background-color: rgb(var(--mdb-secondary-rgb));">
                    <?php echo $is_editing ? 'GUARDAR ALTERAÇÕES' : 'CRIAR UTILIZADOR'; ?>
                </button>
            </div>
        </form>

    </main>

    <script>
        // Inicializar Toastr (para mostrar mensagens de sucesso/erro vindas do process_create_user.php)
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "5000",
            "extendedTimeOut": "1000"
        }

        function displayToastr(status, message) {
            // Assumimos que o backend pode ter usado urlencode
            let decodedMessage = decodeURIComponent(message.replace(/\+/g, ' '));
            
            if (status === 'success') {
                toastr.success(decodedMessage, 'Sucesso!');
            } else if (status === 'error') {
                toastr.error(decodedMessage, 'Erro!');
            }
        }

        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        const message = urlParams.get('message');

        if (status && message) {
            displayToastr(status, message);
            
            if (window.history.replaceState) {
                const url = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({path: url}, '', url);
            }
        }
    </script>
</body>
</html>