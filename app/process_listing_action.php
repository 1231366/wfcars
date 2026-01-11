<?php
/**
 * Script responsável por processar as ações de Marcar como Vendido, Restaurar e Apagar, e DESTAQUE.
 */
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'], $_POST['anuncio_id'])) {
    
    $action = $_POST['action'];
    $anuncio_id = filter_input(INPUT_POST, 'anuncio_id', FILTER_VALIDATE_INT);

    if (!$anuncio_id) {
        $error_msg = base64_encode("Erro: ID de anúncio inválido.");
        header("Location: admin-active-listings.php?status=error&message={$error_msg}");
        exit();
    }

    $conn->begin_transaction();
    $success = false;
    $message = "";

    try {
        
        // AÇÕES DE DESTAQUE
        if ($action === 'mark_highlight') {
            
            // 1. Verificar se já existe o limite de 3 destaques (Robusto)
            $result_count = $conn->query("SELECT COUNT(*) as count FROM anuncios WHERE status = 'Ativo' AND destaque = 1");
            if (!$result_count) {
                throw new Exception("Erro ao contar destaques (DB): " . $conn->error);
            }
            $destaques_count = $result_count->fetch_assoc()['count'];
            
            if ($destaques_count >= 3) {
                throw new Exception("Limite atingido: Apenas 3 anúncios ativos podem estar em destaque.");
            }
            
            // 2. Verificar se o carro está ATIVO (status) (Robusto)
            $status_stmt = $conn->prepare("SELECT status FROM anuncios WHERE id = ?");
            $status_stmt->bind_param("i", $anuncio_id);
            $status_stmt->execute();
            $result_status = $status_stmt->get_result();
            
            if ($result_status->num_rows === 0) {
                throw new Exception("Anúncio não encontrado.");
            }
            $car_status = $result_status->fetch_assoc()['status'];
            $status_stmt->close();
            
            if ($car_status !== 'Ativo') {
                 throw new Exception("Erro: Apenas anúncios ativos podem ser destacados.");
            }

            // 3. Marcar como Destaque
            $stmt = $conn->prepare("UPDATE anuncios SET destaque = 1 WHERE id = ?");
            $stmt->bind_param("i", $anuncio_id);
            if ($stmt->execute()) {
                $success = true;
                $message = base64_encode("Anúncio marcado como DESTAQUE com sucesso.");
            } else {
                throw new Exception("Erro ao marcar destaque: " . $conn->error);
            }
            $stmt->close();
            
        } else if ($action === 'unmark_highlight') {
            
            // Remover Destaque
            $stmt = $conn->prepare("UPDATE anuncios SET destaque = 0 WHERE id = ?");
            $stmt->bind_param("i", $anuncio_id);
            if ($stmt->execute()) {
                $success = true;
                $message = base64_encode("Anúncio removido do DESTAQUE.");
            } else {
                throw new Exception("Erro ao remover destaque: " . $conn->error);
            }
            $stmt->close();

        // AÇÕES EXISTENTES
        } else if ($action === 'mark_sold') {
            // AÇÃO: Marcar como Vendido e remover destaque
            $stmt = $conn->prepare("UPDATE anuncios SET status = 'Vendido', destaque = 0 WHERE id = ?");
            $stmt->bind_param("i", $anuncio_id);
            if ($stmt->execute()) {
                $success = true;
                $message = base64_encode("Anúncio marcado como VENDIDO com sucesso.");
            } else {
                throw new Exception("Erro ao marcar como vendido: " . $conn->error);
            }
            $stmt->close();

        } else if ($action === 'mark_reserved') {
            // AÇÃO: Marcar como Reservado
            $stmt = $conn->prepare("UPDATE anuncios SET status = 'Reservado' WHERE id = ?");
            $stmt->bind_param("i", $anuncio_id);
            if ($stmt->execute()) {
                $success = true;
                $message = base64_encode("Anúncio marcado como RESERVADO.");
            } else {
                throw new Exception("Erro ao marcar como reservado: " . $conn->error);
            }
            $stmt->close();

        } else if ($action === 'mark_soon') {
            // AÇÃO: Marcar como Brevemente
            $stmt = $conn->prepare("UPDATE anuncios SET status = 'Brevemente', destaque = 0 WHERE id = ?");
            $stmt->bind_param("i", $anuncio_id);
            if ($stmt->execute()) {
                $success = true;
                $message = base64_encode("Anúncio marcado como BREVEMENTE.");
            } else {
                throw new Exception("Erro ao marcar como brevemente: " . $conn->error);
            }
            $stmt->close();

        } else if ($action === 'restore') {
            // AÇÃO: Restaurar (Mudar de Vendido para Ativo)
            $stmt = $conn->prepare("UPDATE anuncios SET status = 'Ativo' WHERE id = ?");
            $stmt->bind_param("i", $anuncio_id);
            if ($stmt->execute()) {
                $success = true;
                $message = base64_encode("Anúncio restaurado (marcado como ATIVO) com sucesso.");
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
            
            // 2. Apagar o anúncio
            $stmt = $conn->prepare("DELETE FROM anuncios WHERE id = ?");
            $stmt->bind_param("i", $anuncio_id);
            
            if ($stmt->execute()) {
                $success = true;
                $message = base64_encode("Anúncio apagado permanentemente.");
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
            // Lógica de Redirecionamento
            $redirect_status = 'Ativo';
            
            if ($action === 'delete' || $action === 'mark_sold') {
                 $redirect_status = 'Vendido';
            }
            
            // Exceção: O restauro deve voltar para a lista de ATIVOS, mas se o delete for de um ativo, deve ir para ATIVOS
            if ($action === 'delete' || $action === 'mark_sold') {
                $redirect_status = 'Vendido';
            } else {
                $redirect_status = 'Ativo';
            }
            
            header("Location: admin-active-listings.php?list={$redirect_status}&status=success&message={$message}");
        } else {
            throw new Exception("Ação não concluída.");
        }

    } catch (Exception $e) {
        $conn->rollback();
        // Redireciona com a mensagem de erro da exceção
        $error_msg = base64_encode("Erro: " . $e->getMessage());
        header("Location: admin-active-listings.php?status=error&message={$error_msg}");
    }
    
    $conn->close();
    exit();
} else {
    // Acesso direto, redireciona
    header("Location: admin-active-listings.php");
    exit();
}
?>