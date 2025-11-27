<?php
/**
 * Script responsável por fazer o UPDATE do anúncio.
 */
session_start();
include 'db_connect.php'; 


if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
// Configurações de Upload (reutilizadas)
$upload_dir = 'uploads/car_photos/'; 
$max_files = 8;
$allowed_types = ['image/jpeg', 'image/png'];
$max_size = 5 * 1024 * 1024; // 5 MB por foto

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- 1. Recolha e Validação de Campos ---
    $anuncio_id = filter_input(INPUT_POST, 'anuncio_id', FILTER_VALIDATE_INT);
    $titulo = trim($conn->real_escape_string($_POST['modelo'])); 
    $marca = trim($conn->real_escape_string($_POST['marca']));
    $descricao = trim($conn->real_escape_string($_POST['descricao']));
    $transmissao = trim($conn->real_escape_string($_POST['transmissao']));
    
    // NOVOS CAMPOS
    $cilindrada = filter_input(INPUT_POST, 'cilindrada', FILTER_VALIDATE_INT);
    $combustivel = trim($conn->real_escape_string($_POST['combustivel']));
    $raw_extras = trim($conn->real_escape_string($_POST['raw_extras']));

    // Filtros de segurança para números
    $ano = filter_input(INPUT_POST, 'ano', FILTER_VALIDATE_INT);
    $preco = filter_input(INPUT_POST, 'preco', FILTER_VALIDATE_FLOAT);
    $km = filter_input(INPUT_POST, 'km', FILTER_VALIDATE_INT);
    $hp = filter_input(INPUT_POST, 'hp', FILTER_VALIDATE_INT);


    if (empty($anuncio_id) || empty($titulo) || empty($marca) || $ano === false || $preco === false || $km === false || $hp === false || $cilindrada === false || empty($combustivel)) 
    {
        $error = urlencode("Erro: Dados de anúncio ou ID inválidos. Por favor, preencha todos os campos obrigatórios.");
        header("Location: admin-edit-listing.php?id={$anuncio_id}&status=error&message={$error}");
        exit();
    }
            
    // --- 2. Inicia Transação e Faz o UPDATE (Inclui os novos campos) ---
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
    
    // Tipos: s (string), i (integer), f (float). Total de 11 parâmetros + ID (12)
    $stmt->bind_param("siiissiisii", 
        $titulo, $marca, $ano, $cilindrada, $combustivel, $raw_extras, $descricao, $preco, $km, $hp, $transmissao, $anuncio_id
    );
            
    if ($stmt->execute()) {
        $stmt->close();
        $all_uploads_success = true;
        $uploaded_paths = [];

        // --- 3. Processamento de Novas Imagens (Lógica de Substituição Total) ---
        if (isset($_FILES['car_images']) && !empty($_FILES['car_images']['name'][0])) {
            
            // 3.1 Apagar todas as fotos antigas (do disco e da DB)
            try {
                $result_old_photos = $conn->query("SELECT caminho_foto FROM fotos_anuncio WHERE anuncio_id = {$anuncio_id}");
                while ($row = $result_old_photos->fetch_assoc()) {
                    $full_path = __DIR__ . '/../' . $row['caminho_foto'];
                    if (file_exists($full_path)) {
                        unlink($full_path);
                    }
                }
                $conn->query("DELETE FROM fotos_anuncio WHERE anuncio_id = {$anuncio_id}");
                
                // 3.2 Fazer o upload das novas fotos
                $file_count = count($_FILES['car_images']['name']);
                $full_upload_dir = __DIR__ . '/../' . $upload_dir;

                if (!is_dir($full_upload_dir)) { mkdir($full_upload_dir, 0755, true); }

                for ($i = 0; $i < $file_count; $i++) {
                    if ($_FILES['car_images']['error'][$i] !== UPLOAD_ERR_OK) { continue; }

                    $file_tmp = $_FILES['car_images']['tmp_name'][$i];
                    $file_type = $_FILES['car_images']['type'][$i];
                    $file_size = $_FILES['car_images']['size'][$i];
                    $file_ext = strtolower(pathinfo($_FILES['car_images']['name'][$i], PATHINFO_EXTENSION));

                    if (!in_array($file_type, $allowed_types) || $file_size > $max_size) { $all_uploads_success = false; break; }

                    $file_name = uniqid('car_', true) . '.' . $file_ext;
                    $file_path = $upload_dir . $file_name;
                    $full_file_path = $full_upload_dir . $file_name;

                    if (move_uploaded_file($file_tmp, $full_file_path)) {
                        $uploaded_paths[] = $file_path;
                    } else {
                        $all_uploads_success = false;
                        break;
                    }
                }

                // 3.3 Registo dos novos caminhos na DB
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

            } catch (Exception $e) {
                $all_uploads_success = false;
            }
        } else {
             $all_uploads_success = true;
        }

        // --- 4. Commit ou Rollback ---
        if ($all_uploads_success) {
            $conn->commit();
            $message = urlencode("Anúncio #{$anuncio_id} atualizado com sucesso!");
            header("Location: admin-active-listings.php?list=Ativo&status=success&message={$message}");
        } else {
            $conn->rollback();
            $error = urlencode("Erro ao atualizar fotos. As informações do carro foram salvas, mas as fotos não foram substituídas. (Verifique permissões)");
            header("Location: admin-edit-listing.php?id={$anuncio_id}&status=error&message={$error}");
        }

    } else {
        // Erro na execução do UPDATE
        $conn->rollback();
        $error = urlencode("Erro ao atualizar anúncio (DB): " . $conn->error);
        header("Location: admin-edit-listing.php?id={$anuncio_id}&status=error&message={$error}");
    }
    
    $conn->close();
    exit(); 
} else {
    header("Location: admin-dashboard.php");
    exit();
}
?>