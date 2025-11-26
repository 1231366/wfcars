<?php
// === BLOC DA SESSÃO E SEGURANÇA ===
session_start();
include 'db_connect.php';

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
// === FIM BLOC SESSÃO E SEGURANÇA ===
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WFCARS | Novo Anúncio</title>
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
        
        .file-upload-box {
            border: 4px dashed rgba(192, 192, 192, 0.4);
            padding: 40px;
            text-align: center;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        .file-upload-box:hover {
            border-color: rgb(var(--mdb-secondary-rgb));
            background-color: rgba(192, 192, 192, 0.05);
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
                <span class="text-highlight">Novo Anúncio</span>
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
                    <a href="admin-new-listing.php" class="list-group-item list-group-item-action ripple active">
                        <i class="fas fa-plus fa-fw"></i><span class="text-highlight">Novo Anúncio</span>
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
            Criar <span class="text-highlight">Novo Anúncio</span>
        </h1>

        <form action="create_listing.php" method="POST" enctype="multipart/form-data">
            
            <div class="card bg-highlight-card shadow-lg mb-4">
                <div class="card-header bg-transparent border-bottom border-secondary-subtle">
                    <h5 class="text-white mb-0">Galeria de Fotos</h5>
                </div>
                <div class="card-body">
                    <div class="file-upload-box mb-3" onclick="document.getElementById('image-upload').click()">
                        <i class="fas fa-cloud-upload-alt fa-3x text-highlight mb-3"></i>
                        <p class="text-highlight fw-bold">+ Upload Photos</p>
                        <p class="text-subtle small mt-1">Arrastar & Soltar ou Clicar (Máx. 8 fotos)</p>
                        <input type="file" multiple class="d-none" id="image-upload" name="car_images[]" accept="image/jpeg, image/png">
                    </div>
                     <p class="text-info small text-center"><i class="fas fa-info-circle me-1"></i> Use fotos de alta qualidade para atrair compradores.</p>
                </div>
            </div>

            <div class="card bg-highlight-card shadow-lg mb-4">
                <div class="card-header bg-transparent border-bottom border-secondary-subtle">
                    <h5 class="text-white mb-0">Detalhes da Viatura</h5>
                </div>
                <div class="card-body">
                    <div class="form-outline mb-4">
                        <input type="text" id="modelo" name="modelo" class="form-control" placeholder="Porsche 911 GT3 Clubsport 2023" required />
                        <label class="form-label" for="modelo">Título do Anúncio / Modelo</label>
                    </div>
                    
                    <div class="form-outline mb-4">
                         <textarea id="descricao" name="descricao" rows="4" class="form-control" placeholder="Diga aos compradores mais sobre o carro (história, extras, etc)."></textarea>
                        <label class="form-label" for="descricao">Descrição Completa</label>
                    </div>
                </div>
            </div>

            <div class="card bg-highlight-card shadow-lg mb-4">
                <div class="card-header bg-transparent border-bottom border-secondary-subtle">
                    <h5 class="text-white mb-0">Ficha Técnica & Preço</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="form-outline">
                                <input type="text" id="marca" name="marca" class="form-control" placeholder="Marca" required />
                                <label class="form-label" for="marca">Marca</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-outline">
                                <input type="number" id="ano" name="ano" class="form-control" placeholder="Ano" required />
                                <label class="form-label" for="ano">Ano</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-outline">
                                <input type="number" id="preco" name="preco" class="form-control" placeholder="Preço (€)" required />
                                <label class="form-label" for="preco">Preço Final (€)</label>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-outline">
                                <input type="number" id="km" name="km" class="form-control" placeholder="KM" required />
                                <label class="form-label" for="km">Quilometragem (KM)</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                             <div class="form-outline">
                                <input type="number" id="hp" name="hp" class="form-control" placeholder="HP" required />
                                <label class="form-label" for="hp">Potência (HP)</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select id="transmissao" name="transmissao" class="form-select text-white" style="background-color: rgb(var(--mdb-primary-rgb)); border-color: rgba(192, 192, 192, 0.5);">
                                <option value="Automática">Automática</option>
                                <option value="Manual">Manual</option>
                            </select>
                            <label class="form-label select-label text-highlight">Transmissão</label>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-lg btn-block btn-rounded text-dark fw-bold" style="background-color: rgb(var(--mdb-secondary-rgb));">
                PUBLICAR ANÚNCIO
            </button>
        </form>

    </main>
    
    <nav class="mobile-nav-app d-lg-none">
        <a href="admin-dashboard.php" class="mobile-nav-item">
            <i class="fas fa-chart-line"></i>
            <div>Dash</div>
        </a>
        <a href="admin-active-listings.php" class="mobile-nav-item">
            <i class="fas fa-car"></i>
            <div>Anúncios</div>
        </a>
        <a href="#" class="mobile-nav-item">
            <i class="fas fa-search-dollar"></i>
            <div>Vendas</div>
        </a>
        <a href="admin-new-listing.php" class="mobile-nav-item active">
            <i class="fas fa-plus-circle"></i>
            <div>Novo</div>
        </a>
        <a href="logout.php" class="mobile-nav-item">
            <i class="fas fa-sign-out-alt"></i>
            <div>Sair</div>
        </a>
    </nav>
    
    <script>
        document.getElementById('image-upload').addEventListener('change', (event) => {
            const files = event.target.files;
            if (files.length > 0) {
                alert(`Selecionados ${files.length} ficheiro(s) para upload. Nota: O processamento de fotos ainda não está ativo no backend.`);
            }
        });
    </script>
</body>
</html>