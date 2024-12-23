
<?php

// File: auth.php
session_start();

// Verificar se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    // Verifica o cookie "Remember Me"
    if (isset($_COOKIE['remember_me'])) {
        // Inclui o arquivo de conexão ao banco de dados
        include __DIR__ . 'db.php';

        try {
            $token = $_COOKIE['remember_me'];

            // Busca o usuário pelo token "Remember Me"
            $stmt = $conn->prepare("SELECT * FROM users WHERE remember_token = ?");
            $stmt->execute([$token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Autentica automaticamente com base no token
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_level'] = $user['level'];
            } else {
                // Token inválido, redireciona para o login
                header("Location: login.php");
                exit;
            }
        } catch (Exception $e) {
            // Erro na conexão ou consulta ao banco de dados
            die("Erro ao verificar 'Remember Me': " . $e->getMessage());
        }
    } else {
        // Não autenticado e sem cookie "Remember Me", redireciona para o login
        header("Location: login.php");
        exit;
    }
}
