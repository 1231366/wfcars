<?php
/**
 * Script responsável por processar o formulário 'Novo Anúncio',
 * inserir o carro na DB e lidar com o upload seguro de imagens (até 8).
 */

session_start(); 
include 'db_connect.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
// Configurações de Upload
$upload_dir = 'uploads/car_photos/'; // Caminho de uploads. Deve estar na raiz, não dentro de /app/ para ser mais limpo.
$max_files = 8;
$allowed_types = ['image/jpeg', 'image/png'];
$max_size = 5 * 1024 * 1024; // 5 MB por foto

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- 1. Recolha e Validação de Campos de Texto ---
    $titulo = trim($conn->real_escape_string($_POST['modelo'])); 
    $marca = trim($conn->real_escape_string($_POST['marca']));
    $descricao = trim($conn->real_escape_string($_POST['descricao']));
    $transmissao = trim($conn->real_escape_string($_POST['transmissao']));
    
    // Novos campos
    $cilindrada = filter_input(INPUT_POST, 'cilindrada', FILTER_VALIDATE_INT);
    $combustivel = trim($conn->real_escape_string($_POST['combustivel']));
    $raw_extras = trim($conn->real_escape_string($_POST['raw_extras']));

    // Filtros de segurança para números
    $ano = filter_input(INPUT_POST, 'ano', FILTER_VALIDATE_INT);
    $preco = filter_input(INPUT_POST, 'preco', FILTER_VALIDATE_FLOAT);
    $km = filter_input(INPUT_POST, 'km', FILTER_VALIDATE_INT);
    $hp = filter_input(INPUT_POST, 'hp', FILTER_VALIDATE_INT);

    // Validação de campos obrigatórios
    if (empty($titulo) || empty($marca) || $ano === false || $preco === false || $km === false || $hp === false || $cilindrada === false || empty($combustivel) || !in_array($transmissao, ['Automática', 'Manual'])) 
    {
        $error = urlencode("Erro: Por favor, preencha todos os campos obrigatórios corretamente, incluindo Cilindrada e Combustível.");
        header("Location: admin-new-listing.php?status=error&message={$error}");
        exit();
    }
            
    // --- 2. Inserção do Anúncio Principal (Inicia Transação) ---
    $conn->begin_transaction();
    
    // QUERY ATUALIZADA para incluir cilindrada_cc, tipo_combustivel, raw_extras
    $sql = "INSERT INTO anuncios (
                titulo, marca, modelo_ano, cilindrada_cc, tipo_combustivel, raw_extras, descricao, preco, quilometragem, potencia_hp, transmissao
            ) VALUES (
                '$titulo', 
                '$marca', 
                $ano, 
                $cilindrada,
                '$combustivel',
                '$raw_extras',
                '$descricao', 
                $preco, 
                $km, 
                $hp, 
                '$transmissao'
            )";
            
    if ($conn->query($sql) === TRUE) {
        $anuncio_id = $conn->insert_id;
        $all_uploads_success = true;
        $uploaded_paths = [];
        
        // --- 3. Processamento de Imagens ---
        if (isset($_FILES['car_images'])) {
            $file_count = count($_FILES['car_images']['name']);
            
            // Verifica o número máximo de ficheiros
            if ($file_count > $max_files) {
                 $error = urlencode("Erro: Máximo de {$max_files} fotos permitido.");
                 $conn->rollback();
                 header("Location: admin-new-listing.php?status=error&message={$error}");
                 exit();
            }
            
            // Criar o diretório se não existir (O diretório 'uploads' deve estar na raiz do projeto)
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    $error = urlencode("Erro fatal: O diretório de upload '{$upload_dir}' não existe e não pôde ser criado. Verifique permissões.");
                    $conn->rollback();
                    header("Location: admin-new-listing.php?status=error&message={$error}");
                    exit();
                }
            }

            for ($i = 0; $i < $file_count; $i++) {
                if ($_FILES['car_images']['error'][$i] !== UPLOAD_ERR_OK) { continue; }

                $file_tmp = $_FILES['car_images']['tmp_name'][$i];
                $file_type = $_FILES['car_images']['type'][$i];
                $file_size = $_FILES['car_images']['size'][$i];
                $file_ext = strtolower(pathinfo($_FILES['car_images']['name'][$i], PATHINFO_EXTENSION));

                if (!in_array($file_type, $allowed_types) || $file_size > $max_size) {
                    $all_uploads_success = false;
                    break; 
                }

                $file_name = uniqid('car_', true) . '.' . $file_ext;
                $file_path = $upload_dir . $file_name;

                if (move_uploaded_file($file_tmp, __DIR__ . '/../' . $file_path)) {
                    $uploaded_paths[] = $file_path;
                } else {
                    $all_uploads_success = false;
                    break;
                }
            }
        }
        
        // --- 4. Registo dos Caminhos na DB (Tabela fotos_anuncio) ---
        if ($all_uploads_success && !empty($uploaded_paths)) {
            $insert_photos_sql = "INSERT INTO fotos_anuncio (anuncio_id, caminho_foto) VALUES ";
            $values = [];
            foreach ($uploaded_paths as $path) {
                $values[] = "({$anuncio_id}, '{$conn->real_escape_string($path)}')"; 
            }
            $insert_photos_sql .= implode(', ', $values);
            
            if ($conn->query($insert_photos_sql) === FALSE) {
                 $all_uploads_success = false;
            }
        }

        // --- 5. Commit ou Rollback (Finaliza Transação) ---
        if ($all_uploads_success) {
            $conn->commit();
            $message = urlencode("Anúncio '{$titulo}' publicado com sucesso! Fotos carregadas: " . count($uploaded_paths));
            header("Location: admin-active-listings.php?status=success&message={$message}"); 
        } else {
            $conn->rollback();
            foreach ($uploaded_paths as $path) {
                if (file_exists(__DIR__ . '/../' . $path)) {
                    unlink(__DIR__ . '/../' . $path);
                }
            }
            $error = urlencode("Erro (Fotos): Ocorreu um erro no upload, validação, ou registo de fotos. (Verifique as permissões da pasta 'uploads/car_photos/')");
            header("Location: admin-new-listing.php?status=error&message={$error}");
        }

    } else {
        // Erro na inserção do anúncio principal
        $conn->rollback();
        $error = urlencode("Erro ao criar anúncio (DB): " . $conn->error);
        header("Location: admin-new-listing.php?status=error&message={$error}");
    }
    
    $conn->close();
    exit(); 
} else {
    header("Location: admin-new-listing.php");
    exit();
}
?>