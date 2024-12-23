<?php
// File: login.php
include 'db.php';
require 'logs.php';

session_start();

// Verificar se há um token de "Remember Me" válido
if (isset($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE remember_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Autenticação automática via "Remember Me"
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_level'] = $user['level'];
        header("Location: dashboard.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'];
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && hash('sha256', $user['salt'] . $password) === $user['password']) {
        // Login bem-sucedido
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_level'] = $user['level'];

        if ($remember_me) {
            // Gerar um token único para "Remember Me"
            $token = bin2hex(random_bytes(32));
            setcookie("remember_me", $token, time() + (86400 * 30), "/"); // Cookie válido por 30 dias

            // Atualizar token no banco de dados
            $stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
            $stmt->execute([$token, $user['id']]);
        }

            logs("Login: " . $login ); 
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Login ou senha incorretos.";
        logs("erro no login");
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <link rel="stylesheet" href="css/login.css">
    <title>Login</title>
</head>
<body>
    <form method="POST" action="">
        <h1>Login</h1>
        <label>Login:</label>
        <input type="text" name="login" autocomplete="off" required>
        <label>Senha:</label>
        <input type="password" name="password" required>
        <label>
            <input type="checkbox" name="remember_me"> Lembrar-me
        </label>
        <button type="submit">Entrar</button>

        <!-- Exibir mensagem de erro abaixo do botão -->
        <?php if (!empty($error)): ?>
            <p class="error-message"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <div class="forgot-password">
            <a href="esqueceu_senha.php">Esqueceu sua senha? Clique aqui</a>
        </div>
    </form>
</body>
</html>

