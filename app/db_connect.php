<?php
/**
 * Ficheiro de Configuração e Ligação à Base de Dados (MySQL/MariaDB).
 */

// === CONFIGURAÇÃO DA BASE DE DADOS ===
$host = 'localhost'; 
$db_name = 'wfcars_db'; 
$username = 'root';  
$password = '';      // CONFIRME SE A SUA PASSWORD ROOT ESTÁ VAZIA
// ====================================

// Tenta estabelecer a ligação usando a API MySQLi
$conn = new mysqli($host, $username, $password, $db_name);

// Verifica se a ligação falhou
if ($conn->connect_error) {
    // Em produção, deve-se usar uma página de erro genérica em vez de die().
    // Se esta linha for executada, o MySQL não está a correr ou as credenciais estão erradas.
    die("A ligação à base de dados falhou: " . $conn->connect_error);
}

// Define o charset para garantir que caracteres especiais (como ã, ç) funcionam.
$conn->set_charset("utf8mb4");

// A variável $conn está agora pronta.
?>