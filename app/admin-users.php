<?php
session_start();
include 'db_connect.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// --- Lógica para buscar utilizadores ---
$sql_users = "SELECT id, username, email, role, data_registo FROM users ORDER BY role ASC, username ASC";
$result_users = $conn->query($sql_users);
$users_count = $result_users ? $result_users->num_rows : 0;

// Função para mostrar mensagens de status (reutilizada)
function display_status_message() {
    if (isset($_GET['status']) && isset($_GET['message'])) {
        $status = htmlspecialchars($_GET['status']);
        // Assumindo que a mensagem foi urlencode ou base64_encode no backend
        $message = htmlspecialchars(isset($_GET['message']) ? $_GET['message'] : ''); 
        
        $alert_class = ($status == 'success') ? 'alert-success' : 'alert-danger';
        $style = ($status == 'success') 
            ? 'background-color: #1f4420; color: #d4edda; border-color: #1c7430;'
            : 'background-color: #58151c; color: #ffcccc; border-color: #7b242e;';
        
        echo '<div class="alert ' . $alert_class . ' mt-3" style="' . $style . '">';
        echo $message;
        echo '</div>';
    }
}
// Variável de controle para o menu mobile
$current_page = basename($_SERVER['PHP_SELF']); 
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WFCARS | Gerir Utilizadores</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
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
        
        /* Estilos ESPECÍFICOS para Listagem de Users */
        .user-table th, .user-table td {
            color: white !important;
            border-bottom: 1px solid rgba(192, 192, 192, 0.1) !important;
        }
        .user-table th {
             color: rgb(var(--mdb-secondary-rgb)) !important;
             font-weight: 600;
             opacity: 0.8;
        }
        .user-role-admin { color: gold; font-weight: 700; }

        /* === ESTILOS DO SIDEBAR (DARK CLEAN MINIMAL) === */
        .sidebar-menu { 
            min-height: 100vh; 
            background-color: rgb(var(--sidebar-bg)) !important; 
            width: 240px; 
            border-right: none !important; 
            box-shadow: none !important;
            padding-top: 20px;
        }

        .list-group-item {
            background-color: transparent !important;
            border: none !important;
            border-radius: 8px !important; 
            color: rgb(var(--link-color)) !important; 
            font-weight: 500;
            padding: 12px 15px !important; 
            margin-bottom: 5px; 
            transition: all 0.2s ease-in-out;
            display: flex;
            align-items: center;
        }
        
        .list-group-item i {
            color: rgb(var(--link-color));
            font-size: 1.1rem; 
            margin-right: 15px; 
            width: 20px;
            text-align: center;
        }

        .list-group-item:hover:not(.active):not(.text-danger) {
            background-color: rgba(var(--active-bg), 0.5) !important; 
            color: white !important;
        }
        .list-group-item:hover:not(.active):not(.text-danger) i {
            color: white !important;
        }

        .list-group-item.active {
            font-weight: 600;
            background-color: rgb(var(--active-bg)) !important; 
            color: rgb(var(--active-text-color)) !important; 
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); 
        }

        .list-group-item.active i {
            color: rgb(var(--active-icon-color)) !important; 
        }

        .sidebar-divider {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin: 20px 0;
        }

        .list-group-item.text-danger {
            color: var(--mdb-danger) !important;
            border-top: none !important;
            border-bottom: none !important;
            margin-bottom: 0 !important;
        }
        .list-group-item.text-danger i {
            color: var(--mdb-danger) !important;
        }
        
        /* === MENU INFERIOR MOBILE (APP STYLE) === */
        .mobile-nav-app {
            position: fixed; bottom: 0; left: 0; right: 0; z-index: 1030;
            background-color: rgb(var(--sidebar-bg)) !important;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            height: 65px;
            display: flex;
            justify-content: space-around;
            align-items: center;
        }
        .mobile-nav-item {
            color: rgb(var(--link-color)); 
            opacity: 0.8;
            transition: color 0.2s, opacity 0.2s;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 0.7rem; 
            font-weight: 500;
        }
        .mobile-nav-item i { 
            font-size: 1.3rem; 
            margin-bottom: 3px;
            color: rgb(var(--link-color));
        }
        
        .mobile-nav-item.active {
            color: rgb(var(--active-icon-color)) !important; 
            opacity: 1;
            font-weight: 600;
        }
        .mobile-nav-item.active i {
            color: rgb(var(--active-icon-color)) !important;
        }

        /* === RESPONSIVIDADE & LAYOUT === */
        main { 
            padding: 20px;
            padding-top: 78px; 
            margin-left: 0 !important; 
        }

        @media (max-width: 991.98px) {
            main { padding-bottom: 75px; } /* Adiciona padding para não cobrir a barra inferior */
            .admin-title-desktop { font-size: 2.5rem; }
        }

        @media (min-width: 992px) {
            main { 
                margin-left: 240px !important; 
                margin-top: 0 !important; 
                padding-top: 20px !important; 
            }
            .mobile-nav-app { display: none !important; }
        }
        
        /* === OTIMIZAÇÃO DE TABELA MOBILE (NOVO) === */
        @media (max-width: 991.98px) {
            /* Oculta colunas menos importantes no mobile */
            .user-table th:nth-child(1), .user-table td:nth-child(1), /* ID */
            .user-table th:nth-child(3), .user-table td:nth-child(3), /* Email */
            .user-table th:nth-child(5), .user-table td:nth-child(5) { /* Registo */
                display: none !important;
            }
            
            /* Ajusta o tamanho da fonte e paddings para as colunas restantes */
            .user-table td, .user-table th {
                font-size: 0.8rem; 
                padding: 0.5rem;
            }
            .user-table th:nth-child(2), .user-table td:nth-child(2) {
                min-width: 100px; /* Garante que o username tenha espaço */
            }

            /* Compacta os botões de Ações */
            .user-table td:last-child .btn-sm {
                padding: .25rem .3rem !important; /* Mais compacto */
                font-size: .7rem !important;
            }
        }
    </style>
</head>
<body class="bg-dark text-white">
    <?php display_status_message(); ?>

    <nav class="navbar navbar-dark bg-dark d-lg-none" style="background-color: rgb(var(--sidebar-bg)) !important;">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin-dashboard.php">
                <img src="logo.png" alt="WF Cars" class="sidebar-logo me-2" />
                <span class="text-highlight">Gerir Utilizadores</span>
            </a>
        </div>
    </nav>
    
    <nav id="sidebarMenu" class="d-none d-lg-block sidebar-menu fixed-top shadow">
        <div class="position-sticky d-flex flex-column h-100">
            <div class="sidebar-top-section">
                <div class="text-center mt-4 mb-5">
                    <img src="logo.png" alt="WF Cars" class="sidebar-logo mb-3" />
                </div>
                <div class="list-group list-group-flush mx-3">
                    <a href="admin-dashboard.php" class="list-group-item list-group-item-action ripple">
                        <i class="fas fa-chart-line fa-fw"></i><span>Dashboard</span>
                    </a>
                    <a href="admin-active-listings.php" class="list-group-item list-group-item-action ripple">
                        <i class="fas fa-car fa-fw"></i><span>Anúncios Ativos</span>
                    </a>
                    <a href="admin-new-listing.php" class="list-group-item list-group-item-action ripple">
                        <i class="fas fa-plus fa-fw"></i><span>Novo Anúncio</span>
                    </a>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <div class="sidebar-divider"></div>
                    <a href="admin-users.php" class="list-group-item list-group-item-action ripple active">
                        <i class="fas fa-users-cog fa-fw"></i><span class="text-highlight">Gerir Utilizadores</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="list-group list-group-flush mx-3 mt-auto mb-3">
                 <div class="sidebar-divider"></div>
                <a href="logout.php" class="list-group-item ripple text-danger">
                    <i class="fas fa-sign-out-alt fa-fw"></i><span>Sair</span>
                </a>
            </div>
        </div>
    </nav>

    <main>
        
        <h1 class="admin-title-desktop mb-5">
            Gerir <span class="text-highlight">Utilizadores</span>
        </h1>
        
        <a href="admin-create-user.php" class="btn btn-sm btn-light btn-rounded mb-4">
            <i class="fas fa-user-plus me-2"></i> Adicionar Novo Utilizador
        </a>

        <div class="card bg-highlight-card border-0 shadow-lg p-3">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-dark table-hover user-table">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Utilizador</th>
                                <th scope="col">Email</th>
                                <th scope="col">Função</th>
                                <th scope="col">Registo</th>
                                <th scope="col">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($result_users && $result_users->num_rows > 0) {
                                while ($user = $result_users->fetch_assoc()) {
                                    $role_class = ($user['role'] === 'admin') ? 'user-role-admin' : '';
                            ?>
                            <tr>
                                <th scope="row"><?php echo $user['id']; ?></th>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><span class="<?php echo $role_class; ?>"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span></td>
                                <td><?php echo date('Y-m-d', strtotime($user['data_registo'])); ?></td>
                                <td>
                                    <a href="admin-create-user.php?edit_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-info me-2" title="Editar Utilizador">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <a href="process_user_management.php?action=delete&user_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-danger" title="Apagar Utilizador" onclick="return confirm('Tem certeza que deseja APAGAR o utilizador <?php echo htmlspecialchars($user['username']); ?>?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                                }
                            } else {
                                echo '<tr><td colspan="6" class="text-center text-subtle">Nenhum utilizador encontrado.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
    
    <nav class="mobile-nav-app d-lg-none">
        <a href="admin-dashboard.php" class="mobile-nav-item <?php echo ($current_page == 'admin-dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i>
            <div>Dash</div>
        </a>
        <a href="admin-active-listings.php" class="mobile-nav-item <?php echo ($current_page == 'admin-active-listings.php') ? 'active' : ''; ?>">
            <i class="fas fa-car"></i>
            <div>Anúncios</div>
        </a>
        <a href="admin-new-listing.php" class="mobile-nav-item <?php echo ($current_page == 'admin-new-listing.php') ? 'active' : ''; ?>">
            <i class="fas fa-plus-circle"></i>
            <div>Novo</div>
        </a>
        
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
        <a href="admin-users.php" class="mobile-nav-item <?php echo ($current_page == 'admin-users.php') ? 'active' : ''; ?>">
            <i class="fas fa-users-cog"></i>
            <div>Users</div>
        </a>
        <?php endif; ?>
        
        <a href="logout.php" class="mobile-nav-item">
            <i class="fas fa-sign-out-alt"></i>
            <div>Sair</div>
        </a>
    </nav>
    
    </body>
</html>