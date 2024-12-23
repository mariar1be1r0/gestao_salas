<?php
require 'logs.php';

session_start();

// Remover todas as variáveis de sessão
session_unset();

// Destruir a sessão
session_destroy();

// Remover o cookie "remember_me"
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/'); // Expira imediatamente
    
}
logs("saiu");
// Redirecionar para a página de login
header("Location: login.php");
exit;
