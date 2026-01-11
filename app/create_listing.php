<?php
/**
 * Script DE CRIAÇÃO (INSERT) - VERSÃO CORRIGIDA PATHS & DEBUG
 */
session_start(); 
include 'db_connect.php'; 

// Aumentar limites de memória temporariamente para processar imagens
ini_set('memory_limit', '256M');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// === CONFIGURAÇÃO DE UPLOAD ===
// Caminho relativo a partir deste script. 
// Se o create_listing.php está na mesma pasta que admin-new-listing.php, não uses /../
$base_upload_folder = 'uploads/car_photos/'; 

$max_files = 8;
$allowed_types = ['image/jpeg', 'image/png', 'image/webp'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Verificar se o POST não excedeu o limite do servidor
    if (empty($_POST) && empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $error = urlencode("Erro: O tamanho total dos ficheiros excede o limite do servidor (post_max_size).");
        header("Location: admin-new-listing.php?status=error&message={$error}");
        exit();
    }

    // 2. Recolha de Dados
    $titulo = trim($conn->real_escape_string($_POST['modelo'])); 
    $marca = trim($conn->real_escape_string($_POST['marca']));
    $descricao = trim($conn->real_escape_string($_POST['descricao']));
    $transmissao = trim($conn->real_escape_string($_POST['transmissao']));
    $combustivel = trim($conn->real_escape_string($_POST['combustivel']));
    $raw_extras = trim($conn->real_escape_string($_POST['raw_extras']));

    $cilindrada = filter_input(INPUT_POST, 'cilindrada', FILTER_VALIDATE_INT);
    $ano = filter_input(INPUT_POST, 'ano', FILTER_VALIDATE_INT);
    $preco = filter_input(INPUT_POST, 'preco', FILTER_VALIDATE_FLOAT);
    $km = filter_input(INPUT_POST, 'km', FILTER_VALIDATE_INT);
    $hp = filter_input(INPUT_POST, 'hp', FILTER_VALIDATE_INT);

    // Validação Básica
    if (empty($titulo) || empty($marca) || !$ano || !$preco) {
        $error = urlencode("Erro: Preencha todos os campos obrigatórios.");
        header("Location: admin-new-listing.php?status=error&message={$error}");
        exit();
    }
            
    $conn->begin_transaction();
    
    // 3. INSERT na Base de Dados (Carro)
    $stmt = $conn->prepare("INSERT INTO anuncios (titulo, marca, modelo_ano, cilindrada_cc, tipo_combustivel, raw_extras, descricao, preco, quilometragem, potencia_hp, transmissao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiisssdiis", $titulo, $marca, $ano, $cilindrada, $combustivel, $raw_extras, $descricao, $preco, $km, $hp, $transmissao);
            
    if ($stmt->execute()) {
        $anuncio_id = $conn->insert_id;
        $stmt->close();
        
        // 4. Upload de Fotos (Lógica de Caminhos Melhorada)
        $uploaded_paths = [];
        $upload_errors = [];

        if (isset($_FILES['car_images']) && !empty($_FILES['car_images']['name'][0])) {
            
            // Define o caminho absoluto correto
            // __DIR__ é a pasta onde está este script.
            // Se 'uploads' está na mesma pasta, usamos apenas __DIR__ . '/' . $folder
            $absolute_path = __DIR__ . '/' . $base_upload_folder;
            
            // Cria a pasta se não existir
            if (!is_dir($absolute_path)) {
                if (!mkdir($absolute_path, 0755, true)) {
                    // Se falhar o mkdir, rollback e erro
                    $conn->rollback();
                    $error = urlencode("Erro crítico: Não foi possível criar a pasta de uploads. Verifique permissões.");
                    header("Location: admin-new-listing.php?status=error&message={$error}");
                    exit();
                }
            }

            $total = count($_FILES['car_images']['name']);
            for ($i = 0; $i < $total && $i < $max_files; $i++) {
                if ($_FILES['car_images']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['car_images']['tmp_name'][$i];
                    $name = basename($_FILES['car_images']['name'][$i]);
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    
                    if (in_array($_FILES['car_images']['type'][$i], $allowed_types)) {
                        $new_name = uniqid('car_', true) . '.' . $ext;
                        $target_file = $absolute_path . $new_name; 
                        $db_path = $base_upload_folder . $new_name; // Caminho para guardar na DB
                        
                        if (move_uploaded_file($tmp_name, $target_file)) {
                            $uploaded_paths[] = $db_path;
                        } else {
                            $upload_errors[] = "Falha ao mover ficheiro: $name";
                        }
                    }
                } elseif ($_FILES['car_images']['error'][$i] === UPLOAD_ERR_INI_SIZE) {
                     $upload_errors[] = "Ficheiro $i demasiado grande (upload_max_filesize).";
                }
            }
        }
        
        // 5. Guardar Fotos na DB
        if (!empty($uploaded_paths)) {
            $sql_photos = "INSERT INTO fotos_anuncio (anuncio_id, caminho_foto, is_principal) VALUES (?, ?, ?)";
            $stmt_photo = $conn->prepare($sql_photos);
            
            $bind_path = "";
            $bind_is_main = 0;
            // O ID é sempre o mesmo, não precisa estar dentro do loop, mas as variáveis ligadas sim
            $stmt_photo->bind_param("isi", $anuncio_id, $bind_path, $bind_is_main);
            
            foreach ($uploaded_paths as $index => $path) {
                $bind_path = $path; 
                $bind_is_main = ($index === 0) ? 1 : 0;
                $stmt_photo->execute();
            }
            $stmt_photo->close();
        } elseif (!empty($_FILES['car_images']['name'][0])) {
            // Se o utilizador tentou enviar fotos mas nenhuma entrou no array $uploaded_paths
            // Provavelmente falharam as permissões ou tamanho
            $conn->rollback();
            $msg_extra = implode(", ", $upload_errors);
            $error = urlencode("Carro não criado. Erro no upload das fotos: " . $msg_extra);
            header("Location: admin-new-listing.php?status=error&message={$error}");
            exit();
        }

        $conn->commit();
        $msg = urlencode("Anúncio criado com sucesso!");
        header("Location: admin-active-listings.php?status=success&message={$msg}");

    } else {
        $conn->rollback();
        $error = urlencode("Erro na base de dados: " . $stmt->error);
        header("Location: admin-new-listing.php?status=error&message={$error}");
    }
    
    $conn->close();
    exit(); 
}
?>