<?php
session_start();
include 'db_connect.php'; 

// Redireciona se o utilizador não estiver autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Lógica de Filtragem: Padrão é 'Ativo'
$current_status = isset($_GET['list']) && in_array($_GET['list'], ['Vendido', 'Ativo']) ? $_GET['list'] : 'Ativo';

// Função para mostrar mensagens de status (sucesso/erro)
function display_status_message() {
    if (isset($_GET['status']) && isset($_GET['message'])) {
        $status = htmlspecialchars($_GET['status']);
        $message = htmlspecialchars($_GET['message']);
        
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
                    (SELECT caminho_foto FROM fotos_anuncio WHERE anuncio_id = a.id ORDER BY id ASC LIMIT 1) AS foto_principal
                FROM 
                    anuncios a
                WHERE 
                    a.status = '{$current_status}'
                ORDER BY 
                    a.data_criacao DESC";

$result_listings = $conn->query($sql_listings);
$listings_count = $result_listings ? $result_listings->num_rows : 0;

// Lógica para contar ativos e vendidos (para os botões)
$ativos_count_all = $conn->query("SELECT COUNT(*) as count FROM anuncios WHERE status = 'Ativo'")->fetch_assoc()['count'];
$vendidos_count_all = $conn->query("SELECT COUNT(*) as count FROM anuncios WHERE status = 'Vendido'")->fetch_assoc()['count'];
// ---------------------------------------------------
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WFCARS | Anúncios <?php echo $current_status === 'Ativo' ? 'Ativos' : 'Vendidos'; ?></title>
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

    <nav class="navbar navbar-dark bg-dark d-lg-none" style="background-color: rgb(var(--sidebar-bg)) !important;">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin-dashboard.php">
                <img src="logo.png" alt="WF Cars" class="sidebar-logo me-2" />
                <span class="text-highlight">Anúncios Ativos</span>
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
                    <a href="admin-active-listings.php?list=Ativo" class="list-group-item list-group-item-action ripple <?php echo $current_status === 'Ativo' ? 'active' : ''; ?>">
                        <i class="fas fa-car fa-fw"></i><span class="text-highlight">Anúncios Ativos</span>
                    </a>
                    <a href="admin-new-listing.php" class="list-group-item list-group-item-action ripple">
                        <i class="fas fa-plus fa-fw"></i><span>Novo Anúncio</span>
                    </a>
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
            Anúncios <span class="text-highlight"><?php echo $current_status === 'Ativo' ? 'Ativos' : 'Vendidos'; ?></span>
        </h1>
        
        <div class="mb-4 d-flex">
            <a href="admin-active-listings.php?list=Ativo" class="btn btn-sm btn-rounded me-2 
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
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            
            <?php 
            if ($result_listings && $result_listings->num_rows > 0) {
                while ($anuncio = $result_listings->fetch_assoc()) {
                    // Usa a foto principal dinâmica ou placeholder
                    $image_src = $anuncio['foto_principal'] ? htmlspecialchars($anuncio['foto_principal']) : 'heroimage.jpeg';
                    $preco_formatado = "€ " . number_format($anuncio['preco'], 0, ',', '.');
                    $km_formatado = number_format($anuncio['quilometragem'], 0, ',', '.') . 'km';
            ?>
            <div class="col">
                <div class="card listing-card-item <?php echo $anuncio['status'] === 'Vendido' ? 'sold' : ''; ?>">
                    <a href="car-details.html?id=<?php echo $anuncio['id']; ?>" target="_blank" title="Ver Detalhes (Frontend)">
                        <img src="<?php echo $image_src; ?>" class="listing-card-img" alt="<?php echo htmlspecialchars($anuncio['titulo']); ?>">
                    </a>
                    <div class="card-body">
                        <h5 class="card-title text-highlight"><?php echo htmlspecialchars($anuncio['titulo']); ?></h5>
                        <p class="listing-meta"><?php echo $anuncio['modelo_ano'] . ' | ' . $km_formatado; ?></p>
                        
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="listing-price text-highlight"><?php echo $preco_formatado; ?></span>
                            <div class="d-flex align-items-center">
                                
                                <?php if ($anuncio['status'] === 'Ativo'): ?>
                                    <form method="POST" action="process_listing_action.php" class="d-inline-block me-2">
                                        <input type="hidden" name="action" value="mark_sold">
                                        <input type="hidden" name="anuncio_id" value="<?php echo $anuncio['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary-subtle text-success" title="Marcar como Vendido" onclick="return confirm('Tem certeza que deseja marcar este anúncio como VENDIDO?');">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="process_listing_action.php" class="d-inline-block me-2">
                                        <input type="hidden" name="action" value="restore">
                                        <input type="hidden" name="anuncio_id" value="<?php echo $anuncio['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary-subtle text-warning" title="Restabelecer como Ativo" onclick="return confirm('Tem certeza que deseja RESTAURAR este anúncio? Ele voltará para a lista de ATIVOS.');">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <a href="admin-edit-listing.php?id=<?php echo $anuncio['id']; ?>" class="btn btn-sm btn-outline-secondary-subtle text-info me-2" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <form method="POST" action="process_listing_action.php" class="d-inline-block">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="anuncio_id" value="<?php echo $anuncio['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary-subtle text-danger" title="Apagar" onclick="return confirm('ATENÇÃO: Tem certeza que deseja APAGAR este anúncio e todas as suas fotos? Esta ação é IRREVERSÍVEL.');">
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
                echo '<p class="text-subtle ms-4">Não existem anúncios ' . ($current_status === 'Ativo' ? 'ativos' : 'vendidos') . ' para mostrar.</p>';
            }
            ?>

        </div>

    </main>
    
    <nav class="mobile-nav-app d-lg-none">
        <a href="admin-dashboard.php" class="mobile-nav-item">
            <i class="fas fa-chart-line"></i>
            <div>Dash</div>
        </a>
        <a href="admin-active-listings.php?list=Ativo" class="mobile-nav-item <?php echo $current_status === 'Ativo' ? 'active' : ''; ?>">
            <i class="fas fa-car"></i>
            <div>Anúncios</div>
        </a>
        <a href="admin-active-listings.php?list=Vendido" class="mobile-nav-item <?php echo $current_status === 'Vendido' ? 'active' : ''; ?>">
            <i class="fas fa-search-dollar"></i>
            <div>Vendas</div>
        </a>
        <a href="admin-new-listing.php" class="mobile-nav-item">
            <i class="fas fa-plus-circle"></i>
            <div>Novo</div>
        </a>
        <a href="logout.php" class="mobile-nav-item">
            <i class="fas fa-sign-out-alt"></i>
            <div>Sair</div>
        </a>
    </nav>
    
</body>
</html>