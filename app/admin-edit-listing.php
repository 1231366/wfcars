<?php
/**
 * Página de edição de um anúncio. Puxa dados existentes e pré-preenche o formulário.
 */
session_start();
include 'db_connect.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}


// 1. Obter o ID do anúncio a editar
$anuncio_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$anuncio_id) {
    $error = urlencode("ID de anúncio inválido para edição.");
    header("Location: admin-active-listings.php?status=error&message={$error}");
    exit();
}

// 2. Buscar dados do anúncio (Incluindo os novos campos)
$stmt = $conn->prepare("SELECT * FROM anuncios WHERE id = ?");
$stmt->bind_param("i", $anuncio_id);
$stmt->execute();
$result = $stmt->get_result();
$anuncio = $result->fetch_assoc();
$stmt->close();

if (!$anuncio) {
    $error = urlencode("Anúncio não encontrado.");
    header("Location: admin-active-listings.php?status=error&message={$error}");
    exit();
}

// 3. Buscar fotos existentes (caminho_foto SEM o prefixo '../' para o JS)
$photos_stmt = $conn->prepare("SELECT caminho_foto FROM fotos_anuncio WHERE anuncio_id = ? ORDER BY is_principal DESC, id ASC");
$photos_stmt->bind_param("i", $anuncio_id);
$photos_stmt->execute();
$photos_result = $photos_stmt->get_result();
$existing_photos_paths = [];
while ($row = $photos_result->fetch_assoc()) {
    $existing_photos_paths[] = $row['caminho_foto'];
}
$photos_stmt->close();

// Certifique-se de que o campo tipo_combustivel existe, caso contrário defina um valor padrão
$current_fuel = $anuncio['tipo_combustivel'] ?? 'Diesel';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WFCARS | Editar Anúncio #<?php echo $anuncio_id; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        /* Bloco de Estilos COPIADO de admin-new-listing.php */
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
        /* Cor de texto para os labels fixos */
        .input-label-fixed {
            color: rgb(var(--mdb-secondary-rgb)) !important;
            font-weight: 500;
            margin-bottom: 5px; /* Espaçamento entre o label e o input */
            display: block; /* Garante que o label ocupe toda a largura */
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
        
        /* Estilos específicos para a pré-visualização (Drag & Drop) */
        .preview-item {
            position: relative;
            height: 96px; /* h-24 */
            width: 100%;
            background-color: rgb(var(--mdb-dark-rgb));
            border-radius: 0.25rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            padding: 8px; 
            cursor: grab; /* Indica que o elemento é arrastável */
            border: 2px solid transparent;
            transition: border-color 0.2s;
        }
        .preview-item img {
            max-width: 100%; 
            max-height: 100%; 
            object-fit: contain; 
            display: block; 
        }

        /* Estilo para quando o elemento está a ser arrastado */
        .dragging {
            opacity: 0.5;
            border-color: rgba(var(--mdb-secondary-rgb), 0.8) !important;
            box-shadow: 0 0 15px rgba(var(--mdb-secondary-rgb), 0.5) !important;
        }
        /* Estilo para o drop zone */
        .drag-over {
            border-color: rgb(var(--mdb-secondary-rgb)) !important;
            box-shadow: 0 0 10px rgba(var(--mdb-secondary-rgb), 0.5);
        }


        .remove-btn {
            position: absolute;
            top: 4px;
            right: 4px;
            background-color: rgba(220, 38, 38, 0.7); 
            color: white;
            padding: 4px;
            border-radius: 9999px; 
            opacity: 0;
            transition: opacity 0.3s;
            cursor: pointer;
            border: none;
            z-index: 10;
        }
        .preview-item:hover .remove-btn {
            opacity: 1;
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
        
        /* Toastr Customização (Opcional) */
        #toast-container > .toast-success { background-color: #1f4420 !important; }
        #toast-container > .toast-error { background-color: #58151c !important; }
    </style>
</head>
<body class="bg-dark text-white">

    <nav class="navbar navbar-dark bg-dark d-lg-none" style="background-color: rgb(var(--sidebar-bg)) !important;">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin-dashboard.php">
                <img src="logo.png" alt="WF Cars" class="sidebar-logo me-2" />
                <span class="text-highlight">Editar Anúncio</span>
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
                    <a href="admin-active-listings.php" class="list-group-item list-group-item-action ripple active">
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
            Editar <span class="text-highlight">Anúncio #<?php echo $anuncio_id; ?></span>
        </h1>

        <form id="edit-form" action="update_listing.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="anuncio_id" value="<?php echo $anuncio_id; ?>">
            <input type="hidden" id="photos-to-keep-paths" name="photos_to_keep_paths" value="">

            <div class="card bg-highlight-card shadow-lg mb-4">
                <div class="card-header bg-transparent border-bottom border-secondary-subtle">
                    <h5 class="text-white mb-0">Galeria de Fotos</h5>
                </div>
                <div class="card-body">
                    <div class="file-upload-box mb-3" onclick="document.getElementById('image-upload').click()">
                        <i class="fas fa-cloud-upload-alt fa-3x text-highlight mb-3"></i>
                        <p class="text-highlight fw-bold">+ Adicionar Fotos</p>
                        <p class="text-subtle small mt-1">Arrastar & Soltar ou Clicar (Máx. 8 fotos no total)</p>
                        <input type="file" multiple class="d-none" id="image-upload" name="car_images[]" accept="image/jpeg, image/png">
                    </div>
                     <p class="text-info small text-center"><i class="fas fa-info-circle me-1"></i> Arraste os cartões para mudar a ordem ou clique no "X" para remover.</p>
                     
                    <div id="preview-container" class="row g-3 mt-4">
                        </div>

                </div>
            </div>

            <div class="card bg-highlight-card shadow-lg mb-4">
                <div class="card-header bg-transparent border-bottom border-secondary-subtle">
                    <h5 class="text-white mb-0">Detalhes da Viatura</h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <label class="input-label-fixed" for="modelo">Título do Anúncio / Modelo</label>
                        <input type="text" id="modelo" name="modelo" class="form-control" value="<?php echo htmlspecialchars($anuncio['titulo']); ?>" required />
                    </div>
                    
                    <div class="mb-4">
                        <label class="input-label-fixed" for="descricao">Descrição (Vantagens/Histórico)</label>
                         <textarea id="descricao" name="descricao" rows="4" class="form-control"><?php echo htmlspecialchars($anuncio['descricao'] ?? ''); ?></textarea>
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
                            <div class="mb-4">
                                <label class="input-label-fixed" for="marca">Marca</label>
                                <input type="text" id="marca" name="marca" class="form-control" value="<?php echo htmlspecialchars($anuncio['marca']); ?>" required />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-4">
                                <label class="input-label-fixed" for="ano">Ano</label>
                                <input type="number" id="ano" name="ano" class="form-control" value="<?php echo htmlspecialchars($anuncio['modelo_ano']); ?>" required />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-4">
                                <label class="input-label-fixed" for="preco">Preço Final (€)</label>
                                <input type="number" step="0.01" id="preco" name="preco" class="form-control" value="<?php echo htmlspecialchars(number_format((float)$anuncio['preco'], 2, '.', '')); ?>" required />
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="mb-4">
                                <label class="input-label-fixed" for="km">Quilometragem (KM)</label>
                                <input type="number" id="km" name="km" class="form-control" value="<?php echo htmlspecialchars($anuncio['quilometragem']); ?>" required />
                            </div>
                        </div>
                        <div class="col-md-3">
                             <div class="mb-4">
                                 <label class="input-label-fixed" for="hp">Potência (CV)</label>
                                <input type="number" id="hp" name="hp" class="form-control" value="<?php echo htmlspecialchars($anuncio['potencia_hp']); ?>" required />
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                             <div class="mb-4">
                                <label class="input-label-fixed" for="cilindrada">Cilindrada (CC)</label>
                                <input type="number" id="cilindrada" name="cilindrada" class="form-control" value="<?php echo htmlspecialchars($anuncio['cilindrada_cc'] ?? ''); ?>" required />
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                             <div class="mb-4">
                                <label class="input-label-fixed text-highlight" for="combustivel">Combustível</label>
                                <select id="combustivel" name="combustivel" class="form-select text-white" style="background-color: rgb(var(--mdb-primary-rgb)); border-color: rgba(192, 192, 192, 0.5);">
                                    <option value="Diesel" <?php echo ($current_fuel === 'Diesel') ? 'selected' : ''; ?>>Diesel</option>
                                    <option value="Gasolina" <?php echo ($current_fuel === 'Gasolina') ? 'selected' : ''; ?>>Gasolina</option>
                                    <option value="Híbrido" <?php echo ($current_fuel === 'Híbrido') ? 'selected' : ''; ?>>Híbrido</option>
                                    <option value="Híbrido Diesel" <?php echo ($current_fuel === 'Híbrido Diesel') ? 'selected' : ''; ?>>Híbrido Diesel</option>
                                    <option value="Híbrido Gasolina" <?php echo ($current_fuel === 'Híbrido Gasolina') ? 'selected' : ''; ?>>Híbrido Gasolina</option>
                                    <option value="Elétrico" <?php echo ($current_fuel === 'Elétrico') ? 'selected' : ''; ?>>Elétrico</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                             <div class="mb-4">
                                <label class="input-label-fixed text-highlight" for="transmissao">Transmissão</label>
                                <select id="transmissao" name="transmissao" class="form-select text-white" style="background-color: rgb(var(--mdb-primary-rgb)); border-color: rgba(192, 192, 192, 0.5);">
                                    <option value="Automática" <?php echo ($anuncio['transmissao'] === 'Automática') ? 'selected' : ''; ?>>Automática</option>
                                    <option value="Manual" <?php echo ($anuncio['transmissao'] === 'Manual') ? 'selected' : ''; ?>>Manual</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card bg-highlight-card shadow-lg mb-4">
                <div class="card-header bg-transparent border-bottom border-secondary-subtle">
                    <h5 class="text-white mb-0">Lista de Extras (Linha por Linha)</h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <label class="input-label-fixed" for="raw_extras">Extras (Lista Bruta)</label>
                         <textarea id="raw_extras" name="raw_extras" rows="8" class="form-control" placeholder="Ex:&#10;Volante aquecido&#10;Teto panorâmico&#10;Sensores estacionamento (frente e trás)&#10;GPS profissional&#10;Aviso de ângulo morto"><?php echo htmlspecialchars($anuncio['raw_extras'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end align-items-center mt-4">
                 <button type="button" onclick="window.location.href='admin-active-listings.php'" class="btn btn-lg btn-secondary-subtle me-3">
                    CANCELAR
                </button>
                <button type="submit" class="btn btn-lg btn-rounded text-dark fw-bold" style="background-color: rgb(var(--mdb-secondary-rgb));">
                    GUARDAR ALTERAÇÕES
                </button>
            </div>
        </form>

    </main>

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
            // Decodifica Base64 (assumindo que o backend usa base64)
            try {
                 let decodedMessage = atob(message); 
                 if (status === 'success') {
                    toastr.success(decodedMessage, 'Sucesso!');
                } else if (status === 'error') {
                    toastr.error(decodedMessage, 'Erro!');
                }
            } catch (e) {
                // Em caso de erro de decodificação, mostra a mensagem bruta
                 toastr.error(message, 'Erro!');
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

        // --- CÓDIGO JS DE EDIÇÃO CORRIGIDO (Lógica de Fotos com Drag & Drop) ---
        const imageUpload = document.getElementById('image-upload');
        const previewContainer = document.getElementById('preview-container');
        const form = document.getElementById('edit-form');
        const photosToKeepPathsInput = document.getElementById('photos-to-keep-paths');
        const maxFiles = 8;
        
        let dragSrcEl = null; 
        
        // Fotos existentes carregadas do PHP. Serão mapeadas para currentPreviews
        const initialExistingPaths = <?php echo json_encode($existing_photos_paths); ?>; 
        
        // Objeto FileList simulado para novos uploads
        let newUploadsFileList = new DataTransfer();

        // Variável principal para armazenar o estado das fotos novas e existentes no DOM (inclui File Objects)
        // Estrutura: { path: URL/Caminho, isNew: bool, identifier: string (caminho antigo ou nome do ficheiro novo), fileObject: File/null }
        let currentPreviews = []; 

        // Função para re-renderizar todas as miniaturas e sincronizar inputs
        function renderPreviews() {
            previewContainer.innerHTML = '';
            
            // 1. Limitar a 8 fotos no total (se necessário)
            currentPreviews = currentPreviews.slice(0, maxFiles);

            // 2. Desenhar a lista final no DOM
            currentPreviews.forEach((item, index) => {
                // Usamos 'identifier' como o nome/caminho que o backend precisa
                const colDiv = createPreviewElement(item.path, item.identifier, index, item.isNew);
                previewContainer.appendChild(colDiv);
                addDragDropListeners(colDiv);
            });
            
            // 3. Sincronizar campo escondido para submissão e FileList
            updateHiddenPathsInput();
        }

        // Cria o elemento de pré-visualização no DOM
        function createPreviewElement(src, identifier, index, isNew) {
            // Adiciona o '../' apenas para caminhos existentes (para o browser carregar)
            const finalSrc = isNew ? src : '../' + src; 
            
            const colDiv = document.createElement('div');
            colDiv.classList.add('col-6', 'col-md-3', 'col-lg-2');
            colDiv.setAttribute('draggable', 'true');
            colDiv.setAttribute('data-identifier', identifier); // Caminho para existentes, Nome de ficheiro para novos
            
            colDiv.innerHTML = `
                <div class="preview-item">
                    <img src="${finalSrc}" alt="Preview" />
                    <button type="button" data-identifier="${identifier}" data-is-new="${isNew}" class="remove-btn">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
            `;
            
            colDiv.querySelector('.remove-btn').addEventListener('click', function(e) {
                e.stopPropagation(); 
                handleRemoveItem(
                    e.target.closest('.remove-btn').getAttribute('data-identifier'),
                    e.target.closest('.remove-btn').getAttribute('data-is-new') === 'true'
                );
            });
            
            return colDiv;
        }

        // Remove um item (existente ou novo) da lista e re-renderiza
        function handleRemoveItem(identifierToRemove, isNew) {
            
            // 1. Remover do array principal (currentPreviews)
            currentPreviews = currentPreviews.filter(item => item.identifier !== identifierToRemove);
            
            // 2. Se for novo, remover do FileList de novos uploads
            if (isNew) {
                const newFiles = new DataTransfer();
                Array.from(newUploadsFileList.files).forEach(file => {
                    if (file.name !== identifierToRemove) { // O identifier é o file.name para novos uploads
                        newFiles.items.add(file);
                    }
                });
                newUploadsFileList = newFiles;
            }
            
            imageUpload.files = newUploadsFileList.files;
            renderPreviews();
        }

        // Lógica para adicionar novos ficheiros ao FileList e re-renderizar
        imageUpload.addEventListener('change', function() {
            Array.from(imageUpload.files).forEach(file => {
                 // Apenas adiciona se o limite total não for excedido
                 if (currentPreviews.length < maxFiles) {
                    newUploadsFileList.items.add(file);
                    
                    // Adicionar ao array principal de previews
                    currentPreviews.push({ 
                        path: URL.createObjectURL(file), 
                        isNew: true, 
                        identifier: file.name, // Usamos o nome do ficheiro como identificador
                        fileObject: file 
                    });
                 } else {
                     toastr.warning(`Atingido o limite de ${maxFiles} fotos.`, 'Limite de Upload');
                 }
            });
            
            // Limpa o input file para que o mesmo ficheiro possa ser selecionado novamente
            imageUpload.value = ''; 
            imageUpload.files = newUploadsFileList.files;
            renderPreviews();
        });

        // Sincroniza o hidden input com a ordem atual dos identificadores para o backend
        function updateHiddenPathsInput() {
            const finalOrderPaths = [];
            
             // 1. Sincronizar a ordem dos identificadores (paths antigos ou nomes de ficheiros novos)
             document.querySelectorAll('#preview-container > div').forEach(colDiv => {
                 finalOrderPaths.push(colDiv.getAttribute('data-identifier'));
             });
             
             // 2. Sincronizar o array interno 'currentPreviews' com a ordem do DOM
             const newPreviews = [];
             finalOrderPaths.forEach(identifier => {
                 const item = currentPreviews.find(p => p.identifier === identifier);
                 if (item) newPreviews.push(item);
             });
             currentPreviews = newPreviews;
             
             // 3. Atualizar o input escondido com a lista JSON ordenada
             photosToKeepPathsInput.value = JSON.stringify(finalOrderPaths);
        }

        // --- DRAG AND DROP LÓGICA ---

        function addDragDropListeners(element) {
             element.addEventListener('dragstart', handleDragStart);
             element.addEventListener('dragover', handleDragOver);
             element.addEventListener('dragleave', handleDragLeave);
             element.addEventListener('drop', handleDrop);
             element.addEventListener('dragend', handleDragEnd);
        }

        function handleDragStart(e) {
            dragSrcEl = this;
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', this.getAttribute('data-identifier'));
            
            setTimeout(() => {
                 this.style.opacity = '0.5';
                 this.classList.add('dragging');
            }, 0);
        }

        function handleDragOver(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            
            if (this !== dragSrcEl) {
                this.classList.add('drag-over');
            }
        }

        function handleDragLeave(e) {
            this.classList.remove('drag-over');
        }

        function handleDrop(e) {
            e.stopPropagation();
            e.preventDefault();
            
            this.classList.remove('drag-over');

            if (dragSrcEl !== this) {
                const container = previewContainer;
                const dropTarget = this;
                
                // Mover visualmente o elemento no DOM
                const dragIndex = Array.from(container.children).indexOf(dragSrcEl);
                const dropIndex = Array.from(container.children).indexOf(dropTarget);
                
                const referenceNode = dragIndex < dropIndex
                    ? dropTarget.nextElementSibling
                    : dropTarget;
                
                container.insertBefore(dragSrcEl, referenceNode);
                
                // Sincronizar o array interno e o campo escondido
                updateHiddenPathsInput(); // updateHiddenPathsInput agora faz a reordenação do array interno
            }
        }
        
        function handleDragEnd(e) {
            this.style.opacity = '1';
            this.classList.remove('dragging');
            document.querySelectorAll('#preview-container > div').forEach(colDiv => {
                colDiv.classList.remove('drag-over');
            });
        }
        
        // Carregar as previews existentes ao carregar a página
        window.addEventListener('load', () => {
             // Mapeia os caminhos existentes para a estrutura de preview
             initialExistingPaths.forEach(path => {
                currentPreviews.push({ 
                    path: path, 
                    isNew: false, 
                    identifier: path, // O identificador para caminhos antigos é o próprio caminho
                    fileObject: null 
                }); 
             });
             renderPreviews();
        });
    </script>
</body>
</html>