<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WFCARS | Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root {
            --mdb-dark-rgb: 28, 28, 28; 
            --mdb-secondary-rgb: 192, 192, 192; /* PRATA (Highlight) */
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
        .stat-value { font-size: 2.5rem; font-weight: 800; }
        .stat-label { font-weight: 500; color: rgb(var(--mdb-secondary-rgb)); opacity: 0.7; }
        .admin-title-desktop { font-size: 3rem; font-weight: 800; }
        
        /* === ESTILOS DO SIDEBAR (DARK CLEAN MINIMAL) === */
        .sidebar-menu { 
            min-height: 100vh; 
            background-color: rgb(var(--sidebar-bg)) !important; 
            width: 240px; 
            border-right: none !important; 
            box-shadow: none !important;
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

        /* Item Sair (Corrigido com Flexbox, logo as regras de position absolute foram removidas) */
        .list-group-item.text-danger {
            color: var(--mdb-danger) !important;
            border-top: none !important;
            border-bottom: none !important;
            margin-bottom: 0 !important; /* Limpa margens desnecessárias no fundo */
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

    <nav class="navbar navbar-dark bg-dark d-lg-none" style="background-color: rgb(var(--sidebar-bg)) !important;">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin-dashboard.html">
                <img src="logo.png" alt="WF Cars" class="sidebar-logo me-2" />
                <span class="text-highlight">Dashboard</span>
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
                    <a href="admin-dashboard.html" class="list-group-item list-group-item-action ripple active">
                        <i class="fas fa-chart-line fa-fw"></i><span>Dashboard</span>
                    </a>
                    <a href="admin-active-listings.html" class="list-group-item list-group-item-action ripple">
                        <i class="fas fa-car fa-fw"></i><span>Anúncios Ativos</span>
                    </a>
                    <a href="admin-new-listing.html" class="list-group-item list-group-item-action ripple">
                        <i class="fas fa-plus fa-fw"></i><span>Novo Anúncio</span>
                    </a>
                </div>
            </div>

            <div class="list-group list-group-flush mx-3 mt-auto mb-3">
                 <div class="sidebar-divider"></div>
                <a href="index.html" class="list-group-item ripple text-danger">
                    <i class="fas fa-sign-out-alt fa-fw"></i><span>Sair</span>
                </a>
            </div>
        </div>
    </nav>

    <main>
        
        <h1 class="admin-title-desktop mb-5">
            Painel <span class="text-highlight">Geral</span>
        </h1>
        
        <section class="mb-4">
            <div class="row">
                
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card bg-highlight-card border-0 shadow-lg">
                        <div class="card-body">
                            <h5 class="stat-value text-highlight">3</h5>
                            <p class="stat-label text-uppercase">Anúncios Ativos</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card bg-highlight-card border-0 shadow-lg">
                        <div class="card-body">
                            <h5 class="stat-value text-highlight">5</h5>
                            <p class="stat-label text-uppercase">Vendidos (Último Mês)</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card bg-highlight-card border-0 shadow-lg">
                        <div class="card-body">
                            <h5 class="stat-value text-highlight">34</h5>
                            <p class="stat-label text-uppercase">Vendidos (Último Ano)</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card bg-highlight-card border-0 shadow-lg">
                        <div class="card-body">
                            <h5 class="stat-value text-highlight">€ 1.2M</h5>
                            <p class="stat-label text-uppercase">Faturação (Último Ano)</p>
                        </div>
                    </div>
                </div>

            </div>
        </section>
        
        <section class="card bg-highlight-card border-0 shadow-lg mb-5">
            <div class="card-header bg-transparent border-bottom border-secondary-subtle">
                <h5 class="text-white mb-0">Atividade Recente</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush bg-highlight-card">
                    <li class="list-group-item bg-transparent text-white border-bottom border-secondary-subtle">
                        <span class="badge bg-success-subtle text-success me-2">VENDIDO</span> Mercedes AMG GT (Faturação de €250k)
                        <span class="float-end text-subtle">2 dias atrás</span>
                    </li>
                    <li class="list-group-item bg-transparent text-white border-bottom border-secondary-subtle">
                        <span class="badge bg-warning-subtle text-warning me-2">NOVO</span> Ferrari SF90 Stradale
                        <span class="float-end text-subtle">1 semana atrás</span>
                    </li>
                    <li class="list-group-item bg-transparent text-white border-bottom border-secondary-subtle">
                        <span class="badge bg-info-subtle text-info me-2">CONSULTA</span> 2 novos pedidos de contacto no formulário.
                        <span class="float-end text-subtle">1 dia atrás</span>
                    </li>
                </ul>
            </div>
        </section>

    </main>
    
    <nav class="mobile-nav-app d-lg-none">
        <a href="admin-dashboard.html" class="mobile-nav-item active">
            <i class="fas fa-chart-line"></i>
            <div>Dash</div>
        </a>
        <a href="admin-active-listings.html" class="mobile-nav-item">
            <i class="fas fa-car"></i>
            <div>Anúncios</div>
        </a>
        <a href="#" class="mobile-nav-item">
            <i class="fas fa-search-dollar"></i>
            <div>Vendas</div>
        </a>
        <a href="admin-new-listing.html" class="mobile-nav-item">
            <i class="fas fa-plus-circle"></i>
            <div>Novo</div>
        </a>
        <a href="index.html" class="mobile-nav-item">
            <i class="fas fa-sign-out-alt"></i>
            <div>Sair</div>
        </a>
    </nav>
    
</body>
</html>