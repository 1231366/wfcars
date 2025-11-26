<?php
/**
 * Script responsável por autenticar o utilizador.
 */
session_start(); // Inicia a sessão para guardar o estado do login
include 'db_connect.php'; 

// Verifica se o formulário foi submetido
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Certifique-se de que os campos existem
    if (empty($_POST['username']) || empty($_POST['password'])) {
        header("Location: login.php?error=" . urlencode("Por favor, preencha todos os campos."));
        exit();
    }
    
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password']; 
    
    // 1. Prepara a query para buscar o utilizador
    // Busca pelo username ou pelo email
    $sql = "SELECT id, username, password_hash, role FROM users WHERE username = '$username' OR email = '$username'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // 2. Verifica a password com o hash armazenado (A PARTE CRUCIAL DA SEGURANÇA)
        if (password_verify($password, $user['password_hash'])) {
            
            // Password correta! Cria a sessão de login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            
            // Redireciona para o painel principal
            header("Location: admin-dashboard.php");
            exit();
            
        } else {
            // Password incorreta
            header("Location: login.php?error=" . urlencode("Password incorreta."));
            exit();
        }
    } else {
        // Utilizador não encontrado
        header("Location: login.php?error=" . urlencode("Utilizador ou Email não encontrado."));
        exit();
    }
    
    $conn->close();
} else {
    // Redireciona se o acesso não foi via POST
    header("Location: login.php");
    exit();
}
?>