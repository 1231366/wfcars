<?php
session_start();
include 'db_connect.php'; 

// Redireciona se o utilizador não estiver autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Lógica de Filtragem: Aceita todos os estados válidos
$valid_statuses = ['Ativo', 'Vendido', 'Reservado', 'Brevemente'];
$current_status = isset($_GET['list']) && in_array($_GET['list'], $valid_statuses) ? $_GET['list'] : 'Ativo';

// Função para mostrar mensagens de status (reutilizada)
function display_status_message() {
    if (isset($_GET['status']) && isset($_GET['message'])) {
        $status = htmlspecialchars($_GET['status']);
        // Mensagem vem de base64_encode ou urlencode
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

// --- Lógica para buscar anúncios (dinâmica) ---
$sql_listings = "SELECT 
                    a.id, 
                    a.titulo, 
                    a.modelo_ano, 
                    a.quilometragem, 
                    a.preco,
                    a.status,
                    a.destaque, /* NOVO: Buscar estado de destaque */
                    (SELECT caminho_foto FROM fotos_anuncio WHERE anuncio_id = a.id AND is_principal = 1 LIMIT 1) AS foto_principal
                FROM 
                    anuncios a
                WHERE 
                    a.status = '{$current_status}'
                ORDER BY 
                    a.data_criacao DESC";

$result_listings = $conn->query($sql_listings);
$listings_count = $result_listings ? $result_listings->num_rows : 0;

// Lógica para contar estados (para os botões)
$ativos_count_all = $conn->query("SELECT COUNT(*) as count FROM anuncios WHERE status = 'Ativo'")->fetch_assoc()['count'];
$vendidos_count_all = $conn->query("SELECT COUNT(*) as count FROM anuncios WHERE status = 'Vendido'")->fetch_assoc()['count'];
$reservados_count_all = $conn->query("SELECT COUNT(*) as count FROM anuncios WHERE status = 'Reservado'")->fetch_assoc()['count'];
$brevemente_count_all = $conn->query("SELECT COUNT(*) as count FROM anuncios WHERE status = 'Brevemente'")->fetch_assoc()['count'];

// NOVO: Contar quantos carros estão atualmente em destaque (Ativos)
$destaques_count = $conn->query("SELECT COUNT(*) as count FROM anuncios WHERE status = 'Ativo' AND destaque = 1")->fetch_assoc()['count'];
$max_destaques = 3;
$can_highlight = $destaques_count < $max_destaques;
// ---------------------------------------------------
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WFCARS | Anúncios <?php echo htmlspecialchars($current_status); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.umd.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
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
        
        /* Estilos ESPECÍFICOS para a Lista de Anúncios */
        .listing-card-item {
            background-color: rgb(var(--mdb-dark-rgb)) !important;
            border: 1px solid rgba(192, 192, 192, 0.2);
            border-radius: 0.5rem;
            overflow: hidden;
            transition: transform 0.3s;
        }
        .listing-card-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.5);
        }
        .listing-price { font-weight: 800; font-size: 1.5rem; }
        .listing-meta { color: rgb(var(--mdb-secondary-rgb)); opacity: 0.7; font-size: 0.85rem; }
        .action-icon { color: rgb(var(--mdb-secondary-rgb)); }
        
        /* Estilo para imagem de listagem */
        .listing-card-img {
            height: 180px; 
            object-fit: cover;
            width: 100%;
        }

        /* Efeito para quando o carro está vendido */
        .listing-card-item.sold {
            opacity: 0.7;
            border-color: #58151c;
            background-color: rgb(var(--mdb-dark-rgb), 0.8) !important;
        }
        
        /* NOVO: Estilo para Destaque */
        .listing-card-item.featured {
             border-left: 5px solid gold !important;
             box-shadow: 0 0 10px rgba(255, 215, 0, 0.4);
        }
        
        /* Toastr Customização (Opcional) */
        #toast-container > .toast-success { background-color: #1f4420 !important; }
        #toast-container > .toast-error { background-color: #58151c !important; }


        /* === ESTILOS DO SIDEBAR (DARK CLEAN MINIMAL) === */
        .sidebar-menu { 
            min-height: 100vh; 
            background-color: rgb(var(--sidebar-bg)) !important; 
            width: 240px; 
            border-right: none !important; 
            box-shadow: none !important;
            padding-top: 20px;
        }

        /* Limpeza e Estilo Padrão dos Itens da Lista */
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
        
        /* Cor dos Ícones */
        .list-group-item i {
            color: rgb(var(--link-color));
            font-size: 1.1rem; 
            margin-right: 15px; 
            width: 20px;
            text-align: center;
        }

        /* Efeito Hover */
        .list-group-item:hover:not(.active):not(.text-danger) {
            background-color: rgba(var(--active-bg), 0.5) !important; 
            color: white !important;
        }
        .list-group-item:hover:not(.active):not(.text-danger) i {
            color: white !important;
        }

        /* Item Ativo */
        .list-group-item.active {
            font-weight: 600;
            background-color: rgb(var(--active-bg)) !important; 
            color: rgb(var(--active-text-color)) !important; 
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); 
        }

        .list-group-item.active i {
            color: rgb(var(--active-icon-color)) !important; 
        }

        /* Divisor */
        .sidebar-divider {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin: 20px 0;
        }

        /* Item Sair */
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
            main { padding-bottom: 75px; } 
            .admin-title-desktop { font-size: 2.5rem; }
        }

        @media (min-width: 992px) {
            main { 
                margin-left: 240px !important; 
                margin-top: 0 !important; 
                padding-top: 20px !important; 
            }
        }
    </style>
</head>
<body class="bg-dark text-white">
    <?php display_status_message(); ?>

    <nav id="sidebarMenu" class="d-none d-lg-block sidebar-menu fixed-top shadow">
        <div class="position-sticky d-flex flex-column h-100">
            <div class="sidebar-top-section">
                <div class="text-center mt-4 mb-5">
                    <img src="logo.png" alt="WF Cars" class="sidebar-logo mb-3" />
                </div>
                <div class="list-group list-group-flush mx-3">
                    <a href="admin-dashboard.php" class="list-group-item list-group-item-action ripple <?php echo (basename($_SERVER['PHP_SELF']) == 'admin-dashboard.php') ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line fa-fw"></i><span>Dashboard</span>
                    </a>
                    <a href="admin-active-listings.php" class="list-group-item list-group-item-action ripple <?php echo (basename($_SERVER['PHP_SELF']) == 'admin-active-listings.php') ? 'active' : ''; ?>">
                        <i class="fas fa-car fa-fw"></i><span>Anúncios Ativos</span>
                    </a>
                    <a href="admin-new-listing.php" class="list-group-item list-group-item-action ripple <?php echo (basename($_SERVER['PHP_SELF']) == 'admin-new-listing.php' || basename($_SERVER['PHP_SELF']) == 'admin-create-user.php') ? 'active' : ''; ?>">
                        <i class="fas fa-plus fa-fw"></i><span>Novo Anúncio</span>
                    </a>
                    
                    <?php 
                    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): 
                    ?>
                    <div class="sidebar-divider"></div>
                    <a href="admin-users.php" class="list-group-item list-group-item-action ripple <?php echo (basename($_SERVER['PHP_SELF']) == 'admin-users.php' || basename($_SERVER['PHP_SELF']) == 'admin-create-user.php') ? 'active' : ''; ?>">
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
    <nav class="navbar navbar-dark bg-dark d-lg-none" style="background-color: rgb(var(--sidebar-bg)) !important;">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin-dashboard.php">
                <img src="logo.png" alt="WF Cars" class="sidebar-logo me-2" />
                <span class="text-highlight">Anúncios Ativos</span>
            </a>
        </div>
    </nav>
    <main>
        
        <h1 class="admin-title-desktop mb-5">
            Anúncios <span class="text-highlight"><?php echo htmlspecialchars($current_status); ?></span>
        </h1>
        
        <div class="mb-4 d-flex flex-wrap gap-2">
            <a href="admin-active-listings.php?list=Ativo" class="btn btn-sm btn-rounded 
                <?php echo $current_status === 'Ativo' ? 
                    'btn-light' : 
                    'btn-secondary-subtle text-white'; ?>" 
                style="background-color: <?php echo $current_status === 'Ativo' ? 'rgb(var(--mdb-secondary-rgb))' : 'rgb(45, 45, 45)'; ?>; color: <?php echo $current_status === 'Ativo' ? 'rgb(var(--mdb-primary-rgb))' : 'white'; ?>;"
            >
                Ativos (<?php echo $ativos_count_all; ?>)
            </a>
            
            <a href="admin-active-listings.php?list=Vendido" class="btn btn-sm btn-rounded 
                <?php echo $current_status === 'Vendido' ? 
                    'btn-light' : 
                    'btn-secondary-subtle text-white'; ?>" 
                style="background-color: <?php echo $current_status === 'Vendido' ? 'rgb(var(--mdb-secondary-rgb))' : 'rgb(45, 45, 45)'; ?>; color: <?php echo $current_status === 'Vendido' ? 'rgb(var(--mdb-primary-rgb))' : 'white'; ?>;"
            >
                Vendidos (<?php echo $vendidos_count_all; ?>)
            </a>

            <a href="admin-active-listings.php?list=Reservado" class="btn btn-sm btn-rounded 
                <?php echo $current_status === 'Reservado' ? 
                    'btn-light' : 
                    'btn-secondary-subtle text-white'; ?>" 
                style="background-color: <?php echo $current_status === 'Reservado' ? 'rgb(var(--mdb-secondary-rgb))' : 'rgb(45, 45, 45)'; ?>; color: <?php echo $current_status === 'Reservado' ? 'rgb(var(--mdb-primary-rgb))' : 'white'; ?>;"
            >
                Reservados (<?php echo $reservados_count_all; ?>)
            </a>

            <a href="admin-active-listings.php?list=Brevemente" class="btn btn-sm btn-rounded 
                <?php echo $current_status === 'Brevemente' ? 
                    'btn-light' : 
                    'btn-secondary-subtle text-white'; ?>" 
                style="background-color: <?php echo $current_status === 'Brevemente' ? 'rgb(var(--mdb-secondary-rgb))' : 'rgb(45, 45, 45)'; ?>; color: <?php echo $current_status === 'Brevemente' ? 'rgb(var(--mdb-primary-rgb))' : 'white'; ?>;"
            >
                Brevemente (<?php echo $brevemente_count_all; ?>)
            </a>
        </div>
        
        <?php if ($current_status === 'Ativo'): ?>
            <p class="text-warning small mb-4">
                 <i class="fas fa-star me-1"></i> Destaques Ativos: <?php echo $destaques_count; ?> de <?php echo $max_destaques; ?>
            </p>
        <?php endif; ?>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            
            <?php 
            if ($result_listings && $result_listings->num_rows > 0) {
                while ($anuncio = $result_listings->fetch_assoc()) {
                    // Usa a foto principal dinâmica ou placeholder
                    $image_src = $anuncio['foto_principal'] ? '../' . htmlspecialchars($anuncio['foto_principal']) : 'heroimage.jpeg';
                    $preco_formatado = "€ " . number_format($anuncio['preco'], 0, ',', '.');
                    $km_formatado = number_format($anuncio['quilometragem'], 0, ',', '.') . 'km';
            ?>
            <div class="col">
                <div class="card listing-card-item <?php echo $anuncio['status'] === 'Vendido' ? 'sold' : ''; ?> <?php echo $anuncio['destaque'] == 1 ? 'featured' : ''; ?>">
                    <a href="car-details.php?id=<?php echo $anuncio['id']; ?>" target="_blank" title="Ver Detalhes (Frontend)">
                        <img src="<?php echo $image_src; ?>" class="listing-card-img" alt="<?php echo htmlspecialchars($anuncio['titulo']); ?>">
                    </a>
                    <div class="card-body">
                        <h5 class="card-title text-highlight"><?php echo htmlspecialchars($anuncio['titulo']); ?></h5>
                        <p class="listing-meta"><?php echo $anuncio['modelo_ano'] . ' | ' . $km_formatado; ?></p>
                        
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="listing-price text-highlight"><?php echo $preco_formatado; ?></span>
                            <div class="d-flex align-items-center flex-wrap gap-1">
                                
                                <?php if ($anuncio['status'] !== 'Vendido'): ?>
                                    
                                    <?php if ($anuncio['status'] === 'Ativo'): ?>
                                        <?php if ($anuncio['destaque'] == 1): ?>
                                            <form method="POST" action="process_listing_action.php" class="d-inline-block">
                                                <input type="hidden" name="action" value="unmark_highlight">
                                                <input type="hidden" name="anuncio_id" value="<?php echo $anuncio['id']; ?>">
                                                <button type="button" class="btn btn-sm btn-outline-secondary-subtle text-warning" title="Remover Destaque" 
                                                        onclick="confirmAction(this, 'Remover Destaque', 'Tem certeza que deseja remover o destaque deste anúncio?', 'unmark_highlight', '<?php echo $anuncio['id']; ?>');">
                                                    <i class="fas fa-star-half-alt"></i>
                                                </button>
                                            </form>
                                        <?php elseif ($can_highlight): ?>
                                            <form method="POST" action="process_listing_action.php" class="d-inline-block">
                                                <input type="hidden" name="action" value="mark_highlight">
                                                <input type="hidden" name="anuncio_id" value="<?php echo $anuncio['id']; ?>">
                                                <button type="button" class="btn btn-sm btn-outline-secondary-subtle text-warning" title="Marcar como Destaque"
                                                        onclick="confirmAction(this, 'Marcar Destaque', 'Tem certeza que deseja marcar este anúncio como **DESTAQUE**?', 'mark_highlight', '<?php echo $anuncio['id']; ?>');">
                                                    <i class="far fa-star"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                             <button class="btn btn-sm btn-outline-secondary-subtle text-secondary" title="Limite de Destaques Atingido" disabled>
                                                 <i class="far fa-star"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <form method="POST" action="process_listing_action.php" class="d-inline-block">
                                        <input type="hidden" name="action" value="mark_reserved">
                                        <input type="hidden" name="anuncio_id" value="<?php echo $anuncio['id']; ?>">
                                        <button type="button" class="btn btn-sm btn-outline-secondary-subtle text-warning" title="Marcar Reservado"
                                                onclick="confirmAction(this, 'Reservar', 'Marcar como RESERVADO?', 'mark_reserved', '<?php echo $anuncio['id']; ?>');">
                                            <i class="fas fa-bookmark"></i>
                                        </button>
                                    </form>

                                    <form method="POST" action="process_listing_action.php" class="d-inline-block">
                                        <input type="hidden" name="action" value="mark_soon">
                                        <input type="hidden" name="anuncio_id" value="<?php echo $anuncio['id']; ?>">
                                        <button type="button" class="btn btn-sm btn-outline-secondary-subtle text-info" title="Marcar Brevemente"
                                                onclick="confirmAction(this, 'Brevemente', 'Marcar como BREVEMENTE?', 'mark_soon', '<?php echo $anuncio['id']; ?>');">
                                            <i class="fas fa-clock"></i>
                                        </button>
                                    </form>
                                
                                    <form method="POST" action="process_listing_action.php" class="d-inline-block">
                                        <input type="hidden" name="action" value="mark_sold">
                                        <input type="hidden" name="anuncio_id" value="<?php echo $anuncio['id']; ?>">
                                        <button type="button" class="btn btn-sm btn-outline-secondary-subtle text-success" title="Marcar como Vendido"
                                                onclick="confirmAction(this, 'Marcar Vendido', 'Tem certeza que deseja marcar **<?php echo htmlspecialchars($anuncio['titulo']); ?>** como VENDIDO? Ele será removido da lista Ativa.', 'mark_sold', '<?php echo $anuncio['id']; ?>');">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>

                                    <?php if ($anuncio['status'] !== 'Ativo'): ?>
                                        <form method="POST" action="process_listing_action.php" class="d-inline-block">
                                            <input type="hidden" name="action" value="restore">
                                            <input type="hidden" name="anuncio_id" value="<?php echo $anuncio['id']; ?>">
                                            <button type="button" class="btn btn-sm btn-outline-secondary-subtle text-white" title="Restabelecer como Ativo"
                                                    onclick="confirmAction(this, 'Restaurar Anúncio', 'Tem certeza que deseja **RESTAURAR** o anúncio **<?php echo htmlspecialchars($anuncio['titulo']); ?>**? Ele voltará para a lista de ATIVOS.', 'restore', '<?php echo $anuncio['id']; ?>');">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                <?php else: ?>
                                    <form method="POST" action="process_listing_action.php" class="d-inline-block me-2">
                                        <input type="hidden" name="action" value="restore">
                                        <input type="hidden" name="anuncio_id" value="<?php echo $anuncio['id']; ?>">
                                        <button type="button" class="btn btn-sm btn-outline-secondary-subtle text-warning" title="Restabelecer como Ativo"
                                                onclick="confirmAction(this, 'Restaurar Anúncio', 'Tem certeza que deseja **RESTAURAR** o anúncio **<?php echo htmlspecialchars($anuncio['titulo']); ?>**? Ele voltará para a lista de ATIVOS.', 'restore', '<?php echo $anuncio['id']; ?>');">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <a href="admin-edit-listing.php?id=<?php echo $anuncio['id']; ?>" class="btn btn-sm btn-outline-secondary-subtle text-info" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <form method="POST" action="process_listing_action.php" class="d-inline-block">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="anuncio_id" value="<?php echo $anuncio['id']; ?>">
                                    <button type="button" class="btn btn-sm btn-outline-secondary-subtle text-danger" title="Apagar"
                                            onclick="confirmAction(this, 'Apagar Anúncio', 'ATENÇÃO: Tem certeza que deseja **APAGAR** este anúncio permanentemente? Esta ação é IRREVERSÍVEL.', 'delete', '<?php echo $anuncio['id']; ?>');">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
                }
            } else {
                echo '<p class="text-subtle ms-4">Não existem anúncios com o estado ' . htmlspecialchars($current_status) . '.</p>';
            }
            ?>

        </div>

    </main>
    
    <nav class="mobile-nav-app d-lg-none">
        <a href="admin-dashboard.php" class="mobile-nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'admin-dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i>
            <div>Dash</div>
        </a>
        <a href="admin-active-listings.php" class="mobile-nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'admin-active-listings.php') ? 'active' : ''; ?>">
            <i class="fas fa-car"></i>
            <div>Anúncios</div>
        </a>
        <a href="admin-new-listing.php" class="mobile-nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'admin-new-listing.php') ? 'active' : ''; ?>">
            <i class="fas fa-plus-circle"></i>
            <div>Novo</div>
        </a>
        
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
        <a href="admin-users.php" class="mobile-nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'admin-users.php' || basename($_SERVER['PHP_SELF']) == 'admin-create-user.php') ? 'active' : ''; ?>">
            <i class="fas fa-users-cog"></i>
            <div>Users</div>
        </a>
        <?php endif; ?>
        
        <a href="logout.php" class="mobile-nav-item">
            <i class="fas fa-sign-out-alt"></i>
            <div>Sair</div>
        </a>
    </nav>
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-highlight-card text-white">
                <div class="modal-header border-bottom border-secondary-subtle">
                    <h5 class="modal-title" id="confirmationModalLabel">Confirmação de Ação</h5>
                    <button type="button" class="btn-close btn-close-white" data-mdb-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="modalMessage" class="lead text-subtle"></p>
                    <p class="text-warning mt-4"><i class="fas fa-exclamation-triangle me-2"></i> Esta ação não pode ser desfeita, exceto o Restauro/Venda.</p>
                </div>
                <div class="modal-footer border-top border-secondary-subtle">
                    <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmSubmitBtn">Confirmar</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // === LÓGICA TOASTR ===
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-bottom-right",
            "timeOut": "5000",
            "extendedTimeOut": "1000"
        }

        function displayToastr(status, message) {
            // Decodifica Base64
            let decodedMessage = atob(message); 
            
            if (status === 'success') {
                toastr.success(decodedMessage, 'Sucesso!');
            } else if (status === 'error') {
                toastr.error(decodedMessage, 'Erro!');
            }
        }

        // Verifica os parâmetros da URL e dispara o Toastr
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        const message = urlParams.get('message');

        if (status && message) {
            displayToastr(status, message);
            
            // Limpa os parâmetros da URL para que a notificação não apareça em um refresh
            if (window.history.replaceState) {
                const url = window.location.protocol + "//" + window.location.host + window.location.pathname + window.location.search.replace(/[\?&]status=[^&]*|[\?&]message=[^&]*/g, "").replace(/^&/, '?');
                window.history.replaceState({path: url}, '', url);
            }
        }

        // === LÓGICA MODAL DE CONFIRMAÇÃO (Para ações de Carro) ===
        function confirmAction(triggerButton, title, message, action, id) {
            const modalTitle = document.getElementById('confirmationModalLabel');
            const modalMessage = document.getElementById('modalMessage');
            const confirmBtn = document.getElementById('confirmSubmitBtn');
            const modalElement = new mdb.Modal(document.getElementById('confirmationModal'));

            modalTitle.textContent = title;
            modalMessage.innerHTML = message;
            
            // Remove handlers antigos e adiciona o novo
            confirmBtn.onclick = null; 
            confirmBtn.addEventListener('click', function submitHandler() {
                // Remove o handler para evitar múltiplos submissions
                confirmBtn.removeEventListener('click', submitHandler);
                
                // Encontra a form correspondente e submete
                const form = triggerButton.closest('form');
                form.submit();
                
                // Esconde o modal
                modalElement.hide();
            });

            // Mostra o modal
            modalElement.show();
        }
    </script>
</body>
</html>