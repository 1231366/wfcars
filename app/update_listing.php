<?php
/**
 * Script responsável por fazer o UPDATE do anúncio.
 * Corrigido por: Assistente — ajustes: bind_param, validações, prepared statements para fotos, limpeza de uploads em erro.
 */
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}

// Configurações de Upload (reutilizadas)
$upload_dir = 'uploads/car_photos/'; 
$max_files = 8;
$allowed_types = ['image/jpeg', 'image/png'];
$max_size = 5 * 1024 * 1024; // 5 MB por foto

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin-dashboard.php");
    exit();
}

// --- 1. Recolha e Limpeza de Campos ---
$anuncio_id = filter_input(INPUT_POST, 'anuncio_id', FILTER_VALIDATE_INT);

// Função de limpeza agressiva para strings de utilizador (anti-injeção)
$clean_string = function($conn, $str) {
    $str = trim((string)$str);
    return $conn->real_escape_string($str);
};

// Função de normalização robusta para ENUMs
$normalize_enum = function($str) {
    $str = preg_replace('/\s+/', '', strtolower(trim((string)$str)));
    $str = str_replace(
        ['á','à','ã','â','é','è','ê','í','ì','î','ó','ò','õ','ô','ú','ù','û','ç'],
        ['a','a','a','a','e','e','e','i','i','i','o','o','o','o','u','u','u','c'],
        $str
    );
    return $str;
};

// Aceita tanto 'titulo' como 'modelo' (compatibilidade com formulários)
$titulo_raw = $_POST['titulo'] ?? $_POST['modelo'] ?? '';
$marca_raw = $_POST['marca'] ?? '';
$descricao_raw = $_POST['descricao'] ?? '';
$transmissao_raw = $_POST['transmissao'] ?? ''; 
$combustivel_raw = $_POST['combustivel'] ?? ''; 
$raw_extras_raw = $_POST['raw_extras'] ?? '';

// Limpeza
$titulo_clean = $clean_string($conn, $titulo_raw);
$marca_clean = $clean_string($conn, $marca_raw);
$descricao_clean = $clean_string($conn, $descricao_raw);
$raw_extras_clean = $clean_string($conn, $raw_extras_raw);

// Validações numéricas mais sólidas (aceita strings numéricas)
$cilindrada = isset($_POST['cilindrada']) ? filter_var($_POST['cilindrada'], FILTER_VALIDATE_INT) : null;
$ano = isset($_POST['ano']) ? filter_var($_POST['ano'], FILTER_VALIDATE_INT) : null;
$preco = isset($_POST['preco']) ? filter_var($_POST['preco'], FILTER_VALIDATE_FLOAT) : null;
$km = isset($_POST['km']) ? filter_var($_POST['km'], FILTER_VALIDATE_INT) : null;
$hp = isset($_POST['hp']) ? filter_var($_POST['hp'], FILTER_VALIDATE_INT) : null;

// NOVO: Recolha da ordem final das fotos (JSON do JS)
$final_photo_order_json = $_POST['photos_to_keep_paths'] ?? '[]';
$final_photo_order = json_decode($final_photo_order_json, true);
if (!is_array($final_photo_order)) {
    $final_photo_order = [];
}

// --- CORREÇÃO ENUMs ---
$transmissao_norm = $normalize_enum($transmissao_raw);
$combustivel_norm = $normalize_enum($combustivel_raw);

// Transmissão: 'Automática'|'Manual'
$transmissao_clean = ($transmissao_norm === 'manual') ? 'Manual' : 'Automática';

// Combustível: 'Diesel','Gasolina','Híbrido','Elétrico'
if (strpos($combustivel_norm, 'gasolina') !== false) {
    $combustivel_clean = 'Gasolina';
} elseif (strpos($combustivel_norm, 'hibrido') !== false || strpos($combustivel_norm, 'híbrido') !== false) {
    $combustivel_clean = 'Híbrido';
} elseif (strpos($combustivel_norm, 'eletrico') !== false || strpos($combustivel_norm, 'eléctrico') !== false) {
    $combustivel_clean = 'Elétrico';
} else {
    $combustivel_clean = 'Diesel';
}

// Validação campos obrigatórios
$invalid = false;
$missing_fields = [];

if ($anuncio_id === false || $anuncio_id === null) { $invalid = true; $missing_fields[] = 'anuncio_id'; }
if ($titulo_clean === '') { $invalid = true; $missing_fields[] = 'titulo'; }
if ($marca_clean === '') { $invalid = true; $missing_fields[] = 'marca'; }
if ($ano === false || $ano === null) { $invalid = true; $missing_fields[] = 'ano'; }
if ($preco === false || $preco === null) { $invalid = true; $missing_fields[] = 'preco'; }
if ($km === false || $km === null) { $invalid = true; $missing_fields[] = 'km'; }
if ($hp === false || $hp === null) { $invalid = true; $missing_fields[] = 'hp'; }
if ($cilindrada === false) { $invalid = true; $missing_fields[] = 'cilindrada'; }
if ($combustivel_clean === '') { $invalid = true; $missing_fields[] = 'combustivel'; }

if ($invalid) {
    $err = base64_encode("Erro: Por favor preencha todos os campos obrigatórios corretamente. Faltam: " . implode(', ', $missing_fields));
    header("Location: admin-edit-listing.php?id={$anuncio_id}&status=error&message={$err}");
    exit();
}

// --- 2. Inicia Transação e Faz o UPDATE ---
$conn->begin_transaction();

$sql_update = "UPDATE anuncios SET
                titulo = ?, 
                marca = ?, 
                modelo_ano = ?, 
                cilindrada_cc = ?,
                tipo_combustivel = ?,
                raw_extras = ?,
                descricao = ?, 
                preco = ?, 
                quilometragem = ?, 
                potencia_hp = ?, 
                transmissao = ?
            WHERE id = ?";

$stmt = $conn->prepare($sql_update);
if (!$stmt) {
    $conn->rollback();
    $err = base64_encode("Erro na preparação da query UPDATE: " . $conn->error);
    header("Location: admin-edit-listing.php?id={$anuncio_id}&status=error&message={$err}");
    exit();
}

// Tipos corretos (12 parâmetros):
// 1 titulo(s), 2 marca(s), 3 ano(i), 4 cilindrada(i), 5 tipo_combustivel(s), 6 raw_extras(s),
// 7 descricao(s), 8 preco(d), 9 quilometragem(i), 10 potencia_hp(i), 11 transmissao(s), 12 id(i)
// => "ssiisssdiisi"
$bind_types = "ssiisssdiisi";

if (!$stmt->bind_param(
    $bind_types,
    $titulo_clean,
    $marca_clean,
    $ano,
    $cilindrada,
    $combustivel_clean,
    $raw_extras_clean,
    $descricao_clean,
    $preco,
    $km,
    $hp,
    $transmissao_clean,
    $anuncio_id
)) {
    $stmt->close();
    $conn->rollback();
    $err = base64_encode("Erro ao ligar parâmetros: " . $stmt->error);
    header("Location: admin-edit-listing.php?id={$anuncio_id}&status=error&message={$err}");
    exit();
}

// Executa UPDATE
if (!$stmt->execute()) {
    $stmt->close();
    $conn->rollback();
    $err = base64_encode("Erro ao executar UPDATE: " . $stmt->error);
    header("Location: admin-edit-listing.php?id={$anuncio_id}&status=error&message={$err}");
    exit();
}
$stmt->close();

$all_uploads_success = true;

try {
    // --- 3. Processamento de Imagens ---
    $new_uploads_map = []; // original_filename => stored_path
    $new_uploads_success = true;
    $full_upload_dir = __DIR__ . '/../' . $upload_dir;

    // Verifica se há novos ficheiros a processar
    if (isset($_FILES['car_images']) && !empty($_FILES['car_images']['name'][0])) {
        if (!is_dir($full_upload_dir)) { 
            if (!mkdir($full_upload_dir, 0755, true)) {
                throw new Exception("Não foi possível criar diretório de upload.");
            }
        }

        $file_count = count($_FILES['car_images']['name']);
        if ($file_count > $max_files) {
            throw new Exception("Número de ficheiros excede o máximo permitido ({$max_files}).");
        }

        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['car_images']['error'][$i] !== UPLOAD_ERR_OK) { 
                $new_uploads_success = false;
                break;
            }

            $file_tmp = $_FILES['car_images']['tmp_name'][$i];
            $file_type = $_FILES['car_images']['type'][$i];
            $file_size = $_FILES['car_images']['size'][$i];
            $client_name = $_FILES['car_images']['name'][$i];
            $file_ext = strtolower(pathinfo($client_name, PATHINFO_EXTENSION));

            if (!in_array($file_type, $allowed_types) || $file_size > $max_size) {
                $new_uploads_success = false;
                break;
            }

            $file_name = uniqid('car_', true) . '.' . $file_ext;
            $file_path = $upload_dir . $file_name;
            $full_file_path = $full_upload_dir . $file_name;

            if (!move_uploaded_file($file_tmp, $full_file_path)) {
                $new_uploads_success = false;
                break;
            }

            // Mapeia pelo nome original do ficheiro (o frontend deve usar este nome para identificar novos ficheiros)
            $new_uploads_map[$client_name] = $file_path;
        }

        if (!$new_uploads_success) {
            // Limpeza imediata de quaisquer ficheiros movidos
            foreach ($new_uploads_map as $p) {
                $full = __DIR__ . '/../' . $p;
                if (file_exists($full)) { @unlink($full); }
            }
            throw new Exception("Erro ao processar um ou mais ficheiros de imagem durante o upload.");
        }
    }

    // 3.2 Obter caminhos antigos para decidir quais apagar do disco (prepared stmt)
    $old_paths_to_delete = [];
    $sel = $conn->prepare("SELECT caminho_foto FROM fotos_anuncio WHERE anuncio_id = ?");
    if (!$sel) {
        throw new Exception("Erro ao preparar SELECT fotos: " . $conn->error);
    }
    $sel->bind_param("i", $anuncio_id);
    $sel->execute();
    $res = $sel->get_result();
    while ($row = $res->fetch_assoc()) {
        $old_paths_to_delete[] = $row['caminho_foto'];
    }
    $sel->close();

    // 3.3 Apagar registos antigos da DB (prepared)
    $del = $conn->prepare("DELETE FROM fotos_anuncio WHERE anuncio_id = ?");
    if (!$del) {
        throw new Exception("Erro ao preparar DELETE fotos: " . $conn->error);
    }
    $del->bind_param("i", $anuncio_id);
    if (!$del->execute()) {
        $del->close();
        throw new Exception("Erro ao apagar fotos antigas da BD: " . $del->error);
    }
    $del->close();

    // 3.4 Reinserir fotos na ordem fornecida pelo frontend
    $paths_to_insert = [];
    $paths_to_keep = [];

    foreach ($final_photo_order as $path_or_name) {
        // Se for exactamente um caminho antigo mantido
        if (in_array($path_or_name, $old_paths_to_delete, true)) { 
            $paths_to_insert[] = $path_or_name;
            $paths_to_keep[] = $path_or_name;
        } 
        // Se for um ficheiro novo identificado pelo nome original
        elseif (isset($new_uploads_map[$path_or_name])) {
            $paths_to_insert[] = $new_uploads_map[$path_or_name];
        } 
        // Caso o frontend envie índices ou outras marcas, tentar mapear por basename
        else {
            $basename = basename((string)$path_or_name);
            // procura em old_paths_to_delete por fim igual a basename
            foreach ($old_paths_to_delete as $oldp) {
                if (basename($oldp) === $basename) {
                    $paths_to_insert[] = $oldp;
                    $paths_to_keep[] = $oldp;
                    continue 2;
                }
            }
            // procura em new_uploads_map por chave com mesmo basename
            foreach ($new_uploads_map as $orig => $newp) {
                if (basename($orig) === $basename) {
                    $paths_to_insert[] = $newp;
                    continue 2;
                }
            }
            // senão ignora esse item
        }
    }

    // Inserção dos novos registos na ordem correta (prepared)
    if (!empty($paths_to_insert)) {
        $insert_stmt = $conn->prepare("INSERT INTO fotos_anuncio (anuncio_id, caminho_foto, is_principal) VALUES (?, ?, ?)");
        if (!$insert_stmt) {
            throw new Exception("Erro ao preparar INSERT fotos: " . $conn->error);
        }
        foreach ($paths_to_insert as $index => $path) {
            $is_principal = ($index === 0) ? 1 : 0;
            $p_escaped = $path; // usamos bind_param, não é preciso real_escape_string aqui
            $insert_stmt->bind_param("isi", $anuncio_id, $p_escaped, $is_principal);
            if (!$insert_stmt->execute()) {
                $insert_stmt->close();
                throw new Exception("Erro ao registar foto na BD: " . $insert_stmt->error);
            }
        }
        $insert_stmt->close();
    }

    // 3.5 Limpar fotos antigas que **não** foram mantidas (Do disco)
    $paths_to_clean_disk = array_diff($old_paths_to_delete, $paths_to_keep);
    foreach ($paths_to_clean_disk as $path) {
        $full_path = __DIR__ . '/../' . $path;
        if (file_exists($full_path)) {
            @unlink($full_path);
        }
    }

    $all_uploads_success = true;

} catch (Exception $e) {
    $all_uploads_success = false;
    // Garante remoção de quaisquer ficheiros novos movidos
    if (!empty($new_uploads_map)) {
        foreach ($new_uploads_map as $p) {
            $full = __DIR__ . '/../' . $p;
            if (file_exists($full)) { @unlink($full); }
        }
    }
    $error = base64_encode("Erro ao atualizar fotos. As informações do carro foram salvas, mas as fotos falharam: " . $e->getMessage());
}

// --- 4. Commit ou Rollback ---
if ($all_uploads_success) {
    $conn->commit();
    $message = base64_encode("Anúncio #{$anuncio_id} atualizado com sucesso!"); 
    header("Location: admin-active-listings.php?list=Ativo&status=success&message={$message}");
    $conn->close();
    exit();
} else {
    $conn->rollback();
    // Se $error não existir, gera uma mensagem genérica
    if (!isset($error)) {
        $error = base64_encode("Erro desconhecido ao processar as fotos.");
    }
    header("Location: admin-edit-listing.php?id={$anuncio_id}&status=error&message={$error}");
    $conn->close();
    exit();
}
