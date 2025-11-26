<?php
/**
 * Script responsável por processar as ações de Marcar como Vendido, Restaurar e Apagar.
 */
session_start();
include 'db_connect.php';

// Redireciona se o utilizador não estiver autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'], $_POST['anuncio_id'])) {
    
    $action = $_POST['action'];
    $anuncio_id = filter_input(INPUT_POST, 'anuncio_id', FILTER_VALIDATE_INT);

    if (!$anuncio_id) {
        $error = urlencode("Erro: ID de anúncio inválido.");
        header("Location: admin-active-listings.php?status=error&message={$error}");
        exit();
    }

    $conn->begin_transaction();
    $success = false;
    $message = "";

    try {
        if ($action === 'mark_sold') {
            // AÇÃO: Marcar como Vendido
            $stmt = $conn->prepare("UPDATE anuncios SET status = 'Vendido' WHERE id = ?");
            $stmt->bind_param("i", $anuncio_id);
            if ($stmt->execute()) {
                $success = true;
                $message = urlencode("Anúncio marcado como VENDIDO com sucesso.");
            } else {
                throw new Exception("Erro ao marcar como vendido: " . $conn->error);
            }
            $stmt->close();

        } else if ($action === 'restore') {
            // AÇÃO: Restaurar (Mudar de Vendido para Ativo)
            $stmt = $conn->prepare("UPDATE anuncios SET status = 'Ativo' WHERE id = ?");
            $stmt->bind_param("i", $anuncio_id);
            if ($stmt->execute()) {
                $success = true;
                $message = urlencode("Anúncio restaurado (marcado como ATIVO) com sucesso.");
            } else {
                throw new Exception("Erro ao restaurar anúncio: " . $conn->error);
            }
            $stmt->close();
            
        } else if ($action === 'delete') {
            // AÇÃO: Apagar
            
            // 1. Obter caminhos das fotos para apagar do disco
            $photo_paths = [];
            $result = $conn->query("SELECT caminho_foto FROM fotos_anuncio WHERE anuncio_id = {$anuncio_id}");
            while ($row = $result->fetch_assoc()) {
                $photo_paths[] = $row['caminho_foto'];
            }
            
            // 2. Apagar o anúncio (FOREIGN KEY CASCADE deve apagar as entradas em fotos_anuncio)
            $stmt = $conn->prepare("DELETE FROM anuncios WHERE id = ?");
            $stmt->bind_param("i", $anuncio_id);
            
            if ($stmt->execute()) {
                $success = true;
                $message = urlencode("Anúncio apagado permanentemente.");
                $stmt->close();
                
                // 3. Apagar as fotos do disco
                foreach ($photo_paths as $path) {
                    $full_path = __DIR__ . '/../' . $path; 
                    if (file_exists($full_path)) {
                        unlink($full_path);
                    }
                }
            } else {
                throw new Exception("Erro ao apagar anúncio: " . $conn->error);
            }

        } else {
            throw new Exception("Ação inválida.");
        }

        if ($success) {
            $conn->commit();
            // Redireciona para a mesma lista para onde a ação foi submetida
            $redirect_status = ($action === 'restore' || $action === 'mark_sold') ? 'Ativo' : 'Vendido';
            header("Location: admin-active-listings.php?list={$redirect_status}&status=success&message={$message}");
        } else {
            throw new Exception("Ação não concluída.");
        }

    } catch (Exception $e) {
        $conn->rollback();
        $error = urlencode("Erro de DB: " . $e->getMessage());
        header("Location: admin-active-listings.php?status=error&message={$error}");
    }
    
    $conn->close();
    exit();
} else {
    // Acesso direto, redireciona
    header("Location: admin-active-listings.php");
    exit();
}
?>