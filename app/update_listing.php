<?php
/**
 * Script responsável por fazer o UPDATE do anúncio - VERSÃO CORRIGIDA WEB
 */
session_start();
include 'db_connect.php';

// Aumentar memória para lidar com múltiplas imagens
ini_set('memory_limit', '256M');

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}

// === CONFIGURAÇÕES DE PASTA (CRÍTICO) ===
// Caminho relativo para guardar na BD
$base_upload_folder = 'uploads/car_photos/'; 
// Caminho absoluto no servidor (sem o /../ se o script estiver na root/public_html)
$absolute_upload_path = __DIR__ . '/' . $base_upload_folder;

$max_files = 8;
$allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
$max_size = 10 * 1024 * 1024; // 10 MB

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin-dashboard.php");
    exit();
}

// --- 1. Recolha e Limpeza de Campos ---
$anuncio_id = filter_input(INPUT_POST, 'anuncio_id', FILTER_VALIDATE_INT);

$clean_string = function($conn, $str) {
    return $conn->real_escape_string(trim((string)$str));
};

// Normalização de ENUMs
$normalize_enum = function($str) {
    $str = preg_replace('/\s+/', '', strtolower(trim((string)$str)));
    $str = str_replace(
        ['á','à','ã','â','é','è','ê','í','ì','î','ó','ò','õ','ô','ú','ù','û','ç'],
        ['a','a','a','a','e','e','e','i','i','i','o','o','o','o','u','u','u','c'],
        $str
    );
    return $str;
};

// Dados do formulário
$titulo_clean = $clean_string($conn, $_POST['titulo'] ?? $_POST['modelo'] ?? '');
$marca_clean = $clean_string($conn, $_POST['marca'] ?? '');
$descricao_clean = $clean_string($conn, $_POST['descricao'] ?? '');
$raw_extras_clean = $clean_string($conn, $_POST['raw_extras'] ?? '');

$cilindrada = filter_input(INPUT_POST, 'cilindrada', FILTER_VALIDATE_INT);
$ano = filter_input(INPUT_POST, 'ano', FILTER_VALIDATE_INT);
$preco = filter_input(INPUT_POST, 'preco', FILTER_VALIDATE_FLOAT);
$km = filter_input(INPUT_POST, 'km', FILTER_VALIDATE_INT);
$hp = filter_input(INPUT_POST, 'hp', FILTER_VALIDATE_INT);

// Recolha da ordem final das fotos (JSON do JS)
$final_photo_order_json = $_POST['photos_to_keep_paths'] ?? '[]';
$final_photo_order = json_decode($final_photo_order_json, true);
if (!is_array($final_photo_order)) {
    $final_photo_order = [];
}

// Tratamento Transmissão e Combustível
$transmissao_norm = $normalize_enum($_POST['transmissao'] ?? '');
$combustivel_norm = $normalize_enum($_POST['combustivel'] ?? '');

$transmissao_clean = ($transmissao_norm === 'manual') ? 'Manual' : 'Automática';

if (strpos($combustivel_norm, 'hibridodiesel') !== false) { $combustivel_clean = 'Híbrido Diesel'; }
elseif (strpos($combustivel_norm, 'hibridogasolina') !== false) { $combustivel_clean = 'Híbrido Gasolina'; }
elseif (strpos($combustivel_norm, 'hibrido') !== false) { $combustivel_clean = 'Híbrido'; }
elseif (strpos($combustivel_norm, 'gasolina') !== false) { $combustivel_clean = 'Gasolina'; }
elseif (strpos($combustivel_norm, 'eletrico') !== false) { $combustivel_clean = 'Elétrico'; }
else { $combustivel_clean = 'Diesel'; }

// Validação
$invalid = false;
if (!$anuncio_id || $titulo_clean === '' || $marca_clean === '' || !$ano || !$preco) {
    $invalid = true;
}

if ($invalid) {
    $err = base64_encode("Erro: Preencha todos os campos obrigatórios.");
    header("Location: admin-edit-listing.php?id={$anuncio_id}&status=error&message={$err}");
    exit();
}

// --- 2. Inicia Transação e Faz o UPDATE dos Dados ---
$conn->begin_transaction();

$sql_update = "UPDATE anuncios SET titulo=?, marca=?, modelo_ano=?, cilindrada_cc=?, tipo_combustivel=?, raw_extras=?, descricao=?, preco=?, quilometragem=?, potencia_hp=?, transmissao=? WHERE id=?";
$stmt = $conn->prepare($sql_update);

if (!$stmt) {
    $conn->rollback();
    die("Erro prepare update: " . $conn->error);
}

$stmt->bind_param("ssiisssdiisi", $titulo_clean, $marca_clean, $ano, $cilindrada, $combustivel_clean, $raw_extras_clean, $descricao_clean, $preco, $km, $hp, $transmissao_clean, $anuncio_id);

if (!$stmt->execute()) {
    $stmt->close();
    $conn->rollback();
    $err = base64_encode("Erro ao atualizar dados: " . $stmt->error);
    header("Location: admin-edit-listing.php?id={$anuncio_id}&status=error&message={$err}");
    exit();
}
$stmt->close();

// --- 3. Processamento de Imagens (A PARTE CRÍTICA) ---
$all_uploads_success = true;
$error_msg = "";

try {
    $new_uploads_map = []; // NomeOriginal -> CaminhoBD
    
    // Verificar/Criar pasta
    if (!is_dir($absolute_upload_path)) { 
        if (!mkdir($absolute_upload_path, 0755, true)) {
            throw new Exception("Não foi possível criar diretório de upload no servidor.");
        }
    }

    // A. Processar NOVOS uploads
    if (isset($_FILES['car_images']) && !empty($_FILES['car_images']['name'][0])) {
        
        $file_count = count($_FILES['car_images']['name']);
        
        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['car_images']['error'][$i] === UPLOAD_ERR_OK) {
                
                $tmp_name = $_FILES['car_images']['tmp_name'][$i];
                $client_name = $_FILES['car_images']['name'][$i];
                $type = $_FILES['car_images']['type'][$i];
                $size = $_FILES['car_images']['size'][$i];
                $ext = strtolower(pathinfo($client_name, PATHINFO_EXTENSION));

                if (in_array($type, $allowed_types) && $size <= $max_size) {
                    $new_filename = uniqid('car_', true) . '.' . $ext;
                    $target_file = $absolute_upload_path . $new_filename; // Caminho Físico
                    $db_path = $base_upload_folder . $new_filename;       // Caminho BD

                    if (move_uploaded_file($tmp_name, $target_file)) {
                        $new_uploads_map[$client_name] = $db_path;
                    }
                }
            }
        }
    }

    // B. Buscar fotos ANTIGAS da DB (para saber o que apagar depois)
    $old_paths_db = [];
    $sel = $conn->prepare("SELECT caminho_foto FROM fotos_anuncio WHERE anuncio_id = ?");
    $sel->bind_param("i", $anuncio_id);
    $sel->execute();
    $res = $sel->get_result();
    while ($row = $res->fetch_assoc()) {
        $old_paths_db[] = $row['caminho_foto'];
    }
    $sel->close();

    // C. Limpar tabela para reinserir na ordem correta
    // (IMPORTANTE: Só fazemos isto se tivermos a certeza que vamos conseguir reinserir)
    $del = $conn->prepare("DELETE FROM fotos_anuncio WHERE anuncio_id = ?");
    $del->bind_param("i", $anuncio_id);
    $del->execute();
    $del->close();

    // D. Construir a lista final e INSERIR
    $paths_to_keep_on_disk = [];
    
    $insert_stmt = $conn->prepare("INSERT INTO fotos_anuncio (anuncio_id, caminho_foto, is_principal) VALUES (?, ?, ?)");
    
    foreach ($final_photo_order as $index => $item_identifier) {
        $final_path = "";

        // 1. É uma foto que já existia? (O JS manda o path completo ou nome do ficheiro)
        // Verificamos se o item está na lista de caminhos antigos ou se o basename coincide
        foreach ($old_paths_db as $old_p) {
            if ($item_identifier == $old_p || basename($item_identifier) == basename($old_p)) {
                $final_path = $old_p;
                break;
            }
        }

        // 2. É uma foto nova? (O JS manda o nome original do ficheiro)
        if (empty($final_path) && isset($new_uploads_map[$item_identifier])) {
            $final_path = $new_uploads_map[$item_identifier];
        }

        // Se encontrarmos um caminho válido, inserimos na BD
        if (!empty($final_path)) {
            $is_principal = ($index === 0) ? 1 : 0;
            $insert_stmt->bind_param("isi", $anuncio_id, $final_path, $is_principal);
            $insert_stmt->execute();
            
            $paths_to_keep_on_disk[] = $final_path;
        }
    }
    $insert_stmt->close();

    // E. Limpeza física do disco (Apagar fotos que já não são usadas)
    // Compara o que existia antes ($old_paths_db) com o que mantivemos ($paths_to_keep_on_disk)
    $to_delete = array_diff($old_paths_db, $paths_to_keep_on_disk);
    
    foreach ($to_delete as $del_path) {
        $file_to_del = __DIR__ . '/' . $del_path; // Caminho absoluto correto
        if (file_exists($file_to_del)) {
            @unlink($file_to_del);
        }
    }

} catch (Exception $e) {
    $all_uploads_success = false;
    $error_msg = $e->getMessage();
}

// --- 4. Finalização ---
if ($all_uploads_success) {
    $conn->commit();
    $message = base64_encode("Anúncio atualizado com sucesso!"); 
    header("Location: admin-active-listings.php?status=success&message={$message}");
} else {
    $conn->rollback();
    $error = base64_encode("Erro nas fotos: " . $error_msg);
    header("Location: admin-edit-listing.php?id={$anuncio_id}&status=error&message={$error}");
}

$conn->close();
exit();
?>