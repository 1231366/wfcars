<?php
/**
 * Script responsável por gerir a criação, edição e eliminação de utilizadores admin/editor.
 */
session_start();
include 'db_connect.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" || (isset($_GET['action']) && $_GET['action'] === 'delete')) {
    
    $action = $_GET['action'] ?? ($_POST['action'] ?? null); // Obtém a ação de GET ou POST
    $redirect_url = "admin-users.php";
    $success = false;
    $message = "";

    try {
        if ($action === 'create' || $action === 'edit') {
            
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $role = $_POST['role'] ?? 'editor';
            $password = $_POST['password'] ?? '';
            $user_id = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
            
            // Define o URL de redirecionamento para o formulário em caso de falha
            if ($action === 'create') {
                $redirect_url = "admin-create-user.php";
            } else {
                 $redirect_url = "admin-create-user.php?edit_id={$user_id}";
            }

            // CORRIGIDO: A validação agora apenas exige que a password NÃO esteja vazia na criação.
            if (empty($username) || empty($email) || ($action === 'create' && empty($password))) {
                 // CORRIGIDO: Mensagem de erro alterada para remover a menção ao mínimo de 6 caracteres.
                 $error_msg_text = "Dados inválidos: Utilizador, Email e Password são necessários.";
                 throw new Exception($error_msg_text);
            }

            $password_hash = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;
            
            if ($action === 'create') {
                // Insere Novo Utilizador
                $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $username, $email, $password_hash, $role);
                $stmt->execute();
                $message = base64_encode("Utilizador '{$username}' criado com sucesso.");

            } else if ($action === 'edit') {
                // Edita Utilizador Existente
                if (!$user_id) throw new Exception("ID de utilizador inválido para edição.");
                
                $sql_update = "UPDATE users SET username=?, email=?, role=?";
                $params = [$username, $email, $role];
                $types = "sss";
                
                if ($password_hash) {
                    $sql_update .= ", password_hash=?";
                    $params[] = $password_hash;
                    $types .= "s";
                }
                
                $sql_update .= " WHERE id=?";
                $params[] = $user_id;
                $types .= "i";

                $stmt = $conn->prepare($sql_update);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $message = base64_encode("Utilizador '{$username}' atualizado com sucesso.");
                $redirect_url = "admin-users.php"; // Redireciona para a lista após edição bem-sucedida
            }
            $success = true;

        } else if ($_GET['action'] === 'delete') {
             $user_id = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
             if (!$user_id) throw new Exception("ID de utilizador inválido para apagar.");
             if ($user_id == $_SESSION['user_id']) throw new Exception("Não pode apagar o seu próprio utilizador.");

             // Apaga Utilizador
             $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
             $stmt->bind_param("i", $user_id);
             $stmt->execute();
             $message = base64_encode("Utilizador apagado com sucesso.");
             $success = true;
        }

        if ($success) {
            $conn->commit();
            header("Location: {$redirect_url}?status=success&message={$message}");
        } else {
            throw new Exception("Operação não concluída.");
        }

    } catch (Exception $e) {
        $conn->rollback();
        
        // Trata erros de DB como Duplicate entry
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
             $error_msg = base64_encode("Falha na Operação: Nome de utilizador ou E-mail já existe.");
        } else {
             $error_msg = base64_encode("Falha na Operação: " . $e->getMessage());
        }
        
        header("Location: {$redirect_url}?status=error&message={$error_msg}");
    }
    
    $conn->close();
    exit();
} else {
    header("Location: admin-users.php");
    exit();
}
?>