<?php
/**
 * Página de edição de um anúncio. Puxa dados existentes e pré-preenche o formulário.
 */
session_start();
include 'db_connect.php';

// Redireciona se o utilizador não estiver autenticado
if (!isset($_SESSION['user_id'])) {
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

// 2. Buscar dados do anúncio
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

// Nota: Esta página reutiliza a estrutura do formulário de criação.
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WFCARS | Editar Anúncio</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/7.1.0/mdb.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;600;700;800;900&display=swap" rel="stylesheet">
    <style> /* COPIE AQUI O BLOCO <style> COMPLETO DO admin-new-listing.php */ </style>
</head>
<body class="bg-dark text-white">

    <main>
        
        <h1 class="admin-title-desktop mb-5">
            Editar <span class="text-highlight">Anúncio #<?php echo $anuncio_id; ?></span>
        </h1>
        
        <form action="update_listing.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="anuncio_id" value="<?php echo $anuncio_id; ?>">
            
            <div class="card bg-highlight-card shadow-lg mb-4">
                <div class="card-header bg-transparent border-bottom border-secondary-subtle">
                    <h5 class="text-white mb-0">Galeria de Fotos (Manteremos o campo de upload, mas a gestão de fotos existentes é mais complexa)</h5>
                </div>
                <div class="card-body">
                    <p class="text-subtle">Nota: Para simplificar, o upload de novas fotos substituirá as antigas na submissão, mas isso não está implementado na `update_listing.php` simulada.</p>
                    <div class="file-upload-box mb-3" onclick="document.getElementById('image-upload').click()">
                        <i class="fas fa-cloud-upload-alt fa-3x text-highlight mb-3"></i>
                        <p class="text-highlight fw-bold">Selecione para substituir fotos</p>
                        <input type="file" multiple class="d-none" id="image-upload" name="car_images[]" accept="image/jpeg, image/png">
                    </div>
                </div>
            </div>

            <div class="card bg-highlight-card shadow-lg mb-4">
                <div class="card-header bg-transparent border-bottom border-secondary-subtle">
                    <h5 class="text-white mb-0">Detalhes da Viatura</h5>
                </div>
                <div class="card-body">
                    <div class="form-outline mb-4">
                        <input type="text" id="modelo" name="modelo" class="form-control" value="<?php echo htmlspecialchars($anuncio['titulo']); ?>" required />
                        <label class="form-label" for="modelo">Título do Anúncio / Modelo</label>
                    </div>
                    
                    <div class="form-outline mb-4">
                        <textarea id="descricao" name="descricao" rows="4" class="form-control"><?php echo htmlspecialchars($anuncio['descricao']); ?></textarea>
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
                                <input type="text" id="marca" name="marca" class="form-control" value="<?php echo htmlspecialchars($anuncio['marca']); ?>" required />
                                <label class="form-label" for="marca">Marca</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-outline">
                                <input type="number" id="ano" name="ano" class="form-control" value="<?php echo htmlspecialchars($anuncio['modelo_ano']); ?>" required />
                                <label class="form-label" for="ano">Ano</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-outline">
                                <input type="number" id="preco" name="preco" class="form-control" value="<?php echo htmlspecialchars($anuncio['preco']); ?>" required />
                                <label class="form-label" for="preco">Preço Final (€)</label>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-outline">
                                <input type="number" id="km" name="km" class="form-control" value="<?php echo htmlspecialchars($anuncio['quilometragem']); ?>" required />
                                <label class="form-label" for="km">Quilometragem (KM)</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                             <div class="form-outline">
                                <input type="number" id="hp" name="hp" class="form-control" value="<?php echo htmlspecialchars($anuncio['potencia_hp']); ?>" required />
                                <label class="form-label" for="hp">Potência (HP)</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select id="transmissao" name="transmissao" class="form-select text-white" style="background-color: rgb(var(--mdb-primary-rgb)); border-color: rgba(192, 192, 192, 0.5);">
                                <option value="Automática" <?php echo ($anuncio['transmissao'] === 'Automática') ? 'selected' : ''; ?>>Automática</option>
                                <option value="Manual" <?php echo ($anuncio['transmissao'] === 'Manual') ? 'selected' : ''; ?>>Manual</option>
                            </select>
                            <label class="form-label select-label text-highlight">Transmissão</label>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-lg btn-block btn-rounded text-dark fw-bold" style="background-color: rgb(var(--mdb-secondary-rgb));">
                GUARDAR ALTERAÇÕES
            </button>
        </form>

    </main>

</body>
</html>